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
 * Adds or edits a navigation node, for the whole site or as an exception for a category or a course.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extendednav\local\access_guard;
use local_extendednav\local\node_access;
use local_extendednav\local\node_display;
use local_extendednav\local\node_list;
use local_extendednav\local\node_manager;
use local_extendednav\local\node_repository;
use local_extendednav\menu_manager;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_extendednav_manage');

$id = optional_param('id', 0, PARAM_INT);
$menukey = optional_param('menu', 'primary', PARAM_ALPHANUMEXT);
$contextid = optional_param('contextid', 0, PARAM_INT);
$nodekey = optional_param('nodekey', '', PARAM_ALPHANUMEXT);

$node = $id ? $DB->get_record('local_extendednav', ['id' => $id], '*', MUST_EXIST) : null;
if ($node) {
    $menukey = $node->menu;
    $contextid = (int)$node->contextid;
}
$menu = menu_manager::get_menu($menukey);
if ($menu === null) {
    $menukey = 'primary';
    $menu = menu_manager::get_menu($menukey);
}
if ($contextid !== 0 && !node_list::supports_scopes($menukey)) {
    $contextid = 0;
}

// A node that a category or a course only inherits is changed with an exception: the form starts from the node it sees, and
// saving it gives the scope a row of its own.
$inherited = null;
if ($node === null && $contextid !== 0 && $nodekey !== '') {
    $inherited = node_list::get_scope_nodes($menukey, $contextid)[$nodekey] ?? null;
}

$params = ['menu' => $menukey] + ($contextid ? ['contextid' => $contextid] : []) + ($id ? ['id' => $id] : [])
    + ($nodekey !== '' && !$id ? ['nodekey' => $nodekey] : []);
$url = new moodle_url('/local/extendednav/edit.php', $params);
$PAGE->set_url($url);
// Find Moodle's items, so the form shows their titles in the current language.
$menu->ensure_synced();
$heading = get_string($id ? 'edit_node' : 'add_node', 'local_extendednav') . ' (' . $menu->get_name() . ')';
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

$manageurl = new moodle_url(
    '/local/extendednav/manage.php',
    ['menu' => $menukey] + ($contextid ? ['contextid' => $contextid] : [])
);
$source = $node ?? $inherited;
$form = new \local_extendednav\form\node_form(
    $url,
    ['menu' => $menukey, 'node' => $source, 'contextid' => $contextid]
);

if ($source) {
    $formdata = clone $source;
    $formdata->roles = node_access::split_list($source->roles);
    $formdata->langs = node_access::split_list($source->langs);
    // Offer Moodle's default size when the node is switched to a pop-up.
    $formdata->popupwidth = $source->popupwidth ?? node_display::DEFAULT_WIDTH;
    $formdata->popupheight = $source->popupheight ?? node_display::DEFAULT_HEIGHT;
    $formdata->contextid = $contextid;
    // The row that is being created has no id yet.
    $formdata->id = $node ? $node->id : 0;
    $form->set_data($formdata);
} else if ($contextid) {
    $form->set_data((object)['contextid' => $contextid]);
}

if ($form->is_cancelled()) {
    redirect($manageurl);
} else if ($data = $form->get_data()) {
    if ($source && $menu->is_native($source->nodekey)) {
        $data->nodekey = $source->nodekey;
    }
    $record = node_manager::prepare($data, $menukey);
    $record->contextid = $contextid;

    if ($node) {
        $record->id = $node->id;
        node_manager::update($record);
        if ($contextid === 0 && (string)($record->parentkey ?? '') !== (string)($node->parentkey ?? '')) {
            node_manager::place_after_parent_change($node, (string)($record->parentkey ?? ''));
        }
        // Keep submenu items attached when a parent is renamed.
        if ($contextid === 0 && $record->nodekey !== $node->nodekey) {
            $children = $DB->get_records(
                'local_extendednav',
                ['menu' => $menukey, 'parentkey' => $node->nodekey, 'contextid' => 0]
            );
            foreach ($children as $child) {
                node_manager::update((object)['id' => $child->id, 'parentkey' => $record->nodekey]);
            }
        }
    } else {
        if ($inherited) {
            $record->sortorder = $inherited->sortorder;
        }
        node_manager::create($record);
    }

    if (
        $contextid === 0 && access_guard::check_fallbacks(node_repository::get_all())
            === access_guard::FALLBACKS_BLOCKED_FOR_SOME
    ) {
        \core\notification::warning(get_string('warn_fallbacks_blocked_for_some', 'local_extendednav'));
    }
    redirect($manageurl, get_string('node_saved', 'local_extendednav'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
