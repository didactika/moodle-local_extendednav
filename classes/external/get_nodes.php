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
use local_extendednav\local\listing\nodes;

/**
 * One page of the nodes of a navigation, with the counts of each visibility.
 *
 * @package    local_extendednav
 * @category   external
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_nodes extends node_service {
    /**
     * Describes the function parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'menu' => new external_value(PARAM_ALPHANUMEXT, 'Navigation key.'),
            'contextid' => new external_value(PARAM_INT, 'Context of the scope, 0 for the whole site.', VALUE_DEFAULT, 0),
            'search' => new external_value(PARAM_RAW, 'Text to find in the key, the title and the link.', VALUE_DEFAULT, ''),
            'visibility' => new external_value(PARAM_TEXT, '0, 1 and 2, separated by commas, or empty.', VALUE_DEFAULT, ''),
            'parent' => new external_value(PARAM_ALPHANUMEXT, 'Key of a parent node, or empty.', VALUE_DEFAULT, ''),
            'page' => new external_value(PARAM_INT, 'Zero-based page.', VALUE_DEFAULT, 0),
            'perpage' => new external_value(PARAM_INT, 'Rows per page, from 1 to 100, or 0 for all.', VALUE_DEFAULT, 0),
            'origin' => new external_value(PARAM_TEXT, 'native and custom, separated by commas, or empty.', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Executes the function.
     *
     * @param string $menu Navigation key.
     * @param int $contextid Context of the scope.
     * @param string $search Text to find.
     * @param string $visibility Visibility filter.
     * @param string $parent Parent filter.
     * @param int $page Page.
     * @param int $perpage Rows per page, 0 for every row.
     * @param string $origin Nodes of Moodle (native), nodes added (custom), or both.
     * @return array The page.
     */
    public static function execute(
        string $menu,
        int $contextid = 0,
        string $search = '',
        string $visibility = '',
        string $parent = '',
        int $page = 0,
        int $perpage = 0,
        string $origin = ''
    ): array {
        $params = self::validate_parameters(
            self::execute_parameters(),
            compact('menu', 'contextid', 'search', 'visibility', 'parent', 'page', 'perpage', 'origin')
        );
        self::require_scope($params['menu'], $params['contextid']);
        if ($params['page'] < 0 || $params['perpage'] < 0 || $params['perpage'] > nodes::MAX_PAGE_SIZE) {
            throw new \invalid_parameter_exception('Page must be non-negative and perpage between 0 and 100.');
        }
        foreach (array_filter(explode(',', $params['visibility']), static fn(string $value): bool => $value !== '') as $value) {
            if (!in_array($value, ['0', '1', '2'], true)) {
                throw new \invalid_parameter_exception('Unsupported visibility.');
            }
        }
        return nodes::get(
            $params['menu'],
            $params['contextid'],
            $params['search'],
            $params['visibility'],
            $params['parent'],
            $params['page'],
            $params['perpage'],
            $params['origin']
        );
    }

    /**
     * Describes the function result.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'total' => new external_value(PARAM_INT, 'Rows that match the filters.'),
            'counts' => new external_single_structure([
                'all' => new external_value(PARAM_INT, 'Nodes.'),
                'visible' => new external_value(PARAM_INT, 'Nodes visible to everyone.'),
                'roles' => new external_value(PARAM_INT, 'Nodes restricted to roles.'),
                'hidden' => new external_value(PARAM_INT, 'Hidden nodes.'),
                'native' => new external_value(PARAM_INT, 'Nodes of Moodle.'),
                'custom' => new external_value(PARAM_INT, 'Nodes added by the plugin.'),
            ]),
            'rows' => new external_multiple_structure(self::row_structure()),
            'reorderable' => new external_value(PARAM_BOOL, 'Whether the list can be reordered as it is.'),
            'path' => new external_multiple_structure(new external_single_structure([
                'key' => new external_value(PARAM_ALPHANUMEXT, 'Key of a category.'),
                'title' => new external_value(PARAM_RAW, 'Title of the category.'),
            ]), 'The categories from the top of the tree to the level listed.'),
        ]);
    }
}
