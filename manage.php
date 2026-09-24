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
$search = optional_param('search', '', PARAM_RAW);
$parent = optional_param('parent', '', PARAM_ALPHANUMEXT);

$baseparams = [];
if ($search !== '') $baseparams['search'] = $search;
if ($parent !== '') $baseparams['parent'] = $parent;

$baseurl = new moodle_url('/local/extendednav/manage.php', $baseparams);

if ($action === 'moveup' || $action === 'movedown') {
    require_sesskey();
    if ($node = $DB->get_record('local_extendednav', ['id' => $id])) {
        $nodes = $DB->get_records('local_extendednav', null, 'sortorder ASC, id ASC');
        $ordered = array_values($nodes);
        
        $pos = -1;
        foreach ($ordered as $i => $n) {
            if ($n->id == $id) {
                $pos = $i;
                break;
            }
        }
        
        if ($pos !== -1) {
            if ($action === 'moveup' && $pos > 0) {
                $temp = $ordered[$pos];
                $ordered[$pos] = $ordered[$pos - 1];
                $ordered[$pos - 1] = $temp;
            } else if ($action === 'movedown' && $pos < count($ordered) - 1) {
                $temp = $ordered[$pos];
                $ordered[$pos] = $ordered[$pos + 1];
                $ordered[$pos + 1] = $temp;
            }
            
            foreach ($ordered as $index => $n) {
                $DB->set_field('local_extendednav', 'sortorder', $index, ['id' => $n->id]);
            }
            try { \cache::make('local_extendednav', 'nodes')->purge(); } catch (\Throwable $e) {}
            redirect($baseurl);
        }
    }
}

if ($action === 'delete') {
    require_sesskey();
    $DB->delete_records('local_extendednav', ['id' => $id]);
    try { \cache::make('local_extendednav', 'nodes')->purge(); } catch (\Throwable $e) {}
    redirect($baseurl);
}

