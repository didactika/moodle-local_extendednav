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

namespace local_extendednav\table;

use local_extendednav\local\node_access;
use local_extendednav\local\node_icon;
use local_extendednav\local\node_repository;
use local_extendednav\local\node_text;
use local_extendednav\menu\base;
use local_extendednav\menu\primary;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Table of the nodes of one menu on the management page.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class nodes_table extends \flexible_table {
    /** @var base The menu shown. */
    private base $menu;

    /** @var bool Whether the list is filtered, which disables reordering. */
    private bool $filtered;

    /** @var string[] Shortnames of the roles that exist on the site. */
    private array $roleshortnames;

    /** @var array Keys of the nodes shown, as array keys. */
    private array $shownkeys = [];

    /** @var array Keys of the nodes of the menu that have children, as array keys. */
    private array $parentkeys = [];

    /**
     * Constructor.
     *
     * @param base $menu The menu shown.
     * @param \moodle_url $baseurl Page URL.
     * @param bool $filtered Whether the list is filtered.
     */
    public function __construct(base $menu, \moodle_url $baseurl, bool $filtered) {
        global $DB;

        parent::__construct('local-extendednav-manage');
        $this->menu = $menu;
        $this->filtered = $filtered;
        $this->roleshortnames = $DB->get_fieldset_sql('SELECT shortname FROM {role}');
        foreach (node_repository::get_menu_nodes($menu->get_key()) as $node) {
            if (!empty($node->parentkey)) {
                $this->parentkeys[$node->parentkey] = true;
            }
        }

        $columns = ['select', 'order', 'title', 'visibility', 'icon', 'nodekey', 'url', 'actions'];
        $headers = [
            \html_writer::checkbox(
                'selectall',
                1,
                false,
                \html_writer::span(get_string('selectall'), 'visually-hidden sr-only'),
                ['id' => 'select-all-nodes']
            ),
            get_string('order', 'local_extendednav'),
            get_string('title', 'local_extendednav'),
            get_string('visibility', 'local_extendednav'),
            get_string('icon', 'local_extendednav'),
            get_string('nodekey', 'local_extendednav'),
            get_string('url', 'local_extendednav'),
            get_string('actions'),
        ];
        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->define_baseurl($baseurl);
        $this->set_attribute('id', 'local-extendednav-manage');
        $this->set_attribute('class', 'generaltable table-hover mb-0 local-extendednav-nodes');
        $this->set_attribute('data-menu', $menu->get_key());
        $this->collapsible(false);
        $this->sortable(false);
        $this->pageable(false);
        foreach (['select', 'order', 'icon'] as $column) {
            $this->column_class($column, 'text-center align-middle text-nowrap');
        }
        $this->column_class('actions', 'align-middle text-nowrap');
        $this->column_class('visibility', 'align-middle');
        foreach (['title', 'nodekey', 'url'] as $column) {
            $this->column_class($column, 'align-middle text-break local-extendednav-col-' . $column);
        }
    }

    /**
     * Prints the rows.
     *
     * @param \stdClass[] $nodes Nodes of the menu, grouped by parent (see node_manager::group_by_parent()).
     */
    public function display(array $nodes): void {
        $this->shownkeys = [];
        foreach ($nodes as $node) {
            $this->shownkeys[$node->nodekey] = true;
        }
        $this->setup();
        foreach ($nodes as $node) {
            $this->add_data_keyed($this->format_row($node), 'local-extendednav-row');
        }
        // Without pages there is nothing to fill, and hidden filler rows would be offered as drop targets.
        $this->pagesize = 0;
        $this->finish_output();
    }

    /**
     * Selection checkbox.
     *
     * @param \stdClass $node Node record.
     * @return string
     */
    public function col_select($node): string {
        $native = $this->menu->is_native($node->nodekey);
        return \html_writer::checkbox(
            'nodeids[]',
            $node->id,
            false,
            \html_writer::span(get_string('select'), 'visually-hidden sr-only'),
            [
                'class' => 'node-checkbox',
                'data-native' => $native ? 1 : 0,
                'data-node-id' => $node->id,
                'data-nodekey' => $node->nodekey,
                // Only a parent shown in the table groups the row; drag and drop keeps the row inside it.
                'data-parentkey' => isset($this->shownkeys[(string)$node->parentkey]) ? $node->parentkey : '',
            ]
        );
    }

    /**
     * Drag handle to reorder the node. Not shown while the list is filtered.
     *
     * @param \stdClass $node Node record.
     * @return string
     */
    public function col_order($node): string {
        global $OUTPUT;

        if ($this->filtered) {
            return '';
        }
        $title = strip_tags(format_string(node_text::get_title($node, $this->menu->get_native_node($node->nodekey))));
        return $OUTPUT->render_from_template('core/drag_handle', [
            'movetitle' => get_string('movenode', 'local_extendednav', $title !== '' ? $title : $node->nodekey),
        ]);
    }

    /**
     * Title, with the parent and a core node marker.
     *
     * @param \stdClass $node Node record.
     * @return string
     */
    public function col_title($node): string {
        global $OUTPUT;

        $native = $this->menu->get_native_node($node->nodekey);
        $title = node_text::get_title($node, $native);
        $text = $title !== '' ? format_string($title) : \html_writer::span('-', 'font-italic fst-italic text-muted');
        $editurl = new \moodle_url('/local/extendednav/edit.php', ['id' => $node->id, 'menu' => $node->menu]);
        $html = \html_writer::link($editurl, $text, ['class' => 'font-weight-bold fw-bold']);

        if ($native !== null) {
            $html .= $OUTPUT->pix_icon(
                'i/circleinfo',
                get_string('nativenodeinfo', 'local_extendednav'),
                'moodle',
                ['class' => 'text-warning ml-1 ms-1']
            );
        }
        if (empty($node->parentkey)) {
            return $html;
        }

        $parent = \html_writer::span(
            get_string('submenuof', 'local_extendednav', s($node->parentkey)),
            'badge badge-pill rounded-pill badge-light text-bg-light border font-weight-normal fw-normal'
        );
        return \html_writer::div(
            \html_writer::span('↳', 'text-muted mr-2 me-2', ['aria-hidden' => 'true']) .
            \html_writer::div($html . \html_writer::div($parent, 'mt-1')),
            'd-flex align-items-start local-extendednav-child'
        );
    }

    /**
     * Visibility badges: visibility, blocked URL, unknown roles and languages.
     *
     * @param \stdClass $node Node record.
     * @return string
     */
    public function col_visibility($node): string {
        $visibility = (int)$node->visibility;
        if ($visibility === node_access::VISIBILITY_HIDDEN) {
            $badges = [$this->badge(get_string('vis_hidden', 'local_extendednav'), 'danger')];
        } else if ($visibility === node_access::VISIBILITY_ROLES) {
            $badges = [$this->badge(get_string('vis_roles', 'local_extendednav'), 'warning')];
        } else {
            $badges = [$this->badge(get_string('vis_all', 'local_extendednav'), 'success')];
        }
        // A Moodle item that Moodle does not add right now, for example because it is turned off.
        if (
            $this->menu instanceof primary && $this->menu->is_native($node->nodekey)
                && !$this->menu->is_shown_by_moodle($node->nodekey)
        ) {
            $badges[] = \html_writer::span(
                get_string('badge_unavailable', 'local_extendednav'),
                'badge badge-pill rounded-pill badge-secondary text-bg-secondary',
                ['title' => get_string('badge_unavailable_help', 'local_extendednav')]
            );
        }
        if (!empty($node->blockaccess) && $visibility !== node_access::VISIBILITY_ALL) {
            $badges[] = $this->badge(get_string('badge_blocked', 'local_extendednav'), 'dark');
        }
        if ($visibility === node_access::VISIBILITY_ROLES) {
            foreach (array_diff(node_access::split_list($node->roles), $this->roleshortnames) as $unknown) {
                $badges[] = $this->badge(get_string('unknown_role', 'local_extendednav', $unknown), 'danger');
            }
        }
        $langs = node_access::split_list($node->langs);
        if (!empty($langs)) {
            $badges[] = $this->badge(implode(', ', $langs), 'info');
        }
        return \html_writer::div(implode('', $badges), 'local-extendednav-badges');
    }

    /**
     * Icon preview.
     *
     * @param \stdClass $node Node record.
     * @return string
     */
    public function col_icon($node): string {
        $html = node_icon::render($node->icon);
        return $html === '' ? \html_writer::span('-', 'text-secondary') : $html;
    }

    /**
     * Node key.
     *
     * @param \stdClass $node Node record.
     * @return string
     */
    public function col_nodekey($node): string {
        return \html_writer::tag('code', s($node->nodekey));
    }

    /**
     * URL, or the core URL in grey when the node uses it, with a warning when the node has children.
     *
     * @param \stdClass $node Node record.
     * @return string
     */
    public function col_url($node): string {
        global $OUTPUT;

        if (trim((string)$node->url) !== '') {
            $html = s($node->url);
        } else if ($native = $this->menu->get_native_node($node->nodekey)) {
            $html = \html_writer::span(s($native->action), 'text-muted');
        } else {
            $html = \html_writer::span('-', 'font-italic fst-italic text-muted');
        }
        // A node with children opens its submenu, so its URL is not followed.
        if (isset($this->parentkeys[$node->nodekey])) {
            $html .= $OUTPUT->pix_icon(
                'i/warning',
                get_string('parenturlunused', 'local_extendednav'),
                'moodle',
                ['class' => 'text-warning ml-1 ms-1']
            );
        }
        return $html;
    }

    /**
     * Edit and delete buttons. Core nodes can only be deleted when their menu allows it.
     *
     * @param \stdClass $node Node record.
     * @return string
     */
    public function col_actions($node): string {
        global $OUTPUT;

        $params = ['menu' => $node->menu, 'id' => $node->id, 'sesskey' => sesskey()];
        $html = $OUTPUT->action_icon(
            new \moodle_url('/local/extendednav/edit.php', ['id' => $node->id, 'menu' => $node->menu]),
            new \pix_icon('t/edit', get_string('edit'))
        );
        if ($this->menu->can_delete($node->nodekey)) {
            $html .= $OUTPUT->action_icon(
                new \moodle_url('/local/extendednav/manage.php', $params + ['action' => 'delete']),
                new \pix_icon('t/delete', get_string('delete')),
                null,
                ['data-action' => 'delete-node', 'class' => 'text-danger']
            );
        }

        return \html_writer::div($html, 'd-flex align-items-center', ['data-node-id' => $node->id]);
    }

    /**
     * Renders a badge with both Bootstrap 4 and Bootstrap 5 classes.
     *
     * @param string $text Badge text.
     * @param string $variant Colour variant (success, danger...).
     * @return string
     */
    private function badge(string $text, string $variant): string {
        return \html_writer::span(s($text), 'badge badge-pill rounded-pill badge-' . $variant . ' text-bg-' . $variant);
    }
}
