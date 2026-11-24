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

namespace local_extendednav\external;

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_extendednav\local\io\exporter;
use local_extendednav\local\io\importer;
use local_extendednav\local\listing\nodes;
use local_extendednav\local\navigation\context_scope;
use local_extendednav\local\node\manager;
use local_extendednav\local\node\repository;
use local_extendednav\local\node\validator;
use local_extendednav\menu_manager;

/**
 * The services that manage the navigations from outside Moodle, beside the ones of the management page: the navigations of
 * the site, saving one node with its fields, and moving the navigations in and out of a file.
 *
 * @package    local_extendednav
 * @category   external
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class manage_navigation extends node_service {
    /**
     * Describes the parameters of get_navigations().
     *
     * @return external_function_parameters
     */
    public static function get_navigations_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Lists the navigations of the site.
     *
     * @return array The navigations, each with its key, name, short name, whether its changes are applied, whether it is a
     *     tree and whether it has exceptions.
     */
    public static function get_navigations(): array {
        self::require_manage();
        $navigations = [];
        foreach (menu_manager::get_menus() as $key => $menu) {
            $navigations[] = [
                'key' => $key,
                'name' => $menu->get_name(),
                'shortname' => $menu->get_short_name(),
                'enabled' => menu_manager::is_enabled($key),
                'tree' => nodes::is_tree($key),
                'scopes' => nodes::supports_scopes($key),
            ];
        }
        return ['navigations' => $navigations];
    }

    /**
     * Describes the result of get_navigations().
     *
     * @return external_single_structure
     */
    public static function get_navigations_returns(): external_single_structure {
        return new external_single_structure([
            'navigations' => new external_multiple_structure(new external_single_structure([
                'key' => new external_value(PARAM_ALPHANUMEXT, 'Navigation key.'),
                'name' => new external_value(PARAM_TEXT, 'Name.'),
                'shortname' => new external_value(PARAM_TEXT, 'Short name.'),
                'enabled' => new external_value(PARAM_BOOL, 'Whether its changes are applied.'),
                'tree' => new external_value(PARAM_BOOL, 'Whether its nodes are folders of any depth.'),
                'scopes' => new external_value(PARAM_BOOL, 'Whether categories, courses, activities or users change it.'),
            ])),
        ]);
    }

    /**
     * Describes the parameters of save_node().
     *
     * @return external_function_parameters
     */
    public static function save_node_parameters(): external_function_parameters {
        $text = static fn(string $description): external_value => new external_value(PARAM_RAW, $description, VALUE_OPTIONAL);
        $int = static fn(string $description): external_value => new external_value(PARAM_INT, $description, VALUE_OPTIONAL);
        return new external_function_parameters([
            'menu' => new external_value(PARAM_ALPHANUMEXT, 'Navigation key.'),
            'contextid' => new external_value(PARAM_INT, 'Context of the exception, 0 for the whole site.', VALUE_DEFAULT, 0),
            'nodekey' => new external_value(PARAM_ALPHANUMEXT, 'Node key: one of Moodle to change it, a new one to add a node.'),
            'fields' => new external_single_structure([
                'title' => $text('Title, or empty to keep the one of Moodle.'),
                'tooltip' => $text('Text shown when the pointer is over the link.'),
                'url' => $text('Address: a path of the site or a whole address.'),
                'icon' => $text('FontAwesome class, Moodle icon or none.'),
                'visibility' => $int('1 visible to everyone, 2 restricted to roles, 0 not visible.'),
                'roles' => $text('Short names of roles, separated by commas.'),
                'langs' => $text('Language codes, separated by commas.'),
                'parentkey' => $text('Key of the parent node.'),
                'display' => $int('0 current window, 1 new window, 2 pop-up.'),
                'popupwidth' => $int('Width of the pop-up.'),
                'popupheight' => $int('Height of the pop-up.'),
                'placement' => $int('In a secondary navigation: 0 where Moodle puts it, 1 always in More, 2 never in More.'),
                'hideadmins' => $int('1 to apply the rules to the administrators too.'),
                'blockaccess' => $int('1 to block the address to whoever does not see the node.'),
                'availability' => $text('Access restrictions of Moodle, as JSON.'),
            ], 'The fields to set; the ones left out keep their value.', VALUE_DEFAULT, []),
        ]);
    }

    /**
     * Adds a node, or changes one, in the whole site or as an exception of a context, with the checks of the form.
     *
     * @param string $menu Navigation key.
     * @param int $contextid Context of the exception, 0 for the whole site.
     * @param string $nodekey Node key.
     * @param array $fields The fields to set.
     * @return array The id of the row, whether it was created, and the warnings.
     */
    public static function save_node(string $menu, int $contextid, string $nodekey, array $fields = []): array {
        $params = self::validate_parameters(self::save_node_parameters(), compact('menu', 'contextid', 'nodekey', 'fields'));
        $menu = $params['menu'];
        $contextid = $params['contextid'];
        $navigation = menu_manager::get_menu($menu);
        if ($navigation === null) {
            throw new \invalid_parameter_exception('Unknown navigation.');
        }
        $fields = array_filter($params['fields'], static fn($value): bool => $value !== null);

        $existing = $contextid ? array_column(repository::get_exceptions($menu, $contextid), null, 'nodekey')
            : repository::get_menu_nodes_by_key($menu);
        $current = $existing[$params['nodekey']] ?? null;
        $native = $navigation->is_native($params['nodekey']);
        if ($contextid === 0) {
            self::require_scope($menu, 0);
        } else {
            self::require_scope($menu, $contextid, $current === null && !$native ? 'addnodes' : '');
            $scope = context_scope::from_id($contextid);
            $locked = array_intersect(array_keys($fields), $scope->locked_fields());
            if ($locked) {
                throw new \invalid_parameter_exception('These fields cannot be changed here: ' . implode(', ', $locked) . '.');
            }
            if ($current === null && !$native && !$scope->can('addnodes')) {
                throw new \required_capability_exception($scope->context(), 'local/extendednav:addnodes', 'nopermissions', '');
            }
        }

        // The fields not given keep their value: the node the context sees, or a new one.
        $base = $current ?? ($contextid ? (nodes::get_scope_nodes($menu, $contextid)[$params['nodekey']] ?? null) : null);
        $data = array_intersect_key((array)($base ?? []), array_flip(manager::EDITABLE_FIELDS));
        $data = array_merge($data, $fields, ['nodekey' => $params['nodekey']]);
        if ($contextid) {
            $data['contextid'] = $contextid;
        }
        $record = manager::prepare($data, $menu);
        $others = $existing;
        unset($others[$params['nodekey']]);
        $result = validator::validate($record, $others);
        if (!empty($result['errors'])) {
            $messages = [];
            foreach ($result['errors'] as $field => $message) {
                $messages[] = $field . ': ' . $message;
            }
            throw new \invalid_parameter_exception(implode(' ', $messages));
        }
        if ($current !== null) {
            $record->id = $current->id;
            $saved = manager::update($record);
            $created = false;
        } else {
            if ($base !== null) {
                $record->sortorder = $base->sortorder;
            }
            $saved = manager::create($record);
            $created = true;
        }
        repository::purge();
        return ['id' => (int)$saved->id, 'created' => $created, 'warnings' => array_values($result['warnings'] ?? [])];
    }

    /**
     * Describes the result of save_node().
     *
     * @return external_single_structure
     */
    public static function save_node_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Row of the node.'),
            'created' => new external_value(PARAM_BOOL, 'Whether the row is new.'),
            'warnings' => new external_multiple_structure(new external_value(PARAM_TEXT, 'Warning.')),
        ]);
    }

    /**
     * Describes the parameters of export_navigations().
     *
     * @return external_function_parameters
     */
    public static function export_navigations_parameters(): external_function_parameters {
        return new external_function_parameters([
            'menus' => new external_multiple_structure(
                new external_value(PARAM_ALPHANUMEXT, 'Navigation key.'),
                'The navigations; none for all of them.',
                VALUE_DEFAULT,
                []
            ),
            'contextids' => new external_multiple_structure(
                new external_value(PARAM_INT, 'Context, 0 for the whole site.'),
                'Where the changes come from: 0 for the whole site, and the contexts of users, courses or categories.',
                VALUE_DEFAULT,
                [0]
            ),
        ]);
    }

    /**
     * Writes the navigations chosen as a file, as the export of the management page does.
     *
     * @param string[] $menus The navigations; none for all of them.
     * @param int[] $contextids Where the changes come from.
     * @return array The content of the file, empty when it holds no node.
     */
    public static function export_navigations(array $menus = [], array $contextids = [0]): array {
        $params = self::validate_parameters(self::export_navigations_parameters(), compact('menus', 'contextids'));
        self::require_manage();
        $menus = $params['menus'] ?: array_keys(menu_manager::get_menus());
        foreach ($menus as $menu) {
            if (menu_manager::get_menu($menu) === null) {
                throw new \invalid_parameter_exception('Unknown navigation: ' . $menu . '.');
            }
        }
        return ['content' => (string)exporter::export_file($menus, array_map('intval', $params['contextids']))];
    }

    /**
     * Describes the result of export_navigations().
     *
     * @return external_single_structure
     */
    public static function export_navigations_returns(): external_single_structure {
        return new external_single_structure([
            'content' => new external_value(PARAM_RAW, 'The file, in YAML.'),
        ]);
    }

    /**
     * Describes the parameters of import_navigations().
     *
     * @return external_function_parameters
     */
    public static function import_navigations_parameters(): external_function_parameters {
        return new external_function_parameters([
            'content' => new external_value(PARAM_RAW, 'The file, in YAML.'),
            'menu' => new external_value(PARAM_ALPHANUMEXT, 'Navigation of the nodes that name none.', VALUE_DEFAULT, 'primary'),
            'overwrite' => new external_value(PARAM_BOOL, 'Whether nodes that exist are replaced.', VALUE_DEFAULT, false),
        ]);
    }

    /**
     * Reads a file as the import of the management page does: the nodes of contexts the site does not have are skipped with
     * a warning, and nothing is saved when a node is not valid.
     *
     * @param string $content The file.
     * @param string $menu Navigation of the nodes that name none.
     * @param bool $overwrite Whether nodes that exist are replaced.
     * @return array The counts, the errors and the warnings.
     */
    public static function import_navigations(string $content, string $menu = 'primary', bool $overwrite = false): array {
        $params = self::validate_parameters(self::import_navigations_parameters(), compact('content', 'menu', 'overwrite'));
        self::require_manage();
        try {
            $parsed = \local_extendednav\yaml::parse($params['content']);
        } catch (\Throwable $e) {
            throw new \invalid_parameter_exception(get_string('err_invalid_yaml_format', 'local_extendednav'));
        }
        if (!isset($parsed['nodes']) || !is_array($parsed['nodes'])) {
            throw new \invalid_parameter_exception(get_string('err_invalid_yaml_structure', 'local_extendednav'));
        }
        $result = (new importer($params['menu'], $params['overwrite']))->import($parsed['nodes']);
        repository::purge();
        return [
            'created' => (int)$result['counts']['created'],
            'updated' => (int)$result['counts']['updated'],
            'skipped' => (int)$result['counts']['skipped'],
            'errors' => array_values(array_map('strval', $result['errors'])),
            'warnings' => array_values(array_map('strval', $result['warnings'])),
        ];
    }

    /**
     * Describes the result of import_navigations().
     *
     * @return external_single_structure
     */
    public static function import_navigations_returns(): external_single_structure {
        $list = static fn(string $description): external_multiple_structure =>
            new external_multiple_structure(new external_value(PARAM_TEXT, $description));
        return new external_single_structure([
            'created' => new external_value(PARAM_INT, 'Nodes created.'),
            'updated' => new external_value(PARAM_INT, 'Nodes replaced.'),
            'skipped' => new external_value(PARAM_INT, 'Nodes left out.'),
            'errors' => $list('Why nothing was saved.'),
            'warnings' => $list('What was left out, and why.'),
        ]);
    }
}
