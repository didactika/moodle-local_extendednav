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
 * Form definition for navigation nodes.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_extendednav\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Defines the form for adding or editing extended navigation nodes.
 */
class node_form extends \moodleform {

    /**
     * Form definition.
     */
    public function definition() {
        global $DB, $PAGE, $OUTPUT;
        $mform = $this->_form;

        $core_keys = [];
        try {
            \local_extendednav\hooks::$skip_hook = true;
            $temp_page = new \moodle_page();
            $temp_page->set_context(\context_system::instance());
            $temp_page->set_url($PAGE->url);
            $primary = new \core\navigation\views\primary($temp_page);
            $primary->initialise();
            foreach ($primary->children as $child) {
                if ($child->key) {
                    $core_keys[] = $child->key;
                }
            }
            \local_extendednav\hooks::$skip_hook = false;
        } catch (\Exception $e) {
            \local_extendednav\hooks::$skip_hook = false;
        }
        
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'nodekey', get_string('nodekey', 'local_extendednav'), ['size' => '30']);
        $mform->setType('nodekey', PARAM_ALPHANUMEXT);
        $mform->addRule('nodekey', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('nodekey', 'nodekey', 'local_extendednav');

        $alert_html = '<div id="core_node_alert" class="alert alert-warning mt-2 mb-0" style="display: none;">' 
            . get_string('coreoverridealert', 'local_extendednav') . '</div>';
            
        $alert_html .= '<div id="duplicate_node_alert" class="alert alert-danger mt-2 mb-0" style="display: none;">' 
            . get_string('err_duplicate_key_alert', 'local_extendednav') . '</div>';
            
        $mform->addElement('static', 'corealert', '', $alert_html);

        $mform->addElement('text', 'title', get_string('title', 'local_extendednav'), ['size' => '50']);
        $mform->setType('title', PARAM_TEXT);
        $mform->addHelpButton('title', 'title', 'local_extendednav');

        $mform->addElement('text', 'url', get_string('url', 'local_extendednav'), ['size' => '60']);
        $mform->setType('url', PARAM_RAW);
        $mform->addHelpButton('url', 'url', 'local_extendednav');
        
        $mform->addElement('advcheckbox', 'newwindow', get_string('newwindow', 'local_extendednav'));
        $mform->addHelpButton('newwindow', 'newwindow', 'local_extendednav');

        $mform->addElement('text', 'icon', get_string('icon', 'local_extendednav'), ['size' => '30']);
        $mform->setType('icon', PARAM_RAW);
        $mform->addHelpButton('icon', 'icon', 'local_extendednav');

        $visoptions = [
            1 => get_string('vis_all', 'local_extendednav'),
            2 => get_string('vis_roles', 'local_extendednav'),
            0 => get_string('vis_hidden', 'local_extendednav'),
        ];
        $mform->addElement('select', 'visibility', get_string('visibility', 'local_extendednav'), $visoptions);
        $mform->setDefault('visibility', 1);

        $roles = $DB->get_records('role', null, '', 'id, shortname, name');
        $options = [];
        foreach ($roles as $role) {
            $options[$role->id] = $role->name . ' (' . $role->shortname . ')';
        }
        $mform->addElement('autocomplete', 'roles', get_string('roles', 'local_extendednav'), $options, ['multiple' => true]);
        $mform->addHelpButton('roles', 'roles', 'local_extendednav');
        $mform->hideIf('roles', 'visibility', 'neq', 2);

        $current_id = optional_param('id', 0, PARAM_INT);
        $current_nodekey = '';
        $is_parent = false;

        if ($current_id) {
            $current_record = $DB->get_record('local_extendednav', ['id' => $current_id]);
            if ($current_record) {
                $current_nodekey = $current_record->nodekey;
                $is_parent = $DB->record_exists('local_extendednav', ['parentkey' => $current_nodekey]);
            }
        }

        $parent_options = ['' => get_string('opt_none_root', 'local_extendednav')];
        $before_options = ['' => get_string('opt_end_list', 'local_extendednav')];
        
        foreach ($core_keys as $ckey) {
            if ($ckey !== $current_nodekey) {
                $child = $primary->get($ckey);
                if ($child) {
                    $clean_text = strip_tags((string)$child->text);
                    $a = new \stdClass();
                    $a->text = $clean_text;
                    $a->key = $ckey;
                    
                    if ($ckey !== 'siteadminnode') {
                        $parent_options[$ckey] = get_string('opt_native', 'local_extendednav', $a);
                    }
                    $before_options[$ckey] = get_string('opt_native', 'local_extendednav', $a);
                }
            }
        }

        $customs = $DB->get_records('local_extendednav', null, 'sortorder ASC', 'id, nodekey, title, parentkey');
        $custom_keys = [];
        foreach ($customs as $c) {
            if ($c->nodekey === $current_nodekey) {
                continue;
            }

            $custom_keys[] = $c->nodekey;

            $title = $c->title ? $c->title : get_string('none_title', 'local_extendednav');
            
            $a = new \stdClass();
            $a->title = $title;
            $a->key = $c->nodekey;
            
            if (empty($c->parentkey)) {
                $parent_options[$c->nodekey] = get_string('opt_plugin', 'local_extendednav', $a);
            }
            $before_options[$c->nodekey] = get_string('opt_plugin', 'local_extendednav', $a);
        }

        if ($is_parent) {
            $parent_options = ['' => get_string('opt_invalid_has_submenus', 'local_extendednav')];
        }
        if ($current_nodekey === 'siteadminnode') {
            $parent_options = ['' => get_string('opt_invalid_admin_submenu', 'local_extendednav')];
        }

        $mform->addElement('select', 'parentkey', get_string('parentkey', 'local_extendednav'), $parent_options);
        $mform->addHelpButton('parentkey', 'parentkey', 'local_extendednav');
        if ($is_parent || $current_nodekey === 'siteadminnode') {
            $mform->freeze('parentkey');
        }

        $mform->addElement('select', 'beforekey', get_string('beforekey', 'local_extendednav'), $before_options);
        $mform->addHelpButton('beforekey', 'beforekey', 'local_extendednav');

        $this->add_action_buttons(true, get_string('savechanges'));

        $req_string = get_string('requiredelement', 'form');
        $req_icon_html = \html_writer::span(
            $OUTPUT->pix_icon('req', $req_string) . ' ',
            'req text-danger',
            ['title' => $req_string]
        );

        $PAGE->requires->js_call_amd('local_extendednav/node_form', 'init', [$core_keys, $req_icon_html, $custom_keys]);
    }

