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

// Sync core nodes into the DB if they are missing.
\local_extendednav\hooks::$skiphook = true;
$temppage = new \moodle_page();
$temppage->set_context(\context_system::instance());
$temppage->set_url(new \moodle_url('/'));
$primary = new \core\navigation\views\primary($temppage);
$primary->initialise();
\local_extendednav\hooks::$skiphook = false;

$dbnodes = $DB->get_records('local_extendednav');
$dbnodesbykey = [];
$maxsort = 0;
foreach ($dbnodes as $n) {
    $dbnodesbykey[$n->nodekey] = $n;
    if ($n->sortorder > $maxsort) {
        $maxsort = $n->sortorder;
    }
}

foreach ($primary->children as $cnode) {
    if ($cnode->key && !isset($dbnodesbykey[$cnode->key])) {
        $maxsort++;
        $rec = new stdClass();
        $rec->nodekey = $cnode->key;
        $rec->visibility = 1;
        $rec->sortorder = $maxsort;
        $rec->newwindow = 0;
        $DB->insert_record('local_extendednav', $rec);
    }
}


$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);
$parent = optional_param('parent', '', PARAM_ALPHANUMEXT);

$baseparams = [];
if ($search !== '') {
    $baseparams['search'] = $search;
}
if ($parent !== '') {
    $baseparams['parent'] = $parent;
}

$baseurl = new moodle_url('/local/extendednav/manage.php', $baseparams);



if ($action === 'moveup' || $action === 'movedown') {
    require_sesskey();
    $current = $DB->get_record('local_extendednav', ['id' => $id]);
    if ($current) {
        $operator = $action === 'moveup' ? '>' : '<';
        $sortdir = $action === 'moveup' ? 'ASC' : 'DESC';
        $adjacents = $DB->get_records_select(
            'local_extendednav',
            "sortorder $operator ? OR (sortorder = ? AND id $operator ?)",
            [$current->sortorder, $current->sortorder, $current->id],
            "sortorder $sortdir, id $sortdir",
            '*',
            0,
            1
        );
        if ($adjacents) {
            $adjacent = reset($adjacents);
            $tempsort = $current->sortorder;
            $current->sortorder = $adjacent->sortorder;
            $adjacent->sortorder = $tempsort;
            // Also swap IDs slightly if sortorders were the same.
            if ($current->sortorder === $adjacent->sortorder) {
                if ($action === 'moveup') {
                    $current->sortorder++;
                } else {
                    $current->sortorder--;
                }
            }
            $DB->update_record('local_extendednav', $current);
            $DB->update_record('local_extendednav', $adjacent);
            try {
                \cache::make('local_extendednav', 'nodes')->purge();
            } catch (\Throwable $e) {
                unset($e);
                // Expected exception.
            }
        }
    }
    redirect($baseurl);
}

if ($action === 'delete') {
    require_sesskey();
    $DB->delete_records('local_extendednav', ['id' => $id]);
    try {
        \cache::make('local_extendednav', 'nodes')->purge();
    } catch (\Throwable $e) {
            unset($e); // Phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
            // Silently ignored.

            // Expected exception.
    }
    redirect($baseurl);
}

if ($action === 'bulkdelete') {
    require_sesskey();
    $nodeids = optional_param_array('nodeids', [], PARAM_INT);
    if (!empty($nodeids)) {
        $corekeys = [];
        if (isset($primary)) {
            foreach ($primary->children as $child) {
                if ($child->key) {
                    $corekeys[$child->key] = true;
                }
            }
        }

        $todelete = [];
        $skipped = false;
        foreach ($nodeids as $nid) {
            $rec = $DB->get_record('local_extendednav', ['id' => $nid]);
            if ($rec) {
                if (isset($corekeys[$rec->nodekey])) {
                    $skipped = true;
                } else {
                    $todelete[] = $nid;
                }
            }
        }

        if (!empty($todelete)) {
            [$insql, $inparams] = $DB->get_in_or_equal($todelete);
            $DB->delete_records_select('local_extendednav', "id $insql", $inparams);
            try {
                \cache::make('local_extendednav', 'nodes')->purge();
            } catch (\Throwable $e) {
                unset($e);
            }
        }

        if ($skipped) {
            \core\notification::warning(get_string('native_cannot_delete', 'local_extendednav'));
        }

        if (!empty($todelete)) {
            redirect($baseurl, get_string('bulk_deleted', 'local_extendednav'), null, \core\output\notification::NOTIFY_SUCCESS);
        } else {
            redirect($baseurl);
        }
    } else {
        redirect($baseurl);
    }
}

