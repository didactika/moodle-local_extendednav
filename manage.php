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
use local_extendednav\local\navigation\sample_page;
use local_extendednav\menu\admintree;
use local_extendednav\menu_manager;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_extendednav_manage');

$menukey = optional_param('menu', 'primary', PARAM_ALPHANUMEXT);
$contextid = optional_param('contextid', 0, PARAM_INT);

$menu = menu_manager::get_menu($menukey);
if ($menu === null) {
    $menukey = 'primary';
    $menu = menu_manager::get_menu($menukey);
}
$hasscopes = nodes::supports_scopes($menukey);
$scopename = get_string('applies_whole_site', 'local_extendednav');
if ($contextid !== 0) {
    $context = $hasscopes ? context::instance_by_id($contextid, IGNORE_MISSING) : false;
    if ($context && in_array($context->contextlevel, [CONTEXT_COURSECAT, CONTEXT_COURSE], true)) {
        $scopename = $context->contextlevel === CONTEXT_COURSE
            ? format_string(get_course($context->instanceid)->fullname, true, ['context' => $context])
            : (core_course_category::get($context->instanceid, IGNORE_MISSING, true)?->get_formatted_name()
                ?? $context->get_context_name(false, true));
    } else {
        $contextid = 0;
    }
}

$pageurl = new moodle_url('/local/extendednav/manage.php', ['menu' => $menukey] + ($contextid ? ['contextid' => $contextid] : []));
$PAGE->set_url($pageurl);

// The navigations, in groups: the ones of the whole site, the ones of the courses and the site administration.
$groups = menu_manager::get_origin_groups($menukey, '/local/extendednav/manage.php');
['origins' => $origins, 'originoptions' => $originoptions] = $groups;

$scopeparams = ['menu' => $menukey] + ($contextid ? ['contextid' => $contextid] : []);
// A page that shows the navigation, so its nodes can be found from the empty list.
$sample = sample_page::for_navigation($menukey);
$config = json_encode([
    'menu' => $menukey,
    'contextid' => $contextid,
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
    'hasscopes' => $hasscopes,
    'scopename' => $scopename,
    'canadd' => true,
    'addurl' => (new moodle_url('/local/extendednav/edit.php', $scopeparams))->out(false),
    'importurl' => (new moodle_url('/local/extendednav/import.php', ['menu' => $menukey]))->out(false),
    'exporturl' => (new moodle_url('/local/extendednav/export.php', ['menu' => $menukey]))->out(false),
    'exporteverythingurl' => (new moodle_url('/local/extendednav/export.php', ['menu' => $menukey, 'everything' => 1]))->out(false),
    'searchlabel' => get_string('search_nodes', 'local_extendednav'),
]);
$PAGE->requires->js_call_amd('local_extendednav/manage', 'init', ['[data-region="manage"]']);
echo $OUTPUT->footer();
