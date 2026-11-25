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

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * Steps of Extended navigation.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_extendednav extends behat_base {
    /**
     * Leaves the guided tours of the plugin out of the way of a scenario, which they would otherwise open over the page the first
     * time.
     *
     * @Given /^the guided tours of extended navigation are off$/
     */
    public function the_guided_tours_are_off(): void {
        global $DB;

        $like = $DB->sql_like('name', ':name');
        $DB->set_field_select('tool_usertours_tours', 'enabled', 0, $like, ['name' => '%,local_extendednav']);
        \tool_usertours\cache::notify_tour_change();
    }

    /**
     * Opens the page Preferences of a user, as an administrator does from the profile of that user.
     *
     * @When /^I visit the preferences page of "(?P<username>(?:[^"]|\\")*)"$/
     * @param string $username The user.
     */
    public function i_visit_the_preferences_page_of(string $username): void {
        $userid = $this->get_user_id_by_identifier($username);
        $this->getSession()->visit($this->locate_path('/user/preferences.php?userid=' . $userid));
    }
}
