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
 * Activity log page for navigation actions.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_extendednav_manage');

$menu = optional_param('menu', 'primary', PARAM_ALPHANUMEXT);
$menuhandler = \local_extendednav\menu_manager::get_menu($menu);
if (!$menuhandler) {
    $menu = 'primary';
    $menuhandler = \local_extendednav\menu_manager::get_menu('primary');
}
$menuname = $menuhandler ? $menuhandler->get_name() : $menu;

$filterform = new \local_extendednav\form\activity_filter_form(
    new \moodle_url('/local/extendednav/activity.php'),
    [
        'menu' => $menu,
        'periodstart' => (time() - 30 * DAYSECS),
        'periodend' => time(),
    ]
);

$submittedfilters = $filterform->get_data();
$datefromparam = isset($_GET['datefrom']) && !is_array($_GET['datefrom'])
    ? optional_param('datefrom', 0, PARAM_INT)
    : 0;
$datetoparam = isset($_GET['dateto']) && !is_array($_GET['dateto'])
    ? optional_param('dateto', 0, PARAM_INT)
    : 0;
$actionparam = isset($_GET['action']) && !is_array($_GET['action'])
    ? optional_param('action', '', PARAM_TEXT)
    : '';

$filters = [
    'datefrom' => $submittedfilters ? (int)$submittedfilters->datefrom : $datefromparam,
    'dateto' => $submittedfilters ? (int)$submittedfilters->dateto : $datetoparam,
    'action' => $submittedfilters ? trim($submittedfilters->action) : $actionparam,
];

$url = new \moodle_url('/local/extendednav/activity.php', ['menu' => $menu]);
foreach ($filters as $name => $value) {
    if ($value !== '' && $value !== 0) {
        $url->param($name, $value);
    }
}

$PAGE->set_url($url);
$headingtext = get_string('activity_log', 'local_extendednav') . ' &ndash; ' . $menuname;
$PAGE->set_title(get_string('activity_log', 'local_extendednav') . ' - ' . $menuname);
$PAGE->set_heading($headingtext);

echo $OUTPUT->header();

if ($submittedfilters || array_filter($filters, static fn($value): bool => $value !== '' && $value !== 0)) {
    $filterform->set_data($filters);
}

ob_start();
$filterform->display();
$filterformhtml = ob_get_clean();

$hasfilters = array_filter($filters, static fn($value): bool => $value !== '' && $value !== 0) !== [];
$reseturl = new \moodle_url('/local/extendednav/activity.php', ['menu' => $menu]);
$backurl = new \moodle_url('/local/extendednav/manage.php', ['menu' => $menu]);
$filterbtnclass = $hasfilters ? 'btn-primary' : 'btn-secondary';

echo \html_writer::start_div('d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3');

// Left: Back button.
echo \html_writer::link(
    $backurl,
    $OUTPUT->pix_icon('t/left', '', 'core') . ' ' . get_string('back', 'moodle'),
    ['class' => 'btn btn-secondary']
);

// Right: Filter dropdown matching local_delegateaccount.
echo \html_writer::start_div('dropdown local-extendednav-filters', ['data-region' => 'local-extendednav-filters']);
$filterbtnicon = $OUTPUT->pix_icon('i/filter', get_string('filters', 'local_extendednav'), 'core');
echo \html_writer::tag(
    'button',
    $filterbtnicon . ' ' . get_string('filters', 'local_extendednav') . ' ',
    [
        'class' => 'btn ' . $filterbtnclass . ' dropdown-toggle',
        'type' => 'button',
        'id' => 'extendednav-activity-filters',
        'data-toggle' => 'dropdown',
        'data-bs-toggle' => 'dropdown',
        'data-bs-auto-close' => 'outside',
        'aria-haspopup' => 'true',
        'aria-expanded' => 'false',
    ]
);

echo \html_writer::start_div(
    'dropdown-menu dropdown-menu-right dropdown-menu-end moodle-has-zindex local-extendednav-filter-menu shadow',
    [
        'data-region' => 'local-extendednav-filter-menu',
        'aria-labelledby' => 'extendednav-activity-filters',
    ]
);
echo \html_writer::start_div('local-extendednav-filter-content p-3');
echo $filterformhtml;
if ($hasfilters) {
    echo \html_writer::tag(
        'div',
        \html_writer::link($reseturl, get_string('reset', 'local_extendednav')),
        ['class' => 'pt-2']
    );
}
echo \html_writer::end_div(); // End filter content.
echo \html_writer::end_div(); // End dropdown-menu.
echo \html_writer::end_div(); // End dropdown.
echo \html_writer::end_div(); // End flex toolbar.

$table = new \local_extendednav\table\activity_table($url, $menu, $filters);
$table->out(25, true);

echo $OUTPUT->footer();
