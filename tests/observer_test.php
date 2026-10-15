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

use local_extendednav\local\node_manager;

/**
 * Tests for the synchronisation with Moodle's menu settings and the role observer.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\observer
 */
final class observer_test extends \advanced_testcase {
    /**
     * Returns the node of a menu with a key.
     *
     * @param string $menu Menu key.
     * @param string $nodekey Node key.
     * @return \stdClass|false
     */
    private function get_node(string $menu, string $nodekey) {
        global $DB;
        return $DB->get_record('local_extendednav', ['menu' => $menu, 'nodekey' => $nodekey]);
    }

    /**
     * Custom menu lines are imported with their tooltip and languages, never as icons.
     */
    public function test_custom_menu_fields(): void {
        $this->resetAfterTest();
        set_config('custommenuitems', "Help|/help.php|Get help|en,es\nAyuda|/ayuda.php||es\n-Guides|/guides.php");
        menu_manager::get_menu('primary')->sync_from_config();

        $help = $this->get_node('primary', 'cmenu_help');
        $this->assertSame('Help', $help->title);
        $this->assertSame('/help.php', $help->url);
        $this->assertSame('Get help', $help->tooltip);
        $this->assertSame('en,es', $help->langs);
        $this->assertNull($help->icon);

        $this->assertSame('es', $this->get_node('primary', 'cmenu_ayuda')->langs);
        $this->assertSame('cmenu_ayuda', $this->get_node('primary', 'cmenu_guides')->parentkey);
    }

    /**
     * Lines removed from the setting delete their node; edited lines update it.
     */
    public function test_custom_menu_changes(): void {
        $this->resetAfterTest();
        $menu = menu_manager::get_menu('primary');
        set_config('custommenuitems', "Help|/help.php\nOld|/old.php");
        $menu->sync_from_config();
        $id = $this->get_node('primary', 'cmenu_help')->id;

        set_config('custommenuitems', "Help desk|/help.php");
        $menu->sync_from_config();

        $this->assertFalse($this->get_node('primary', 'cmenu_old'));
        $this->assertFalse($this->get_node('primary', 'cmenu_helpdesk'));
        $this->assertSame('Help desk', $this->get_node('primary', 'cmenu_help')->title);
        $this->assertEquals($id, $this->get_node('primary', 'cmenu_help')->id);
    }

    /**
     * Core user menu items keep empty values while they match Moodle's default.
     */
    public function test_user_menu_core_items_stay_empty(): void {
        $this->resetAfterTest();
        set_config('customusermenuitems', "profile,moodle|/user/profile.php\ngrades,grades|/grade/report/other.php\n"
            . "Library|/library.php|fa-book");
        menu_manager::get_menu('usermenu')->sync_from_config();

        $profile = $this->get_node('usermenu', 'umenu_profilemoodle');
        $this->assertNull($profile->title);
        $this->assertNull($profile->url);

        $grades = $this->get_node('usermenu', 'umenu_gradesgrades');
        $this->assertNull($grades->title);
        $this->assertSame('/grade/report/other.php', $grades->url);

        // Moodle reads user menu lines as text|url, so the rest belongs to the URL.
        $this->assertSame('/library.php|fa-book', $this->get_node('usermenu', 'umenu_library')->url);
    }

    /**
     * Deleting a node removes its line from the setting.
     */
    public function test_delete_removes_line(): void {
        $this->resetAfterTest();
        set_config('custommenuitems', "Help|/help.php\nNews|/news.php");
        menu_manager::get_menu('primary')->sync_from_config();

        node_manager::delete([$this->get_node('primary', 'cmenu_help')]);
        $this->assertSame('News|/news.php', get_config('core', 'custommenuitems'));
    }

    /**
     * The import runs again only when the setting changed since the last import.
     */
    public function test_ensure_synced(): void {
        global $DB;

        $this->resetAfterTest();
        $menu = menu_manager::get_menu('primary');
        $menu->ensure_synced();
        $this->assertFalse($this->get_node('primary', 'cmenu_help'));

        // A change that raised no event, as from the command line.
        $DB->set_field('config', 'value', 'Help|/help.php', ['name' => 'custommenuitems']);
        $menu->ensure_synced();
        $this->assertNotFalse($this->get_node('primary', 'cmenu_help'));
    }

    /**
     * A deleted role is removed from the nodes restricted to it.
     */
    public function test_role_deleted(): void {
        $this->resetAfterTest();
        $roleid = $this->getDataGenerator()->create_role(['shortname' => 'tutor']);
        $generator = $this->getDataGenerator()->get_plugin_generator('local_extendednav');
        $node = $generator->create_node(['nodekey' => 'staff', 'title' => 'Staff', 'url' => '/s.php', 'visibility' => 2,
            'roles' => 'tutor,editingteacher']);
        $other = $generator->create_node(['nodekey' => 'tutoring', 'title' => 'T', 'url' => '/t.php', 'visibility' => 2,
            'roles' => 'tutoring']);

        delete_role($roleid);

        $this->assertSame('editingteacher', $this->get_node('primary', 'staff')->roles);
        $this->assertSame('tutoring', $this->get_node('primary', 'tutoring')->roles);
        $this->assertNotEmpty($node->id);
        $this->assertNotEmpty($other->id);
    }
}