    /**
     * Validates form submission data.
     *
     * @param array $data Passed form fields.
     * @param array $files Uploaded files.
     * @return array Array of errors.
     */
    public function validation($data, $files) {
        global $DB, $PAGE;
        $errors = parent::validation($data, $files);
        
        if ($data['visibility'] == 2 && empty($data['roles'])) {
            $errors['roles'] = get_string('required');
        }

        $is_core = false;
        try {
            \local_extendednav\hooks::$skip_hook = true;
            $temp_page = new \moodle_page();
            $temp_page->set_context(\context_system::instance());
            $temp_page->set_url($PAGE->url);
            $primary = new \core\navigation\views\primary($temp_page);
            $primary->initialise();
            foreach ($primary->children as $child) {
                if ($child->key === $data['nodekey']) {
                    $is_core = true;
                    break;
                }
            }
            \local_extendednav\hooks::$skip_hook = false;
        } catch (\Exception $e) {
            \local_extendednav\hooks::$skip_hook = false;
        }

        if (!$is_core) {
            if (empty(trim((string)$data['title']))) {
                $errors['title'] = get_string('required');
            }
            if (empty(trim((string)$data['url']))) {
                $errors['url'] = get_string('required');
            }
        }
        
        $existing = $DB->get_record('local_extendednav', ['nodekey' => $data['nodekey']], '*', IGNORE_MULTIPLE);
        if ($existing && $existing->id != $data['id']) {
            $errors['nodekey'] = get_string('err_duplicate_key', 'local_extendednav');
        }
        
        if ($data['nodekey'] === 'siteadminnode') {
            if ($data['visibility'] != 1) {
                $errors['visibility'] = get_string('err_admin_hide', 'local_extendednav');
            }
            if (!empty($data['parentkey'])) {
                $errors['parentkey'] = get_string('err_admin_child', 'local_extendednav');
            }
        }
        
        if (!empty($data['parentkey']) && $data['parentkey'] === 'siteadminnode') {
            $errors['parentkey'] = get_string('err_admin_parent', 'local_extendednav');
        }

        if (!empty($data['parentkey'])) {
            if ($data['parentkey'] === $data['nodekey']) {
                $errors['parentkey'] = get_string('err_parent_self', 'local_extendednav');
            } else {
                $parent_record = $DB->get_record('local_extendednav', ['nodekey' => $data['parentkey']]);
                if ($parent_record && !empty($parent_record->parentkey)) {
                    $errors['parentkey'] = get_string('err_parent_thirdlevel', 'local_extendednav');
                }
                
                $has_children = $DB->record_exists('local_extendednav', ['parentkey' => $data['nodekey']]);
                if ($has_children) {
                    $errors['parentkey'] = get_string('err_parent_haschildren', 'local_extendednav');
                }
            }
        }
        
        return $errors;
    }
}
