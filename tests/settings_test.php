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

namespace local_extendednav;

/**
 * Tests for the admin pages of the plugin.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class settings_test extends \advanced_testcase {
    /**
     * Loads the admin tree for the current user.
     *
     * @return \admin_root
     */
    private function load_admin_tree(): \admin_root {
        global $CFG;

        require_once($CFG->libdir . '/adminlib.php');
        $root = admin_get_root(true, false);
        // Other plugins may report debugging messages while the tree is built for a non-administrator.
        $this->resetDebugging();
        return $root;
    }

    /**
     * Users with the manage capability reach the management page but not the settings.
     */
    public function test_manager_with_capability(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/extendednav:manage', CAP_ALLOW, $roleid, \context_system::instance());
        role_assign($roleid, $user->id, \context_system::instance());
        $this->setUser($user);

        $root = $this->load_admin_tree();
        $manage = $root->locate('local_extendednav_manage');
        $this->assertInstanceOf(\admin_externalpage::class, $manage);
        $this->assertTrue($manage->check_access());
        $this->assertNotNull($root->locate('appearance')->locate('local_extendednav_cat'));
        $this->assertFalse($root->locate('local_extendednav_settings')->check_access());
    }

    /**
     * Users without the capability do not see the pages.
     */
    public function test_user_without_capability(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $this->assertNull($this->load_admin_tree()->locate('local_extendednav_manage'));
    }

    /**
     * Administrators reach both pages.
     */
    public function test_admin(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $root = $this->load_admin_tree();
        $this->assertTrue($root->locate('local_extendednav_manage')->check_access());
        $this->assertTrue($root->locate('local_extendednav_settings')->check_access());
        $this->assertNotNull($root->locate('appearance')->locate('local_extendednav_cat'));
    }
}
