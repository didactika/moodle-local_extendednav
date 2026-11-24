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

use local_extendednav\local\navigation\context_scope;
use local_extendednav\local\navigation\discovery;
use local_extendednav\local\node\repository;
use local_extendednav\menu_manager;

/**
 * Tests for the navigation of each kind of activity: its key, the items found in the activities, the pages that change it
 * and the change from the single navigation of the activities of the versions before 2.0.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\menu\activity
 * @covers     \local_extendednav\menu\secondary
 * @covers     \local_extendednav\local\navigation\discovery
 * @covers     \local_extendednav\local\navigation\context_scope
 * @covers     \local_extendednav\task\split_activity_navigation
 */
final class activity_test extends \advanced_testcase {
    /**
     * Each kind of activity that is turned on has a navigation; a kind turned off or unknown has none.
     */
    public function test_each_kind_has_a_navigation(): void {
        global $DB;

        $this->resetAfterTest();
        activity::reset();
        $this->assertInstanceOf(activity::class, menu_manager::get_menu('mod_forum'));
        $this->assertSame('mod_forum', menu_manager::get_menu('mod_forum')->get_key());
        $this->assertNull(menu_manager::get_menu('mod_nothing'));
        $this->assertNull(menu_manager::get_menu('activity'));
        $this->assertArrayHasKey('mod_assign', menu_manager::get_menus());

        $DB->set_field('modules', 'visible', 0, ['name' => 'forum']);
        activity::reset();
        $this->assertNull(menu_manager::get_menu('mod_forum'));
        $this->assertArrayNotHasKey('mod_forum', menu_manager::get_menus());
        activity::reset();
    }

    /**
     * An item Moodle numbers is known by the path of its address, the same on every page.
     */
    public function test_a_numbered_item_is_known_by_its_path(): void {
        $this->resetAfterTest();
        $this->assertSame('mod_forum_report_summary_index', secondary::key_of_path('/mod/forum/report/summary/index.php?f=3'));
        $this->assertSame('mod_forum_export', secondary::key_of_path('/mod/forum/export.php'));
        $this->assertNull(secondary::key_of_path(''));

        $url = new \moodle_url('/mod/forum/export.php', ['id' => 4]);
        $numbered = \navigation_node::create('Export', $url, \navigation_node::TYPE_CUSTOM, null, 12);
        $this->assertSame('mod_forum_export', secondary::key_of($numbered));
        $url = new \moodle_url('/course/modedit.php');
        $named = \navigation_node::create('Settings', $url, \navigation_node::TYPE_SETTING, null, 'modedit');
        $this->assertSame('modedit', secondary::key_of($named));
    }

    /**
     * The items of a kind are found by building the navigation of its activities, and only that kind gets them.
     */
    public function test_the_items_are_found_in_the_activities(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);

        $result = discovery::kind('forum');

        $this->assertSame(1, $result['activities']);
        $this->assertGreaterThan(0, $result['found']);
        $natives = (new activity('forum'))->get_native_nodes();
        $this->assertArrayHasKey('modedit', $natives);
        $this->assertArrayHasKey('forumsubscriptions', $natives);
        $this->assertArrayNotHasKey('modulepage', $natives, 'The page of the activity itself is not an item.');
        $this->assertArrayNotHasKey('forumsubscriptions', (new activity('assign'))->get_native_nodes());
        $this->assertSame(['activities' => 0, 'found' => 0], discovery::kind('assign'), 'No assignment, nothing found.');
    }

    /**
     * An activity changes its own navigation; a course its own and the one of each kind it has; a category every kind.
     */
    public function test_the_navigations_of_a_context(): void {
        $this->resetAfterTest();
        $category = $this->getDataGenerator()->create_category();
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);

        $activity = context_scope::from_id(\context_module::instance($forum->cmid)->id);
        $this->assertSame(['mod_forum'], $activity->menus());
        $this->assertSame('activity', $activity->kind());
        $this->assertTrue($activity->is_activity());

        $menus = context_scope::from_id(\context_course::instance($course->id)->id)->menus();
        $this->assertSame('course', $menus[0]);
        $this->assertContains('mod_forum', $menus);
        $this->assertNotContains('mod_assign', $menus, 'A course lists the kinds it has.');

        $menus = context_scope::from_id(\context_coursecat::instance($category->id)->id)->menus();
        $this->assertContains('category', $menus);
        $this->assertContains('mod_assign', $menus, 'A category lists every kind.');
    }

    /**
     * The changes of the single navigation of the activities go to each kind that has the item, the nodes added to every
     * kind, and a change made for one activity to its kind only.
     */
    public function test_the_old_navigation_is_given_to_each_kind(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        set_config('nativenodes_activity', json_encode([
            'forumsubscriptions' => ['action' => '/mod/forum/subscribers.php', 'text' => 'Subscriptions'],
            'logreport' => ['action' => '/report/log/', 'text' => 'Logs'],
        ]), 'local_extendednav');
        $forumcontext = \context_module::instance($forum->cmid)->id;
        $rows = [
            ['forumsubscriptions', 0, ['visibility' => 0]],
            ['logreport', 0, ['title' => 'Activity logs']],
            ['help', 0, ['title' => 'Help', 'url' => '/help.php']],
            ['logreport', $forumcontext, ['visibility' => 0]],
        ];
        foreach ($rows as [$key, $contextid, $data]) {
            $DB->insert_record('local_extendednav', (object)($data + [
                'menu' => 'activity', 'nodekey' => $key, 'contextid' => $contextid, 'visibility' => 1, 'sortorder' => 0,
                'timecreated' => 1, 'timemodified' => 2,
            ]));
        }
        set_config('enabled_activity', '0', 'local_extendednav');
        set_config('legacyactivity', 1, 'local_extendednav');

        (new \local_extendednav\task\split_activity_navigation())->execute();

        $get = static fn(string $menu, string $key, int $contextid = 0) => $DB->get_record(
            'local_extendednav',
            ['menu' => $menu, 'nodekey' => $key, 'contextid' => $contextid]
        );
        $this->assertSame('0', (string)$get('mod_forum', 'forumsubscriptions')->visibility);
        $this->assertFalse($get('mod_assign', 'forumsubscriptions'), 'An assignment has no subscriptions.');
        $this->assertSame('Activity logs', $get('mod_forum', 'logreport')->title);
        $this->assertSame('Activity logs', $get('mod_assign', 'logreport')->title);
        $this->assertSame('Help', $get('mod_assign', 'help')->title, 'A node added goes to every kind.');
        $this->assertSame('Help', $get('mod_page', 'help')->title);
        $this->assertNotFalse($get('mod_forum', 'logreport', $forumcontext));
        $this->assertFalse($get('mod_assign', 'logreport', $forumcontext), 'A change of a forum goes to forums only.');
        $this->assertSame(0, $DB->count_records('local_extendednav', ['menu' => 'activity']));
        $this->assertFalse(get_config('local_extendednav', 'legacyactivity'));
        set_config('enable_plugin', 1, 'local_extendednav');
        $this->assertFalse(menu_manager::is_enabled('mod_forum'), 'A navigation turned off stays off for every kind.');
        repository::purge();
    }
}
