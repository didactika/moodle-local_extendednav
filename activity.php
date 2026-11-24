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
 * Report of the changes made to the nodes of a navigation: who created, changed or deleted each one, and when.
 *
 * The rows are loaded by the web service local_extendednav_get_activity, so the search, the filters, the order and the pages
 * change without reloading the page.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extendednav\menu_manager;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_extendednav_activity');

$menukey = optional_param('menu', 'primary', PARAM_ALPHANUMEXT);
$menu = menu_manager::get_menu($menukey);
if ($menu === null) {
    $menukey = 'primary';
    $menu = menu_manager::get_menu($menukey);
}

$PAGE->set_url(new moodle_url('/local/extendednav/activity.php', ['menu' => $menukey]));
$PAGE->set_title(get_string('activity_report', 'local_extendednav') . ' - ' . $menu->get_name());
$PAGE->set_heading(get_string('activity_report', 'local_extendednav'));

$groups = menu_manager::get_origin_groups($menukey, '/local/extendednav/activity.php');

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_extendednav/activity/page', [
    'id' => 'len-activity',
    'config' => json_encode(['menu' => $menukey]),
    'name' => $menu->get_name(),
    'origins' => $groups['origins'],
    'originoptions' => $groups['originoptions'],
]);
$PAGE->requires->js_call_amd('local_extendednav/activity', 'init', ['#len-activity']);
echo $OUTPUT->footer();