if ($action === 'bulkdelete') {
    require_sesskey();
    $nodeids = optional_param_array('nodeids', [], PARAM_INT);
    if (!empty($nodeids)) {
        list($insql, $inparams) = $DB->get_in_or_equal($nodeids);
        $DB->delete_records_select('local_extendednav', "id $insql", $inparams);
        try { \cache::make('local_extendednav', 'nodes')->purge(); } catch (\Throwable $e) {}
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
$is_filtered = (!empty($wheresql));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage_nodes', 'local_extendednav'));
echo html_writer::tag('p', get_string('manage_nodes_desc', 'local_extendednav'), ['class' => 'mb-4']); 

// --- TOP ACTIONS BAR (Matches theme_vle and local_servicemanager) ---
$addurl = new moodle_url('/local/extendednav/edit.php');
$importurl = new moodle_url('/local/extendednav/import.php');
$exportallurl = new moodle_url('/local/extendednav/export.php', ['all' => 1]);

echo '<div class="d-flex flex-wrap justify-content-end mb-3">';
echo '    <div class="btn-toolbar">';
echo html_writer::link($addurl, '<i class="fa fa-plus mr-1"></i>' . get_string('add_node', 'local_extendednav'), ['class' => 'btn btn-primary']);
echo html_writer::link($importurl, '<i class="fa fa-upload mr-1"></i>' . get_string('import', 'local_extendednav'), ['class' => 'btn btn-primary ml-2']);
echo html_writer::link($exportallurl, '<i class="fa fa-file-code-o mr-1"></i>' . get_string('export_all_btn', 'local_extendednav'), ['class' => 'btn btn-outline-secondary ml-2']);
echo '    </div>';
echo '</div>';

$filterbtnclass = $is_filtered ? 'btn-primary' : 'btn-outline-secondary';

echo '<div class="reportbuilder-wrapper">';
echo '<div class="d-flex justify-content-end mb-3">';
echo '<div class="dropdown extendednav-filters">';
echo '<button class="btn ' . $filterbtnclass . ' dropdown-toggle" type="button" id="extendednav-manage-filters" data-toggle="dropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">';
echo '<i class="fa fa-filter mr-1"></i> ' . get_string('filters', 'local_extendednav') . ' ';
echo '</button>';

echo '<div class="dropdown-menu dropdown-menu-right dropdown-menu-end p-3 shadow" aria-labelledby="extendednav-manage-filters" style="min-width: 320px;">';
echo '<form method="get" action="manage.php">';

echo '<div class="form-group mb-3">';
echo '<label for="fsearch">'.get_string('search_free', 'local_extendednav').'</label>';
echo '<input type="text" id="fsearch" name="search" value="'.s($search).'" class="form-control" autocomplete="off">';
echo '</div>';

echo '<div class="form-group mb-3">';
echo '<label for="fparent">'.get_string('search_parent', 'local_extendednav').'</label>';
echo '<input type="text" id="fparent" name="parent" value="'.s($parent).'" class="form-control" autocomplete="off">';
echo '</div>';

echo '<button type="submit" class="btn btn-primary">'.get_string('apply', 'local_extendednav').'</button>';

if ($is_filtered) {
    echo html_writer::tag('div', html_writer::link(new moodle_url('/local/extendednav/manage.php'), get_string('reset', 'local_extendednav')), ['class' => 'pt-3']);
}

echo '</form>';
echo '</div>'; // End dropdown-menu
echo '</div>'; // End dropdown
echo '</div>'; // End d-flex wrapper
echo '</div>'; // End reportbuilder-wrapper

// Bulk Actions Bar (Moodle Standard Placement)
echo '<div class="d-flex justify-content-between align-items-center mb-3">';
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
echo '</div>';


// Bulk actions form start
echo '<form id="bulk-export-form" method="POST" action="export.php">
<input type="hidden" name="sesskey" value="'.sesskey().'">';

$table = new flexible_table('local-extendednav-manage');
$table->define_baseurl($baseurl);
$table->define_columns(['select', 'nodekey', 'title', 'url', 'tree', 'visibility', 'order', 'actions']);
$table->define_headers([
    '<input type="checkbox" id="select-all-nodes">',
    get_string('nodekey', 'local_extendednav'),
    get_string('title', 'local_extendednav'),
    get_string('url', 'local_extendednav'),
    get_string('positioning', 'local_extendednav'),
    get_string('visibility', 'local_extendednav'),
    get_string('order', 'local_extendednav'),
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

    $tree_html = '';
    if (!empty($n->parentkey)) {
        $tree_html .= html_writer::tag('span', get_string('inside', 'local_extendednav') . ' <b>' . s($n->parentkey) . '</b>', ['class' => 'text-info small']);
        $tree_html .= '<br>';
    }
    if (!empty($n->beforekey)) {
        $tree_html .= html_writer::tag('span', get_string('before', 'local_extendednav') . ' <b>' . s($n->beforekey) . '</b>', ['class' => 'text-muted small']);
    }
    if (empty($tree_html)) {
        $tree_html = '<span class="text-secondary">-</span>';
    }

    $actions = '';
    $editurl = new moodle_url('/local/extendednav/edit.php', ['id' => $n->id]);
    $actions .= html_writer::link($editurl, $OUTPUT->pix_icon('t/edit', get_string('edit')));

    $delurl = new moodle_url('/local/extendednav/manage.php', array_merge(['id' => $n->id, 'action' => 'delete', 'sesskey' => sesskey()], $baseparams));
    $actions .= '&nbsp;' . html_writer::link($delurl, $OUTPUT->pix_icon('t/delete', get_string('delete')),
        ['onclick' => "return confirm('".get_string('delete_node_confirm', 'local_extendednav')."');"]);

    $order = '';
    if ($is_filtered) {
        $order = html_writer::tag('span', get_string('filter_active', 'local_extendednav'), ['class' => 'text-secondary small font-italic']);
    } else {
        if ($i > 0) {
            $upurl = new moodle_url('/local/extendednav/manage.php', ['id' => $n->id, 'action' => 'moveup', 'sesskey' => sesskey()]);
            $order .= html_writer::link($upurl, $OUTPUT->pix_icon('t/up', get_string('moveup')));
        }
        if ($i < $total - 1) {
            $downurl = new moodle_url('/local/extendednav/manage.php', ['id' => $n->id, 'action' => 'movedown', 'sesskey' => sesskey()]);
            $order .= html_writer::link($downurl, $OUTPUT->pix_icon('t/down', get_string('movedown')));
        }
    }

    $table->add_data([
        '<input type="checkbox" name="nodeids[]" value="' . $n->id . '" class="node-checkbox">',
        '<b>'.s($n->nodekey).'</b>',
        !empty($n->title) ? format_string($n->title) : '<i class="text-muted">'.get_string('native_string', 'local_extendednav').'</i>',
        !empty($n->url) ? s($n->url) : '<i class="text-muted">'.get_string('native_route', 'local_extendednav').'</i>',
        $tree_html,
        $vis,
        $order,
        $actions
    ]);
    
    $i++;
}

$table->finish_output();

echo '</form>';

$js = "
    var selectAll = document.getElementById('select-all-nodes');
    var checkboxes = document.querySelectorAll('.node-checkbox');
    var bulkBar = document.getElementById('bulk-actions-bar');
    var bulkCount = document.getElementById('bulk-count');

    function updateBulkBar() {
        var count = 0;
        var allChecked = true;

        for (var i = 0; i < checkboxes.length; i++) {
            if (checkboxes[i].checked) {
                count++;
            } else {
                allChecked = false;
            }
        }

        if (selectAll && checkboxes.length > 0) {
            selectAll.checked = allChecked;
        }

        if (count > 0) {
            bulkCount.textContent = count;
            bulkBar.classList.remove('d-none');
        } else {
            bulkBar.classList.add('d-none');
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            var isChecked = this.checked;
            for (var i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = isChecked;
            }
            updateBulkBar();
        });
    }

    for (var i = 0; i < checkboxes.length; i++) {
        checkboxes[i].addEventListener('change', updateBulkBar);
    }
";
$PAGE->requires->js_amd_inline($js);

echo $OUTPUT->footer();