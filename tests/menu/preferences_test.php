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

namespace local_extendednav\menu;

use local_extendednav\external\set_personal;
use local_extendednav\local\navigation\preferences_injector;
use local_extendednav\local\node\access;
use local_extendednav\local\node\repository;

#[\PHPUnit\Framework\Attributes\CoversClass(\local_extendednav\menu\preferences::class)]
/**
 * Tests for the navigation of the page Preferences and for what each user arranges on it.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\menu\preferences
 * @covers     \local_extendednav\local\navigation\preferences_injector
 * @covers     \local_extendednav\external\set_personal
 */
final class preferences_test extends \advanced_testcase {
    /**
     * Builds settings as Moodle builds them for the page Preferences: two groups with a key, one without (Roles) and an item
     * outside every group.
     *
     * @return \navigation_node
     */
    private function settings(): \navigation_node {
        $settings = new \navigation_node(['text' => 'Preferences', 'key' => 'usercurrentsettings']);
        $account = $settings->add('User account', null, \navigation_node::TYPE_CONTAINER, null, 'useraccount');
        $account->add('Edit profile', new \moodle_url('/user/edit.php'), \navigation_node::TYPE_SETTING, null, 'editprofile');
        $account->add(
            'Change password',
            new \moodle_url('/login/change_password.php'),
            \navigation_node::TYPE_SETTING,
            null,
            'changepassword'
        );
        $account->add(
            'Forum preferences',
            new \moodle_url('/user/forum.php'),
            \navigation_node::TYPE_SETTING,
            null,
            'forumpreferences'
        );
        $blogs = $settings->add('Blogs', null, \navigation_node::TYPE_CONTAINER, null, 'blogs');
        $blogs->add(
            'Blog preferences',
            new \moodle_url('/blog/preferences.php'),
            \navigation_node::TYPE_SETTING,
            null,
            'blogpreferences'
        );
        $roles = $settings->add('Roles', null, \navigation_node::TYPE_SETTING);
        $roles->add('This user’s roles', new \moodle_url('/admin/roles/usersroles.php'), \navigation_node::TYPE_SETTING);
        $settings->add(
            'Autograder preferences',
            new \moodle_url('/report/autograder/preferences.php'),
            \navigation_node::TYPE_SETTING,
            null,
            'autograderpreferences'
        );
        return $settings;
    }

    /**
     * The groups of the page, in order, each with its items.
     *
     * @param \navigation_node $settings The settings.
     * @return array Item keys by group key; the loose items under miscellaneous.
     */
    private function shown(\navigation_node $settings): array {
        $shown = [];
        foreach (preferences_injector::read($settings) as $groupkey => $group) {
            $shown[$groupkey] = array_keys($group['items']);
        }
        return $shown;
    }

    /**
     * The groups and items are recorded with their group, Roles by its first item and the loose ones under Miscellaneous.
     */
    public function test_the_groups_and_items_are_recorded(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        preferences_injector::apply($this->settings(), get_admin());
        $natives = (new preferences())->get_native_nodes();

        $this->assertSame('', $natives['useraccount']->parentkey);
        $this->assertSame('useraccount', $natives['changepassword']->parentkey);
        $this->assertArrayHasKey('group_admin_roles_usersroles', $natives, 'A group without a key is known by its first item.');
        $this->assertSame('miscellaneous', $natives['autograderpreferences']->parentkey);
        $this->assertTrue(repository::get_menu_nodes_by_key(preferences::KEY) !== [], 'Every group and item has its row.');
    }

