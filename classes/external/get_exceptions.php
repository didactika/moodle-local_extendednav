<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_extendednav\external;

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_extendednav\local\listing\exceptions;

/**
 * One page of the categories and courses that change a navigation, for the overview of the site administration.
 *
 * @package    local_extendednav
 * @category   external
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_exceptions extends node_service {
    /**
     * Describes the function parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'search' => new external_value(PARAM_RAW, 'Part of the name of a category or a course.', VALUE_DEFAULT, ''),
            'kind' => new external_value(PARAM_TEXT, 'category and course, separated by commas, or empty.', VALUE_DEFAULT, ''),
            'page' => new external_value(PARAM_INT, 'Page, from 0.', VALUE_DEFAULT, 0),
            'perpage' => new external_value(PARAM_INT, 'Rows per page, from 1 to 100.', VALUE_DEFAULT, 12),
        ]);
    }

    /**
     * Returns one page of the list.
     *
     * @param string $search Part of a name.
     * @param string $kind Kinds of context.
     * @param int $page Page, from 0.
     * @param int $perpage Rows per page.
     * @return array
     */
    public static function execute(string $search = '', string $kind = '', int $page = 0, int $perpage = 12): array {
        $params = self::validate_parameters(self::execute_parameters(), compact('search', 'kind', 'page', 'perpage'));
        self::require_manage();
        if ($params['page'] < 0 || $params['perpage'] < 1 || $params['perpage'] > exceptions::MAX_PAGE_SIZE) {
            throw new \invalid_parameter_exception('Page must be non-negative and perpage between 1 and 100.');
        }
        return exceptions::get($params['search'], $params['kind'], $params['page'], $params['perpage']);
    }

    /**
     * Describes the function result.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        $text = static fn(string $description): external_value => new external_value(PARAM_RAW, $description);
        return new external_single_structure([
            'total' => new external_value(PARAM_INT, 'Categories and courses that match.'),
            'counts' => new external_single_structure([
                'category' => new external_value(PARAM_INT, 'Categories that match.'),
                'course' => new external_value(PARAM_INT, 'Courses that match.'),
            ]),
            'rows' => new external_multiple_structure(new external_single_structure([
                'contextid' => new external_value(PARAM_INT, 'Context of the category or the course.'),
                'name' => $text('Name of the category or the course.'),
                'path' => $text('The categories around it, from the top.'),
                'kind' => new external_value(PARAM_ALPHA, 'category or course.'),
                'total' => new external_value(PARAM_INT, 'Nodes it changes.'),
                'navigations' => new external_multiple_structure(new external_single_structure([
                    'name' => $text('Name of the navigation.'),
                    'count' => new external_value(PARAM_INT, 'Nodes it changes in that navigation.'),
                    'url' => $text('Page of that navigation in the context.'),
                ])),
                'url' => $text('Page of the navigations of the context.'),
            ])),
        ]);
    }
}
