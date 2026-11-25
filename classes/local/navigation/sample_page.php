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

namespace local_extendednav\local\navigation;

/**
 * A page of the site that shows a secondary navigation, so its nodes can be found while the list of that navigation is empty:
 * the nodes of Moodle are recorded the first time a page shows them.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sample_page {
    /**
     * Returns a page that shows a navigation, or null when the site has none yet (no course, no activity).
     *
     * @param string $menukey Key of the navigation.
     * @return \moodle_url|null
     */
    public static function for_navigation(string $menukey): ?\moodle_url {
        global $DB;

        switch ($menukey) {
            case 'frontpage':
                return new \moodle_url('/', ['redirect' => 0]);
            case 'course':
                $courseid = $DB->get_field_sql('SELECT MIN(id) FROM {course} WHERE id <> :site', ['site' => SITEID]);
                return $courseid ? new \moodle_url('/course/view.php', ['id' => $courseid]) : null;
            case 'category':
                $categoryid = $DB->get_field_sql('SELECT MIN(id) FROM {course_categories}');
                return $categoryid ? new \moodle_url('/course/index.php', ['categoryid' => $categoryid]) : null;
            default:
                $menu = \local_extendednav\menu\activity::from_key($menukey);
                if ($menu === null) {
                    return null;
                }
                // An activity of that kind in a course, the first one created.
                $cmid = $DB->get_field_sql(
                    'SELECT MIN(cm.id)
                       FROM {course_modules} cm
                       JOIN {modules} m ON m.id = cm.module
                      WHERE m.name = :modname AND cm.course <> :site AND cm.deletioninprogress = 0',
                    ['modname' => $menu->get_modname(), 'site' => SITEID]
                );
                return $cmid ? new \moodle_url('/mod/' . $menu->get_modname() . '/view.php', ['id' => $cmid]) : null;
        }
    }
}
