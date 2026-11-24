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

use local_extendednav\external\search_export;
use local_extendednav\menu_manager;

/**
 * Chooses what a file of the site holds, in three parts: the navigations (the ones of the site and the kinds of activity) and
 * whether their changes for the whole site go in it; the users whose arrangement of their own Preferences page goes in it,
 * found by a search; and, when wanted, some courses and categories, which otherwise travel with the backups of Moodle.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class export_form extends \moodleform {
    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $current = $this->_customdata['menu'] ?? 'primary';

        $mform->addElement('header', 'navigationshdr', get_string('export_navigations', 'local_extendednav'));
        $mform->setExpanded('navigationshdr');
        $this->add_search('site', get_string('export_site_navigations', 'local_extendednav'));
        $this->add_search('kinds', get_string('export_kinds', 'local_extendednav'));
        if (in_array($current, search_export::SITE, true)) {
            $mform->setDefault('site', [$current]);
        } else if (menu_manager::get_menu($current) !== null) {
            $mform->setDefault('kinds', [$current]);
        }
        $mform->addElement('advcheckbox', 'withsite', get_string('export_withsite', 'local_extendednav'));
        $mform->addHelpButton('withsite', 'export_withsite', 'local_extendednav');
        $mform->setDefault('withsite', 1);

        $mform->addElement('header', 'usershdr', get_string('export_users', 'local_extendednav'));
        $this->add_search('users', get_string('export_users_chosen', 'local_extendednav'));
        $mform->addHelpButton('users', 'export_users_chosen', 'local_extendednav');

        // Courses and categories travel with the backups of Moodle; a file can still hold the changes of some of them.
        $mform->addElement('header', 'contextshdr', get_string('export_contexts', 'local_extendednav'));
        $mform->addElement('static', 'contextsnote', '', get_string('export_contexts_note', 'local_extendednav'));
        $this->add_search('courses', get_string('export_courses', 'local_extendednav'));
        $this->add_search('categories', get_string('export_categories', 'local_extendednav'));

        $this->add_action_buttons(true, get_string('export', 'local_extendednav'));
    }

    /**
     * Adds a select that offers the first results and finds more, a few at a time, as the user types.
     *
     * @param string $kind site, kinds, users, courses or categories.
     * @param string $label Label of the field.
     */
    private function add_search(string $kind, string $label): void {
        $options = [];
        foreach (search_export::find($kind, '') as $result) {
            $options[$result['value']] = $result['label'];
        }
        $current = $this->_customdata['menu'] ?? '';
        if (($kind === 'site' || $kind === 'kinds') && $current !== '' && !isset($options[$current])) {
            $menu = menu_manager::get_menu($current);
            if ($menu !== null && ($kind === 'site') === in_array($current, search_export::SITE, true)) {
                $options[$current] = search_export::label_of($kind, $current);
            }
        }
        $this->_form->addElement('autocomplete', $kind, $label, $options, [
            'multiple' => true,
            'ajax' => 'local_extendednav/form/export_search',
            'valuehtmlcallback' => static fn($value): string => s(search_export::label_of($kind, (string)$value)),
            'noselectionstring' => get_string('export_none', 'local_extendednav'),
            'data-kind' => $kind,
        ]);
    }

    /**
     * Returns what the file holds.
     *
     * @return array|null The navigations (menus) and the contexts, 0 for the whole site; null when the form was not sent.
     */
    public function get_choice(): ?array {
        $data = $this->get_data();
        if ($data === null) {
            return null;
        }
        $menus = array_values((array)($data->site ?? []));
        $menus = array_merge($menus, array_values((array)($data->kinds ?? [])));
        $contexts = !empty($data->withsite) ? [0] : [];
        $users = array_filter(array_map('intval', (array)($data->users ?? [])));
        foreach ($users as $userid) {
            $contexts[] = (int)\context_user::instance($userid)->id;
        }
        if ($users && !in_array('preferences', $menus, true)) {
            $menus[] = 'preferences';
        }
        foreach (array_filter(array_map('intval', (array)($data->courses ?? []))) as $courseid) {
            $contexts[] = (int)\context_course::instance($courseid)->id;
        }
        foreach (array_filter(array_map('intval', (array)($data->categories ?? []))) as $categoryid) {
            $contexts[] = (int)\context_coursecat::instance($categoryid)->id;
        }
        $menus = array_values(array_filter(array_unique($menus), static fn($key): bool => menu_manager::get_menu($key) !== null));
        return ['menus' => $menus, 'contexts' => $contexts];
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
        $menus = !empty($data['site']) || !empty($data['kinds']);
        $where = !empty($data['withsite']) || !empty($data['users']) || !empty($data['courses']) || !empty($data['categories']);
        if (!$where || (!$menus && empty($data['users']))) {
            $errors['withsite'] = get_string('export_nothing', 'local_extendednav');
        }
        return $errors;
    }
}
