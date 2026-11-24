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
 * The navigations of one category, course or activity: what it changes for itself and for everything inside it, starting
 * from what the site and the contexts around it set. Reached from the menu of the category, the course or the activity.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extendednav\local\navigation\context_scope;
use local_extendednav\local\navigation\sample_page;
use local_extendednav\menu_manager;

require_once(__DIR__ . '/../../config.php');

$contextid = required_param('contextid', PARAM_INT);
$scope = context_scope::from_id($contextid);
if ($scope === null) {
    throw new moodle_exception('err_scope_context', 'local_extendednav');
}
// In the activity, in the course, or in the site with the category as the context of the page.
require_login($scope->course(), false, $scope->cm());
if ($scope->kind() === 'category') {
    $PAGE->set_context($scope->context());
    $PAGE->set_category_by_id($scope->context()->instanceid);
}
$scope->require_manage();

$menus = $scope->menus();
$menukey = optional_param('menu', reset($menus), PARAM_ALPHANUMEXT);
if (!in_array($menukey, $menus, true)) {
    $menukey = reset($menus);
}
$menu = menu_manager::get_menu($menukey);

$PAGE->set_url($scope->url($menukey));
// The page of an activity keeps the layout of the pages of the activity, with its header and its navigation.
$PAGE->set_pagelayout($scope->is_activity() ? 'incourse' : 'admin');
if ($scope->is_activity()) {
    // The description of the activity says nothing about its navigation.
    $PAGE->activityheader->set_attrs(['description' => '', 'hidecompletion' => true]);
}
$PAGE->set_title(get_string('context_navigation', 'local_extendednav') . ' - ' . $scope->name());
$PAGE->set_heading($scope->name());
$PAGE->navbar->add(get_string('context_navigation', 'local_extendednav'), $PAGE->url);

$groups = menu_manager::get_origin_groups($menukey, '/local/extendednav/context.php', ['contextid' => $contextid], $menus);
$sample = sample_page::for_navigation($menukey);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_extendednav/manage/page', [
    'id' => 'len-nodes',
    'config' => json_encode([
        'menu' => $menukey,
        'contextid' => $contextid,
        'tree' => false,
        'sampleurl' => $sample ? $sample->out(false) : '',
    ]),
    'name' => $menu->get_name(),
    'origins' => $groups['origins'],
    'originoptions' => $groups['originoptions'],
    'sitewide' => false,
    // A select, so the list keeps the width of the page beside the index of the course.
    'compact' => true,
    // An activity has one navigation: there is nothing to choose.
    'single' => count($menus) === 1,
    'scopename' => $scope->name(),
    'canadd' => true,
    'addurl' => (new moodle_url('/local/extendednav/edit.php', ['menu' => $menukey, 'contextid' => $contextid]))->out(false),
    'searchlabel' => get_string('search_nodes', 'local_extendednav'),
    // The kinds of restriction the menu Restriction of the list offers.
    'restrictions' => \local_extendednav\local\listing\nodes::restriction_options(),
]);
$PAGE->requires->js_call_amd('local_extendednav/manage', 'init', ['[data-region="manage"]']);
echo $OUTPUT->footer();
