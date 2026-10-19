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

use local_extendednav\menu_manager;

/**
 * Imports and exports nodes in the YAML file format.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class node_importer {
    /** @var string[] Keys written for each node, in order. */
    public const EXPORT_FIELDS = [
        'nodekey', 'menu', 'order', 'title', 'tooltip', 'url', 'icon', 'visibility', 'roles',
        'blockaccess', 'langs', 'parentkey', 'display', 'popupwidth', 'popupheight',
    ];

    /** @var string[] Fields written as whole numbers. */
    private const INT_FIELDS = ['order', 'visibility', 'blockaccess', 'display'];

    /** @var string[] Fields written as whole numbers, or empty when not set. */
    private const OPTIONAL_INT_FIELDS = ['popupwidth', 'popupheight'];

    /** @var string Menu used for nodes that do not name one. */
    private string $defaultmenu;

    /** @var bool Whether nodes that already exist are replaced (true) or skipped (false). */
    private bool $overwrite;

    /**
     * Constructor.
     *
     * @param string $defaultmenu Menu used for nodes that do not name one.
     * @param bool $overwrite Whether nodes that already exist are replaced (true) or skipped (false).
     */
    public function __construct(string $defaultmenu, bool $overwrite) {
        $this->defaultmenu = $defaultmenu;
        $this->overwrite = $overwrite;
    }

    /**
     * Returns the exported form of nodes of one menu, with their position in the menu.
     *
     * @param \stdClass[] $nodes Nodes to export.
     * @param \stdClass[] $menunodes Every node of their menu, to number the positions as the management page lists them.
     * @return array[]
     */
    public static function export_nodes(array $nodes, array $menunodes): array {
        $positions = [];
        foreach (node_manager::group_by_parent($menunodes) as $index => $node) {
            $positions[(int)$node->id] = $index + 1;
        }
        $exported = [];
        foreach ($nodes as $node) {
            $exported[] = self::export_node($node, $positions[(int)$node->id] ?? null);
        }
        usort($exported, static fn(array $a, array $b): int => $a['order'] <=> $b['order']);
        return $exported;
    }

    /**
     * Returns the exported form of a node.
     *
     * @param \stdClass $node Node record.
     * @param int|null $order Position of the node in its menu, 1 being the first.
     * @return array
     */
    public static function export_node(\stdClass $node, ?int $order = null): array {
        $node = clone $node;
        $node->order = $order ?? 0;
        $data = [];
        foreach (self::EXPORT_FIELDS as $field) {
            $value = $node->$field ?? null;
            if (in_array($field, self::INT_FIELDS, true)) {
                $data[$field] = (int)$value;
            } else if (in_array($field, self::OPTIONAL_INT_FIELDS, true)) {
                $data[$field] = $value === null ? '' : (int)$value;
            } else {
                $data[$field] = $value === null ? '' : (string)$value;
            }
        }
        return $data;
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

        $existing = [];
        foreach (node_repository::get_all() as $node) {
            $existing[$node->menu][$node->nodekey] = $node;
        }

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

            $values = array_intersect_key($item, array_flip(node_manager::EDITABLE_FIELDS));
            $record = node_manager::prepare($values, $menukey);
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
                $result = node_validator::validate($record, $others);
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

        foreach ($batch as $menukey => $records) {
            $ids = [];
            foreach ($records as $nodekey => $record) {
                if (isset($record->id)) {
                    node_manager::update($record);
                    $counts['updated']++;
                } else {
                    $record = node_manager::create($record);
                    $counts['created']++;
                }
                $ids[$nodekey] = (int)$record->id;
            }
            if (!empty($orders[$menukey])) {
                self::apply_orders($menukey, $orders[$menukey], $ids);
            }
        }
        if (access_guard::check_fallbacks(node_repository::get_all()) === access_guard::FALLBACKS_BLOCKED_FOR_SOME) {
            $warnings[] = get_string('warn_fallbacks_blocked_for_some', 'local_extendednav');
        }
        return ['errors' => [], 'warnings' => $warnings, 'counts' => $counts];
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
        $nodes = $DB->get_records('local_extendednav', ['menu' => $menukey], '', 'id, nodekey, parentkey, sortorder');
        $list = array_values(array_diff(
            array_map(static fn(\stdClass $node): int => (int)$node->id, node_manager::group_by_parent($nodes)),
            $placed
        ));
        foreach ($orders as $nodekey => [$order]) {
            array_splice($list, min($order - 1, count($list)), 0, [$ids[$nodekey]]);
        }
        node_manager::reorder($menukey, $list);
    }
}
