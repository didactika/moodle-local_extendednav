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

namespace local_extendednav\form;

use local_extendednav\local\navigation\access_guard;
use local_extendednav\local\node\access;
use local_extendednav\local\node\availability;
use local_extendednav\local\node\display;
use local_extendednav\local\node\manager;
use local_extendednav\local\node\repository;
use local_extendednav\local\node\text;
use local_extendednav\local\node\validator;
use local_extendednav\menu_manager;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to add or edit a navigation node.
 *
 * Custom data: 'menu' (menu key) and 'node' (the record being edited, or null).
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class node_form extends \moodleform {
    /**
     * Form definition.
     */
    public function definition() {
        global $CFG, $OUTPUT, $PAGE;

        $mform = $this->_form;
        $menukey = $this->_customdata['menu'] ?? 'primary';
        $menu = menu_manager::get_menu($menukey);
        $current = $this->_customdata['node'] ?? null;
        $currentkey = $current ? (string)$current->nodekey : '';
        $contextid = (int)($this->_customdata['contextid'] ?? 0);
        $nodes = repository::get_menu_nodes_by_key($menukey);
        $isnative = $current !== null && $menu->is_native($currentkey);
        $issecondary = $menu instanceof \local_extendednav\menu\secondary;
        $istree = $menu instanceof \local_extendednav\menu\admintree;
        // A page of the site administration keeps its address and its look: only its name and who sees it change.
        $istreepage = $istree && $isnative;
        $isparent = false;
        foreach ($nodes as $node) {
            if ($currentkey !== '' && $node->parentkey === $currentkey) {
                $isparent = true;
                break;
            }
        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'menu', $menukey);
        $mform->setType('menu', PARAM_ALPHANUMEXT);
        $mform->addElement('hidden', 'contextid', $contextid);
        $mform->setType('contextid', PARAM_INT);

        // The node: what it is called and where it leads.
        $mform->addElement('header', 'nodeheader', get_string('form_section_node', 'local_extendednav'));
        $mform->setExpanded('nodeheader', true);
        // The notice of a node of Moodle opens the section, so it is read before any field; the script shows it.
        $mform->addElement('html', \html_writer::div(
            get_string('coreoverridealert', 'local_extendednav'),
            'alert alert-info',
            ['id' => 'core_node_alert', 'hidden' => 'hidden']
        ));
        $mform->addElement(
            'static',
            'menudisplay',
            get_string('menu_type', 'local_extendednav'),
            \html_writer::tag('strong', $menu->get_name()) . ($contextid ? ' · '
                . s(\local_extendednav\local\io\scope::describe($contextid)) : '')
        );

        $mform->addElement('text', 'nodekey', get_string('nodekey', 'local_extendednav'), ['size' => 30, 'maxlength' => 50]);
        $mform->setType('nodekey', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('nodekey', 'nodekey', 'local_extendednav');
        if ($isnative) {
            $mform->freeze('nodekey');
        } else {
            $mform->addRule('nodekey', get_string('required'), 'required', null, 'client');
        }

        $alerts = \html_writer::div(
            get_string('err_duplicate_key_alert', 'local_extendednav'),
            'alert alert-danger mt-2 mb-0',
            ['id' => 'duplicate_node_alert', 'hidden' => 'hidden']
        );
        // In the column of the fields, with no row of its own: while the alert is hidden it takes no room.
        $mform->addElement('html', \html_writer::div(\html_writer::div($alerts, 'col-md-9 offset-md-3'), 'row'));

        // A node of the plugin in the site administration is one of the two parts of its tree, as Moodle names them: a category,
        // which holds pages and other categories (at the top, the categories with categories inside are tabs), or a page.
        if ($istree && !$isnative) {
            $mform->addElement('select', 'treetype', get_string('tree_type', 'local_extendednav'), [
                'page' => get_string('tree_type_page', 'local_extendednav'),
                'category' => get_string('tree_type_category', 'local_extendednav'),
                'tab' => get_string('tree_type_tab', 'local_extendednav'),
            ]);
            $mform->setDefault('treetype', 'page');
            $mform->addHelpButton('treetype', 'tree_type', 'local_extendednav');
        }
        // A node of the site in the page Preferences is a section of its own, which holds items, or a link in a section.
        $isprefs = $menu instanceof \local_extendednav\menu\preferences;
        if ($isprefs && !$isnative) {
            $mform->addElement('select', 'treetype', get_string('tree_type', 'local_extendednav'), [
                'page' => get_string('pref_type_link', 'local_extendednav'),
                'tab' => get_string('pref_type_section', 'local_extendednav'),
            ]);
            $mform->setDefault('treetype', 'page');
            $mform->addHelpButton('treetype', 'pref_type', 'local_extendednav');
        }
        // In a menu with submenus, a node of the site is a link, or a submenu that groups links and has no address of its own.
        $hassubmenus = !$istree && !$isprefs && !$issecondary;
        if ($hassubmenus && !$isnative) {
            $mform->addElement('select', 'treetype', get_string('tree_type', 'local_extendednav'), [
                'page' => get_string('node_type_link', 'local_extendednav'),
                'tab' => get_string('node_type_submenu', 'local_extendednav'),
            ]);
            $mform->setDefault('treetype', 'page');
            $mform->addHelpButton('treetype', 'node_type', 'local_extendednav');
        }

        $mform->addElement('text', 'title', get_string('title', 'local_extendednav'), ['size' => 40, 'maxlength' => 255]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addHelpButton('title', 'title', 'local_extendednav');

        if ($isprefs && self::is_section($menu, $current)) {
            // A section of the page Preferences is no link: it holds options, and has no address.
            $mform->addElement('hidden', 'url', '');
            $mform->setType('url', PARAM_TEXT);
        } else if ($istreepage) {
            $mform->addElement(
                'static',
                'urlinfo',
                get_string('url', 'local_extendednav'),
                \html_writer::tag('code', s((string)($menu->get_native_node($currentkey)->action ?? '')))
            );
        } else {
            $mform->addElement('text', 'url', get_string('url', 'local_extendednav'), ['size' => 60]);
            $mform->setType('url', PARAM_TEXT);
            $mform->addHelpButton('url', 'url', 'local_extendednav');
            if ($istree || (($isprefs || $hassubmenus) && !$isnative)) {
                // A tab, a category or a section has no address: Moodle lists what it holds.
                $mform->hideIf('url', 'treetype', 'neq', 'page');
            }
            if ($isparent && $isnative) {
                $mform->addElement('static', 'urlnotice', '', \html_writer::div(
                    get_string('parenturlunused', 'local_extendednav'),
                    'alert alert-warning mb-0'
                ));
            }
        }

        // How it looks and how it opens. The pages of the administration and of the preferences are drawn by Moodle, as plain
        // links, and keep their look.
        if (!$istree && !$isprefs) {
            $mform->addElement('header', 'lookheader', get_string('form_section_look', 'local_extendednav'));
            $mform->setExpanded('lookheader', true);
            // The icon is chosen from a list, never typed: the value goes in a hidden field and the picker shows it.
            $mform->addElement('hidden', 'icon');
            $mform->setType('icon', PARAM_TEXT);
            $mform->addElement('static', 'iconpicker', get_string('icon', 'local_extendednav'), \html_writer::div('', '', [
                'data-region' => 'iconfield',
                'data-native' => $isnative ? '1' : '0',
            ]));
            $mform->addHelpButton('iconpicker', 'icon', 'local_extendednav');
            $PAGE->requires->js_call_amd('local_extendednav/form/icon_picker', 'init', ['icon']);

            $mform->addElement('text', 'tooltip', get_string('tooltip', 'local_extendednav'), ['size' => 40, 'maxlength' => 255]);
            $mform->setType('tooltip', PARAM_TEXT);
            $mform->addHelpButton('tooltip', 'tooltip', 'local_extendednav');

            $mform->addElement('select', 'display', get_string('display', 'local_extendednav'), display::get_options());
            $mform->setDefault('display', display::CURRENT);
            $mform->addHelpButton('display', 'display', 'local_extendednav');
            $sizes = ['popupwidth' => display::DEFAULT_WIDTH, 'popupheight' => display::DEFAULT_HEIGHT];
            foreach ($sizes as $field => $default) {
                $mform->addElement('text', $field, get_string($field, 'local_extendednav'), ['size' => 5]);
                $mform->setType($field, PARAM_INT);
                $mform->setDefault($field, $default);
                $mform->hideIf($field, 'display', 'neq', display::POPUP);
            }
        }

        // Who sees it.
        $mform->addElement('header', 'accessheader', get_string('form_section_access', 'local_extendednav'));
        $mform->setExpanded('accessheader', true);
        // On a user's own page Preferences, an item is shown or hidden to that user: roles say nothing there.
        $own = $contextid && \context::instance_by_id($contextid, IGNORE_MISSING) instanceof \context_user;
        $mform->addElement('select', 'visibility', get_string('visibility', 'local_extendednav'), $own ? [
            access::VISIBILITY_ALL => get_string('pref_shown', 'local_extendednav'),
            access::VISIBILITY_HIDDEN => get_string('pref_hidden', 'local_extendednav'),
        ] : [
            access::VISIBILITY_ALL => get_string('vis_all', 'local_extendednav'),
            access::VISIBILITY_ROLES => get_string('vis_roles', 'local_extendednav'),
            access::VISIBILITY_HIDDEN => get_string('vis_hidden', 'local_extendednav'),
        ]);
        $mform->setDefault('visibility', access::VISIBILITY_ALL);
        $mform->addHelpButton('visibility', 'visibility', 'local_extendednav');

        $mform->addElement(
            'autocomplete',
            'roles',
            get_string('roles', 'local_extendednav'),
            $this->get_role_options($current),
            ['multiple' => true]
        );
        $mform->setType('roles', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('roles', 'roles', 'local_extendednav');
        $mform->hideIf('roles', 'visibility', 'neq', access::VISIBILITY_ROLES);

        // The pages of the administration are never blocked: Moodle checks their capabilities.
        if (!$istree) {
            $mform->addElement('advcheckbox', 'blockaccess', get_string('blockaccess', 'local_extendednav'));
            $mform->setDefault('blockaccess', 1);
            $mform->addHelpButton('blockaccess', 'blockaccess', 'local_extendednav');
            $mform->hideIf('blockaccess', 'visibility', 'eq', access::VISIBILITY_ALL);
        }

        $mform->addElement(
            'autocomplete',
            'langs',
            get_string('langs', 'local_extendednav'),
            $this->get_language_options($current),
            [
                'multiple' => true,
                'noselectionstring' => get_string('langs_all', 'local_extendednav'),
            ]
        );
        $mform->setType('langs', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('langs', 'langs', 'local_extendednav');
        // A node nobody sees has no language or condition to meet: those fields are hidden while it is not visible.
        $mform->hideIf('langs', 'visibility', 'eq', access::VISIBILITY_HIDDEN);

        // Moodle's Restrict access, as for an activity: dates, groups, groupings, profile fields and the conditions the site
        // has. Its editor is drawn by Moodle's script in place of this field. Whoever may not restrict the nodes here does not
        // get the field, nor the script, which would wait for it.
        if (!empty($CFG->enableavailability) && !in_array('availability', $this->_customdata['locked'] ?? [], true)) {
            $mform->addElement(
                'textarea',
                'availabilityconditionsjson',
                get_string('accessrestrictions', 'availability'),
                ['class' => 'd-none']
            );
            $mform->addHelpButton('availabilityconditionsjson', 'conditions', 'local_extendednav');
            $mform->hideIf('availabilityconditionsjson', 'visibility', 'eq', access::VISIBILITY_HIDDEN);
            $mform->addElement('html', $OUTPUT->container(
                $OUTPUT->render_from_template('core/loading', []),
                'd-flex justify-content-center py-3',
                'availabilityconditions-loading'
            ));
            $course = $this->course_for_conditions($contextid);
            \core_availability\frontend::include_all_javascript($course);
            // A grade or the completion of an activity needs the activities of one course: outside a course they are not offered.
            if ((int)$course->id === (int)SITEID) {
                $PAGE->requires->js_call_amd('local_extendednav/form/conditions', 'init', [['grade', 'completion']]);
            }
        }

        $mform->addElement('advcheckbox', 'hideadmins', get_string('hideadmins', 'local_extendednav'));
        $mform->addHelpButton('hideadmins', 'hideadmins', 'local_extendednav');
        // A node of the site administration is hidden from administrators too, unless they choose otherwise.
        $mform->setDefault('hideadmins', $istree ? 1 : 0);

        // Where it goes.
        $mform->addElement('header', 'placeheader', get_string('form_section_place', 'local_extendednav'));
        $mform->setExpanded('placeheader', $istree && !$isnative);
        if ($issecondary) {
            // Where the node goes in the navigation: where Moodle puts it, the More menu or the bar.
            $mform->addElement('select', 'placement', get_string('placement', 'local_extendednav'), [
                0 => get_string('placement_default', 'local_extendednav'),
                1 => get_string('placement_more', 'local_extendednav'),
                2 => get_string('placement_main', 'local_extendednav'),
            ]);
            $mform->addHelpButton('placement', 'placement', 'local_extendednav');
        }

        if ($istreepage) {
            // A page of the administration stays in its category.
            $native = $menu->get_native_node($currentkey);
            $parentname = $native && $native->parentkey !== '' ? ($menu->get_native_node($native->parentkey)->text ?? '') : '';
            $mform->addElement(
                'static',
                'parentkeyinfo',
                get_string('category_of_page', 'local_extendednav'),
                s($parentname !== '' ? $parentname : get_string('tree_top', 'local_extendednav'))
            );
            $mform->addElement('hidden', 'parentkey', (string)($native->parentkey ?? ''));
            $mform->setType('parentkey', PARAM_ALPHANUMEXT);
        } else if ($istree) {
            // What the parent can be depends on the type: a tab has none, a category goes in a tab or in another category, and a
            // page goes in a tab or a category, or in General, where Moodle shows the pages at the top of the administration.
            $categories = $this->get_tree_categories($menu, $currentkey);
            $mform->addElement(
                'select',
                'parentkey',
                get_string('category_of_page', 'local_extendednav'),
                ['' => get_string('tree_top_general', 'local_extendednav')] + $categories
            );
            $mform->setType('parentkey', PARAM_ALPHANUMEXT);
            $mform->addHelpButton('parentkey', 'category_of_page', 'local_extendednav');
            $mform->hideIf('parentkey', 'treetype', 'neq', 'page');
            $mform->addElement(
                'select',
                'categoryparent',
                get_string('category_of_page', 'local_extendednav'),
                $categories ?: ['' => get_string('tree_no_categories', 'local_extendednav')]
            );
            $mform->setType('categoryparent', PARAM_ALPHANUMEXT);
            $mform->addHelpButton('categoryparent', 'category_of_category', 'local_extendednav');
            $mform->hideIf('categoryparent', 'treetype', 'neq', 'category');
        } else if ($menu instanceof \local_extendednav\menu\preferences) {
            // The page Preferences: a section stays at the top; an item, of Moodle or of the site, goes in any section.
            if (self::is_section($menu, $current)) {
                $mform->addElement('hidden', 'parentkey', '');
            } else {
                $mform->addElement(
                    'select',
                    'parentkey',
                    get_string('section_of_item', 'local_extendednav'),
                    self::sections_of($menu, $currentkey)
                );
                $mform->addHelpButton('parentkey', 'section_of_item', 'local_extendednav');
                if (!$isnative) {
                    $mform->hideIf('parentkey', 'treetype', 'eq', 'tab');
                }
            }
            $mform->setType('parentkey', PARAM_ALPHANUMEXT);
        } else if ($issecondary) {
            // The secondary navigations have no submenus.
            $mform->addElement('hidden', 'parentkey', '');
            $mform->setType('parentkey', PARAM_ALPHANUMEXT);
        } else if ($isparent && !$isnative) {
            // A submenu stays at the top: it holds the links under it.
            $mform->addElement('hidden', 'parentkey', '');
            $mform->setType('parentkey', PARAM_ALPHANUMEXT);
        } else if ($isparent || ($menukey === 'primary' && $currentkey === 'siteadminnode')) {
            // This node cannot be placed in a submenu: explain why instead of an empty, locked select.
            $reason = $isparent ? 'opt_invalid_has_submenus' : 'opt_invalid_admin_submenu';
            $mform->addElement(
                'static',
                'parentkeyinfo',
                get_string('parentkey', 'local_extendednav'),
                \html_writer::span(get_string($reason, 'local_extendednav'), 'text-muted')
            );
            $mform->addHelpButton('parentkeyinfo', 'parentkey', 'local_extendednav');
        } else {
            // A link goes in a submenu; with no submenu in the menu, there is nothing to choose and the field is not shown.
            $parents = $this->get_parent_options($menukey, $currentkey, $nodes);
            if (count($parents) > 1) {
                $mform->addElement('select', 'parentkey', get_string('parentkey', 'local_extendednav'), $parents);
                $mform->addHelpButton('parentkey', 'parentkey', 'local_extendednav');
                if (!$isnative) {
                    $mform->hideIf('parentkey', 'treetype', 'eq', 'tab');
                }
            } else {
                $mform->addElement('hidden', 'parentkey', '');
            }
            $mform->setType('parentkey', PARAM_ALPHANUMEXT);
        }

        $this->add_action_buttons(true, get_string('savechanges'));

        $customkeys = $contextid ? [] : array_values(array_diff(array_keys($nodes), [$currentkey]));
        $required = \html_writer::span(
            $OUTPUT->pix_icon('req', get_string('requiredelement', 'form')),
            'req text-danger',
            ['title' => get_string('requiredelement', 'form')]
        );
        // What the script of the form needs goes in the page, not in the call: the keys of a navigation can be hundreds.
        $data = [
            'placeholders' => $menu->get_placeholders(),
            'customKeys' => $customkeys,
            'requiredHtml' => $required,
            'wwwroot' => (new \moodle_url('/'))->out(false),
            'currentKey' => $currentkey,
        ];
        // The fields of the changes the user may not make in this category, course, activity or page of their own are not shown:
        // they keep their value, and a field that does nothing for the user only gets in the way.
        $parts = ['availability' => ['availabilityconditionsjson'], 'icon' => ['icon', 'iconpicker'], 'url' => ['url', 'urlinfo']];
        foreach ($this->_customdata['locked'] ?? [] as $field) {
            foreach ($parts[$field] ?? [$field] as $element) {
                if ($mform->elementExists($element)) {
                    $mform->removeElement($element);
                }
            }
        }

        $mform->addElement('html', \html_writer::tag(
            'script',
            json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES),
            ['type' => 'application/json', 'data-region' => 'nodeformdata']
        ));
        $PAGE->requires->js_call_amd('local_extendednav/form/node_form', 'init');
    }

    /**
     * Validates the submitted node with the shared rules.
     *
     * @param array $data Submitted values.
     * @param array $files Uploaded files.
     * @return array Errors keyed by field.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $menukey = $this->_customdata['menu'] ?? 'primary';
        $current = $this->_customdata['node'] ?? null;
        if ($current !== null && menu_manager::get_menu($menukey)->is_native($current->nodekey)) {
            $data['nodekey'] = $current->nodekey;
        }
        $data['availability'] = self::conditions_of($data['availabilityconditionsjson'] ?? '');
        $data = (array)self::tree_values((object)$data);
        $record = manager::prepare($data, $menukey);

        $contextid = (int)($data['contextid'] ?? 0);
        // The nodes of a category or a course are checked against its own: they repeat the key of a node of the site on purpose.
        $others = $contextid === 0 ? repository::get_menu_nodes_by_key($menukey)
            : array_column(repository::get_exceptions($menukey, $contextid), null, 'nodekey');
        if ($current !== null) {
            foreach ($others as $key => $other) {
                if ((int)$other->id === (int)$current->id) {
                    unset($others[$key]);
                }
            }
        }

        $result = validator::validate($record, $others);
        $errors += $result['errors'];
        // The conditions are shown and checked in the field of Moodle's editor.
        if (isset($errors['availability'])) {
            $errors['availabilityconditionsjson'] = $errors['availability'];
            unset($errors['availability']);
        }
        // The icon is a hidden field: its error is shown under the picker.
        if (isset($errors['icon'])) {
            $errors['iconpicker'] = $errors['icon'];
            unset($errors['icon']);
        }

        // A link added to the site administration needs the category it goes in; a category can go at the top.
        $menu = menu_manager::get_menu($menukey);
        if ($menu instanceof \local_extendednav\menu\admintree && !$menu->is_native((string)$record->nodekey)) {
            $categories = $this->get_tree_categories($menu, (string)$record->nodekey);
            $parentkey = (string)($record->parentkey ?? '');
            $type = $data['treetype'] ?? 'page';
            if ($type === 'page' && trim((string)($data['url'] ?? '')) === '') {
                $errors['url'] = get_string('required');
            }
            // A page may stay in General (no parent); a category needs a tab or a category to go in.
            if ($type === 'page' && $parentkey !== '' && !isset($categories[$parentkey])) {
                $errors['parentkey'] = get_string('err_tree_category', 'local_extendednav');
            }
            if ($type === 'category' && !isset($categories[$parentkey])) {
                $errors['categoryparent'] = get_string('err_tree_category', 'local_extendednav');
            }
        }

        // In a menu with submenus, a link of the site needs its address; a submenu has none.
        $withsubmenus = !$menu instanceof \local_extendednav\menu\admintree && !$menu instanceof \local_extendednav\menu\preferences
            && !$menu instanceof \local_extendednav\menu\secondary;
        if (
            $withsubmenus && ($data['treetype'] ?? '') === 'page' && !$menu->is_native((string)$record->nodekey)
            && trim((string)($data['url'] ?? '')) === ''
        ) {
            $errors['url'] = get_string('required');
        }

        // In the preferences, a link of the site needs its address, and every item a section to go in.
        if ($menu instanceof \local_extendednav\menu\preferences && ($data['treetype'] ?? '') !== 'tab') {
            $native = $menu->get_native_node((string)$record->nodekey);
            $issection = $native !== null && $native->parentkey === '';
            if (!$issection && !isset(self::sections_of($menu, (string)$record->nodekey)[(string)($record->parentkey ?? '')])) {
                $errors['parentkey'] = get_string('required');
            }
            if ($native === null && trim((string)($data['url'] ?? '')) === '') {
                $errors['url'] = get_string('required');
            }
        }

        if (
            $contextid === 0 && empty($errors)
                && access_guard::check_fallbacks(manager::get_all_with($record, (int)($current->id ?? 0)))
                === access_guard::FALLBACKS_BLOCKED
        ) {
            $errors['blockaccess'] = get_string('err_fallbacks_blocked', 'local_extendednav');
        }
        return $errors;
    }

    /**
     * Shows the conditions of the node in the field of Moodle's editor.
     *
     * @param \stdClass|array $defaultvalues The values of the form.
     */
    public function set_data($defaultvalues) {
        if (is_object($defaultvalues) && property_exists($defaultvalues, 'availability')) {
            $defaultvalues->availabilityconditionsjson = (string)($defaultvalues->availability ?? '');
        }
        // A node of the site administration without an address is a category.
        if (is_object($defaultvalues) && !empty($defaultvalues->nodekey) && property_exists($defaultvalues, 'url')) {
            $parentkey = (string)($defaultvalues->parentkey ?? '');
            // A node that holds others is a submenu, whatever address it kept from before.
            $holds = false;
            foreach (repository::get_menu_nodes_by_key((string)($this->_customdata['menu'] ?? 'primary')) as $other) {
                if ((string)($other->parentkey ?? '') === (string)$defaultvalues->nodekey) {
                    $holds = true;
                    break;
                }
            }
            if (trim((string)$defaultvalues->url) !== '' && !($holds && $parentkey === '')) {
                $defaultvalues->treetype = 'page';
            } else {
                $defaultvalues->treetype = $parentkey === '' ? 'tab' : 'category';
                $defaultvalues->categoryparent = $parentkey;
            }
        } else if (is_object($defaultvalues) && !empty($defaultvalues->parentkey)) {
            // A new node of the level open: a category starts in the same parent as a page.
            $defaultvalues->categoryparent = $defaultvalues->parentkey;
        }
        parent::set_data($defaultvalues);
    }

    /**
     * Returns the values of the form, with the conditions of the editor as the availability of the node.
     *
     * @return \stdClass|null
     */
    public function get_data() {
        $data = parent::get_data();
        if ($data !== null) {
            $data->availability = self::conditions_of($data->availabilityconditionsjson ?? '');
            $data = self::tree_values($data);
        }
        return $data;
    }

    /**
     * Whether a node of the page Preferences is a section: one of Moodle at the top, or one of the site without an address.
     *
     * @param \local_extendednav\menu\base $menu The navigation of the preferences.
     * @param \stdClass|null $node The node edited, or null for a new one.
     * @return bool
     */
    private static function is_section($menu, ?\stdClass $node): bool {
        if ($node === null) {
            return false;
        }
        $native = $menu->get_native_node((string)$node->nodekey);
        if ($native !== null) {
            return $native->parentkey === '';
        }
        return trim((string)($node->url ?? '')) === '' && (string)($node->parentkey ?? '') === '';
    }

    /**
     * The sections of the page Preferences an item can go in: the ones of Moodle and the ones of the site, by name.
     *
     * @param \local_extendednav\menu\base $menu The navigation of the preferences.
     * @param string $exclude Key of the node edited.
     * @return string[] Names by key.
     */
    private static function sections_of($menu, string $exclude = ''): array {
        $sections = [];
        foreach ($menu->get_native_nodes() as $key => $native) {
            if ($native->parentkey === '') {
                $sections[$key] = format_string(text::resolve($native->text));
            }
        }
        foreach (repository::get_menu_nodes_by_key($menu->get_key()) as $key => $row) {
            if (
                !isset($sections[$key]) && !$menu->is_native($key) && trim((string)($row->url ?? '')) === ''
                    && (string)($row->parentkey ?? '') === ''
            ) {
                $sections[$key] = format_string(text::resolve((string)$row->title)) ?: $key;
            }
        }
        unset($sections[$exclude]);
        \core_collator::asort($sections);
        return $sections;
    }

    /**
     * Gives the values of a node of the site administration what its type means: a tab and a category keep no address, a tab
     * goes at the top and a category in the parent chosen for it. Other nodes are returned as they are.
     *
     * @param \stdClass $data The values of the form.
     * @return \stdClass
     */
    private static function tree_values(\stdClass $data): \stdClass {
        $type = $data->treetype ?? '';
        if ($type === 'tab' || $type === 'category') {
            $data->url = '';
            $data->parentkey = $type === 'tab' ? '' : (string)($data->categoryparent ?? '');
        }
        return $data;
    }

    /**
     * The conditions the editor wrote, or none when it wrote an empty set of them.
     *
     * @param string $json What the editor wrote.
     * @return string The conditions, or empty.
     */
    private static function conditions_of(string $json): string {
        try {
            return (string)availability::clean($json);
        } catch (\Throwable $e) {
            // The validator says why they are not valid.
            return $json;
        }
    }

    /**
     * The course the conditions are written for: the course of the exception, so its groups and groupings are offered, or
     * the site course.
     *
     * @param int $contextid Context of the exception, 0 for the whole site.
     * @return \stdClass
     */
    private function course_for_conditions(int $contextid): \stdClass {
        global $SITE;

        $context = $contextid ? \context::instance_by_id($contextid, IGNORE_MISSING) : false;
        $coursecontext = $context ? $context->get_course_context(false) : false;
        return $coursecontext ? get_course($coursecontext->instanceid) : $SITE;
    }

    /**
     * Returns the categories of the site administration, indented as the tree has them: the ones of Moodle and the ones the
     * plugin added (its nodes without a link), each after its parent.
     *
     * @param \local_extendednav\menu\admintree $menu The site administration.
     * @param string $exclude Key of the node edited, which cannot go inside itself.
     * @return array Names keyed by node key.
     */
    private function get_tree_categories($menu, string $exclude = ''): array {
        $entries = [];
        foreach ($menu->get_native_nodes() as $key => $node) {
            if ($node->category) {
                $entries[$key] = ['text' => $node->text, 'parent' => (string)$node->parentkey];
            }
        }
        foreach (repository::get_menu_nodes_by_key($menu->get_key()) as $key => $row) {
            if (!isset($entries[$key]) && !$menu->is_native($key) && trim((string)($row->url ?? '')) === '') {
                $entries[$key] = [
                    'text' => text::resolve((string)$row->title) ?: $key,
                    'parent' => (string)($row->parentkey ?? ''),
                ];
            }
        }
        // The tree in order, from the top: each category followed by the ones inside it.
        $children = [];
        foreach ($entries as $key => $entry) {
            $children[isset($entries[$entry['parent']]) ? $entry['parent'] : ''][] = $key;
        }
        $options = [];
        $walk = static function (string $parent, int $depth) use (&$walk, &$options, $children, $entries, $exclude): void {
            foreach ($children[$parent] ?? [] as $key) {
                if ($key === $exclude) {
                    continue;
                }
                $options[$key] = str_repeat('— ', $depth) . $entries[$key]['text'];
                $walk($key, $depth + 1);
            }
        };
        $walk('', 0);
        return $options;
    }

    /**
     * Returns the role options, keyed by shortname.
     *
     * Roles the node refers to that no longer exist are listed too, so they can be seen and removed.
     *
     * @param \stdClass|null $current Node being edited.
     * @return array
     */
    private function get_role_options(?\stdClass $current): array {
        $options = [];
        foreach (role_fix_names(get_all_roles()) as $role) {
            $name = trim((string)$role->localname);
            $options[$role->shortname] = ($name !== '' ? $name . ' ' : '') . '(' . $role->shortname . ')';
        }
        foreach (access::split_list($current->roles ?? '') as $shortname) {
            if (!isset($options[$shortname])) {
                $options[$shortname] = get_string('unknown_role', 'local_extendednav', $shortname);
            }
        }
        return $options;
    }

    /**
     * Returns the installed languages, plus any language the node refers to that is not installed.
     *
     * @param \stdClass|null $current Node being edited.
     * @return array Keyed by language code.
     */
    private function get_language_options(?\stdClass $current): array {
        // A language pack without a name is offered by its code: an option is never empty.
        $options = [];
        foreach (get_string_manager()->get_list_of_translations() as $code => $name) {
            $options[$code] = trim(strip_tags((string)$name)) !== '' ? $name : $code;
        }
        foreach (access::split_list($current->langs ?? '') as $lang) {
            if (!isset($options[$lang])) {
                $options[$lang] = get_string('unknown_lang', 'local_extendednav', $lang);
            }
        }
        return $options;
    }

    /**
     * Returns the possible parents: core nodes and top-level nodes of the menu.
     *
     * @param string $menukey Menu key.
     * @param string $currentkey Key of the node being edited.
     * @param \stdClass[] $nodes Nodes of the menu keyed by nodekey.
     * @return array
     */
    private function get_parent_options(string $menukey, string $currentkey, array $nodes): array {
        $menu = menu_manager::get_menu($menukey);
        $options = ['' => get_string('opt_none_root', 'local_extendednav')];
        // Only a submenu holds links: a node of the site without an address, or one that already holds others.
        $holders = [];
        foreach ($nodes as $node) {
            if (!empty($node->parentkey)) {
                $holders[(string)$node->parentkey] = true;
            }
        }
        foreach ($menu->get_native_nodes() as $key => $native) {
            if ($key === $currentkey || ($menukey === 'primary' && $key === 'siteadminnode') || !isset($holders[$key])) {
                continue;
            }
            $options[$key] = get_string('opt_native', 'local_extendednav', (object)[
                'text' => strip_tags(text::resolve($native->text)),
                'key' => $key,
            ]);
        }
        foreach ($nodes as $key => $node) {
            $group = !isset($holders[$key]) && !$menu->is_native($key) && trim((string)($node->url ?? '')) === '';
            if (
                $key === $currentkey || isset($options[$key]) || !empty($node->parentkey)
                    || ($menukey === 'primary' && $key === 'siteadminnode') || (!isset($holders[$key]) && !$group)
            ) {
                continue;
            }
            $title = strip_tags(format_string(text::get_title($node, $menu->get_native_node($key))));
            $options[$key] = get_string('opt_plugin', 'local_extendednav', (object)[
                'title' => $title !== '' ? $title : get_string('none_title', 'local_extendednav'),
                'key' => $key,
            ]);
        }
        return $options;
    }
}
