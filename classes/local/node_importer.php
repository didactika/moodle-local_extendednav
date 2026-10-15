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
        'nodekey', 'menu', 'title', 'tooltip', 'url', 'icon', 'visibility', 'roles',
        'blockaccess', 'langs', 'parentkey', 'newwindow',
    ];

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
     * Returns the exported form of a node.
     *
     * @param \stdClass $node Node record.
     * @return array
     */
    public static function export_node(\stdClass $node): array {
        $data = [];
        foreach (self::EXPORT_FIELDS as $field) {
            $value = $node->$field ?? null;
            if (in_array($field, ['visibility', 'blockaccess', 'newwindow'], true)) {
                $data[$field] = (int)$value;
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

        foreach ($batch as $records) {
            foreach ($records as $record) {
                if (isset($record->id)) {
                    node_manager::update($record);
                    $counts['updated']++;
                } else {
                    node_manager::create($record);
                    $counts['created']++;
                }
            }
        }
        if (access_guard::check_fallbacks(node_repository::get_all()) === access_guard::FALLBACKS_BLOCKED_FOR_SOME) {
            $warnings[] = get_string('warn_fallbacks_blocked_for_some', 'local_extendednav');
        }
        return ['errors' => [], 'warnings' => $warnings, 'counts' => $counts];
    }
}
