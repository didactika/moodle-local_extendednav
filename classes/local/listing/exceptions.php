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

namespace local_extendednav\local\listing;

use local_extendednav\local\navigation\context_scope;
use local_extendednav\menu_manager;

/**
 * One page of the categories and courses that change a navigation: how many nodes each changes in each navigation, and the
 * page where it is done. The list is read in one query per page, however many exceptions the site has.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class exceptions {
    /** @var int Most rows in one page. */
    public const MAX_PAGE_SIZE = 100;

    /** @var string[] The navigations that have exceptions. */
    public const MENUS = ['category', 'course', 'activity'];

    /** @var string[] The kinds of context, as the menu of the list names them. */
    public const KINDS = ['category' => CONTEXT_COURSECAT, 'course' => CONTEXT_COURSE];

    /**
     * Returns one page of the list.
     *
     * @param string $search Part of the name of a category, or of the name or short name of a course.
     * @param string $kind category and course, separated by commas, or empty for both.
     * @param int $page Page, from 0.
     * @param int $perpage Rows in a page.
     * @return array With total, counts of each kind and rows.
     */
    public static function get(string $search = '', string $kind = '', int $page = 0, int $perpage = 12): array {
        global $DB;

        [$where, $params] = self::where(trim(\core_text::substr($search, 0, 255)));
        $from = "{local_extendednav} n
                 JOIN {context} ctx ON ctx.id = n.contextid
            LEFT JOIN {course} c ON ctx.contextlevel = :levelcourse AND c.id = ctx.instanceid
            LEFT JOIN {course_categories} cc ON ctx.contextlevel = :levelcategory AND cc.id = ctx.instanceid";
        $params += ['levelcourse' => CONTEXT_COURSE, 'levelcategory' => CONTEXT_COURSECAT];

        // How many categories and courses match, for the menu of the kinds, whatever kind is chosen.
        $counts = array_fill_keys(array_keys(self::KINDS), 0);
        $bylevel = $DB->get_records_sql_menu(
            "SELECT ctx.contextlevel, COUNT(DISTINCT n.contextid) FROM $from WHERE $where GROUP BY ctx.contextlevel",
            $params
        );
        foreach (self::KINDS as $name => $level) {
            $counts[$name] = (int)($bylevel[$level] ?? 0);
        }

        $levels = array_values(array_intersect_key(self::KINDS, array_flip(explode(',', $kind))));
        if ($levels) {
            [$insql, $inparams] = $DB->get_in_or_equal($levels, SQL_PARAMS_NAMED, 'lvl');
            $where .= " AND ctx.contextlevel $insql";
            $params += $inparams;
        }
        $total = (int)$DB->count_records_sql("SELECT COUNT(DISTINCT n.contextid) FROM $from WHERE $where", $params);

        $perpage = min(max($perpage, 1), self::MAX_PAGE_SIZE);
        $sums = implode(', ', array_map(
            static fn(string $menu): string => "SUM(CASE WHEN n.menu = '$menu' THEN 1 ELSE 0 END) AS n_$menu",
            self::MENUS
        ));
        $records = $DB->get_records_sql(
            "SELECT n.contextid, ctx.contextlevel, ctx.instanceid, COUNT(1) AS total, $sums
               FROM $from
              WHERE $where
           GROUP BY n.contextid, ctx.contextlevel, ctx.instanceid, ctx.path
           ORDER BY ctx.path",
            $params,
            max($page, 0) * $perpage,
            $perpage
        );

        $rows = [];
        foreach ($records as $record) {
            $scope = context_scope::from_id((int)$record->contextid);
            if ($scope === null) {
                continue;
            }
            $navigations = [];
            foreach (self::MENUS as $menu) {
                $count = (int)$record->{'n_' . $menu};
                if ($count > 0) {
                    $navigations[] = [
                        'name' => menu_manager::get_menu($menu)->get_name(),
                        'count' => $count,
                        'url' => $scope->url($menu)->out(false),
                    ];
                }
            }
            $rows[] = [
                'contextid' => (int)$record->contextid,
                'name' => $scope->name(),
                'path' => self::path_of($scope->context()),
                'kind' => $scope->is_course() ? 'course' : 'category',
                'total' => (int)$record->total,
                'navigations' => $navigations,
                'url' => $scope->url()->out(false),
            ];
        }
        return ['total' => $total, 'counts' => $counts, 'rows' => $rows];
    }

    /**
     * The conditions of the search.
     *
     * @param string $search Text to find.
     * @return array The SQL and its parameters.
     */
    private static function where(string $search): array {
        global $DB;

        $where = 'n.contextid <> 0';
        $params = [];
        if ($search !== '') {
            $where .= ' AND (' . $DB->sql_like('c.fullname', ':s1', false, false)
                . ' OR ' . $DB->sql_like('c.shortname', ':s2', false, false)
                . ' OR ' . $DB->sql_like('cc.name', ':s3', false, false) . ')';
            $like = '%' . $DB->sql_like_escape($search) . '%';
            $params += ['s1' => $like, 's2' => $like, 's3' => $like];
        }
        return [$where, $params];
    }

    /**
     * The categories around a context, from the top, for the line under its name.
     *
     * @param \context $context A category or a course.
     * @return string
     */
    private static function path_of(\context $context): string {
        $names = [];
        foreach ($context->get_parent_contexts() as $parent) {
            if ($parent->contextlevel === CONTEXT_COURSECAT) {
                $category = \core_course_category::get($parent->instanceid, IGNORE_MISSING, true);
                if ($category) {
                    array_unshift($names, $category->get_formatted_name());
                }
            }
        }
        return implode(' / ', $names);
    }
}
