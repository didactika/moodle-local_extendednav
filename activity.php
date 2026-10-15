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
 * Report of the changes made to the navigation nodes of a menu.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extendednav\menu_manager;
use local_extendednav\table\activity_table;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_extendednav_manage');

$menukey = optional_param('menu', 'primary', PARAM_ALPHANUMEXT);
$menu = menu_manager::get_menu($menukey);
if ($menu === null) {
    $menukey = 'primary';
    $menu = menu_manager::get_menu($menukey);
}

// Reads a date filter typed as YYYY-MM-DD: returns the date as typed (or empty) and its midnight
// timestamp in the user's timezone (or 0).
$readdate = static function (string $name): array {
    $value = optional_param($name, '', PARAM_ALPHANUMEXT);
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts) || !checkdate((int)$parts[2], (int)$parts[3], (int)$parts[1])) {
        return ['', 0];
    }
    return [$value, make_timestamp((int)$parts[1], (int)$parts[2], (int)$parts[3])];
};
[$datefrom, $timefrom] = $readdate('datefrom');
[$dateto, $timeto] = $readdate('dateto');
if ($timefrom && $timeto && $timeto < $timefrom) {
    \core\notification::error(get_string('filter_invalidperiod', 'local_extendednav'));
    [$dateto, $timeto] = ['', 0];
}
$action = optional_param('action', '', PARAM_ALPHA);
if (!in_array($action, activity_table::ACTIONS, true)) {
    $action = '';
}

$url = new moodle_url('/local/extendednav/activity.php', ['menu' => $menukey]);
foreach (['datefrom' => $datefrom, 'dateto' => $dateto, 'action' => $action] as $name => $value) {
    if ($value !== '') {
        $url->param($name, $value);
    }
}
$PAGE->set_url($url);
$PAGE->set_title(get_string('activity_log', 'local_extendednav') . ' - ' . $menu->get_name());
$PAGE->set_heading(get_string('activity_log', 'local_extendednav') . ' - ' . $menu->get_name());

$tabs = [];
foreach (menu_manager::get_menus() as $key => $tabmenu) {
    $tabs[] = [
        'name' => $tabmenu->get_name(),
        'url' => (new moodle_url('/local/extendednav/activity.php', ['menu' => $key]))->out(false),
        'active' => $key === $menukey,
    ];
}
$actions = [];
foreach (activity_table::ACTIONS as $value) {
    $actions[] = ['value' => $value, 'name' => get_string('action_' . $value, 'local_extendednav'),
        'selected' => $value === $action];
}

$table = new activity_table($url, $menukey, ['datefrom' => $timefrom, 'dateto' => $timeto, 'action' => $action]);
ob_start();
$table->out(25, true);
$tablehtml = ob_get_clean();

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_extendednav/activity_toolbar', [
    'tabs' => $tabs,
    'backurl' => (new moodle_url('/local/extendednav/manage.php', ['menu' => $menukey]))->out(false),
]);
echo $OUTPUT->render_from_template('local_extendednav/activity_filters', [
    'menu' => $menukey,
    'datefrom' => $datefrom,
    'dateto' => $dateto,
    'actions' => $actions,
    'clearurl' => (new moodle_url('/local/extendednav/activity.php', ['menu' => $menukey]))->out(false),
]);
echo $OUTPUT->render_from_template('local_extendednav/activity_log', [
    'table' => $tablehtml,
    'count' => (int)$table->totalrows === 1 ? get_string('entries_count_one', 'local_extendednav')
        : get_string('entries_count', 'local_extendednav', (int)$table->totalrows),
    'pagingbar' => $table->get_paging_bar(),
]);
echo $OUTPUT->footer();
