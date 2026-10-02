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
 * Navigation nodes management interface.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

admin_externalpage_setup('local_extendednav_manage');

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);
$parent = optional_param('parent', '', PARAM_ALPHANUMEXT);

$baseparams = [];
if ($search !== '') $baseparams['search'] = $search;
if ($parent !== '') $baseparams['parent'] = $parent;

$baseurl = new moodle_url('/local/extendednav/manage.php', $baseparams);



if ($action === 'delete') {
    require_sesskey();
    $DB->delete_records('local_extendednav', ['id' => $id]);
    try { \cache::make('local_extendednav', 'nodes')->purge(); } catch (\Throwable $e) {
            // Silently ignored.

            // Expected exception
}
    redirect($baseurl);
}

if ($action === 'bulkdelete') {
    require_sesskey();
    $nodeids = optional_param_array('nodeids', [], PARAM_INT);
    if (!empty($nodeids)) {
        list($insql, $inparams) = $DB->get_in_or_equal($nodeids);
        $DB->delete_records_select('local_extendednav', "id $insql", $inparams);
        try { \cache::make('local_extendednav', 'nodes')->purge(); } catch (\Throwable $e) {
            // Silently ignored.

            // Expected exception
}
        redirect($baseurl, get_string('bulk_deleted', 'local_extendednav'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect($baseurl);
    }
}

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(' . $DB->sql_like('nodekey', ':search1', false, false) . ' OR ' . $DB->sql_like('title', ':search2', false, false) . ')';
    $params['search1'] = '%' . $DB->sql_like_escape($search) . '%';
    $params['search2'] = '%' . $DB->sql_like_escape($search) . '%';
}
if ($parent !== '') {
    $where[] = '(parentkey = :parentkey)';
    $params['parentkey'] = $parent;
}

$wheresql = empty($where) ? '' : implode(' AND ', $where);
$isfiltered = (!empty($wheresql));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage_nodes', 'local_extendednav'));
echo html_writer::tag('p', get_string('manage_nodes_desc', 'local_extendednav'), ['class' => 'mb-4']); 

// --- TOP ACTIONS BAR (Matches theme_vle and local_servicemanager) ---
$addurl = new moodle_url('/local/extendednav/edit.php');
$importurl = new moodle_url('/local/extendednav/import.php');
$exportallurl = new moodle_url('/local/extendednav/export.php', ['all' => 1]);

echo html_writer::start_div('d-flex flex-wrap justify-content-end mb-3');
echo '    <div class="btn-toolbar">';
echo html_writer::link($addurl, '<i class="fa fa-plus mr-1"></i>' . get_string('add_node', 'local_extendednav'), ['class' => 'btn btn-primary']);
echo html_writer::link($importurl, '<i class="fa fa-upload mr-1"></i>' . get_string('import', 'local_extendednav'), ['class' => 'btn btn-primary ml-2']);
echo html_writer::link($exportallurl, '<i class="fa fa-file-code-o mr-1"></i>' . get_string('export_all_btn', 'local_extendednav'), ['class' => 'btn btn-outline-secondary ml-2']);
echo '    </div>';
echo html_writer::end_div();

$filterbtnclass = $isfiltered ? 'btn-primary' : 'btn-outline-secondary';

echo html_writer::start_div('reportbuilder-wrapper');
echo html_writer::start_div('d-flex justify-content-end mb-3');
echo html_writer::start_div('dropdown extendednav-filters');
echo html_writer::tag('button', html_writer::tag('i', '', ['class' => 'fa fa-filter mr-1']) . ' ' . get_string('filters', 'local_extendednav') . ' ', ['class' => 'btn ' . $filterbtnclass . ' dropdown-toggle', 'type' => 'button', 'id' => 'extendednav-manage-filters', 'data-toggle' => 'dropdown', 'data-bs-toggle' => 'dropdown', 'data-bs-auto-close' => 'outside', 'aria-haspopup' => 'true', 'aria-expanded' => 'false']);

echo html_writer::start_div('dropdown-menu dropdown-menu-right dropdown-menu-end p-3 shadow', ['aria-labelledby' => 'extendednav-manage-filters', 'style' => 'min-width: 320px;']);
echo html_writer::start_tag('form', ['method' => 'get', 'action' => 'manage.php']);

echo html_writer::start_div('form-group mb-3');
echo html_writer::tag('label', get_string('search_free', 'local_extendednav'), ['for' => 'fsearch']);
echo html_writer::empty_tag('input', ['type' => 'text', 'id' => 'fsearch', 'name' => 'search', 'value' => s($search), 'class' => 'form-control', 'autocomplete' => 'off']);
echo html_writer::end_div();

echo html_writer::start_div('form-group mb-3');
echo html_writer::tag('label', get_string('search_parent', 'local_extendednav'), ['for' => 'fparent']);
echo html_writer::empty_tag('input', ['type' => 'text', 'id' => 'fparent', 'name' => 'parent', 'value' => s($parent), 'class' => 'form-control', 'autocomplete' => 'off']);
echo html_writer::end_div();

echo html_writer::tag('button', get_string('apply', 'local_extendednav'), ['type' => 'submit', 'class' => 'btn btn-primary']);

if ($isfiltered) {
    echo html_writer::tag('div', html_writer::link(new moodle_url('/local/extendednav/manage.php'), get_string('reset', 'local_extendednav')), ['class' => 'pt-3']);
}

echo html_writer::end_tag('form');
echo html_writer::end_div(); // End dropdown-menu
echo html_writer::end_div(); // End dropdown
echo html_writer::end_div(); // End d-flex wrapper
echo html_writer::end_div(); // End reportbuilder-wrapper

// Bulk Actions Bar (Moodle Standard Placement)
echo html_writer::start_div('d-flex justify-content-between align-items-center mb-3');
echo '    <div class="d-flex align-items-center">';
echo '        <div class="bulk-actions d-none" id="bulk-actions-bar">';
echo '            <span class="selected-count font-weight-bold mr-3">';
echo '                <span id="bulk-count">0</span> ' . get_string('nodes_selected', 'local_extendednav');
echo '            </span>';
echo '            <button type="submit" form="bulk-export-form" formaction="export.php" class="btn btn-sm btn-info mr-1">';
echo '                <i class="fa fa-download mr-1"></i>' . get_string('export_selected', 'local_extendednav');
echo '            </button>';
echo '            <button type="submit" form="bulk-export-form" formaction="manage.php" name="action" value="bulkdelete" class="btn btn-sm btn-danger mr-1" onclick="return confirm(\''.addslashes(get_string('bulk_delete_confirm', 'local_extendednav')).'\');">';
echo '                <i class="fa fa-trash mr-1"></i>' . get_string('bulk_delete', 'local_extendednav');
echo '            </button>';

echo '        </div>';
echo '    </div>';
echo html_writer::end_div();


// Bulk actions form start
echo html_writer::start_tag('form', ['id' => 'bulk-export-form', 'method' => 'POST', 'action' => 'export.php']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

$table = new flexible_table('local-extendednav-manage');
$table->define_baseurl($baseurl);
$table->define_columns(['select', 'nodekey', 'title', 'url', 'tree', 'visibility', 'actions']);
$table->define_headers([
    '<input type="checkbox" id="select-all-nodes">',
    get_string('nodekey', 'local_extendednav'),
    get_string('title', 'local_extendednav'),
    get_string('url', 'local_extendednav'),
    get_string('positioning', 'local_extendednav'),
    get_string('visibility', 'local_extendednav'),
    get_string('actions', 'local_extendednav')
]);

$table->column_class('select', 'text-center');
$table->column_style('select', 'width', '40px');
$table->setup();

$nodes = $DB->get_records_select('local_extendednav', $wheresql, $params, 'sortorder ASC, id ASC');
$total = count($nodes);
$i = 0;
foreach ($nodes as $n) {
    if ($n->visibility == 0) $vis = '<span class="badge badge-danger">'.get_string('vis_hidden', 'local_extendednav').'</span>';
    elseif ($n->visibility == 2) $vis = '<span class="badge badge-warning">'.get_string('vis_roles', 'local_extendednav').'</span>';
    else $vis = '<span class="badge badge-success">'.get_string('vis_all', 'local_extendednav').'</span>';

    $treehtml = '';
    if (!empty($n->parentkey)) {
        $treehtml .= html_writer::tag('span', get_string('inside', 'local_extendednav') . ' <b>' . s($n->parentkey) . '</b>', ['class' => 'text-info small']);
        $treehtml .= '<br>';
    }
    if (!empty($n->beforekey)) {
        $treehtml .= html_writer::tag('span', get_string('before', 'local_extendednav') . ' <b>' . s($n->beforekey) . '</b>', ['class' => 'text-muted small']);
    }
    if (empty($treehtml)) {
        $treehtml = '<span class="text-secondary">-</span>';
    }

    $actions = '';
    $editurl = new moodle_url('/local/extendednav/edit.php', ['id' => $n->id]);
    $actions .= html_writer::link($editurl, $OUTPUT->pix_icon('t/edit', get_string('edit')));

    $delurl = new moodle_url('/local/extendednav/manage.php', array_merge(['id' => $n->id, 'action' => 'delete', 'sesskey' => sesskey()], $baseparams));
    $actions .= '&nbsp;' . html_writer::link($delurl, $OUTPUT->pix_icon('t/delete', get_string('delete')),
        ['onclick' => "return confirm('".get_string('delete_node_confirm', 'local_extendednav')."');"]);

    $table->add_data([
        '<input type="checkbox" name="nodeids[]" value="' . $n->id . '" class="node-checkbox">',
        '<b>'.s($n->nodekey).'</b>',
        !empty($n->title) ? format_string($n->title) : '<i class="text-muted">'.get_string('native_string', 'local_extendednav').'</i>',
        !empty($n->url) ? s($n->url) : '<i class="text-muted">'.get_string('native_route', 'local_extendednav').'</i>',
        $treehtml,
        $vis,
        $actions
    ]);
    
    $i++;
}

$table->finish_output();

echo html_writer::end_tag('form');

$PAGE->requires->js_call_amd('local_extendednav/manage', 'init');

echo $OUTPUT->footer();