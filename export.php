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
 * Downloads nodes as a YAML file: one navigation or every navigation of the site, the navigations and contexts chosen, or, from
 * its own page, what one category, course or activity changes.
 *
 * Every file has the same format, so the import page reads any of them: every node names its navigation and its scope, and
 * the file of the site says which navigations are on.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extendednav\local\io\exporter;
use local_extendednav\local\navigation\context_scope;
use local_extendednav\menu_manager;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/filelib.php');

$menukey = optional_param('menu', 'primary', PARAM_ALPHANUMEXT);
$everything = optional_param('everything', 0, PARAM_BOOL);
$choose = optional_param('choose', 0, PARAM_BOOL);
$contextid = optional_param('contextid', 0, PARAM_INT);
$scope = $contextid ? context_scope::from_id($contextid) : null;

/**
 * Sends a file, or goes back to the page with a message when it has no node.
 *
 * @param string|null $content The file.
 * @param string $name The part of its name that says what it holds.
 * @param moodle_url $back The page to go back to.
 */
function local_extendednav_send_export(?string $content, string $name, moodle_url $back): void {
    if ($content === null) {
        redirect($back, get_string('no_nodes_export', 'local_extendednav'), null, \core\output\notification::NOTIFY_ERROR);
    }
    send_file($content, 'extendednav_' . $name . '_' . date('Ymd_His') . '.yaml', 0, 0, true, true, 'application/yaml');
}

// A navigation with thousands of exceptions makes a file of megabytes.
\core_php_time_limit::raise();
raise_memory_limit(MEMORY_EXTRA);

if ($scope !== null) {
    // What one category, course or activity changes, from its own page.
    require_login($scope->course(), false, $scope->cm());
    $scope->require_view();
    local_extendednav_send_export(
        exporter::export_file($scope->menus(), [$contextid]),
        $scope->kind() . $scope->context()->instanceid,
        $scope->url($menukey)
    );
}

admin_externalpage_setup('local_extendednav_manage');
if (menu_manager::get_menu($menukey) === null) {
    $menukey = 'primary';
}
$manageurl = new moodle_url('/local/extendednav/manage.php', ['menu' => $menukey]);

if ($choose) {
    // The navigations and the contexts chosen, with or without the nodes of the site.
    $url = new moodle_url('/local/extendednav/export.php', ['menu' => $menukey, 'choose' => 1]);
    $PAGE->set_url($url);
    $PAGE->set_title(get_string('export_choose', 'local_extendednav'));
    $PAGE->set_heading(get_string('export_choose', 'local_extendednav'));
    $form = new \local_extendednav\form\export_form($url, ['menu' => $menukey]);
    if ($form->is_cancelled()) {
        redirect($manageurl);
    } else if ($data = $form->get_data()) {
        $menukeys = array_values(array_filter((array)$data->menus, static fn($key): bool => menu_manager::get_menu($key) !== null));
        $contexts = array_map('intval', (array)($data->contexts ?? []));
        if (!empty($data->withsite)) {
            $contexts[] = 0;
        }
        local_extendednav_send_export(exporter::export_file($menukeys, $contexts), 'chosen', $url);
    }
    echo $OUTPUT->header();
    echo html_writer::start_div('len');
    $form->display();
    echo html_writer::end_div();
    $PAGE->requires->js_call_amd('local_extendednav/local/select_menu', 'init');
    echo $OUTPUT->footer();
    die();
}

$menukeys = $everything ? array_keys(menu_manager::get_menus()) : [$menukey];
local_extendednav_send_export(exporter::export_file($menukeys), $everything ? 'all' : $menukey, $manageurl);
