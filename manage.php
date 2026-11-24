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
 * Lists, orders, shows, hides and deletes the nodes of a navigation, for the whole site or as an exception for a category or a
 * course. The rows load and change through web services, so the page does not reload.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extendednav\local\listing\nodes;
use local_extendednav\local\navigation\context_scope;
use local_extendednav\local\navigation\sample_page;
use local_extendednav\menu\admintree;
use local_extendednav\menu_manager;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_extendednav_manage');

$menukey = optional_param('menu', 'primary', PARAM_ALPHANUMEXT);
// The navigations of a category or a course are changed on the page of that context.
$contextid = optional_param('contextid', 0, PARAM_INT);
if ($contextid && ($scope = context_scope::from_id($contextid))) {
    redirect($scope->url($menukey));
}

// The kinds of activity are many: Activities lists them, and each opens its own navigation.
if ($menukey === menu_manager::ACTIVITIES) {
    $PAGE->set_url(new moodle_url('/local/extendednav/manage.php', ['menu' => $menukey]));
    $PAGE->navbar->add(get_string('origin_group_activity', 'local_extendednav'), $PAGE->url);
    $groups = menu_manager::get_origin_groups($menukey, '/local/extendednav/manage.php');
    echo $OUTPUT->header();
    echo $OUTPUT->render_from_template('local_extendednav/manage/kinds', [
        'name' => get_string('origin_group_activity', 'local_extendednav'),
        'description' => get_string('manage_nodes_desc', 'local_extendednav'),
        'origins' => $groups['origins'],
        'originoptions' => $groups['originoptions'],
        'rows' => menu_manager::get_kind_rows('/local/extendednav/manage.php'),
    ]);
    $PAGE->requires->js_call_amd('local_extendednav/kinds', 'init', ['[data-region="kinds"]']);
    $PAGE->requires->js_call_amd('local_extendednav/local/select_menu', 'init');
    echo $OUTPUT->footer();
    die();
}

$menu = menu_manager::get_menu($menukey);
if ($menu === null) {
    $menukey = 'primary';
    $menu = menu_manager::get_menu($menukey);
}
$hasscopes = nodes::supports_scopes($menukey);
// The boxes of a profile are found when a profile is built; the first time, the one of the current user is built here.
if ($menu instanceof \local_extendednav\menu\profile && !$menu->get_native_nodes()) {
    \core_user\output\myprofile\manager::build_tree($USER, true);
}

$pageurl = new moodle_url('/local/extendednav/manage.php', ['menu' => $menukey]);
$PAGE->set_url($pageurl);
// The navigation of a kind of activity is reached from Activities, which the breadcrumb of Moodle shows.
if ($menu instanceof \local_extendednav\menu\activity) {
    $PAGE->navbar->add(
        get_string('origin_group_activity', 'local_extendednav'),
        new moodle_url('/local/extendednav/manage.php', ['menu' => menu_manager::ACTIVITIES])
    );
    $PAGE->navbar->add($menu->get_short_name(), $pageurl);
}

// The navigations, in groups: the ones of the whole site, the ones of the courses and the site administration.
$groups = menu_manager::get_origin_groups($menukey, '/local/extendednav/manage.php');
['origins' => $origins, 'originoptions' => $originoptions] = $groups;

$scopeparams = ['menu' => $menukey];
// A page that shows the navigation, so its nodes can be found from the empty list.
$sample = sample_page::for_navigation($menukey);
$config = json_encode([
    'menu' => $menukey,
    'contextid' => 0,
    'tree' => $menukey === admintree::KEY,
    'sampleurl' => $sample ? $sample->out(false) : '',
]);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_extendednav/manage/page', [
    'id' => 'len-nodes',
    'config' => $config,
    'name' => $menu->get_name(),
    'origins' => $origins,
    'originoptions' => $originoptions,
    'enabled' => get_config('local_extendednav', 'enabled_' . $menukey) !== '0',
    'sitewide' => true,
    'hasscopes' => $hasscopes,
    'exceptionsurl' => (new moodle_url('/local/extendednav/exceptions.php', ['menu' => $menukey]))->out(false),
    'canadd' => true,
    // The navigation of a kind of activity can be looked for in the activities of the site.
    'candiscover' => $menu instanceof \local_extendednav\menu\activity,
    'addurl' => (new moodle_url('/local/extendednav/edit.php', $scopeparams))->out(false),
    'importurl' => (new moodle_url('/local/extendednav/import.php', ['menu' => $menukey]))->out(false),
    'exporturl' => (new moodle_url('/local/extendednav/export.php', ['menu' => $menukey]))->out(false),
    'exporteverythingurl' => (new moodle_url('/local/extendednav/export.php', ['menu' => $menukey, 'everything' => 1]))->out(false),
    'exportchooseurl' => (new moodle_url('/local/extendednav/export.php', ['menu' => $menukey, 'choose' => 1]))->out(false),
    'searchlabel' => get_string('search_nodes', 'local_extendednav'),
    // The kinds of restriction the menu Restriction of the list offers.
    'restrictions' => \local_extendednav\local\listing\nodes::restriction_options(),
]);
$PAGE->requires->js_call_amd('local_extendednav/manage', 'init', ['[data-region="manage"]']);
echo $OUTPUT->footer();
