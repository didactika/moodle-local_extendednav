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

use local_extendednav\local\access_guard;
use local_extendednav\local\node_access;
use local_extendednav\local\node_manager;
use local_extendednav\local\node_repository;
use local_extendednav\local\node_text;
use local_extendednav\local\node_validator;
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
        global $OUTPUT, $PAGE;

        $mform = $this->_form;
        $menukey = $this->_customdata['menu'] ?? 'primary';
        $menu = menu_manager::get_menu($menukey);
        $current = $this->_customdata['node'] ?? null;
        $currentkey = $current ? (string)$current->nodekey : '';
        $nodes = node_repository::get_menu_nodes_by_key($menukey);
        $isnative = $current !== null && $menu->is_native($currentkey);
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

        $mform->addElement(
            'static',
            'menudisplay',
            get_string('menu_type', 'local_extendednav'),
            \html_writer::tag('strong', $menu->get_name())
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
            get_string('coreoverridealert', 'local_extendednav'),
            'alert alert-warning mt-2 mb-0',
            ['id' => 'core_node_alert', 'hidden' => 'hidden']
        );
        $alerts .= \html_writer::div(
            get_string('err_duplicate_key_alert', 'local_extendednav'),
            'alert alert-danger mt-2 mb-0',
            ['id' => 'duplicate_node_alert', 'hidden' => 'hidden']
        );
        $mform->addElement('static', 'corealert', '', $alerts);

        $mform->addElement('text', 'title', get_string('title', 'local_extendednav'), ['size' => 40, 'maxlength' => 255]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addHelpButton('title', 'title', 'local_extendednav');

        $mform->addElement('text', 'tooltip', get_string('tooltip', 'local_extendednav'), ['size' => 40, 'maxlength' => 255]);
        $mform->setType('tooltip', PARAM_TEXT);
        $mform->addHelpButton('tooltip', 'tooltip', 'local_extendednav');

        $mform->addElement('text', 'url', get_string('url', 'local_extendednav'), ['size' => 60]);
        $mform->setType('url', PARAM_TEXT);
        $mform->addHelpButton('url', 'url', 'local_extendednav');
        if ($isparent) {
            $mform->addElement('static', 'urlnotice', '', \html_writer::div(
                get_string('parenturlunused', 'local_extendednav'),
                'alert alert-warning mb-0'
            ));
        }

        $mform->addElement('advcheckbox', 'newwindow', get_string('newwindow', 'local_extendednav'));
        $mform->addHelpButton('newwindow', 'newwindow', 'local_extendednav');

        $mform->addElement('text', 'icon', get_string('icon', 'local_extendednav'), ['size' => 30, 'maxlength' => 100]);
        $mform->setType('icon', PARAM_TEXT);
        $mform->addHelpButton('icon', 'icon', 'local_extendednav');

        $mform->addElement('select', 'visibility', get_string('visibility', 'local_extendednav'), [
            node_access::VISIBILITY_ALL => get_string('vis_all', 'local_extendednav'),
            node_access::VISIBILITY_ROLES => get_string('vis_roles', 'local_extendednav'),
            node_access::VISIBILITY_HIDDEN => get_string('vis_hidden', 'local_extendednav'),
        ]);
        $mform->setDefault('visibility', node_access::VISIBILITY_ALL);
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
        $mform->hideIf('roles', 'visibility', 'neq', node_access::VISIBILITY_ROLES);

        $mform->addElement('advcheckbox', 'blockaccess', get_string('blockaccess', 'local_extendednav'));
        $mform->setDefault('blockaccess', 1);
        $mform->addHelpButton('blockaccess', 'blockaccess', 'local_extendednav');
        $mform->hideIf('blockaccess', 'visibility', 'eq', node_access::VISIBILITY_ALL);

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
        $mform->addElement('static', 'langsnotice', '', \html_writer::div(
            get_string('langs_hidden_notice', 'local_extendednav'),
            'alert alert-info mb-0',
            ['id' => 'local_extendednav_langs_notice', 'hidden' => 'hidden']
        ));

        if ($isparent || ($menukey === 'primary' && $currentkey === 'siteadminnode')) {
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
            $mform->addElement(
                'select',
                'parentkey',
                get_string('parentkey', 'local_extendednav'),
                $this->get_parent_options($menukey, $currentkey, $nodes)
            );
            $mform->setType('parentkey', PARAM_ALPHANUMEXT);
            $mform->addHelpButton('parentkey', 'parentkey', 'local_extendednav');
        }

        $this->add_action_buttons(true, get_string('savechanges'));

        $customkeys = array_values(array_diff(array_keys($nodes), [$currentkey]));
        $required = \html_writer::span(
            $OUTPUT->pix_icon('req', get_string('requiredelement', 'form')),
            'req text-danger',
            ['title' => get_string('requiredelement', 'form')]
        );
        $PAGE->requires->js_call_amd('local_extendednav/node_form', 'init', [[
            'placeholders' => $menu->get_placeholders(),
            'customKeys' => $customkeys,
            'requiredHtml' => $required,
            'wwwroot' => (new \moodle_url('/'))->out(false),
            'currentKey' => $currentkey,
        ]]);
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
        $record = node_manager::prepare($data, $menukey);

        $others = node_repository::get_menu_nodes_by_key($menukey);
        if ($current !== null) {
            foreach ($others as $key => $other) {
                if ((int)$other->id === (int)$current->id) {
                    unset($others[$key]);
                }
            }
        }

        $result = node_validator::validate($record, $others);
        $errors += $result['errors'];

        if (
            empty($errors) && access_guard::check_fallbacks(node_manager::get_all_with($record, (int)($current->id ?? 0)))
                === access_guard::FALLBACKS_BLOCKED
        ) {
            $errors['blockaccess'] = get_string('err_fallbacks_blocked', 'local_extendednav');
        }
        return $errors;
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
            $options[$role->shortname] = $role->localname . ' (' . $role->shortname . ')';
        }
        foreach (node_access::split_list($current->roles ?? '') as $shortname) {
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
        $options = get_string_manager()->get_list_of_translations();
        foreach (node_access::split_list($current->langs ?? '') as $lang) {
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
        foreach ($menu->get_native_nodes() as $key => $native) {
            if ($key === $currentkey || ($menukey === 'primary' && $key === 'siteadminnode')) {
                continue;
            }
            $options[$key] = get_string('opt_native', 'local_extendednav', (object)[
                'text' => strip_tags(node_text::resolve($native->text)),
                'key' => $key,
            ]);
        }
        foreach ($nodes as $key => $node) {
            if (
                $key === $currentkey || isset($options[$key]) || !empty($node->parentkey)
                    || ($menukey === 'primary' && $key === 'siteadminnode')
            ) {
                continue;
            }
            $title = strip_tags(format_string(node_text::get_title($node, $menu->get_native_node($key))));
            $options[$key] = get_string('opt_plugin', 'local_extendednav', (object)[
                'title' => $title !== '' ? $title : get_string('none_title', 'local_extendednav'),
                'key' => $key,
            ]);
        }
        return $options;
    }
}
