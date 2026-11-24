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
 * The navigations of one category or course: what it changes for itself and for everything inside it, starting from what
 * the site and the categories around it set. Reached from the menu of the course or of the category.
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
$context = $scope->context();
if ($scope->is_course()) {
    require_login($context->instanceid);
} else {
    require_login();
    $PAGE->set_context($context);
    $PAGE->set_category_by_id($context->instanceid);
}
$scope->require_manage();

$menus = $scope->menus();
$menukey = optional_param('menu', reset($menus), PARAM_ALPHANUMEXT);
if (!in_array($menukey, $menus, true)) {
    $menukey = reset($menus);
}
$menu = menu_manager::get_menu($menukey);

$PAGE->set_url($scope->url($menukey));
$PAGE->set_pagelayout('admin');
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
    'scopename' => $scope->name(),
    'canadd' => true,
    'addurl' => (new moodle_url('/local/extendednav/edit.php', ['menu' => $menukey, 'contextid' => $contextid]))->out(false),
    'searchlabel' => get_string('search_nodes', 'local_extendednav'),
]);
$PAGE->requires->js_call_amd('local_extendednav/manage', 'init', ['[data-region="manage"]']);
echo $OUTPUT->footer();
