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
    if ($scope === null || !$scope->can_manage()) {
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
    if ($scope === null || !$scope->can_manage()) {
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
    if ($scope === null || !$scope->can_manage()) {
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
