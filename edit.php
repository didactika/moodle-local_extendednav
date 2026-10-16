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
 * Adds or edits a navigation node.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extendednav\local\access_guard;
use local_extendednav\local\node_access;
use local_extendednav\local\node_manager;
use local_extendednav\local\node_repository;
use local_extendednav\menu_manager;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_extendednav_manage');

$id = optional_param('id', 0, PARAM_INT);
$menukey = optional_param('menu', 'primary', PARAM_ALPHANUMEXT);

$node = $id ? $DB->get_record('local_extendednav', ['id' => $id], '*', MUST_EXIST) : null;
if ($node) {
    $menukey = $node->menu;
}
$menu = menu_manager::get_menu($menukey);
if ($menu === null) {
    $menukey = 'primary';
    $menu = menu_manager::get_menu($menukey);
}

$url = new moodle_url('/local/extendednav/edit.php', ['menu' => $menukey]);
if ($id) {
    $url->param('id', $id);
}
$PAGE->set_url($url);
// Find Moodle's items, so the form shows their titles in the current language.
$menu->ensure_synced();
$heading = get_string($id ? 'edit_node' : 'add_node', 'local_extendednav') . ' (' . $menu->get_name() . ')';
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

$manageurl = new moodle_url('/local/extendednav/manage.php', ['menu' => $menukey]);
$form = new \local_extendednav\form\node_form($url, ['menu' => $menukey, 'node' => $node]);

if ($node) {
    $formdata = clone $node;
    $formdata->roles = node_access::split_list($node->roles);
    $formdata->langs = node_access::split_list($node->langs);
    $form->set_data($formdata);
}

if ($form->is_cancelled()) {
    redirect($manageurl);
} else if ($data = $form->get_data()) {
    if ($node && $menu->is_native($node->nodekey)) {
        $data->nodekey = $node->nodekey;
    }
    $record = node_manager::prepare($data, $menukey);

    if ($node) {
        $record->id = $node->id;
        node_manager::update($record);
        if ((string)($record->parentkey ?? '') !== (string)($node->parentkey ?? '')) {
            node_manager::place_after_parent_change($node, (string)($record->parentkey ?? ''));
        }
        // Keep submenu items attached when a parent is renamed.
        if ($record->nodekey !== $node->nodekey) {
            $children = $DB->get_records('local_extendednav', ['menu' => $menukey, 'parentkey' => $node->nodekey]);
            foreach ($children as $child) {
                node_manager::update((object)['id' => $child->id, 'parentkey' => $record->nodekey]);
            }
        }
    } else {
        node_manager::create($record);
    }

    if (
        access_guard::check_fallbacks(node_repository::get_all())
            === access_guard::FALLBACKS_BLOCKED_FOR_SOME
    ) {
        \core\notification::warning(get_string('warn_fallbacks_blocked_for_some', 'local_extendednav'));
    }
    redirect($manageurl, get_string('node_saved', 'local_extendednav'), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo html_writer::div(html_writer::link(
    $manageurl,
    $OUTPUT->pix_icon('t/left', '') . get_string('back_to_nodes', 'local_extendednav'),
    ['class' => 'btn btn-outline-secondary']
), 'mb-3');
$form->display();
echo $OUTPUT->footer();
