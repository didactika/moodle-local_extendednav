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

namespace local_extendednav\local\io;

use local_extendednav\menu_manager;
use local_extendednav\local\listing\nodes;
use local_extendednav\local\navigation\access_guard;
use local_extendednav\local\node\manager;
use local_extendednav\local\node\repository;
use local_extendednav\local\node\validator;

/**
 * Reads a YAML file of navigations: checks every node first, then creates or replaces them, places them in the order of
 * the file and turns each navigation on or off as the file says. The file is the one exporter writes.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class importer {
    /** @var string Menu used for nodes that do not name one. */
    private string $defaultmenu;

    /** @var int[] Contexts the nodes of the file go to, whatever the file says, or none to follow the file. */
    private array $targets = [];

    /** @var bool Whether nodes that already exist are replaced (true) or skipped (false). */
    private bool $overwrite;

    /**
     * Constructor.
     *
     * @param string $defaultmenu Menu used for nodes that do not name one.
     * @param bool $overwrite Whether nodes that already exist are replaced (true) or skipped (false).
     */
    public function __construct(string $defaultmenu, bool $overwrite, array $targets = []) {
        $this->targets = array_values(array_filter(array_map('intval', $targets)));
        $this->defaultmenu = $defaultmenu;
        $this->overwrite = $overwrite;
    }

    /**
     * Validates every node and, when all are valid, saves them.
     *
     * @param array $items Nodes read from the file.
     * @return array With 'errors' (list of messages, nothing saved when not empty), 'warnings'
     *               (list of messages) and 'counts' (created, updated and skipped).
     */
    public function import(array $items): array {
        $errors = [];
        $warnings = [];
        $counts = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        $items = $this->expand_legacy_activities($items, $warnings, $counts);
        if ($this->targets) {
            $items = $this->give_to_targets($items, $counts);
        }

        $existing = [];
        foreach (repository::get_all() as $node) {
            $existing[$node->menu][$node->nodekey] = $node;
        }

        // The nodes of Moodle in the file are known as such before they are checked. A secondary navigation learns them from
        // the file, as it would from a page; a node of Moodle this site does not have (a page of a plugin that is not
        // installed) is left out with a warning, and the rest of the file is imported.
        $seen = [];
        foreach ($items as $index => $item) {
            if (!is_array($item) || empty($item['native']) || trim((string)($item['nodekey'] ?? '')) === '') {
                continue;
            }
            $menukey = trim((string)($item['menu'] ?? '')) ?: $this->defaultmenu;
            $menu = menu_manager::get_menu($menukey);
            $nodekey = trim((string)$item['nodekey']);
            if ($menu instanceof \local_extendednav\menu\secondary) {
                $seen[$menukey][$nodekey] = (object)['text' => '', 'action' => ''];
            } else if ($menu !== null && !$menu->is_native($nodekey)) {
                $warnings[] = $menukey . '/' . $nodekey . ': ' . get_string('warn_import_absent', 'local_extendednav');
                $counts['skipped']++;
                unset($items[$index]);
            }
        }
        foreach ($seen as $menukey => $nodes) {
            menu_manager::get_menu($menukey)->record_seen($nodes);
        }

        // The nodes of a category or a course are checked with the rest and saved after the ones of the site.
        $scoped = [];
        foreach ($items as $index => $item) {
            if (is_array($item) && trim((string)($item['scope'] ?? '')) !== '') {
                $scoped[$index] = $item;
                unset($items[$index]);
            }
        }
        $scopedrecords = $this->prepare_scoped($scoped, $errors, $warnings, $counts);

        // Build the records and drop the ones that are skipped.
        $batch = [];
        $orders = [];
        foreach (array_values($items) as $index => $item) {
            $label = get_string('import_entry', 'local_extendednav', $index + 1);
            if (!is_array($item) || !isset($item['nodekey']) || trim((string)$item['nodekey']) === '') {
                $errors[] = $label . ': ' . get_string('err_import_nodekey', 'local_extendednav');
                continue;
            }

            $menukey = isset($item['menu']) && trim((string)$item['menu']) !== '' ? trim((string)$item['menu'])
                : $this->defaultmenu;
            if (menu_manager::get_menu($menukey) === null) {
                $errors[] = $label . ': ' . get_string('err_invalid_menu', 'local_extendednav');
                continue;
            }

            $values = array_intersect_key($item, array_flip(manager::EDITABLE_FIELDS));
            $record = manager::prepare($values, $menukey);
            $label = $menukey . '/' . $record->nodekey;
            $order = $item['order'] ?? '';
            if ($order !== '' && $order !== null && !(is_numeric($order) && (int)$order == $order && (int)$order >= 1)) {
                $errors[] = $label . ' (order): ' . get_string('err_import_order', 'local_extendednav');
                continue;
            }
            if (isset($batch[$menukey][$record->nodekey])) {
                $errors[] = $label . ': ' . get_string('err_import_duplicate', 'local_extendednav');
                continue;
            }

            $current = $existing[$menukey][$record->nodekey] ?? null;
            if ($current !== null && !$this->overwrite) {
                $counts['skipped']++;
                continue;
            }
            if ($current !== null) {
                $record->id = $current->id;
            }
            $batch[$menukey][$record->nodekey] = $record;
            if ($order !== '' && $order !== null) {
                $orders[$menukey][$record->nodekey] = [(int)$order, $index];
            }
        }

        // Validate every record against the menu as it would be after the import.
        $final = $existing;
        foreach ($batch as $menukey => $records) {
            foreach ($records as $nodekey => $record) {
                $final[$menukey][$nodekey] = $record;
            }
        }
        foreach ($batch as $menukey => $records) {
            foreach ($records as $nodekey => $record) {
                $others = $final[$menukey];
                unset($others[$nodekey]);
                $result = validator::validate($record, $others);
                foreach ($result['errors'] as $field => $message) {
                    $errors[] = $menukey . '/' . $nodekey . ' (' . $field . '): ' . $message;
                }
                foreach ($result['warnings'] as $message) {
                    $warnings[] = $menukey . '/' . $nodekey . ': ' . $message;
                }
            }
        }

        $allnodes = [];
        foreach ($final as $records) {
            array_push($allnodes, ...array_values($records));
        }
        if (empty($errors) && access_guard::check_fallbacks($allnodes) === access_guard::FALLBACKS_BLOCKED) {
            $errors[] = get_string('err_fallbacks_blocked', 'local_extendednav');
        }
        if (!empty($errors)) {
            return ['errors' => $errors, 'warnings' => $warnings, 'counts' => $counts];
        }

        // Every node of the file is saved in one go per navigation: one write each, one purge of the cache.
        $count = static function (array $records) use (&$counts): void {
            foreach ($records as $record) {
                $counts[isset($record->id) ? 'updated' : 'created']++;
            }
        };
        foreach ($batch as $menukey => $records) {
            $count($records);
            $ids = array_map(static fn(\stdClass $record): int => (int)$record->id, manager::save_many($records));
            if (!empty($orders[$menukey]) && $menukey === \local_extendednav\menu\admintree::KEY) {
                self::apply_sibling_orders($menukey, $orders[$menukey]);
            } else if (!empty($orders[$menukey])) {
                self::apply_orders($menukey, $orders[$menukey], $ids);
            }
        }
        $count($scopedrecords);
        manager::save_many($scopedrecords);
        if (access_guard::check_fallbacks(repository::get_all()) === access_guard::FALLBACKS_BLOCKED_FOR_SOME) {
            $warnings[] = get_string('warn_fallbacks_blocked_for_some', 'local_extendednav');
        }
        return ['errors' => [], 'warnings' => $warnings, 'counts' => $counts];
    }

    /**
     * Gives every node of the file to each context chosen: the scope of the file is replaced by the one of the context, and a
     * node of a navigation that the context does not change is left out (the navigation of a category goes to categories only,
     * and the one of a kind of activity to an activity of that kind, to its course or to a category).
     *
     * @param array $items Nodes read from the file.
     * @param int[] $counts Counts, whose skipped grows with the nodes left out.
     * @return array The nodes, one for each context that takes them.
     */
    private function give_to_targets(array $items, array &$counts): array {
        $given = [];
        foreach ($this->targets as $contextid) {
            $context = \local_extendednav\local\navigation\context_scope::from_id($contextid);
            if ($context === null) {
                continue;
            }
            $scope = scope::describe($contextid);
            foreach ($items as $item) {
                $menukey = is_array($item) ? (trim((string)($item['menu'] ?? '')) ?: $this->defaultmenu) : '';
                $unsupported = !nodes::supports_scopes($menukey)
                    || ($menukey === 'category' && $context->kind() !== 'category')
                    || ($context->is_activity() && !in_array($menukey, $context->menus(), true));
                if ($unsupported) {
                    $counts['skipped']++;
                    continue;
                }
                // A node the file has more than once (for the site and for a context) is given once: the last one, which is
                // the most specific, as the file lists the site first.
                $key = $contextid . '|' . $menukey . '|' . trim((string)($item['nodekey'] ?? ''));
                $given[$key] = ['menu' => $menukey, 'scope' => $scope] + $item;
            }
        }
        return array_values($given);
    }

    /**
     * Gives each node of the single navigation of the activities, in a file of a version before 2.0, to the navigation of each
     * kind of activity: a node of Moodle to every kind that has it (none has it: it is left out with a warning), a node added
     * to every kind.
     *
     * @param array $items Nodes read from the file.
     * @param string[] $warnings Warnings, to which the nodes left out are added.
     * @param int[] $counts Counts, whose skipped grows with the nodes left out.
     * @return array The nodes, with the ones of the old navigation given to each kind.
     */
    private function expand_legacy_activities(array $items, array &$warnings, array &$counts): array {
        $kinds = array_map(static fn(string $modname): \local_extendednav\menu\activity =>
            new \local_extendednav\menu\activity($modname), array_keys(\local_extendednav\menu\activity::types()));
        $expanded = [];
        foreach ($items as $item) {
            $menukey = is_array($item) ? (trim((string)($item['menu'] ?? '')) ?: $this->defaultmenu) : '';
            if ($menukey !== \local_extendednav\menu\activity::LEGACY) {
                $expanded[] = $item;
                continue;
            }
            $nodekey = trim((string)($item['nodekey'] ?? ''));
            $given = 0;
            foreach ($kinds as $kind) {
                if (empty($item['native']) || $kind->is_native($nodekey)) {
                    $expanded[] = ['menu' => $kind->get_key()] + $item;
                    $given++;
                }
            }
            if ($given === 0) {
                $warnings[] = $menukey . '/' . $nodekey . ': ' . get_string('warn_import_absent', 'local_extendednav');
                $counts['skipped']++;
            }
        }
        return $expanded;
    }

    /**
     * Checks the nodes of a file that apply to a category or a course, and builds the records to save.
     *
     * @param array $items The nodes of the file that name a scope, keyed by their place in the file.
     * @param array $errors The errors found, to add to.
     * @param array $warnings The warnings found, to add to.
     * @param array $counts The nodes kept, to add to.
     * @return \stdClass[] The records to save.
     */
    private function prepare_scoped(array $items, array &$errors, array &$warnings, array &$counts): array {
        $records = [];
        foreach ($items as $index => $item) {
            $label = get_string('import_entry', 'local_extendednav', $index + 1);
            $menukey = isset($item['menu']) && trim((string)$item['menu']) !== ''
                ? trim((string)$item['menu']) : $this->defaultmenu;
            $contextid = scope::resolve((string)$item['scope']);
            if (menu_manager::get_menu($menukey) === null) {
                $errors[] = $label . ': ' . get_string('err_invalid_menu', 'local_extendednav');
                continue;
            }
            if ($contextid === null || !nodes::supports_scopes($menukey)) {
                $errors[] = $label . ': ' . get_string('err_import_scope', 'local_extendednav', s((string)$item['scope']));
                continue;
            }
            $values = array_intersect_key($item, array_flip(manager::EDITABLE_FIELDS));
            $record = manager::prepare($values, $menukey);
            $record->contextid = $contextid;
            $label = $menukey . '@' . $item['scope'] . '/' . $record->nodekey;

            // The key of a node of a category or a course repeats the one of the site on purpose: it is checked against its own.
            $others = repository::get_exceptions($menukey, $contextid);
            $current = null;
            foreach ($others as $other) {
                if ($other->nodekey === $record->nodekey) {
                    $current = $other;
                }
            }
            if (isset($records[$menukey . '@' . $contextid . '/' . $record->nodekey])) {
                $errors[] = $label . ': ' . get_string('err_import_duplicate', 'local_extendednav');
                continue;
            }
            if ($current !== null && !$this->overwrite) {
                $counts['skipped']++;
                continue;
            }
            $others = array_column($others, null, 'nodekey');
            unset($others[$record->nodekey]);
            $result = validator::validate($record, $others);
            foreach ($result['errors'] as $field => $message) {
                $errors[] = $label . ' (' . $field . '): ' . $message;
            }
            foreach ($result['warnings'] as $message) {
                $warnings[] = $label . ': ' . $message;
            }
            $order = $item['order'] ?? '';
            if ($order !== '' && $order !== null && is_numeric($order)) {
                $record->sortorder = (int)$order;
            }
            if ($current !== null) {
                $record->id = $current->id;
            }
            $records[$menukey . '@' . $contextid . '/' . $record->nodekey] = $record;
        }
        return array_values($records);
    }

    /**
     * Moves imported nodes to the position given in the file.
     *
     * The other nodes keep their order around them. Nodes are placed by ascending position, so a file
     * exported from a whole menu is imported in the same order.
     *
     * @param string $menukey Menu key.
     * @param array[] $orders Position and file index, keyed by node key.
     * @param int[] $ids Saved node ids, keyed by node key.
     */
    private static function apply_orders(string $menukey, array $orders, array $ids): void {
        global $DB;

        uasort($orders, static fn(array $a, array $b): int => $a <=> $b);
        $placed = array_map(static fn(string $nodekey): int => $ids[$nodekey], array_keys($orders));
        $nodes = $DB->get_records(
            'local_extendednav',
            ['menu' => $menukey, 'contextid' => 0],
            '',
            'id, nodekey, parentkey, sortorder'
        );
        $list = array_values(array_diff(
            array_map(static fn(\stdClass $node): int => (int)$node->id, manager::group_by_parent($nodes)),
            $placed
        ));
        foreach ($orders as $nodekey => [$order]) {
            array_splice($list, min($order - 1, count($list)), 0, [$ids[$nodekey]]);
        }
        manager::reorder($menukey, $list);
    }

    /**
     * Moves imported nodes of a tree to their position among the others of their category, and remembers that those
     * categories are ordered by hand.
     *
     * @param string $menukey Menu key.
     * @param array[] $orders Position among the siblings and file index, keyed by node key.
     */
    private static function apply_sibling_orders(string $menukey, array $orders): void {
        $nodes = nodes::get_scope_nodes($menukey, 0);
        $groups = [];
        foreach ($nodes as $key => $node) {
            $groups[(string)($node->parentkey ?? '')][] = $key;
        }
        $touched = [];
        foreach ($groups as $parent => $keys) {
            $placed = array_values(array_intersect($keys, array_keys($orders)));
            if (empty($placed)) {
                continue;
            }
            usort($placed, static fn(string $a, string $b): int => $orders[$a] <=> $orders[$b]);
            $list = array_values(array_diff($keys, $placed));
            foreach ($placed as $key) {
                array_splice($list, min($orders[$key][0] - 1, count($list)), 0, [$key]);
            }
            $groups[$parent] = $list;
            $touched[] = (string)$parent;
        }
        if (empty($touched)) {
            return;
        }
        // The whole tree in its new order: each category keeps its place and its children take the order of the file.
        $order = [];
        $queues = $groups;
        foreach ($nodes as $key => $node) {
            $order[] = array_shift($queues[(string)($node->parentkey ?? '')]);
        }
        manager::save_order($menukey, 0, $order);
        foreach ($touched as $parent) {
            \local_extendednav\menu\admintree::mark_ordered($parent);
        }
    }
}
