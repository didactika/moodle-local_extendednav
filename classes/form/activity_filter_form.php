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
 * Native filters for the activity report.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class activity_filter_form extends \moodleform {
    /**
     * Defines date and metadata filters.
     */
    public function definition() {
        $mform = $this->_form;
        $mform->disable_form_change_checker();
        $mform->updateAttributes(['method' => 'get']);
        $mform->updateAttributes(['class' => 'full-width-labels']);

        $mform->addElement(
            'date_selector',
            'datefrom',
            get_string('filter_datefrom', 'local_extendednav')
        );
        $mform->setDefault('datefrom', (int)($this->_customdata['periodstart'] ?? (time() - 30 * DAYSECS)));

        $mform->addElement(
            'date_selector',
            'dateto',
            get_string('filter_dateto', 'local_extendednav')
        );
        $mform->setDefault('dateto', (int)($this->_customdata['periodend'] ?? time()));

        $mform->addElement(
            'text',
            'action',
            get_string('filter_action', 'local_extendednav'),
            ['size' => 40]
        );
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('hidden', 'menu', $this->_customdata['menu'] ?? 'primary');
        $mform->setType('menu', PARAM_ALPHANUMEXT);

        $applybutton = $mform->createElement('submit', 'submitbutton', get_string('apply'));
        $mform->addGroup([$applybutton], 'buttonar', '', ' ', false);
    }

    /**
     * Validates date period.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!empty($data['datefrom']) && !empty($data['dateto']) && $data['dateto'] < $data['datefrom']) {
            $errors['dateto'] = get_string('filter_invalidperiod', 'local_extendednav');
        }

        return $errors;
    }
}
