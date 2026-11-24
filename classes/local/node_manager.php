<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_extendednav\local;

use local_extendednav\event\node_created;
use local_extendednav\event\node_deleted;
use local_extendednav\event\node_updated;
use local_extendednav\menu_manager;

/**
 * Creates, updates, deletes and reorders nodes.
 *
 * Every change goes through this class so timestamps, events and the cache stay consistent.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class node_manager {
    /** @var string[] Fields an administrator can set on a node. */
    public const EDITABLE_FIELDS = [
        'nodekey', 'title', 'tooltip', 'url', 'icon', 'visibility', 'roles',
        'blockaccess', 'langs', 'parentkey', 'display', 'popupwidth', 'popupheight', 'placement', 'hideadmins',
    ];

    /**
     * Builds a record ready to save from submitted values.
     *
     * Empty values become null, the URL is normalised, roles and languages become
     * comma-separated lists, and "Block URL access" and the pop-up size are cleared where they cannot apply.
     *
     * @param array|\stdClass $data Submitted values (form data or an imported node).
     * @param string $menu Menu key.
     * @return \stdClass
     */
    public static function prepare($data, string $menu): \stdClass {
        $data = (array)$data;
        $text = static function ($value): ?string {
            if (is_array($value) || is_object($value)) {
                return null;
            }
            $value = trim((string)$value);
            return $value === '' ? null : $value;
        };
        $list = static function ($value): ?string {
            $items = is_array($value) ? $value : node_access::split_list((string)$value);
            $items = array_values(array_unique(array_filter(array_map('trim', array_map('strval', $items)))));
            return empty($items) ? null : implode(',', $items);
        };

        $record = new \stdClass();
        $record->menu = $menu;
        $record->nodekey = (string)$text($data['nodekey'] ?? '');
        $record->title = $text($data['title'] ?? null);
        $record->tooltip = $text($data['tooltip'] ?? null);
        $record->url = url_helper::normalise($text($data['url'] ?? null));
        $record->icon = $text($data['icon'] ?? null);
        $record->visibility = isset($data['visibility']) ? (int)$data['visibility'] : node_access::VISIBILITY_ALL;
        $record->roles = $record->visibility === node_access::VISIBILITY_ROLES ? $list($data['roles'] ?? null) : null;
        $record->langs = $list($data['langs'] ?? null);
        $record->parentkey = $text($data['parentkey'] ?? null);
        $record->display = isset($data['display']) && is_numeric($data['display']) ? (int)$data['display']
            : node_display::CURRENT;
        $size = static function ($value): ?int {
            if ($value === null || (is_string($value) && trim($value) === '')) {
                return null;
            }
            // Anything that is not a whole number is kept as 0, so validation reports it.
            return is_numeric($value) && (int)$value == $value ? (int)$value : 0;
        };
        $popup = $record->display === node_display::POPUP;
        $record->popupwidth = $popup ? $size($data['popupwidth'] ?? null) : null;
        $record->popupheight = $popup ? $size($data['popupheight'] ?? null) : null;

        $placement = isset($data['placement']) && is_numeric($data['placement']) ? (int)$data['placement'] : 0;
        $record->placement = in_array($placement, [0, 1, 2], true) ? $placement : 0;
        // A node of the site administration is hidden from administrators too unless the data says otherwise.
        if (array_key_exists('hideadmins', $data)) {
            $record->hideadmins = empty($data['hideadmins']) ? 0 : 1;
        } else {
            $record->hideadmins = $menu === \local_extendednav\menu\admintree::KEY ? 1 : 0;
        }
        if (isset($data['contextid']) && is_numeric($data['contextid']) && (int)$data['contextid'] > 0) {
            $record->contextid = (int)$data['contextid'];
        }

        $record->blockaccess = 0;
        if (!empty($data['blockaccess']) && $record->visibility !== node_access::VISIBILITY_ALL) {
            $record->blockaccess = empty(access_guard::get_node_paths($record)) ? 0 : 1;
        }
        return $record;
    }

    /**
     * Creates a node.
     *
     * @param \stdClass $record Record from {@see prepare()}.
     * @param bool $events Whether to trigger the created event.
     * @return \stdClass The saved record, with its id.
     */
    public static function create(\stdClass $record, bool $events = true): \stdClass {
        global $DB;

        $record = clone $record;
        unset($record->id);
        if (!isset($record->sortorder)) {
            $max = $DB->get_field_sql(
                'SELECT MAX(sortorder) FROM {local_extendednav} WHERE menu = :menu AND contextid = :contextid',
                ['menu' => $record->menu, 'contextid' => (int)($record->contextid ?? 0)]
            );
            $record->sortorder = $max === null || $max === false ? 0 : (int)$max + 1;
        }
        $record->timecreated = time();
        $record->timemodified = $record->timecreated;
        $record->id = (int)$DB->insert_record('local_extendednav', $record);

        node_repository::purge();
        if ($events) {
            node_created::create_from_record($record)->trigger();
        }
        return $record;
    }

    /**
     * Updates a node.
     *
     * @param \stdClass $record Record with an id; only the fields present are changed.
     * @param bool $events Whether to trigger the updated event.
     * @return \stdClass The saved record.
     */
    public static function update(\stdClass $record, bool $events = true): \stdClass {
        global $DB;

        $record = clone $record;
        $record->timemodified = time();
        $DB->update_record('local_extendednav', $record);

        $saved = $DB->get_record('local_extendednav', ['id' => $record->id], '*', MUST_EXIST);
        node_repository::purge();
        if ($events) {
            node_updated::create_from_record($saved)->trigger();
        }
        return $saved;
    }

    /**
     * Deletes nodes. Core nodes are only deleted when their menu allows it.
     *
     * Items that came from Moodle's custom menu settings are removed from those settings too.
     *
     * @param \stdClass[] $records Records to delete.
     * @param bool $events Whether to trigger the deleted events.
     * @param bool $updateconfig Whether to remove the items from Moodle's custom menu settings.
     * @return int Number of nodes deleted.
     */
    public static function delete(array $records, bool $events = true, bool $updateconfig = true): int {
        global $DB;

        $todelete = [];
        foreach ($records as $record) {
            $menu = menu_manager::get_menu($record->menu);
            if ($menu === null || $menu->can_delete($record->nodekey)) {
                $todelete[$record->id] = $record;
            }
        }
        if (empty($todelete)) {
            return 0;
        }

        $bymenu = [];
        foreach ($todelete as $record) {
            $bymenu[$record->menu][] = $record;
        }

        // Read every node of the affected menus once, to keep the order shown and find the orphaned children.
        [$menuin, $menuparams] = $DB->get_in_or_equal(array_keys($bymenu), SQL_PARAMS_NAMED);
        $menunodes = $DB->get_records_select('local_extendednav', 'contextid = 0 AND menu ' . $menuin, $menuparams);
        $orders = [];
        $children = [];
        foreach ($bymenu as $menukey => $menurecords) {
            $nodes = array_filter($menunodes, static fn(\stdClass $node): bool => $node->menu === $menukey);
            $ids = array_map(static fn(\stdClass $node): int => (int)$node->id, self::group_by_parent($nodes));
            $orders[$menukey] = array_values(array_diff($ids, array_keys($todelete)));

            $deletedkeys = array_map(static fn(\stdClass $record): string => $record->nodekey, $menurecords);
            foreach ($nodes as $node) {
                if (!isset($todelete[$node->id]) && in_array((string)$node->parentkey, $deletedkeys, true)) {
                    $children[$node->id] = $node;
                }
            }
        }

        $DB->delete_records_list('local_extendednav', 'id', array_keys($todelete));
        // The exceptions of a node that is deleted go with it.
        foreach ($todelete as $record) {
            if ((int)($record->contextid ?? 0) === 0) {
                $DB->delete_records_select(
                    'local_extendednav',
                    'menu = :menu AND nodekey = :nodekey AND contextid <> 0',
                    ['menu' => $record->menu, 'nodekey' => $record->nodekey]
                );
            }
        }

        // The children of a deleted parent become top-level nodes, where the parent was.
        if (!empty($children)) {
            [$insql, $params] = $DB->get_in_or_equal(array_keys($children), SQL_PARAMS_NAMED);
            $now = time();
            $DB->set_field_select('local_extendednav', 'parentkey', null, 'id ' . $insql, $params);
            $DB->set_field_select('local_extendednav', 'timemodified', $now, 'id ' . $insql, $params);
            foreach (array_keys($bymenu) as $menukey) {
                self::reorder($menukey, $orders[$menukey]);
            }
        }
        node_repository::purge();

        if ($events) {
            foreach ($todelete as $record) {
                node_deleted::create_from_record($record)->trigger();
            }
            foreach ($children as $child) {
                $child->parentkey = null;
                $child->timemodified = $now;
                node_updated::create_from_record($child)->trigger();
            }
        }
        foreach ($bymenu as $menukey => $menurecords) {
            $menu = menu_manager::get_menu($menukey);
            $natives = $menu === null ? [] : array_filter(
                array_map(static fn(\stdClass $record): string => $record->nodekey, $menurecords),
                [$menu, 'is_native']
            );
            if (!empty($natives)) {
                $menu->forget_native_nodes(array_values($natives));
            }
        }
        if ($updateconfig) {
            foreach ($bymenu as $menukey => $menurecords) {
                $menu = menu_manager::get_menu($menukey);
                if ($menu !== null) {
                    $menu->remove_nodes_from_config($menurecords);
                }
            }
        }
        return count($todelete);
    }

    /**
     * Saves a new order for the nodes of a menu.
     *
     * Nodes of the menu missing from the list keep their relative order after the listed ones.
     *
     * @param string $menu Menu key.
     * @param int[] $ids Node ids in their new order.
     * @param int $contextid Context the nodes apply to, 0 for the whole site.
     */
    public static function reorder(string $menu, array $ids, int $contextid = 0): void {
        global $DB;

        $current = $DB->get_records(
            'local_extendednav',
            ['menu' => $menu, 'contextid' => $contextid],
            'sortorder ASC, id ASC',
            'id, sortorder'
        );
        $ordered = [];
        foreach ($ids as $id) {
            $id = (int)$id;
            if (isset($current[$id]) && !in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }
        foreach (array_keys($current) as $id) {
            if (!in_array((int)$id, $ordered, true)) {
                $ordered[] = (int)$id;
            }
        }

        $transaction = $DB->start_delegated_transaction();
        foreach ($ordered as $sortorder => $id) {
            if ((int)$current[$id]->sortorder !== $sortorder) {
                $DB->set_field('local_extendednav', 'sortorder', $sortorder, ['id' => $id]);
            }
        }
        $transaction->allow_commit();
        node_repository::purge();
    }

    /**
     * Orders nodes as they are shown: each top-level node followed by its children.
     *
     * Top-level nodes are ordered among themselves and children among their siblings,
     * both by sortorder. A node whose parent is not in the list is treated as top-level.
     *
     * @param \stdClass[] $nodes Nodes of one menu, in any order.
     * @param int $maxdepth Deepest level kept: 2 for the menus, which have two levels of submenus; more for a tree.
     * @return \stdClass[] The same nodes, grouped.
     */
    public static function group_by_parent(array $nodes, int $maxdepth = 2): array {
        $nodes = array_values($nodes);
        usort($nodes, static fn(\stdClass $a, \stdClass $b): int =>
            [(int)$a->sortorder, (int)$a->id] <=> [(int)$b->sortorder, (int)$b->id]);

        $keys = [];
        foreach ($nodes as $node) {
            $keys[$node->nodekey] = true;
        }
        $top = [];
        $children = [];
        foreach ($nodes as $node) {
            $parent = (string)($node->parentkey ?? '');
            if ($parent !== '' && $parent !== $node->nodekey && isset($keys[$parent])) {
                $children[$parent][] = $node;
            } else {
                $top[] = $node;
            }
        }

        $ordered = [];
        $add = static function (\stdClass $node, int $depth) use (&$add, &$ordered, $children, $maxdepth): void {
            $ordered[] = $node;
            if ($depth < $maxdepth) {
                foreach ($children[$node->nodekey] ?? [] as $child) {
                    $add($child, $depth + 1);
                }
            }
        };
        foreach ($top as $node) {
            $add($node, 0);
        }
        return $ordered;
    }

    /**
     * Returns the ids of the nodes of a menu, grouped as they are shown.
     *
     * @param string $menu Menu key.
     * @return int[]
     */
    public static function get_grouped_ids(string $menu): array {
        global $DB;

        $nodes = $DB->get_records(
            'local_extendednav',
            ['menu' => $menu, 'contextid' => 0],
            '',
            'id, nodekey, parentkey, sortorder'
        );
        return array_map(static fn(\stdClass $node): int => (int)$node->id, self::group_by_parent($nodes));
    }

    /**
     * Moves a node whose parent changed to its place in the new group.
     *
     * A node given a parent becomes its last child. A node that becomes top-level is placed
     * right after the group of its former parent, where it was shown.
     *
     * @param \stdClass $before The node as it was before the change.
     * @param string $newparent Key of the new parent, or an empty string for top level.
     */
    public static function place_after_parent_change(\stdClass $before, string $newparent): void {
        global $DB;

        $nodes = $DB->get_records(
            'local_extendednav',
            ['menu' => $before->menu, 'contextid' => 0],
            '',
            'id, nodekey, parentkey, sortorder'
        );
        if (!isset($nodes[$before->id])) {
            return;
        }
        // Work from the order shown before the change, without the moved node.
        $nodes[$before->id]->parentkey = $before->parentkey;
        $ordered = array_map(static fn(\stdClass $node): int => (int)$node->id, self::group_by_parent($nodes));
        $ordered = array_values(array_diff($ordered, [(int)$before->id]));

        $anchor = $newparent !== '' ? $newparent : (string)($before->parentkey ?? '');
        $position = null;
        foreach ($ordered as $index => $id) {
            if ($nodes[$id]->nodekey === $anchor || (string)($nodes[$id]->parentkey ?? '') === $anchor) {
                $position = $index + 1;
            }
        }
        if ($position === null) {
            $ordered[] = (int)$before->id;
        } else {
            array_splice($ordered, $position, 0, [(int)$before->id]);
        }
        self::reorder($before->menu, $ordered);
    }

    /**
     * Orders nodes as they are shown, keyed by node key.
     *
     * @param \stdClass[] $nodes Nodes of one menu, in any order.
     * @param int $maxdepth Deepest level kept.
     * @return \stdClass[] The same nodes, grouped, keyed by node key.
     */
    public static function group_by_parent_keyed(array $nodes, int $maxdepth = 2): array {
        $keyed = [];
        foreach (self::group_by_parent($nodes, $maxdepth) as $node) {
            $keyed[$node->nodekey] = $node;
        }
        return $keyed;
    }

    /**
     * Returns the row a node has in a context, making it as a copy of the node the context sees when it has none.
     *
     * A category or a course changes a node of the site, or of a category around it, with an exception: a row of its own
     * that starts as a copy of the node it replaces and is changed from there.
     *
     * @param string $menu Menu key.
     * @param string $nodekey Node key.
     * @param int $contextid Context of the exception, 0 for the whole site.
     * @return \stdClass The row, saved.
     */
    public static function ensure_row(string $menu, string $nodekey, int $contextid): \stdClass {
        global $DB;

        $row = $DB->get_record('local_extendednav', ['menu' => $menu, 'nodekey' => $nodekey, 'contextid' => $contextid]);
        if ($row) {
            return $row;
        }
        if ($contextid === 0) {
            return self::create(self::prepare(['nodekey' => $nodekey, 'visibility' => node_access::VISIBILITY_ALL], $menu), false);
        }
        $seen = node_list::get_scope_nodes($menu, $contextid)[$nodekey] ?? null;
        $defaults = ['nodekey' => $nodekey, 'visibility' => node_access::VISIBILITY_ALL];
        $copy = $seen === null ? self::prepare($defaults, $menu) : clone $seen;
        unset($copy->id, $copy->timecreated, $copy->timemodified);
        $copy->contextid = $contextid;
        $copy->sortorder = $seen === null ? null : $seen->sortorder;
        if ($copy->sortorder === null) {
            unset($copy->sortorder);
        }
        return self::create($copy, false);
    }

    /**
     * Sets the visibility of nodes in a scope, making an exception of each node that the scope only inherits.
     *
     * @param string $menu Menu key.
     * @param int $contextid Context of the scope, 0 for the whole site.
     * @param string[] $nodekeys Node keys.
     * @param int $visibility One of the visibilities of node_access.
     * @param int $hideadmins 1 to hide the nodes from administrators too, 0 not to, -1 to leave it as it is.
     * @return int Number of nodes changed.
     */
    public static function set_visibility(
        string $menu,
        int $contextid,
        array $nodekeys,
        int $visibility,
        int $hideadmins = -1
    ): int {
        $changed = 0;
        foreach (array_unique($nodekeys) as $nodekey) {
            $row = self::ensure_row($menu, (string)$nodekey, $contextid);
            if (node_access::is_protected($row)) {
                continue;
            }
            $values = ['id' => $row->id, 'visibility' => $visibility];
            $values['roles'] = $visibility === node_access::VISIBILITY_ROLES ? $row->roles : null;
            if ($hideadmins === 0 || $hideadmins === 1) {
                $values['hideadmins'] = $hideadmins;
            }
            self::update((object)$values);
            $changed++;
        }
        return $changed;
    }

    /**
     * Moves a node one place up or down among its siblings in a scope.
     *
     * @param string $menu Menu key.
     * @param int $contextid Context of the scope, 0 for the whole site.
     * @param string $nodekey Node key.
     * @param int $direction -1 to move it up, 1 to move it down.
     */
    public static function move(string $menu, int $contextid, string $nodekey, int $direction): void {
        $keys = array_keys(node_list::get_scope_nodes($menu, $contextid));
        $nodes = node_list::get_scope_nodes($menu, $contextid);
        $parent = (string)($nodes[$nodekey]->parentkey ?? '');
        // The siblings of the node, in order, and the position of the node among them.
        $siblings = array_values(array_filter(
            $keys,
            static fn(string $key): bool => (string)($nodes[$key]->parentkey ?? '') === $parent
        ));
        $at = array_search($nodekey, $siblings, true);
        $to = $at === false ? false : $at + ($direction < 0 ? -1 : 1);
        if ($to === false || !isset($siblings[$to])) {
            return;
        }
        [$siblings[$at], $siblings[$to]] = [$siblings[$to], $siblings[$at]];
        // The order of the list with the siblings swapped: every group keeps its place, the siblings take the new order.
        $order = [];
        $queue = $siblings;
        foreach ($keys as $key) {
            if ((string)($nodes[$key]->parentkey ?? '') === $parent) {
                $order[] = array_shift($queue);
            } else {
                $order[] = $key;
            }
        }
        self::save_order($menu, $contextid, $order);
        if ($menu === \local_extendednav\menu\admintree::KEY) {
            \local_extendednav\menu\admintree::mark_ordered($parent);
        }
    }

    /**
     * Places nodes, together and in the order they have, right before a sibling, right after one, or after the last of them.
     *
     * Every node that moves has the same parent, and the sibling that marks the place is one of theirs and not one that moves.
     *
     * @param string $menu Menu key.
     * @param int $contextid Context of the scope, 0 for the whole site.
     * @param string[] $nodekeys Keys of the nodes that move.
     * @param string $beforekey Key of the sibling they go before, or empty to go after the last sibling.
     * @param string $afterkey Key of the sibling they go after, instead of a sibling they go before.
     */
    public static function place(string $menu, int $contextid, array $nodekeys, string $beforekey, string $afterkey = ''): void {
        $nodes = node_list::get_scope_nodes($menu, $contextid);
        $nodekeys = array_values(array_unique(array_map('strval', $nodekeys)));
        if (empty($nodekeys) || array_diff($nodekeys, array_keys($nodes))) {
            throw new \invalid_parameter_exception('Unknown node.');
        }
        $parent = (string)($nodes[$nodekeys[0]]->parentkey ?? '');
        $issibling = static fn(string $key): bool => isset($nodes[$key]) && (string)($nodes[$key]->parentkey ?? '') === $parent;
        foreach ([...$nodekeys, $beforekey, $afterkey] as $key) {
            if ($key !== '' && !$issibling($key)) {
                throw new \invalid_parameter_exception('Nodes move among their siblings.');
            }
        }
        if (in_array($beforekey, $nodekeys, true) || in_array($afterkey, $nodekeys, true)) {
            throw new \invalid_parameter_exception('A node does not move next to itself.');
        }

        // The nodes that move keep the order they have; the rest keep theirs.
        $moving = array_values(array_intersect(array_keys($nodes), $nodekeys));
        $keys = array_values(array_diff(array_keys($nodes), $moving));
        if ($afterkey !== '') {
            // Right after a sibling is right before the sibling that follows it, or at the end.
            $siblings = array_values(array_filter($keys, $issibling));
            $beforekey = $siblings[array_search($afterkey, $siblings, true) + 1] ?? '';
        }
        if ($beforekey !== '') {
            $at = array_search($beforekey, $keys, true);
        } else {
            $at = 0;
            foreach ($keys as $index => $key) {
                if ($issibling($key)) {
                    $at = $index + 1;
                }
            }
        }
        array_splice($keys, (int)$at, 0, $moving);
        self::save_order($menu, $contextid, $keys);
        if ($menu === \local_extendednav\menu\admintree::KEY) {
            \local_extendednav\menu\admintree::mark_ordered($parent);
        }
    }

    /**
     * Saves the order of the nodes of a scope, making an exception of each node a category or a course only inherits.
     *
     * @param string $menu Menu key.
     * @param int $contextid Context of the scope, 0 for the whole site.
     * @param string[] $nodekeys Node keys in their new order.
     */
    public static function save_order(string $menu, int $contextid, array $nodekeys): void {
        global $DB;

        // The rows the scope already has are read at once; only the missing ones are made one by one.
        $rows = $DB->get_records_menu('local_extendednav', ['menu' => $menu, 'contextid' => $contextid], '', 'nodekey, id');
        $ids = [];
        foreach ($nodekeys as $nodekey) {
            $ids[] = isset($rows[$nodekey]) ? (int)$rows[$nodekey] : (int)self::ensure_row($menu, (string)$nodekey, $contextid)->id;
        }
        self::reorder($menu, $ids, $contextid);
    }

    /**
     * Returns every node, with one node replaced or added, as it would be after saving.
     *
     * @param \stdClass $candidate Node being saved.
     * @param int $id Id of the node being edited, or 0 for a new node.
     * @return \stdClass[]
     */
    public static function get_all_with(\stdClass $candidate, int $id = 0): array {
        $nodes = node_repository::get_all();
        foreach ($nodes as $key => $node) {
            if (
                ($id && (int)$node->id === $id)
                    || ($node->menu === $candidate->menu && $node->nodekey === $candidate->nodekey)
            ) {
                unset($nodes[$key]);
            }
        }
        $nodes[] = $candidate;
        return $nodes;
    }
}
