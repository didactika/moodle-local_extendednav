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
        'blockaccess', 'langs', 'parentkey', 'newwindow',
    ];

    /**
     * Builds a record ready to save from submitted values.
     *
     * Empty values become null, the URL is normalised, roles and languages become
     * comma-separated lists, and "Block URL access" is cleared where it cannot apply.
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
        $record->newwindow = empty($data['newwindow']) ? 0 : 1;

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
                'SELECT MAX(sortorder) FROM {local_extendednav} WHERE menu = :menu',
                ['menu' => $record->menu]
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
     * Deletes nodes. Core nodes are never deleted.
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
            if ($menu === null || !$menu->is_native($record->nodekey)) {
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
        // Keep the order as it is shown, so the children of a deleted parent stay where they were.
        $orders = [];
        foreach (array_keys($bymenu) as $menukey) {
            $orders[$menukey] = array_values(array_diff(self::get_grouped_ids($menukey), array_keys($todelete)));
        }

        $DB->delete_records_list('local_extendednav', 'id', array_keys($todelete));
        node_repository::purge();

        foreach ($todelete as $record) {
            if ($events) {
                node_deleted::create_from_record($record)->trigger();
            }
        }

        // The children of a deleted parent become top-level nodes.
        foreach ($bymenu as $menukey => $menurecords) {
            $keys = array_map(static fn(\stdClass $record): string => $record->nodekey, $menurecords);
            $children = $DB->get_records_list('local_extendednav', 'parentkey', $keys, '', 'id, menu, parentkey');
            foreach ($children as $child) {
                if ($child->menu === $menukey) {
                    self::update((object)['id' => $child->id, 'parentkey' => null], $events);
                }
            }
            self::reorder($menukey, $orders[$menukey]);
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
     */
    public static function reorder(string $menu, array $ids): void {
        global $DB;

        $current = $DB->get_records('local_extendednav', ['menu' => $menu], 'sortorder ASC, id ASC', 'id, sortorder');
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
     * @return \stdClass[] The same nodes, grouped.
     */
    public static function group_by_parent(array $nodes): array {
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
        $add = static function (\stdClass $node, int $depth) use (&$add, &$ordered, $children): void {
            $ordered[] = $node;
            if ($depth < 2) {
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

        $nodes = $DB->get_records('local_extendednav', ['menu' => $menu], '', 'id, nodekey, parentkey, sortorder');
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

        $nodes = $DB->get_records('local_extendednav', ['menu' => $before->menu], '', 'id, nodekey, parentkey, sortorder');
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
