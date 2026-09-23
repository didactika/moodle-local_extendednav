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

// --- TOP ACTIONS BAR (Moodle 4 Report Builder Style) ---
$addurl = new moodle_url('/local/extendednav/edit.php');

$filterbtnclass = $is_filtered ? 'btn-primary' : 'btn-primary';

echo '<div class="reportbuilder-wrapper">';
echo '<div class="d-flex flex-wrap justify-content-end mb-3">';

echo html_writer::link($addurl, get_string('add_node', 'local_extendednav'), ['class' => 'btn btn-primary mr-2']);

echo '<div class="dropdown extendednav-filters">';
echo '<button class="btn ' . $filterbtnclass . ' dropdown-toggle" type="button" id="extendednav-manage-filters" data-toggle="dropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false">';
echo $OUTPUT->pix_icon('i/filter', '') .  ' ' . get_string('filters', 'local_extendednav') . ' ';
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


$table = new flexible_table('local-extendednav-manage');
$table->define_baseurl($baseurl);
$table->define_columns(['nodekey', 'title', 'url', 'tree', 'visibility', 'order', 'actions']);
$table->define_headers([
    get_string('nodekey', 'local_extendednav'),
    get_string('title', 'local_extendednav'),
    get_string('url', 'local_extendednav'),
    get_string('positioning', 'local_extendednav'),
    get_string('visibility', 'local_extendednav'),
    get_string('order', 'local_extendednav'),
    get_string('actions', 'local_extendednav')
]);

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
echo $OUTPUT->footer();