$where = [];
$params = [];
if ($search !== '') {
    $where[] = '(' . $DB->sql_like('nodekey', ':search1', false, false) .
    ' OR ' . $DB->sql_like('title', ':search2', false, false) . ')';
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

// Top actions bar (matches theme_vle and local_servicemanager).
$addurl = new moodle_url('/local/extendednav/edit.php');
$importurl = new moodle_url('/local/extendednav/import.php');
$exportallurl = new moodle_url('/local/extendednav/export.php', ['all' => 1]);

echo html_writer::start_div('d-flex flex-wrap justify-content-end mb-3');
echo '    <div class="btn-toolbar">';
echo html_writer::link(
    $addurl,
    '<i class="fa fa-plus mr-1"></i>' . get_string('add_node', 'local_extendednav'),
    ['class' => 'btn btn-primary']
);
echo html_writer::link(
    $importurl,
    '<i class="fa fa-upload mr-1"></i>' . get_string('import', 'local_extendednav'),
    ['class' => 'btn btn-primary ml-2']
);
echo html_writer::link(
    $exportallurl,
    '<i class="fa fa-file-code-o mr-1"></i>' . get_string('export_all_btn', 'local_extendednav'),
    ['class' => 'btn btn-outline-secondary ml-2']
);
echo '    </div>';
echo html_writer::end_div();

$filterbtnclass = $isfiltered ? 'btn-primary' : 'btn-outline-secondary';

echo html_writer::start_div('reportbuilder-wrapper');
echo html_writer::start_div('d-flex justify-content-between align-items-center mb-3');

// Bulk Actions Bar (Moodle Standard Placement).
echo '    <div class="d-flex align-items-center" style="min-height: 38px;">';
echo '        <div class="bulk-actions d-none" id="bulk-actions-bar">';
echo '            <span class="selected-count font-weight-bold mr-3">';
echo '                <span id="bulk-count">0</span> ' . get_string('nodes_selected', 'local_extendednav');
echo '            </span>';
echo '            <button type="submit" form="bulk-export-form" formaction="export.php" class="btn btn-sm btn-info mr-1">';
echo '                <i class="fa fa-download mr-1"></i>' . get_string('export_selected', 'local_extendednav');
echo '            </button>';
echo "            <button type=\"submit\" form=\"bulk-export-form\" formaction=\"manage.php\" " .
    "name=\"action\" value=\"bulkdelete\" class=\"btn btn-sm btn-danger mr-1\" " .
    "onclick=\"return confirm('" . addslashes(get_string('bulk_delete_confirm', 'local_extendednav')) . "');\">";
echo '                <i class="fa fa-trash mr-1"></i>' . get_string('bulk_delete', 'local_extendednav');
echo '            </button>';
echo '        </div>';
echo '    </div>';

echo html_writer::start_div('dropdown extendednav-filters');
echo html_writer::tag(
    'button',
    html_writer::tag('i', '', ['class' => 'fa fa-filter mr-1']) . ' ' . get_string('filters', 'local_extendednav') . ' ',
    [
        'class' => 'btn ' . $filterbtnclass . ' dropdown-toggle',
        'type' => 'button',
        'id' => 'extendednav-manage-filters',
        'data-toggle' => 'dropdown',
        'data-bs-toggle' => 'dropdown',
        'data-bs-auto-close' => 'outside',
        'aria-haspopup' => 'true',
        'aria-expanded' => 'false',
    ]
);

echo html_writer::start_div(
    'dropdown-menu dropdown-menu-right dropdown-menu-end p-3 shadow',
    ['aria-labelledby' => 'extendednav-manage-filters', 'style' => 'min-width: 320px;']
);
echo html_writer::start_tag('form', ['method' => 'get', 'action' => 'manage.php']);

echo html_writer::start_div('form-group mb-3');
echo html_writer::tag('label', get_string('search_free', 'local_extendednav'), ['for' => 'fsearch']);
echo html_writer::empty_tag(
    'input',
    [
        'type' => 'text',
        'id' => 'fsearch',
        'name' => 'search',
        'value' => s($search),
        'class' => 'form-control',
        'autocomplete' => 'off',
    ]
);
echo html_writer::end_div();

echo html_writer::start_div('form-group mb-3');
echo html_writer::tag('label', get_string('search_parent', 'local_extendednav'), ['for' => 'fparent']);
echo html_writer::empty_tag(
    'input',
    [
        'type' => 'text',
        'id' => 'fparent',
        'name' => 'parent',
        'value' => s($parent),
        'class' => 'form-control',
        'autocomplete' => 'off',
    ]
);
echo html_writer::end_div();

echo html_writer::tag('button', get_string('apply', 'local_extendednav'), ['type' => 'submit', 'class' => 'btn btn-primary']);

if ($isfiltered) {
    echo html_writer::tag(
        'div',
        html_writer::link(new moodle_url('/local/extendednav/manage.php'), get_string('reset', 'local_extendednav')),
        ['class' => 'pt-3']
    );
}

echo html_writer::end_tag('form');
echo html_writer::end_div(); // End dropdown.-menu.
echo html_writer::end_div(); // End dropdown.
echo html_writer::end_div(); // End d-flex wrapper.
echo html_writer::end_div(); // End reportbuilder-wrapper.

// Bulk actions form start.
echo html_writer::start_tag('form', ['id' => 'bulk-export-form', 'method' => 'POST', 'action' => 'export.php']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

$table = new flexible_table('local-extendednav-manage');
$table->define_baseurl($baseurl);
$table->define_columns(['select', 'title', 'visibility', 'icon', 'nodekey', 'url', 'positioning']);
$table->define_headers([
    '<input type="checkbox" id="select-all-nodes">',
    get_string('title', 'local_extendednav'),
    '',
    get_string('icon', 'local_extendednav'),
    get_string('nodekey', 'local_extendednav'),
    get_string('url', 'local_extendednav'),
    get_string('positioning', 'local_extendednav'),
]);

$table->column_class('select', 'text-center');
$table->column_style('select', 'width', '40px');
$table->setup();

$nodes = $DB->get_records_select('local_extendednav', $wheresql, $params, 'sortorder DESC, id DESC');
$total = count($nodes);
$i = 0;

$primarynodes = [];
if (isset($primary)) {
    foreach ($primary->children as $child) {
        $primarynodes[$child->key] = $child;
    }
}

foreach ($nodes as $n) {
    $corenode = $primarynodes[$n->nodekey] ?? null;
    if ($n->visibility == 0) {
        $vis = '<span class="badge badge-danger">' . get_string('vis_hidden', 'local_extendednav') . '</span>';
    } else if ($n->visibility == 2) {
        $vis = '<span class="badge badge-warning">' . get_string('vis_roles', 'local_extendednav') . '</span>';
    } else {
        $vis = '<span class="badge badge-success">' . get_string('vis_all', 'local_extendednav') . '</span>';
    }

    $iconhtml = '';
    if (!empty($n->icon) && $n->icon !== 'none') {
        if (strpos($n->icon, 'fa-') !== false || strpos($n->icon, 'fa ') !== false) {
            $iconhtml = '<i class="icon fa ' . s($n->icon) . ' fa-fw" aria-hidden="true"></i>';
        } else {
            $iconhtml = $OUTPUT->pix_icon($n->icon, '');
        }
    } else {
        $iconhtml = '<span class="text-secondary">-</span>';
    }

    $actions = '<div class="d-flex align-items-center justify-content-center">';

    // Up arrow.
    if ($i > 0 && empty($isfiltered)) {
        $upurl = new moodle_url(
            '/local/extendednav/manage.php',
            array_merge(['id' => $n->id, 'action' => 'moveup', 'sesskey' => sesskey()], $baseparams)
        );
        $actions .= html_writer::link($upurl, $OUTPUT->pix_icon('t/up', get_string('moveup')));
    } else {
        $actions .= html_writer::tag('span', $OUTPUT->pix_icon('t/up', '', 'moodle', ['class' => 'invisible']));
    }

    // Down arrow.
    if ($i < $total - 1 && empty($isfiltered)) {
        $downurl = new moodle_url(
            '/local/extendednav/manage.php',
            array_merge(['id' => $n->id, 'action' => 'movedown', 'sesskey' => sesskey()], $baseparams)
        );
        $actions .= '&nbsp;' . html_writer::link($downurl, $OUTPUT->pix_icon('t/down', get_string('movedown')));
    } else {
        $actions .= '&nbsp;' . html_writer::tag('span', $OUTPUT->pix_icon('t/down', '', 'moodle', ['class' => 'invisible']));
    }

    $editurl = new moodle_url('/local/extendednav/edit.php', ['id' => $n->id]);

    $actions .= '</div>';

    if (!empty($n->title)) {
        $titletext = format_string($n->title);
    } else if ($corenode) {
        $titletext = format_string(strip_tags((string)$corenode->text));
    } else {
        $titletext = '<span class="font-italic text-muted">-</span>';
    }

    $titlehtml = html_writer::link($editurl, $titletext, ['class' => 'font-weight-bold']);

    if ($corenode) {
        $titlehtml .= ' ' . html_writer::tag('i', '', [
            'class' => 'fa fa-info-circle text-warning',
            'title' => get_string('nativenodeinfo', 'local_extendednav'),
            'data-toggle' => 'tooltip',
            'data-placement' => 'top',
        ]);
    }

    $checkbox = '<input type="checkbox" name="nodeids[]" value="' . $n->id . '" class="node-checkbox">';

    if (!empty($n->url)) {
        $urltext = s($n->url);
    } else if ($corenode && $corenode->action) {
        if ($corenode->action instanceof \moodle_url) {
            global $CFG;
            $urltext = s(str_replace($CFG->wwwroot, '', $corenode->action->out(false)));
        } else {
            $urltext = s((string)$corenode->action);
        }
    } else {
        $urltext = '<span class="font-italic text-muted">-</span>';
    }

    $table->add_data([
        $checkbox,
        $titlehtml,
        $vis,
        $iconhtml,
        '<b>' . s($n->nodekey) . '</b>',
        $urltext,
        $actions,
    ]);

    $i++;
}

$table->finish_output();

echo html_writer::end_tag('form');

$PAGE->requires->js_call_amd('local_extendednav/manage', 'init');

echo $OUTPUT->footer();
