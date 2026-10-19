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

namespace local_extendednav\external;

#[\PHPUnit\Framework\Attributes\CoversClass(\local_extendednav\external\get_icons::class)]
/**
 * Tests for the icon picker web service.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\external\get_icons
 */
final class get_icons_test extends \advanced_testcase {
    /**
     * Users who can manage the navigation get the icons.
     */
    public function test_get_icons(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/extendednav:manage', CAP_ALLOW, $roleid, \context_system::instance());
        role_assign($roleid, $user->id, \context_system::instance());
        $this->setUser($user);

        $result = \core_external\external_api::clean_returnvalue(get_icons::execute_returns(), get_icons::execute());
        $this->assertNotEmpty($result['moodle']);
        $this->assertNotEmpty($result['fontawesome']);
        $this->assertNotEmpty($result['brands']);
        $this->assertContains('core:i/home', array_column($result['moodle'], 'value'));
    }

    /**
     * Other users do not.
     */
    public function test_get_icons_needs_capability(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(\required_capability_exception::class);
        get_icons::execute();
    }
}
