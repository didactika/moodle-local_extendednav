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

use local_extendednav\local\listing\nodes;
use local_extendednav\local\navigation\context_scope;
use local_extendednav\local\navigation\preferences_injector;
use local_extendednav\local\node\access;
use local_extendednav\local\node\repository;

#[\PHPUnit\Framework\Attributes\CoversClass(\local_extendednav\menu\preferences::class)]
/**
 * Tests for the navigation of the page Preferences: the sections as folders, what the site changes and what each user arranges
 * as changes of their own context.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\menu\preferences
 * @covers     \local_extendednav\local\navigation\preferences_injector
 * @covers     \local_extendednav\local\navigation\context_scope
 */
final class preferences_test extends \advanced_testcase {
    /**
     * Builds settings as Moodle builds them for the page Preferences: two sections with a key, one without (Roles) and an item
     * outside every section.
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
     * Applies the navigation to fresh settings of a user and returns the sections, in order, with their items.
     *
     * @param \stdClass $user The user whose preferences they are.
     * @return array Item keys by section key; the loose items under miscellaneous.
     */
    private function shown(\stdClass $user): array {
        $settings = $this->settings();
        preferences_injector::apply($settings, $user);
        $shown = [];
        foreach (preferences_injector::read($settings) as $groupkey => $group) {
            $shown[$groupkey] = array_keys($group['items']);
        }
        return $shown;
    }

    /**
     * Saves a change of a user to their own preferences, as the list of their page does.
     *
     * @param \stdClass $user The user.
     * @param array $data Node fields.
     */
    private function personal(\stdClass $user, array $data): void {
        global $DB;

        $contextid = (int)\context_user::instance($user->id)->id;
        $record = \local_extendednav\local\node\manager::prepare($data + ['contextid' => $contextid], 'preferences');
        $record->contextid = $contextid;
        if (isset($data['sortorder'])) {
            $record->sortorder = (int)$data['sortorder'];
        }
        $existing = $DB->get_record('local_extendednav', ['menu' => 'preferences', 'nodekey' => $record->nodekey,
            'contextid' => $contextid]);
        if ($existing) {
            $record->id = $existing->id;
            \local_extendednav\local\node\manager::update($record, false);
        } else {
            \local_extendednav\local\node\manager::create($record, false);
        }
        repository::purge();
        access::reset_caches();
    }

    /**
     * The sections and items are recorded with their section, Roles by its first item and the loose ones under Miscellaneous,
     * and the list shows them as folders.
     */
    public function test_the_sections_are_folders(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->shown(get_admin());
        $natives = (new preferences())->get_native_nodes();
        $this->assertSame('', $natives['useraccount']->parentkey);
        $this->assertSame('useraccount', $natives['changepassword']->parentkey);
        $this->assertArrayHasKey('group_admin_roles_usersroles', $natives, 'A section without a key is known by its first item.');
        $this->assertSame('miscellaneous', $natives['autograderpreferences']->parentkey);
        $this->assertSame(preferences_injector::SECTION, $natives[preferences::PERSONAL]->parentkey);

        $this->assertTrue(nodes::is_tree(preferences::KEY));
        $top = array_column(nodes::get(preferences::KEY, 0)['rows'], 'nodekey');
        $this->assertContains('useraccount', $top);
        $this->assertNotContains('changepassword', $top, 'The items are inside their section.');
        $inside = array_column(nodes::get(preferences::KEY, 0, '', '', 'useraccount')['rows'], 'nodekey');
        $this->assertContains('changepassword', $inside);
    }

    /**
     * The site hides, renames and orders, creates a section of its own and moves items into it.
     */
    public function test_the_site_arranges_the_page(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->shown($user);

        $generator = $this->getDataGenerator()->get_plugin_generator('local_extendednav');
        $generator->create_node(['menu' => 'preferences', 'nodekey' => 'changepassword', 'visibility' => 0,
            'parentkey' => 'useraccount']);
        $generator->create_node(['menu' => 'preferences', 'nodekey' => 'blogs', 'title' => 'Weblogs']);
        $generator->create_node(['menu' => 'preferences', 'nodekey' => 'campus', 'title' => 'Campus']);
        $generator->create_node(['menu' => 'preferences', 'nodekey' => 'library', 'title' => 'Library', 'url' => '/library.php',
            'parentkey' => 'campus']);
        $generator->create_node(['menu' => 'preferences', 'nodekey' => 'forumpreferences', 'parentkey' => 'campus']);
        repository::purge();
        access::reset_caches();

        $settings = $this->settings();
        preferences_injector::apply($settings, $user);
        $this->assertSame('Weblogs', $settings->get('blogs')->text, 'The site renames a section.');
        $shown = $this->shown($user);
        $this->assertNotContains('changepassword', $shown['useraccount'], 'The site hides an item.');
        $this->assertEqualsCanonicalizing(
            ['library', 'forumpreferences'],
            $shown['campus'],
            'A section of the site holds its links and the items moved to it.'
        );
        $this->assertNotContains('forumpreferences', $shown['useraccount'], 'An item moved leaves its section.');
        $this->assertSame('miscellaneous', array_key_last($shown), 'The items outside every section stay at the end.');
    }

