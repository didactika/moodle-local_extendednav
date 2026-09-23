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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for importing navigation nodes from YAML.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_form extends \moodleform {

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $draftitemid = $data['yamlfile'];
        $fs = get_file_storage();
        $context = \context_user::instance($GLOBALS['USER']->id);
        $draftfiles = $fs->get_area_files($context->id, 'user', 'draft', $draftitemid, '', false);
        if (empty($draftfiles)) {
            $errors['yamlfile'] = get_string('required');
        }
        return $errors;
    }

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('filepicker', 'yamlfile', get_string('yamlconfigfile', 'local_extendednav'), null, ['maxfiles' => 1, 'accepted_types' => ['.yml', '.yaml']]);
        $mform->addRule('yamlfile', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('yamlfile', 'yamlconfigfile', 'local_extendednav');

        $options = [
            'overwrite' => get_string('import_overwrite', 'local_extendednav'),
            'append'    => get_string('import_append', 'local_extendednav')
        ];
        $mform->addElement('select', 'importmode', get_string('importmode', 'local_extendednav'), $options);
        $mform->setDefault('importmode', 'append');
        $mform->addHelpButton('importmode', 'importmode', 'local_extendednav');

        $this->add_action_buttons(true, get_string('import', 'local_extendednav'));
    }
}
