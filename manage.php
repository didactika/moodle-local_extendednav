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
 * Lists, orders and deletes the navigation nodes of a menu.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extendednav\local\node_manager;
use local_extendednav\local\node_repository;
use local_extendednav\local\node_text;
use local_extendednav\menu_manager;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_extendednav_manage');

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$search = trim(optional_param('search', '', PARAM_TEXT));
$parent = trim(optional_param('parent', '', PARAM_ALPHANUMEXT));
$visibility = optional_param('visibility', '', PARAM_ALPHANUM);
$visibility = in_array($visibility, ['0', '1', '2'], true) ? (int)$visibility : null;
$menukey = optional_param('menu', 'primary', PARAM_ALPHANUMEXT);

$menu = menu_manager::get_menu($menukey);
if ($menu === null) {
    $menukey = 'primary';
    $menu = menu_manager::get_menu($menukey);
}

$params = ['menu' => $menukey];
if ($search !== '') {
    $params['search'] = $search;
}
if ($parent !== '') {
    $params['parent'] = $parent;
}
if ($visibility !== null) {
    $params['visibility'] = $visibility;
}
$baseurl = new moodle_url('/local/extendednav/manage.php', $params);
$PAGE->set_url($baseurl);

$menu->ensure_synced();

if ($action === 'delete' || $action === 'bulkdelete') {
    require_sesskey();
    $ids = $action === 'delete' ? [$id] : optional_param_array('nodeids', [], PARAM_INT);
    $records = empty($ids) ? [] : $DB->get_records_list('local_extendednav', 'id', $ids);
    $records = array_filter($records, static fn(\stdClass $record): bool => $record->menu === $menukey);
    $deleted = node_manager::delete($records);
    if ($deleted < count($records)) {
        \core\notification::warning(get_string('native_cannot_delete', 'local_extendednav'));
    }
    if ($deleted > 0) {
        redirect(
            $baseurl,
            get_string('nodes_deleted', 'local_extendednav', $deleted),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
    redirect($baseurl);
}

$allnodes = node_repository::get_menu_nodes($menukey);
$filtered = $search !== '' || $parent !== '' || $visibility !== null;
$nodes = array_filter($allnodes, static function (\stdClass $node) use ($menu, $search, $parent, $visibility): bool {
    if ($parent !== '' && $node->parentkey !== $parent) {
        return false;
    }
    if ($visibility !== null && (int)$node->visibility !== $visibility) {
        return false;
    }
    if ($search === '') {
        return true;
    }
    $native = $menu->get_native_node($node->nodekey);
    $haystack = implode(' ', [
        $node->nodekey,
        node_text::get_title($node, $native),
        (string)($node->url ?? '') !== '' ? $node->url : ($native->action ?? ''),
    ]);
    return core_text::strpos(core_text::strtolower($haystack), core_text::strtolower($search)) !== false;
});

// Parent filter options: every key used as a parent in this menu, in menu order.
$parents = [];
foreach ($allnodes as $node) {
    if (!empty($node->parentkey) && !isset($parents[$node->parentkey])) {
        $parents[$node->parentkey] = ['value' => $node->parentkey, 'name' => $node->parentkey,
            'selected' => $node->parentkey === $parent];
    }
}
$visibilities = [];
foreach (['vis_all' => 1, 'vis_roles' => 2, 'vis_hidden' => 0] as $string => $value) {
    $visibilities[] = ['value' => $value, 'name' => get_string($string, 'local_extendednav'),
        'selected' => $visibility === $value];
}

$tabs = [];
foreach (menu_manager::get_menus() as $key => $tabmenu) {
    $tabs[] = [
        'name' => $tabmenu->get_name(),
        'url' => (new moodle_url('/local/extendednav/manage.php', ['menu' => $key]))->out(false),
        'active' => $key === $menukey,
    ];
}

echo $OUTPUT->header();
echo html_writer::tag('p', get_string('manage_nodes_desc', 'local_extendednav'), ['class' => 'mb-4']);

echo $OUTPUT->render_from_template('local_extendednav/manage_toolbar', [
    'tabs' => $tabs,
    'addurl' => (new moodle_url('/local/extendednav/edit.php', ['menu' => $menukey]))->out(false),
    'importurl' => (new moodle_url('/local/extendednav/import.php', ['menu' => $menukey]))->out(false),
    'exporturl' => (new moodle_url('/local/extendednav/export.php', ['all' => 1, 'menu' => $menukey]))->out(false),
    'activityurl' => (new moodle_url('/local/extendednav/activity.php', ['menu' => $menukey]))->out(false),
]);

echo $OUTPUT->render_from_template('local_extendednav/manage_filters', [
    'menu' => $menukey,
    'search' => $search,
    'parents' => array_values($parents),
    'visibilities' => $visibilities,
    'clearurl' => (new moodle_url('/local/extendednav/manage.php', ['menu' => $menukey]))->out(false),
]);

$table = new \local_extendednav\table\nodes_table($menu, $baseurl, $filtered);
ob_start();
$table->display(node_manager::group_by_parent($nodes));
$tablehtml = ob_get_clean();

$count = count($nodes);
echo $OUTPUT->render_from_template('local_extendednav/manage_nodes', [
    'sesskey' => sesskey(),
    'menu' => $menukey,
    'exporturl' => (new moodle_url('/local/extendednav/export.php'))->out(false),
    'table' => $tablehtml,
    'count' => $count === 1 ? get_string('nodes_count_one', 'local_extendednav')
        : get_string('nodes_count', 'local_extendednav', $count),
]);

$PAGE->requires->js_call_amd('local_extendednav/manage', 'init', [['menu' => $menukey, 'sortable' => !$filtered]]);
echo $OUTPUT->footer();
