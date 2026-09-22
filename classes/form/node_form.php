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
        global $DB, $PAGE;
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'nodekey', get_string('nodekey', 'local_extendednav'), ['size' => '30']);
        $mform->setType('nodekey', PARAM_ALPHANUMEXT);
        $mform->addRule('nodekey', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('nodekey', 'nodekey', 'local_extendednav');

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

        $parent_options = ['' => '- Ninguno / Raíz Principal -'];
        $before_options = ['' => '- Final de la lista actual -'];
        
        try {
            $primary = new \core\navigation\views\primary($PAGE);
            $primary->initialise();
            foreach ($primary->children as $child) {
                if ($child->key && $child->key !== $current_nodekey) {
                    $clean_text = strip_tags((string)$child->text);
                    
                    if ($child->key !== 'siteadminnode') {
                        $parent_options[$child->key] = 'Nativo: ' . $clean_text . ' (' . $child->key . ')';
                    }
                    $before_options[$child->key] = 'Nativo: ' . $clean_text . ' (' . $child->key . ')';
                }
            }
        } catch (\Exception $e) {
        }

        $customs = $DB->get_records('local_extendednav', null, 'sortorder ASC', 'id, nodekey, title, parentkey');
        foreach ($customs as $c) {
            if ($c->nodekey === $current_nodekey) {
                continue;
            }

            $title = $c->title ? $c->title : 'Sin título';
            
            if (empty($c->parentkey)) {
                $parent_options[$c->nodekey] = 'Plugin: ' . $title . ' (' . $c->nodekey . ')';
            }
            
            $before_options[$c->nodekey] = 'Plugin: ' . $title . ' (' . $c->nodekey . ')';
        }

        if ($is_parent) {
            $parent_options = ['' => '- Inválido: Este elemento ya contiene sub-menús -'];
        }
        if ($current_nodekey === 'siteadminnode') {
            $parent_options = ['' => '- Inválido: El panel admin no puede ser sub-menú -'];
        }

        $mform->addElement('select', 'parentkey', get_string('parentkey', 'local_extendednav'), $parent_options);
        $mform->addHelpButton('parentkey', 'parentkey', 'local_extendednav');
        if ($is_parent || $current_nodekey === 'siteadminnode') {
            $mform->freeze('parentkey');
        }

        $mform->addElement('select', 'beforekey', get_string('beforekey', 'local_extendednav'), $before_options);
        $mform->addHelpButton('beforekey', 'beforekey', 'local_extendednav');

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Validates form submission data.
     *
     * @param array $data Passed form fields.
     * @param array $files Uploaded files.
     * @return array Array of errors.
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        
        if ($data['visibility'] == 2 && empty($data['roles'])) {
            $errors['roles'] = get_string('required');
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
