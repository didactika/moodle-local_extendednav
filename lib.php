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

/**
 * The ways into the navigations of a category, a course and an activity, from their own menus.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extendednav\local\navigation\context_scope;

/**
 * Adds Navigation to the menu of a course, for whoever may change the navigations of the course.
 *
 * @param navigation_node $navigation The menu of the course.
 * @param stdClass $course The course.
 * @param context_course $context Its context.
 */
function local_extendednav_extend_navigation_course(navigation_node $navigation, stdClass $course, context_course $context): void {
    $scope = context_scope::from_id((int)$context->id);
    if ($scope === null || !$scope->can_view()) {
        return;
    }
    $navigation->add(
        get_string('context_navigation', 'local_extendednav'),
        $scope->url(),
        navigation_node::TYPE_SETTING,
        null,
        'local_extendednav',
        new pix_icon('i/navigationitem', '')
    );
}

/**
 * Adds Navigation to the menu of a category, for whoever may change the navigations of the category.
 *
 * @param navigation_node $navigation The menu of the category.
 * @param context_coursecat $context Its context.
 */
function local_extendednav_extend_navigation_category_settings(navigation_node $navigation, context_coursecat $context): void {
    $scope = context_scope::from_id((int)$context->id);
    if ($scope === null || !$scope->can_view()) {
        return;
    }
    $navigation->add(
        get_string('context_navigation', 'local_extendednav'),
        $scope->url(),
        navigation_node::TYPE_SETTING,
        null,
        'local_extendednav',
        new pix_icon('i/navigationitem', '')
    );
}

/**
 * Applies the navigation of the preferences to the page Preferences of a user: what the site changes and, on the user's own
 * page, what the user chose for themselves.
 *
 * @param navigation_node $settings The node that holds the groups of the preferences.
 * @param stdClass $user The user whose preferences they are.
 * @param context_user $usercontext Context of that user.
 * @param stdClass $course Course of the page.
 * @param context_course $coursecontext Context of that course.
 */
function local_extendednav_extend_navigation_user_settings(
    navigation_node $settings,
    stdClass $user,
    context_user $usercontext,
    stdClass $course,
    context_course $coursecontext
): void {
    \local_extendednav\local\navigation\preferences_injector::apply($settings, $user);
}

/**
 * Adds Navigation to the menu of an activity, for whoever may change the navigation of the activity.
 *
 * @param settings_navigation $navigation The settings of the page.
 * @param context $context Context of the page.
 */
function local_extendednav_extend_settings_navigation(settings_navigation $navigation, context $context): void {
    if ($context->contextlevel !== CONTEXT_MODULE) {
        return;
    }
    $settings = $navigation->find('modulesettings', navigation_node::TYPE_SETTING);
    $scope = $settings ? context_scope::from_id((int)$context->id) : null;
    if ($scope === null || !$scope->can_view()) {
        return;
    }
    $settings->add(
        get_string('context_navigation', 'local_extendednav'),
        $scope->url(),
        navigation_node::TYPE_SETTING,
        null,
        'local_extendednav',
        new pix_icon('i/navigationitem', '')
    );
}

/**
 * Adds the guided tours of the plugin to the site, once each: a tour that is already there, edited or not, is left as it is.
 *
 * Their texts are language strings, so each person reads them in their own language, and a site can change them in the
 * language customisation like any other string.
 *
 * @return int How many tours were added.
 */
function local_extendednav_import_tours(): int {
    global $DB;

    if (!class_exists(\tool_usertours\manager::class)) {
        return 0;
    }
    // A new site installs the local plugins before the tours of Moodle may exist: the tours are then added by an ad-hoc task.
    if (!$DB->get_manager()->table_exists('tool_usertours_tours')) {
        \core\task\manager::queue_adhoc_task(new \local_extendednav\task\import_tours(), true);
        return 0;
    }
    $imported = 0;
    foreach (glob(__DIR__ . '/tours/*.json') as $file) {
        $json = file_get_contents($file);
        $tour = json_decode($json);
        if (!is_object($tour) || empty($tour->name) || empty($tour->steps)) {
            continue;
        }
        if ($DB->record_exists('tool_usertours_tours', ['name' => $tour->name])) {
            continue;
        }
        \tool_usertours\manager::import_tour_from_json($json);
        $imported++;
    }
    return $imported;
}