    /**
     * A user hides, orders and moves their own items, administrators too, and their own section holds the way to do it.
     */
    public function test_a_user_arranges_their_own_page(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $admin = get_admin();
        $this->shown($admin);

        $this->assertContains(preferences::PERSONAL, $this->shown($admin)[preferences_injector::SECTION]);
        $this->personal($admin, ['nodekey' => 'forumpreferences', 'visibility' => 0, 'parentkey' => 'useraccount']);
        $this->personal($admin, ['nodekey' => 'blogs', 'sortorder' => -2]);
        $this->personal($admin, ['nodekey' => 'useraccount', 'sortorder' => -1]);
        $this->personal($admin, ['nodekey' => 'blogpreferences', 'parentkey' => 'useraccount']);

        $shown = $this->shown($admin);
        $this->assertNotContains('forumpreferences', $shown['useraccount'], 'What a user hides is hidden from them, an admin too.');
        $this->assertContains('blogpreferences', $shown['useraccount'], 'The user moves an item to another section.');
        $this->assertArrayNotHasKey('blogs', $shown, 'A section left empty is not shown.');

        // Another user keeps the page of the site.
        $other = $this->getDataGenerator()->create_user();
        $this->setUser($other);
        $this->assertContains('forumpreferences', $this->shown($other)['useraccount']);
    }

    /**
     * The user's own context is a scope of the preferences only, with the changes a user may make.
     */
    public function test_the_scope_of_a_user(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $scope = context_scope::from_id(\context_user::instance($user->id)->id);
        $this->assertTrue($scope->is_user());
        $this->assertSame([preferences::KEY], $scope->menus());
        $this->assertTrue($scope->can_manage());
        $this->assertTrue($scope->can('changevisibility'));
        $this->assertTrue($scope->can('changeorder'));
        $this->assertFalse($scope->can('changetitles'), 'A user does not rename.');
        $this->assertFalse($scope->can('addnodes'), 'A user does not add links.');
        $this->assertContains('title', $scope->locked_fields());

        $theirs = context_scope::from_id(\context_user::instance($other->id)->id);
        $this->assertFalse($theirs->can_manage(), 'A user does not arrange the page of another.');
        $this->setAdminUser();
        $this->assertTrue($theirs->can_manage(), 'Whoever manages the navigation arranges it, from that user.');
        $this->assertNull(context_scope::from_id(\context_user::instance(guest_user()->id)->id));
    }

    /**
     * Without the capability, a user sees what the site sets and has no way to arrange it.
     */
    public function test_personalising_takes_the_capability(): void {
        global $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->assertArrayHasKey(preferences_injector::SECTION, $this->shown($user), 'Every user may arrange their page.');

        $role = $DB->get_field('role', 'id', ['shortname' => 'user']);
        assign_capability('local/extendednav:personalise', CAP_PROHIBIT, $role, \context_system::instance()->id, true);
        accesslib_clear_all_caches_for_unit_testing();

        $this->assertArrayNotHasKey(preferences_injector::SECTION, $this->shown($user));
        $this->assertFalse(context_scope::from_id(\context_user::instance($user->id)->id)->can_manage());
        $this->expectException(\required_capability_exception::class);
        \local_extendednav\external\get_nodes::execute(preferences::KEY, (int)\context_user::instance($user->id)->id);
    }

    /**
     * The changes of a user are their data: listed, exported and deleted by the privacy API, and gone with the user.
     */
    public function test_the_privacy_of_a_user(): void {
        global $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->shown($user);
        $this->personal($user, ['nodekey' => 'forumpreferences', 'visibility' => 0, 'parentkey' => 'useraccount']);
        $context = \context_user::instance($user->id);

        $contexts = \local_extendednav\privacy\provider::get_contexts_for_userid((int)$user->id)->get_contextids();
        $this->assertSame([(int)$context->id], array_map('intval', $contexts));
        $approved = new \core_privacy\local\request\approved_contextlist($user, 'local_extendednav', [$context->id]);
        \local_extendednav\privacy\provider::export_user_data($approved);
        $writer = \core_privacy\local\request\writer::with_context($context);
        $data = $writer->get_data([get_string('pluginname', 'local_extendednav')]);
        $this->assertSame('forumpreferences', $data->preferences[0]->item);
        \local_extendednav\privacy\provider::delete_data_for_user($approved);
        $this->assertFalse($DB->record_exists('local_extendednav', ['contextid' => $context->id]));

        $this->personal($user, ['nodekey' => 'forumpreferences', 'visibility' => 0, 'parentkey' => 'useraccount']);
        delete_user($user);
        $this->assertFalse($DB->record_exists('local_extendednav', ['contextid' => $context->id]));
    }
}
