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

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);
$parent = optional_param('parent', '', PARAM_ALPHANUMEXT);
$activemenu = optional_param('menu', 'primary', PARAM_ALPHANUMEXT);

$baseparams = ['menu' => $activemenu];
if ($search !== '') {
    $baseparams['search'] = $search;
}
if ($parent !== '') {
    $baseparams['parent'] = $parent;
}

$baseurl = new moodle_url('/local/extendednav/manage.php', $baseparams);

$menuhandler = \local_extendednav\menu_manager::get_menu($activemenu);
if (!$menuhandler) {
    $activemenu = 'primary';
    $menuhandler = \local_extendednav\menu_manager::get_menu('primary');
}
$menuhandler->sync_native_nodes();
$menuhandler->sync_from_config();
$activenativenodes = $menuhandler->get_native_nodes();

if ($action === 'moveup' || $action === 'movedown') {
    require_sesskey();
    $current = $DB->get_record('local_extendednav', ['id' => $id]);
    if ($current) {
        $allnodes = array_values($DB->get_records(
            'local_extendednav',
            ['menu' => $current->menu],
            'sortorder ASC, id ASC'
        ));

        $currentindex = null;
        foreach ($allnodes as $idx => $node) {
            if ((int)$node->id === (int)$current->id) {
                $currentindex = $idx;
                break;
            }
        }

        if ($currentindex !== null) {
            $targetindex = $action === 'moveup' ? $currentindex - 1 : $currentindex + 1;
            if ($targetindex >= 0 && $targetindex < count($allnodes)) {
                $temp = $allnodes[$currentindex];
                $allnodes[$currentindex] = $allnodes[$targetindex];
                $allnodes[$targetindex] = $temp;

                foreach ($allnodes as $newsort => $node) {
                    if ((int)$node->sortorder !== $newsort) {
                        $DB->set_field('local_extendednav', 'sortorder', $newsort, ['id' => $node->id]);
                    }
                }

                try {
                    \cache::make('local_extendednav', 'nodes')->purge();
                } catch (\Throwable $e) {
                    unset($e);
                }
            }
        }
    }
    redirect($baseurl);
}

if ($action === 'delete') {
    require_sesskey();
    $rec = $DB->get_record('local_extendednav', ['id' => $id]);
    if ($rec) {
        $handler = ($rec->menu === $activemenu) ? $menuhandler : \local_extendednav\menu_manager::get_menu($rec->menu);
        $nativenodes = ($rec->menu === $activemenu) ? $activenativenodes : ($handler ? $handler->get_native_nodes() : []);
        if (isset($nativenodes[$rec->nodekey])) {
            \core\notification::warning(get_string('native_cannot_delete', 'local_extendednav'));
            redirect($baseurl);
        }
        $DB->delete_records('local_extendednav', ['id' => $id]);
        \local_extendednav\event\node_deleted::create_from_record($rec)->trigger();
        if ($handler) {
            $handler->remove_nodes_from_config([$rec]);
        }
        try {
            \cache::make('local_extendednav', 'nodes')->purge();
        } catch (\Throwable $e) {
            unset($e);
        }
    }
    redirect($baseurl);
}

