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
 * Form to upload a YAML file of nodes.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_form extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement(
            'filepicker',
            'yamlfile',
            get_string('yamlconfigfile', 'local_extendednav'),
            null,
            ['maxfiles' => 1, 'accepted_types' => ['.yaml', '.yml']]
        );
        $mform->addRule('yamlfile', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('yamlfile', 'yamlconfigfile', 'local_extendednav');

        $mform->addElement('select', 'conflict_action', get_string('conflict_handling', 'local_extendednav'), [
            'skip' => get_string('conflict_skip', 'local_extendednav'),
            'overwrite' => get_string('conflict_overwrite', 'local_extendednav'),
        ]);
        $mform->setDefault('conflict_action', 'skip');
        $mform->addHelpButton('conflict_action', 'conflict_handling', 'local_extendednav');

        $this->add_action_buttons(true, get_string('import', 'local_extendednav'));
    }
}
