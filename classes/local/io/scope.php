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

/**
 * The name of a category or a course in a file, and the context it stands for: course:shortname, or category:Parent / Name,
 * and empty for the whole site.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class scope {
    /**
     * Describes the category or the course a node applies to, as the file names it.
     *
     * @param int $contextid Context id, 0 for the whole site.
     * @return string Empty for the whole site, "course:shortname" or "category:Parent / Name".
     */
    public static function describe(int $contextid): string {
        if ($contextid === 0) {
            return '';
        }
        $context = \context::instance_by_id($contextid, IGNORE_MISSING);
        if (!$context) {
            return '';
        }
        if ($context->contextlevel === CONTEXT_COURSE) {
            return 'course:' . get_course($context->instanceid)->shortname;
        }
        $category = \core_course_category::get($context->instanceid, IGNORE_MISSING, true);
        return $category ? 'category:' . $category->get_nested_name(false, ' / ') : '';
    }

    /**
     * Finds the category or the course a file names.
     *
     * @param string $scope As {@see describe()} writes it.
     * @return int|null The context id, or null when nothing matches.
     */
    public static function resolve(string $scope): ?int {
        global $DB;

        [$type, $name] = array_pad(explode(':', trim($scope), 2), 2, '');
        $name = trim($name);
        if ($type === 'course') {
            $id = $DB->get_field('course', 'id', ['shortname' => $name]);
            return $id ? (int)\context_course::instance((int)$id)->id : null;
        }
        if ($type === 'category') {
            foreach (\core_course_category::get_all(['returnhidden' => true]) as $category) {
                if ($category->get_nested_name(false, ' / ') === $name) {
                    return (int)\context_coursecat::instance((int)$category->id)->id;
                }
            }
        }
        return null;
    }
}
