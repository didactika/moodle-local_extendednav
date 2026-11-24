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

use local_extendednav\local\navigation\context_scope;
use local_extendednav\menu_manager;

/**
 * Chooses what a file of the site holds: the navigations, whether the nodes of the site, and the categories, courses and
 * activities whose changes go with them.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_form extends \moodleform {
    /** @var int Contexts offered at most, the ones that change most nodes first. */
    private const MAX_CONTEXTS = 1000;

    /**
     * Form definition.
     */
    protected function definition() {
        global $DB;

        $mform = $this->_form;
        $menus = [];
        foreach (menu_manager::get_menus() as $key => $menu) {
            $menus[$key] = $menu->get_name();
        }
        $mform->addElement('autocomplete', 'menus', get_string('export_menus', 'local_extendednav'), $menus, ['multiple' => true]);
        $mform->setDefault('menus', [$this->_customdata['menu'] ?? 'primary']);
        $mform->addRule('menus', get_string('required'), 'required', null, 'client');

        $mform->addElement('advcheckbox', 'withsite', get_string('export_withsite', 'local_extendednav'));
        $mform->setDefault('withsite', 1);

        // The categories, courses and activities that change a navigation.
        $contexts = [];
        $records = $DB->get_records_sql(
            'SELECT contextid, COUNT(1) AS nodes
               FROM {local_extendednav}
              WHERE contextid <> 0
           GROUP BY contextid
           ORDER BY COUNT(1) DESC, contextid ASC',
            [],
            0,
            self::MAX_CONTEXTS
        );
        foreach ($records as $record) {
            $scope = context_scope::from_id((int)$record->contextid);
            if ($scope !== null) {
                $kind = get_string('kind_' . $scope->kind(), 'local_extendednav');
                $contexts[(int)$record->contextid] = $kind . ': ' . $scope->name();
            }
        }
        \core_collator::asort($contexts);
        $mform->addElement('autocomplete', 'contexts', get_string('export_contexts', 'local_extendednav'), $contexts, [
            'multiple' => true,
            'noselectionstring' => get_string('export_contexts_none', 'local_extendednav'),
        ]);
        $mform->addHelpButton('contexts', 'export_contexts', 'local_extendednav');

        $this->add_action_buttons(true, get_string('export', 'local_extendednav'));
    }

    /**
     * Checks that the file holds something.
     *
     * @param array $data Values of the form.
     * @param array $files Files of the form.
     * @return array Errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (empty($data['withsite']) && empty($data['contexts'])) {
            $errors['contexts'] = get_string('export_nothing', 'local_extendednav');
        }
        return $errors;
    }
}