if ($action === 'bulkdelete') {
    require_sesskey();
    $nodeids = optional_param_array('nodeids', [], PARAM_INT);
    if (!empty($nodeids)) {
        $todelete = [];
        $deletedrecs = [];
        $skipped = false;
        $recs = $DB->get_records_list('local_extendednav', 'id', $nodeids);
        foreach ($recs as $rec) {
            $handler = ($rec->menu === $activemenu) ? $menuhandler : \local_extendednav\menu_manager::get_menu($rec->menu);
            $nativenodes = ($rec->menu === $activemenu) ? $activenativenodes : ($handler ? $handler->get_native_nodes() : []);
            if (isset($nativenodes[$rec->nodekey])) {
                $skipped = true;
            } else {
                $todelete[] = (int)$rec->id;
                $deletedrecs[] = $rec;
            }
        }

        if (!empty($todelete)) {
            [$insql, $inparams] = $DB->get_in_or_equal($todelete);
            $DB->delete_records_select('local_extendednav', "id $insql", $inparams);

            foreach ($deletedrecs as $drec) {
                \local_extendednav\event\node_deleted::create_from_record($drec)->trigger();
            }

            $bymenu = [];
            foreach ($deletedrecs as $drec) {
                $bymenu[$drec->menu][] = $drec;
            }
            foreach ($bymenu as $menukey => $mrecs) {
                $handler = ($menukey === $activemenu) ? $menuhandler : \local_extendednav\menu_manager::get_menu($menukey);
                if ($handler) {
                    $handler->remove_nodes_from_config($mrecs);
                }
            }

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

$where[] = 'menu = :menu';
$params['menu'] = $activemenu;
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
$isfiltered = ($search !== '' || $parent !== '');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage_nodes', 'local_extendednav'));
echo html_writer::tag('p', get_string('manage_nodes_desc', 'local_extendednav'), ['class' => 'mb-4']);

// Scalable tabs generation.
$menus = \local_extendednav\menu_manager::get_menus();
echo html_writer::start_div('nav nav-tabs mb-4');
foreach ($menus as $key => $menu) {
    $name = $menu->get_name();
    $class = 'nav-link';
    if ($key === $activemenu) {
        $class .= ' active font-weight-bold';
    }
    echo html_writer::link(new moodle_url('/local/extendednav/manage.php', ['menu' => $key]), $name, ['class' => $class]);
}
echo html_writer::end_div();

// Top actions bar (matches theme_vle and local_servicemanager).
$addurl = new moodle_url('/local/extendednav/edit.php', ['menu' => $activemenu]);
$importurl = new moodle_url('/local/extendednav/import.php', ['menu' => $activemenu]);
$exportallurl = new moodle_url('/local/extendednav/export.php', ['all' => 1, 'menu' => $activemenu]);
$logsurl = new moodle_url('/local/extendednav/activity.php', ['menu' => $activemenu]);

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
echo html_writer::link(
    $logsurl,
    '<i class="fa fa-history mr-1"></i>' . get_string('activity_log', 'local_extendednav'),
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
            $confirmmsg = s(get_string('bulk_delete_confirm', 'local_extendednav'));
            $deletebtntext = '<i class="fa fa-trash mr-1"></i>' . get_string('bulk_delete', 'local_extendednav');
            echo html_writer::tag('button', $deletebtntext, [
                'type' => 'submit',
                'form' => 'bulk-export-form',
                'formaction' => 'manage.php',
                'name' => 'action',
                'value' => 'bulkdelete',
                'class' => 'btn btn-sm btn-danger mr-1',
                'data-native-warning' => s(get_string('native_cannot_delete', 'local_extendednav')),
                'onclick' => 'return confirm("' . $confirmmsg . '");',
            ]);
            echo '        </div>';
            echo '    </div>';

            echo html_writer::start_div('dropdown extendednav-filters');
            echo html_writer::tag(
                'button',
                html_writer::tag('i', '', ['class' => 'fa fa-filter mr-1']) . ' ' .
                get_string('filters', 'local_extendednav') . ' ',
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

            echo html_writer::tag('button', get_string('apply', 'local_extendednav'), [
                'type' => 'submit',
                'class' => 'btn btn-primary',
            ]);

            if ($isfiltered) {
                echo html_writer::tag(
                    'div',
                    html_writer::link(new moodle_url('/local/extendednav/manage.php'), get_string('reset', 'local_extendednav')),
                    ['class' => 'pt-3']
                );
            }

            echo html_writer::end_tag('form');
            echo html_writer::end_div(); // End dropdown-menu.
            echo html_writer::end_div(); // End dropdown.
            echo html_writer::end_div(); // End d-flex wrapper.
            echo html_writer::end_div(); // End reportbuilder-wrapper.

            // Bulk actions form start.
            $exporturl = new moodle_url('/local/extendednav/export.php', ['menu' => $activemenu]);
            echo html_writer::start_tag('form', ['id' => 'bulk-export-form', 'method' => 'POST', 'action' => $exporturl]);
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

            $table->set_attribute('style', 'min-width: 780px;');

            $table->column_class('select', 'text-center align-middle text-nowrap');
            $table->column_style('select', 'width', '45px');

            $table->column_class('title', 'align-middle text-break');
            $table->column_style('title', 'min-width', '130px');
            $table->column_style('title', 'max-width', '160px');

            $table->column_class('visibility', 'text-center align-middle text-nowrap');
            $table->column_style('visibility', 'width', '125px');

            $table->column_class('icon', 'text-center align-middle text-nowrap');
            $table->column_style('icon', 'width', '60px');

            $table->column_class('nodekey', 'align-middle text-nowrap');
            $table->column_style('nodekey', 'min-width', '130px');
            $table->column_style('nodekey', 'max-width', '180px');

            $table->column_class('url', 'align-middle text-break');
            $table->column_style('url', 'min-width', '160px');
            $table->column_style('url', 'max-width', '250px');

            $table->column_class('positioning', 'text-center align-middle text-nowrap');
            $table->column_style('positioning', 'width', '90px');
            $table->setup();

            $nodes = $DB->get_records_select('local_extendednav', $wheresql, $params, 'sortorder ASC, id ASC');
            $total = count($nodes);
            $i = 0;

            foreach ($nodes as $n) {
                $corenode = $activenativenodes[$n->nodekey] ?? null;
                if ($n->visibility == 0) {
                    $vis = '<span class="badge badge-danger">' . get_string('vis_hidden', 'local_extendednav') . '</span>';
                } else if ($n->visibility == 2) {
                    $vis = '<span class="badge badge-warning">' . get_string('vis_roles', 'local_extendednav') . '</span>';
                } else {
                    $vis = '<span class="badge badge-success">' . get_string('vis_all', 'local_extendednav') . '</span>';
                }

                $iconhtml = '';
                $icon = (!empty($n->icon) && $n->icon !== 'none') ? $n->icon : null;
                if (!empty($icon) && $icon !== 'none') {
                    if (is_string($icon) && (strpos($icon, 'fa-') !== false || strpos($icon, 'fa ') !== false)) {
                        $iconhtml = '<i class="icon fa ' . s($icon) . ' fa-fw" aria-hidden="true"></i>';
                    } else if (is_string($icon)) {
                        try {
                            $iconhtml = $OUTPUT->pix_icon($icon, '');
                        } catch (\moodle_exception $e) {
                            $iconhtml = '<span class="text-secondary">-</span>';
                        }
                    } else if ($icon instanceof \pix_icon) {
                        try {
                            $iconhtml = $OUTPUT->render($icon);
                        } catch (\moodle_exception $e) {
                            $iconhtml = '<span class="text-secondary">-</span>';
                        }
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
                    $invisibledown = $OUTPUT->pix_icon('t/down', '', 'moodle', ['class' => 'invisible']);
                    $actions .= '&nbsp;' . html_writer::tag('span', $invisibledown);
                }

                $editurl = new moodle_url('/local/extendednav/edit.php', ['id' => $n->id, 'menu' => $activemenu]);

                $actions .= '</div>';

                $displaytitle = !empty($n->title) ? $n->title : '';
                if (empty($displaytitle) && $corenode) {
                    $displaytitle = strip_tags((string)$corenode->text);
                }
                if (!empty($displaytitle)) {
                    $titlebits = explode(',', $displaytitle, 2);
                    $isvalidstr = clean_param($titlebits[0], PARAM_STRINGID) !== '';
                    $isvalidcomp = clean_param($titlebits[1], PARAM_COMPONENT) !== '';
                    if (count($titlebits) == 2 && $isvalidstr && $isvalidcomp) {
                        try {
                            $displaytitle = get_string($titlebits[0], $titlebits[1]);
                        } catch (\moodle_exception $e) {
                            unset($e);
                        }
                    }
                    $titletext = format_string($displaytitle);
                } else {
                    $titletext = '<span class="font-italic text-muted">-</span>';
                }

                $titlehtml = html_writer::link($editurl, $titletext, ['class' => 'font-weight-bold']);

                if (!empty($n->parentkey)) {
                    $parentstr = get_string('submenuof', 'local_extendednav', s($n->parentkey));
                    $titlehtml = '<span class="text-muted mr-2" title="' . $parentstr . '">↳</span>' . $titlehtml;
                    $titlehtml .= ' <span class="badge badge-light border ml-2">↳ ' . $parentstr . '</span>';
                }

                if ($corenode) {
                    $titlehtml .= ' ' . html_writer::tag('i', '', [
                        'class' => 'fa fa-info-circle text-warning ml-1',
                        'title' => get_string('nativenodeinfo', 'local_extendednav'),
                        'data-toggle' => 'tooltip',
                        'data-placement' => 'top',
                    ]);
                }

                $titlecell = html_writer::div($titlehtml, 'text-break', ['style' => 'max-width: 160px; word-break: break-word;']);

                $checkbox = '<input type="checkbox" name="nodeids[]" value="' . $n->id .
                    '" class="node-checkbox" data-native="' . ($corenode ? '1' : '0') . '">';

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

                $nodekeycell = html_writer::div('<b>' . s($n->nodekey) . '</b>', 'text-nowrap');
                $urlcell = html_writer::div(
                    $urltext,
                    'text-break',
                    ['style' => 'max-width: 250px; min-width: 160px; word-break: break-all;']
                );

                $table->add_data([
                    $checkbox,
                    $titlecell,
                    $vis,
                    $iconhtml,
                    $nodekeycell,
                    $urlcell,
                    $actions,
                ]);

                $i++;
            }

            $table->finish_output();

            echo html_writer::end_tag('form');

            $PAGE->requires->js_call_amd('local_extendednav/manage', 'init');

            echo $OUTPUT->footer();
