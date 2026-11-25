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

#[\PHPUnit\Framework\Attributes\CoversClass(\local_extendednav\menu_manager::class)]
/**
 * Tests for whether the plugin changes anything in a request.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\menu_manager
 */
final class menu_manager_test extends \advanced_testcase {
    /**
     * The plugin is on when it is enabled and its database is the one of its files.
     */
    public function test_on_when_enabled_and_upgraded(): void {
        $this->resetAfterTest();
        set_config('enable_plugin', 1, 'local_extendednav');

        $this->assertFalse(menu_manager::upgrade_pending());
        $this->assertTrue(menu_manager::is_on());
        $this->assertTrue(menu_manager::is_enabled('usermenu'));
    }

    /**
     * The plugin is off when it is not enabled.
     */
    public function test_off_when_disabled(): void {
        $this->resetAfterTest();
        set_config('enable_plugin', 0, 'local_extendednav');

        $this->assertFalse(menu_manager::is_on());
        $this->assertFalse(menu_manager::is_enabled('usermenu'));
    }

    /**
     * While the upgrade of the plugin is pending nothing of it runs: the files are new and the tables may be old.
     */
    public function test_off_while_upgrade_pending(): void {
        global $CFG, $PAGE;

        $this->resetAfterTest();
        set_config('enable_plugin', 1, 'local_extendednav');
        set_config('version', 2025010100, 'local_extendednav');

        $this->assertTrue(menu_manager::upgrade_pending());
        $this->assertFalse(menu_manager::is_on());
        $this->assertFalse(menu_manager::is_enabled('usermenu'));
        $this->assertFalse(menu_manager::is_enabled('primary'));

        // The user menu is left as Moodle has it, without reading the tables of the plugin.
        $this->setAdminUser();
        $CFG->customusermenuitems = 'profile,moodle|/user/profile.php';
        hooks::reset();
        hooks::before_standard_head(
            new \core\hook\output\before_standard_head_html_generation($PAGE->get_renderer('core'))
        );
        $this->assertSame('profile,moodle|/user/profile.php', $CFG->customusermenuitems);
    }

    /**
     * The page of the state lists every navigation once, in groups, with its own switch and the one of the plugin.
     */
    public function test_status_context(): void {
        $this->resetAfterTest();
        set_config('enable_plugin', 0, 'local_extendednav');
        set_config('enabled_course', 0, 'local_extendednav');

        $context = menu_manager::get_status_context();

        $this->assertFalse($context['pluginenabled']);
        $this->assertFalse($context['upgradepending']);
        $rows = [];
        $listed = 0;
        foreach ($context['groups'] as $group) {
            $this->assertNotEmpty($group['rows']);
            $this->assertNotEmpty($group['countlabel']);
            foreach ($group['rows'] as $row) {
                $rows[$row['key']] = $row;
                $listed++;
            }
        }
        $this->assertEqualsCanonicalizing(array_keys(menu_manager::get_menus()), array_keys($rows));
        $this->assertSame(count($rows), $listed, 'No navigation is listed twice.');
        $this->assertFalse($rows['course']['enabled']);
        $this->assertTrue($rows['primary']['enabled'], 'Each navigation shows its own state while the plugin is off.');
        $this->assertSame(['site', 'secondary'], array_slice(array_column($context['groups'], 'id'), 0, 2));
        $this->assertSame('admin', array_column($context['groups'], 'id')[count($context['groups']) - 1]);
    }
}
