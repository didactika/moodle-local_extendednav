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
    /** @var string[] Names already found in this request, keyed by context id. */
    private static array $names = [];

    /** @var int[]|null Context of each category, keyed by its nested name; built once per request. */
    private static ?array $categories = null;

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
        if (!isset(self::$names[$contextid])) {
            self::preload([$contextid]);
        }
        return self::$names[$contextid] ?? '';
    }

    /**
     * Finds the names of many contexts at once: a file of thousands of exceptions costs a few queries, not one per node.
     *
     * @param int[] $contextids Context ids.
     */
    public static function preload(array $contextids): void {
        global $DB;

        $missing = array_values(array_diff(array_unique(array_filter(array_map('intval', $contextids))), array_keys(self::$names)));
        if (empty($missing)) {
            return;
        }
        $contexts = $DB->get_records_list('context', 'id', $missing, '', 'id, contextlevel, instanceid');
        $courseids = [];
        foreach ($contexts as $context) {
            if ((int)$context->contextlevel === CONTEXT_COURSE) {
                $courseids[] = (int)$context->instanceid;
            }
        }
        $courses = $courseids ? $DB->get_records_list('course', 'id', $courseids, '', 'id, shortname') : [];
        foreach ($missing as $contextid) {
            $context = $contexts[$contextid] ?? null;
            $name = '';
            if ($context && (int)$context->contextlevel === CONTEXT_COURSE && isset($courses[$context->instanceid])) {
                $name = 'course:' . $courses[$context->instanceid]->shortname;
            } else if ($context && (int)$context->contextlevel === CONTEXT_COURSECAT) {
                $category = \core_course_category::get((int)$context->instanceid, IGNORE_MISSING, true);
                $name = $category ? 'category:' . $category->get_nested_name(false, ' / ') : '';
            }
            self::$names[$contextid] = $name;
        }
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
        $known = array_search($type . ':' . $name, self::$names, true);
        if ($known !== false) {
            return (int)$known;
        }
        $contextid = null;
        if ($type === 'course') {
            $id = $DB->get_field('course', 'id', ['shortname' => $name]);
            $contextid = $id ? (int)\context_course::instance((int)$id)->id : null;
        } else if ($type === 'category') {
            // The nested names of the categories are worked out once, whatever the number of nodes of the file.
            if (self::$categories === null) {
                self::$categories = [];
                foreach (\core_course_category::get_all(['returnhidden' => true]) as $category) {
                    $nested = $category->get_nested_name(false, ' / ');
                    self::$categories[$nested] = (int)\context_coursecat::instance((int)$category->id)->id;
                }
            }
            $contextid = self::$categories[$name] ?? null;
        }
        if ($contextid !== null) {
            self::$names[$contextid] = $type . ':' . $name;
        }
        return $contextid;
    }

    /**
     * Forgets the names found in this request, after categories or courses change.
     */
    public static function reset(): void {
        self::$names = [];
        self::$categories = null;
    }
}