    /**
     * The site hides, renames, orders and adds items; the user's own order and hidden items come on top.
     */
    public function test_the_site_and_the_user_arrange_the_page(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        access::reset_caches();
        preferences_injector::apply($this->settings(), $user);

        $generator = $this->getDataGenerator()->get_plugin_generator('local_extendednav');
        $generator->create_node(['menu' => 'preferences', 'nodekey' => 'changepassword', 'visibility' => 0,
            'parentkey' => 'useraccount']);
        $generator->create_node(['menu' => 'preferences', 'nodekey' => 'blogs', 'title' => 'Weblogs', 'parentkey' => '']);
        $generator->create_node(['menu' => 'preferences', 'nodekey' => 'library', 'title' => 'Library',
            'url' => '/library.php', 'parentkey' => 'blogs']);
        repository::purge();
        access::reset_caches();

        $settings = $this->settings();
        preferences_injector::apply($settings, $user);
        $shown = $this->shown($settings);
        $this->assertNotContains('changepassword', $shown['useraccount'], 'The site hides an item.');
        $this->assertContains('library', $shown['blogs'], 'The site adds an item to a group.');
        $this->assertSame('Weblogs', $settings->get('blogs')->text, 'The site renames a group.');
        $this->assertContains(preferences::PERSONAL, array_map(
            static fn($node) => $node->key,
            iterator_to_array($settings->get('useraccount')->children)
        ), 'The user finds the way to arrange the page.');

        preferences_injector::set_personal((int)$user->id, ['hidden' => ['forumpreferences'],
            'order' => ['' => ['blogs', 'useraccount']]]);
        $settings = $this->settings();
        preferences_injector::apply($settings, $user);
        $shown = $this->shown($settings);
        $this->assertSame(['editprofile'], $shown['useraccount'], 'The user hides an item.');
        $this->assertSame('blogs', array_key_first($shown), 'The user orders the groups.');
        $this->assertSame('miscellaneous', array_key_last($shown), 'The items outside every group stay at the end.');
    }

    /**
     * A user who hides every item still finds the way back.
     */
    public function test_the_way_back_is_never_hidden(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        preferences_injector::set_personal((int)$user->id, ['hidden' => ['editprofile', 'changepassword',
            'forumpreferences', 'blogs', 'group_admin_roles_usersroles', 'miscellaneous'], 'order' => []]);

        $settings = $this->settings();
        preferences_injector::apply($settings, $user);

        $this->assertNotFalse($settings->find(preferences::PERSONAL, null));
    }

    /**
     * Without the capability, a user sees what the site sets and nothing of their own.
     */
    public function test_personalising_takes_the_capability(): void {
        global $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->assertTrue(preferences_injector::can_personalise(), 'Every user may arrange their page by default.');

        $role = $DB->get_field('role', 'id', ['shortname' => 'user']);
        assign_capability('local/extendednav:personalise', CAP_PROHIBIT, $role, \context_system::instance()->id, true);
        accesslib_clear_all_caches_for_unit_testing();
        preferences_injector::set_personal((int)$user->id, ['hidden' => ['editprofile'], 'order' => []]);

        $settings = $this->settings();
        preferences_injector::apply($settings, $user);
        $this->assertFalse(preferences_injector::can_personalise());
        $this->assertFalse($settings->find(preferences::PERSONAL, null));
        $this->assertContains('editprofile', $this->shown($settings)['useraccount']);

        $this->expectException(\required_capability_exception::class);
        set_personal::execute(['editprofile'], []);
    }

    /**
     * The service saves what the user chose, and empty lists take the page back to what the site shows.
     */
    public function test_the_service_saves_and_restores(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        set_personal::execute(['blogpreferences'], [['group' => '', 'keys' => ['blogs', 'useraccount']]]);
        $personal = preferences_injector::get_personal((int)$user->id);
        $this->assertSame(['blogpreferences'], $personal['hidden']);
        $this->assertSame(['blogs', 'useraccount'], $personal['order']['']);

        set_personal::execute([], []);
        $this->assertNull(get_user_preferences(preferences_injector::PREFERENCE, null, $user->id));
    }

    /**
     * The profile records its boxes and shows the links of the site in them, for the users they are meant for.
     */
    public function test_the_profile_gets_the_links_of_the_site(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        \core_user\output\myprofile\manager::build_tree($user, true);
        $boxes = (new profile())->get_native_nodes();
        $this->assertArrayHasKey('contact', $boxes);
        $this->assertTrue(access::is_protected(repository::get_menu_nodes_by_key(profile::KEY)['contact']));

        $generator = $this->getDataGenerator()->get_plugin_generator('local_extendednav');
        $generator->create_node(['menu' => 'profile', 'nodekey' => 'handbook', 'title' => 'Handbook',
            'url' => '/handbook.php', 'parentkey' => 'contact']);
        $generator->create_node(['menu' => 'profile', 'nodekey' => 'secret', 'title' => 'Secret',
            'url' => '/secret.php', 'parentkey' => 'contact', 'visibility' => 0]);
        repository::purge();

        $tree = \core_user\output\myprofile\manager::build_tree($user, true);
        $nodes = $tree->categories['contact']->nodes;
        $this->assertArrayHasKey('local_extendednav_handbook', $nodes);
        $this->assertArrayNotHasKey('local_extendednav_secret', $nodes);
    }
}
