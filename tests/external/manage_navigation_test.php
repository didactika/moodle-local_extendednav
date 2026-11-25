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

use local_extendednav\local\node\repository;
use local_extendednav\menu_manager;

#[\PHPUnit\Framework\Attributes\CoversClass(\local_extendednav\external\manage_navigation::class)]
/**
 * Tests for the services that manage the navigations from outside Moodle.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\external\manage_navigation
 * @covers     \local_extendednav\external\search_export
 */
final class manage_navigation_test extends \advanced_testcase {
    /**
     * Every navigation of the site is listed, the preferences among them and no profile.
     */
    public function test_the_navigations_are_listed(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $keys = array_column(manage_navigation::get_navigations()['navigations'], null, 'key');
        $this->assertTrue($keys['preferences']['tree']);
        $this->assertTrue($keys['admin']['tree']);
        $this->assertArrayNotHasKey('profile', $keys);
        $this->assertTrue($keys['course']['scopes']);
        $this->assertTrue(manage_navigation::get_navigations()['pluginenabled']);

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\required_capability_exception::class);
        manage_navigation::get_navigations();
    }

    /**
     * A node is added and changed with the checks of the form; a field left out keeps its value.
     */
    public function test_a_node_is_saved(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $added = manage_navigation::save_node('primary', 0, 'library', ['title' => 'Library', 'url' => '/library.php']);
        $this->assertTrue($added['created']);
        $changed = manage_navigation::save_node('primary', 0, 'library', ['visibility' => 0]);
        $this->assertFalse($changed['created']);
        $row = $DB->get_record('local_extendednav', ['id' => $added['id']]);
        $this->assertSame('Library', $row->title, 'A field left out keeps its value.');
        $this->assertSame(0, (int)$row->visibility);

        $this->expectException(\invalid_parameter_exception::class);
        manage_navigation::save_node('primary', 0, 'broken', ['title' => 'Broken', 'icon' => 'not an icon']);
    }

    /**
     * A user changes their own preferences through the service, only in what a user may change.
     */
    public function test_a_user_saves_their_own_preferences(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        menu_manager::get_menu('preferences')->record_seen([
            'useraccount' => (object)['text' => 'User account', 'action' => '', 'parent' => ''],
            'forumpreferences' => (object)['text' => 'Forum preferences', 'action' => '/user/forum.php', 'parent' => 'useraccount'],
        ]);
        $contextid = (int)\context_user::instance($user->id)->id;

        $saved = manage_navigation::save_node('preferences', $contextid, 'forumpreferences', ['visibility' => 0]);
        $this->assertTrue($saved['created']);
        $this->assertSame(0, (int)repository::get_exceptions('preferences', $contextid)[$saved['id']]->visibility);

        $this->expectException(\invalid_parameter_exception::class);
        manage_navigation::save_node('preferences', $contextid, 'forumpreferences', ['url' => '/elsewhere.php']);
    }

    /**
     * A file goes out and comes back through the services; the user it names and the site lacks is left out with a warning.
     */
    public function test_a_file_goes_out_and_comes_back(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $user = $this->getDataGenerator()->create_user(['username' => 'traveller']);
        menu_manager::get_menu('preferences')->record_seen([
            'useraccount' => (object)['text' => 'User account', 'action' => '', 'parent' => ''],
        ]);
        $contextid = (int)\context_user::instance($user->id)->id;
        manage_navigation::save_node('preferences', $contextid, 'useraccount', ['visibility' => 0]);

        $content = manage_navigation::export_navigations(['preferences'], [$contextid])['content'];
        $this->assertStringContainsString('user:traveller', $content);

        delete_user($user);
        $result = manage_navigation::import_navigations($content, 'preferences', true);
        $this->assertSame([], $result['errors']);
        $this->assertSame(1, $result['skipped']);
        $this->assertNotEmpty($result['warnings']);
    }

    /**
     * The functions are gathered in one service, for a system outside Moodle.
     */
    public function test_the_service_holds_every_function(): void {
        global $DB;

        $this->resetAfterTest();
        $service = $DB->get_record('external_services', ['shortname' => 'local_extendednav'], '*', MUST_EXIST);
        $functions = $DB->get_fieldset_select(
            'external_services_functions',
            'functionname',
            'externalserviceid = ?',
            [$service->id]
        );
        foreach (
            ['local_extendednav_save_node', 'local_extendednav_get_nodes', 'local_extendednav_export_navigations',
                'local_extendednav_import_navigations', 'local_extendednav_set_visibility'] as $function
        ) {
            $this->assertContains($function, $functions);
        }
    }

    /**
     * The selects of the export find ten at a time, the first ones before anything is typed.
     */
    public function test_the_export_finds_ten_at_a_time(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        for ($i = 1; $i <= 15; $i++) {
            $this->getDataGenerator()->create_user(['firstname' => 'Ana', 'lastname' => 'Search' . $i]);
            $this->getDataGenerator()->create_course(['fullname' => 'Algebra ' . $i]);
        }

        $this->assertCount(search_export::LIMIT, search_export::execute('users', '')['results']);
        $this->assertCount(search_export::LIMIT, search_export::execute('users', 'Search')['results']);
        $this->assertCount(1, search_export::execute('users', 'Search15')['results']);
        $this->assertCount(search_export::LIMIT, search_export::execute('courses', 'Algebra')['results']);
        $site = array_column(search_export::execute('site', '')['results'], 'value');
        $this->assertContains('preferences', $site);
        $this->assertNotContains('profile', $site);
        $this->assertSame(['mod_forum'], array_column(search_export::execute('kinds', 'Forum')['results'], 'value'));

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\required_capability_exception::class);
        search_export::execute('users', '');
    }

    /**
     * A tab of the plugin is listed at the top of the site administration while it holds nothing, as a folder.
     */
    public function test_an_empty_tab_is_listed(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        manage_navigation::save_node('admin', 0, 'mytools', ['title' => 'My tools']);

        $rows = array_column(\local_extendednav\local\listing\nodes::get('admin', 0)['rows'], null, 'nodekey');
        $this->assertArrayHasKey('mytools', $rows, 'It is not hidden in General.');
        $this->assertTrue($rows['mytools']['haschildren'], 'It opens, to add its pages.');
    }

    /**
     * Hiding from the dialog also blocks the address when asked, never one of another site.
     */
    public function test_hiding_blocks_the_address(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        manage_navigation::save_node('primary', 0, 'inside', ['title' => 'Inside', 'url' => '/inside.php']);
        manage_navigation::save_node('primary', 0, 'outside', ['title' => 'Outside', 'url' => 'https://example.org/']);

        change_nodes::set_visibility('primary', 0, ['inside', 'outside'], 0, 1, 1);
        $rows = $DB->get_records_menu('local_extendednav', ['menu' => 'primary', 'contextid' => 0], '', 'nodekey, blockaccess');
        $this->assertSame(1, (int)$rows['inside']);
        $this->assertSame(0, (int)$rows['outside'], 'An address of another site is never blocked.');

        change_nodes::set_visibility('primary', 0, ['inside'], 1);
        $this->assertSame(
            0,
            (int)$DB->get_field('local_extendednav', 'blockaccess', ['menu' => 'primary', 'nodekey' => 'inside']),
            'A node shown again is not blocked.'
        );
    }
}
