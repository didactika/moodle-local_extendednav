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
use local_extendednav\local\node_list;

/**
 * The categories and courses a navigation can have exceptions for: the ones that have them and the ones a search finds.
 *
 * @package    local_extendednav
 * @category   external
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_scopes extends node_service {
    /** @var int Most scopes the service returns. */
    private const LIMIT = 30;

    /**
     * Describes the function parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'menu' => new external_value(PARAM_ALPHANUMEXT, 'Navigation key.'),
            'search' => new external_value(PARAM_RAW, 'Part of the name of a category or a course.', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Returns the scopes.
     *
     * @param string $menu Navigation key.
     * @param string $search Part of a name.
     * @return array
     */
    public static function execute(string $menu, string $search = ''): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), compact('menu', 'search'));
        self::require_manage();
        self::validate_scope($params['menu'], 0);
        if (!node_list::supports_scopes($params['menu'])) {
            return ['scopes' => []];
        }
        $search = trim($params['search']);
        $scopes = [];

        // The categories and courses that already have exceptions in this navigation come first.
        $counts = $DB->get_records_sql(
            'SELECT contextid, COUNT(1) AS total FROM {local_extendednav} WHERE menu = :menu AND contextid <> 0 GROUP BY contextid',
            ['menu' => $params['menu']]
        );
        foreach ($counts as $row) {
            $context = \context::instance_by_id((int)$row->contextid, IGNORE_MISSING);
            if ($context && in_array($context->contextlevel, [CONTEXT_COURSECAT, CONTEXT_COURSE], true)) {
                $scopes[(int)$row->contextid] = self::describe($context, (int)$row->total);
            }
        }

        if ($search !== '') {
            $like = $DB->sql_like('name', ':search', false, false);
            $categories = $DB->get_records_select(
                'course_categories',
                $like,
                ['search' => '%' . $DB->sql_like_escape($search) . '%'],
                'name ASC',
                'id, name',
                0,
                self::LIMIT
            );
            foreach ($categories as $category) {
                $context = \context_coursecat::instance((int)$category->id, IGNORE_MISSING);
                if ($context && !isset($scopes[$context->id])) {
                    $scopes[$context->id] = self::describe($context, 0);
                }
            }
            if ($params['menu'] !== 'category') {
                $likecourse = '(' . $DB->sql_like('fullname', ':a', false, false) . ' OR '
                    . $DB->sql_like('shortname', ':b', false, false) . ') AND id <> :site';
                $escaped = '%' . $DB->sql_like_escape($search) . '%';
                $courses = $DB->get_records_select(
                    'course',
                    $likecourse,
                    ['a' => $escaped, 'b' => $escaped, 'site' => SITEID],
                    'fullname ASC',
                    'id',
                    0,
                    self::LIMIT
                );
                foreach ($courses as $course) {
                    $context = \context_course::instance((int)$course->id, IGNORE_MISSING);
                    if ($context && !isset($scopes[$context->id])) {
                        $scopes[$context->id] = self::describe($context, 0);
                    }
                }
            }
        }
        return ['scopes' => array_slice(array_values($scopes), 0, self::LIMIT)];
    }

    /**
     * Describes a scope.
     *
     * @param \context $context Category or course context.
     * @param int $exceptions Nodes the scope changes.
     * @return array
     */
    private static function describe(\context $context, int $exceptions): array {
        $name = $context->contextlevel === CONTEXT_COURSE
            ? format_string(get_course($context->instanceid)->fullname, true, ['context' => $context])
            : \core_course_category::get($context->instanceid, IGNORE_MISSING, true)?->get_formatted_name();
        return [
            'contextid' => (int)$context->id,
            'name' => $name ?? $context->get_context_name(false, true),
            'type' => $context->contextlevel === CONTEXT_COURSE ? 'course' : 'category',
            'exceptions' => $exceptions,
        ];
    }

    /**
     * Describes the function result.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'scopes' => new external_multiple_structure(new external_single_structure([
                'contextid' => new external_value(PARAM_INT, 'Context of the scope.'),
                'name' => new external_value(PARAM_TEXT, 'Name of the category or the course.'),
                'type' => new external_value(PARAM_ALPHA, 'category or course.'),
                'exceptions' => new external_value(PARAM_INT, 'Nodes the scope changes.'),
            ])),
        ]);
    }
}
