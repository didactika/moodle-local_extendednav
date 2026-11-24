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
use local_extendednav\menu\activity;
use local_extendednav\menu_manager;

/**
 * Finds what a file of the site can hold, a few at a time, for the selects of the page that chooses what to export: the
 * navigations of the site, the kinds of activity, the users, the courses and the categories.
 *
 * @package    local_extendednav
 * @category   external
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class search_export extends node_service {
    /** @var int Results of one search. */
    public const LIMIT = 10;

    /** @var string[] The navigations of the site, in the order of the column of the management page. */
    public const SITE = ['primary', 'usermenu', 'preferences', 'frontpage', 'course', 'category', 'admin'];

    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'kind' => new external_value(PARAM_ALPHA, 'site, kinds, users, courses or categories.'),
            'query' => new external_value(PARAM_TEXT, 'Text to find; empty for the first ones.', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Finds the first results that match.
     *
     * @param string $kind What to find.
     * @param string $query Text to find.
     * @return array The results, each with its value and its label.
     */
    public static function execute(string $kind, string $query = ''): array {
        $params = self::validate_parameters(self::execute_parameters(), compact('kind', 'query'));
        self::require_manage();
        return ['results' => self::find($params['kind'], trim($params['query']))];
    }

    /**
     * Finds the first results that match.
     *
     * @param string $kind site, kinds, users, courses or categories.
     * @param string $query Text to find; empty for the first ones.
     * @return array[] Each with value and label.
     */
    public static function find(string $kind, string $query): array {
        global $DB, $CFG;

        $matches = static fn(string $text): bool => $query === '' || \core_text::strpos(
            \core_text::strtolower($text),
            \core_text::strtolower($query)
        ) !== false;
        $results = [];
        switch ($kind) {
            case 'site':
                foreach (self::SITE as $key) {
                    $name = menu_manager::get_menu($key)->get_name();
                    if ($matches($name)) {
                        $results[] = ['value' => $key, 'label' => $name];
                    }
                }
                break;
            case 'kinds':
                foreach (array_keys(activity::types()) as $modname) {
                    $menu = new activity($modname);
                    if ($matches($menu->get_short_name())) {
                        $results[] = ['value' => $menu->get_key(), 'label' => $menu->get_short_name()];
                    }
                }
                \core_collator::asort_array_of_arrays_by_key($results, 'label');
                break;
            case 'users':
                $like = '%' . $DB->sql_like_escape($query) . '%';
                $fullname = $DB->sql_fullname();
                $where = 'deleted = 0 AND confirmed = 1 AND id <> :guest AND mnethostid = :host';
                $params = ['guest' => $CFG->siteguest, 'host' => $CFG->mnet_localhost_id];
                if ($query !== '') {
                    $where .= ' AND (' . $DB->sql_like($fullname, ':q1', false, false)
                        . ' OR ' . $DB->sql_like('email', ':q2', false, false)
                        . ' OR ' . $DB->sql_like('username', ':q3', false, false) . ')';
                    $params += ['q1' => $like, 'q2' => $like, 'q3' => $like];
                }
                $fields = 'id, username, email, ' . implode(', ', \core_user\fields::get_name_fields());
                $users = $DB->get_records_select('user', $where, $params, 'lastname, firstname, id', $fields, 0, self::LIMIT);
                foreach ($users as $user) {
                    $results[] = ['value' => (string)$user->id, 'label' => fullname($user) . ' (' . $user->username . ')'];
                }
                return $results;
            case 'courses':
                $like = '%' . $DB->sql_like_escape($query) . '%';
                $where = 'id <> :site';
                $params = ['site' => SITEID];
                if ($query !== '') {
                    $where .= ' AND (' . $DB->sql_like('fullname', ':q1', false, false) . ' OR '
                        . $DB->sql_like('shortname', ':q2', false, false) . ')';
                    $params += ['q1' => $like, 'q2' => $like];
                }
                $fields = 'id, fullname, shortname';
                $courses = $DB->get_records_select('course', $where, $params, 'fullname, id', $fields, 0, self::LIMIT);
                foreach ($courses as $course) {
                    $label = format_string($course->fullname) . ' (' . $course->shortname . ')';
                    $results[] = ['value' => (string)$course->id, 'label' => $label];
                }
                return $results;
            case 'categories':
                foreach (\core_course_category::make_categories_list() as $id => $name) {
                    if ($matches($name)) {
                        $results[] = ['value' => (string)$id, 'label' => $name];
                    }
                    if (count($results) >= self::LIMIT) {
                        break;
                    }
                }
                return $results;
            default:
                throw new \invalid_parameter_exception('Unknown kind.');
        }
        return array_slice(array_values($results), 0, self::LIMIT);
    }

    /**
     * Returns the label of a value already chosen.
     *
     * @param string $kind site, kinds, users, courses or categories.
     * @param string $value The value.
     * @return string
     */
    public static function label_of(string $kind, string $value): string {
        global $DB;

        switch ($kind) {
            case 'site':
            case 'kinds':
                $menu = menu_manager::get_menu($value);
                return $menu ? ($kind === 'kinds' ? $menu->get_short_name() : $menu->get_name()) : $value;
            case 'users':
                $user = \core_user::get_user((int)$value);
                return $user ? fullname($user) . ' (' . $user->username . ')' : $value;
            case 'courses':
                $course = $DB->get_record('course', ['id' => (int)$value], 'id, fullname, shortname');
                return $course ? format_string($course->fullname) . ' (' . $course->shortname . ')' : $value;
            case 'categories':
                return \core_course_category::make_categories_list()[(int)$value] ?? $value;
        }
        return $value;
    }

    /**
     * Describes the result.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'results' => new external_multiple_structure(new external_single_structure([
                'value' => new external_value(PARAM_RAW, 'Value.'),
                'label' => new external_value(PARAM_TEXT, 'Label.'),
            ])),
        ]);
    }
}
