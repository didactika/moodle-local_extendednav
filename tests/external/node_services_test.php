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

use core_external\external_api;
use local_extendednav\local\node\access;
use local_extendednav\menu_manager;

/**
 * Tests for the web services of the management page.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\external\get_nodes
 * @covers     \local_extendednav\external\get_exceptions
 * @covers     \local_extendednav\external\change_nodes
 */
final class node_services_test extends \advanced_testcase {
    /**
     * Gives the course navigation three nodes and a user who manages the navigation.
     *
     * @return \stdClass The user.
     */
    private function prepare(): \stdClass {
        $this->resetAfterTest();
        $seen = [];
        foreach (['one', 'two', 'three'] as $key) {
            $seen[$key] = (object)['text' => ucfirst($key), 'action' => '/' . $key . '/'];
        }
        $this->setAdminUser();
        menu_manager::get_menu('course')->record_seen($seen);
        menu_manager::get_menu('mod_forum')->record_seen($seen);

        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/extendednav:manage', CAP_ALLOW, $roleid, \context_system::instance());
        role_assign($roleid, $user->id, \context_system::instance());
        $this->setUser($user);
        return $user;
    }

    /**
     * The list is returned in the form of the description.
     */
    public function test_get_nodes(): void {
        $this->prepare();

        $result = external_api::clean_returnvalue(get_nodes::execute_returns(), get_nodes::execute('course', 0, '', '', '', 0, 12));

        $this->assertSame(3, $result['total']);
        $this->assertSame(['one', 'two', 'three'], array_column($result['rows'], 'nodekey'));
        $this->assertTrue($result['reorderable']);
    }

    /**
     * Whoever may not manage the navigation cannot list it, change it or see its scopes.
     */
    public function test_the_capability_is_required(): void {
        $this->prepare();
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(\required_capability_exception::class);
        get_nodes::execute('course');
    }

    /**
     * A navigation that does not exist and a scope that cannot be are refused.
     */
    public function test_the_navigation_and_the_scope_are_checked(): void {
        $this->prepare();
        $category = $this->getDataGenerator()->create_category();

        try {
            get_nodes::execute('nothing');
            $this->fail('An unknown navigation is refused.');
        } catch (\invalid_parameter_exception $e) {
            $this->assertStringContainsString('Unknown', $e->getMessage());
        }
        try {
            get_nodes::execute('primary', \context_coursecat::instance($category->id)->id);
            $this->fail('The primary navigation has no exceptions.');
        } catch (\invalid_parameter_exception $e) {
            $this->assertStringContainsString('no exceptions', $e->getMessage());
        }
        $this->expectException(\invalid_parameter_exception::class);
        get_nodes::execute('course', 0, '', '3');
    }

    /**
     * Nodes are hidden and shown, in the site and as an exception.
     */
    public function test_set_visibility(): void {
        global $DB;

        $this->prepare();
        $course = $this->getDataGenerator()->create_course();
        $contextid = \context_course::instance($course->id)->id;

        $result = change_nodes::set_visibility('course', 0, ['one', 'two'], access::VISIBILITY_HIDDEN);
        $this->assertSame(2, $result['count']);
        $this->assertSame(2, $DB->count_records('local_extendednav', ['menu' => 'course', 'visibility' => 0]));

        change_nodes::set_visibility('course', $contextid, ['one'], access::VISIBILITY_ALL);
        $scope = get_nodes::execute('course', $contextid);
        $rows = array_column($scope['rows'], null, 'nodekey');
        $this->assertSame(access::VISIBILITY_ALL, $rows['one']['visibility']);
        $this->assertTrue($rows['one']['exception']);
        $this->assertSame(access::VISIBILITY_HIDDEN, $rows['two']['visibility']);
        $this->assertFalse($rows['two']['exception']);

        $this->expectException(\invalid_parameter_exception::class);
        change_nodes::set_visibility('course', 0, ['one'], access::VISIBILITY_ROLES);
    }

    /**
     * A node moves, and the order is the one the list returns.
     */
    public function test_move(): void {
        $this->prepare();

        change_nodes::move('course', 0, 'three', -1);

        $this->assertSame(['one', 'three', 'two'], array_column(get_nodes::execute('course')['rows'], 'nodekey'));
        $this->expectException(\invalid_parameter_exception::class);
        change_nodes::move('course', 0, 'one', 2);
    }

    /**
     * Moodle's own nodes stay, the exceptions of a scope go back to the site.
     */
    public function test_delete(): void {
        global $DB;

        $this->prepare();
        $course = $this->getDataGenerator()->create_course();
        $contextid = \context_course::instance($course->id)->id;
        change_nodes::set_visibility('course', $contextid, ['one'], access::VISIBILITY_HIDDEN);

        $this->assertSame(0, change_nodes::delete('course', 0, ['one'])['count'], 'A node of Moodle is not deleted.');
        $this->assertSame(1, change_nodes::delete('course', $contextid, ['one'])['count']);
        $left = $DB->count_records_select('local_extendednav', 'menu = :menu AND contextid <> 0', ['menu' => 'course']);
        $this->assertSame(0, $left);
    }

    /**
     * The overview lists the categories and courses that change a navigation, with the nodes of each, and finds them by name.
     */
    public function test_get_exceptions(): void {
        $this->prepare();
        $category = $this->getDataGenerator()->create_category(['name' => 'Humanities']);
        $course = $this->getDataGenerator()->create_course(['fullname' => 'Ancient history', 'category' => $category->id]);
        $contextid = \context_course::instance($course->id)->id;
        change_nodes::set_visibility('course', $contextid, ['one', 'two'], access::VISIBILITY_HIDDEN);
        change_nodes::set_visibility('course', \context_coursecat::instance($category->id)->id, ['one'], access::VISIBILITY_HIDDEN);
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        change_nodes::set_visibility('mod_forum', \context_module::instance($forum->cmid)->id, ['one'], access::VISIBILITY_HIDDEN);

        $all = external_api::clean_returnvalue(get_exceptions::execute_returns(), get_exceptions::execute());
        $this->assertSame(3, $all['total']);
        $this->assertSame(['category' => 1, 'course' => 1, 'activity' => 1], $all['counts']);
        $activity = array_values(array_filter($all['rows'], static fn(array $row): bool => $row['kind'] === 'activity'))[0];
        $this->assertSame('Humanities / Ancient history', $activity['path']);
        $this->assertSame('mod_forum', $activity['component']);
        $this->assertSame(1, get_exceptions::execute('', 'activity')['total']);
        $course = array_values(array_filter($all['rows'], static fn(array $row): bool => $row['kind'] === 'course'))[0];
        $this->assertSame(2, $course['total']);
        $this->assertSame('Humanities', $course['path']);

        $this->assertSame(2, get_exceptions::execute('ancient')['total'], 'An activity is found by its course.');
        $this->assertSame(1, get_exceptions::execute('', 'category')['total']);
        $this->assertSame(0, get_exceptions::execute('nothing')['total']);
    }

    /**
     * Each kind of change takes its own capability in a course: a teacher who may order the nodes but not hide them sees the
     * list with the order and without the eye, and is refused when hiding; one who may only see the navigation sees it.
     */
    public function test_each_change_takes_its_capability(): void {
        $this->prepare();
        $course = $this->getDataGenerator()->create_course();
        $context = \context_course::instance($course->id);
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'student');
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/extendednav:managecontext', CAP_ALLOW, $roleid, $context);
        assign_capability('local/extendednav:changeorder', CAP_ALLOW, $roleid, $context);
        role_assign($roleid, $teacher->id, $context);
        $this->setUser($teacher);

        $list = external_api::clean_returnvalue(get_nodes::execute_returns(), get_nodes::execute('course', $context->id));
        $this->assertTrue($list['reorderable']);
        $this->assertFalse($list['rows'][0]['canhide'], 'Hiding takes its own capability.');
        $scope = \local_extendednav\local\navigation\context_scope::from_id($context->id);
        $this->assertTrue($scope->can('changeorder'));
        $this->assertFalse($scope->can('changevisibility'));
        $this->assertContains('visibility', $scope->locked_fields());
        $this->assertNotContains('parentkey', $scope->locked_fields());

        try {
            change_nodes::set_visibility('course', $context->id, ['one'], access::VISIBILITY_HIDDEN);
            $this->fail('Hiding needs local/extendednav:changevisibility.');
        } catch (\required_capability_exception $e) {
            $this->assertSame('local/extendednav:changevisibility', $e->a['capability'] ?? 'local/extendednav:changevisibility');
        }

        // A teacher who may only see the navigation of the course reads it and changes nothing.
        $viewer = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($viewer->id, $course->id, 'student');
        $viewrole = $this->getDataGenerator()->create_role();
        assign_capability('local/extendednav:viewcontext', CAP_ALLOW, $viewrole, $context);
        role_assign($viewrole, $viewer->id, $context);
        $this->setUser($viewer);
        $this->assertNotEmpty(get_nodes::execute('course', $context->id)['rows']);
        $this->expectException(\required_capability_exception::class);
        change_nodes::place('course', $context->id, ['one'], '', 'two');
    }

    /**
     * Whoever manages the navigation of a course changes its exceptions, and only there.
     */
    public function test_a_course_manager_changes_only_their_course(): void {
        $this->prepare();
        $course = $this->getDataGenerator()->create_course();
        $other = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/extendednav:managecontext', CAP_ALLOW, $roleid, \context_course::instance($course->id));
        assign_capability('local/extendednav:changevisibility', CAP_ALLOW, $roleid, \context_course::instance($course->id));
        role_assign($roleid, $teacher->id, \context_course::instance($course->id));
        // Enrolled in both courses, so only the capability tells them apart.
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($teacher->id, $other->id, 'student');
        $this->setUser($teacher);

        $own = \context_course::instance($course->id)->id;
        $this->assertSame(1, change_nodes::set_visibility('course', $own, ['one'], access::VISIBILITY_HIDDEN)['count']);

        $this->expectException(\required_capability_exception::class);
        change_nodes::set_visibility('course', \context_course::instance($other->id)->id, ['one'], access::VISIBILITY_HIDDEN);
    }

    /**
     * Nodes are placed before a sibling, after one, or at the end, together and in their order, as when they are dragged.
     */
    public function test_place(): void {
        $this->prepare();

        change_nodes::place('course', 0, ['three'], 'one');
        $this->assertSame(['three', 'one', 'two'], array_column(get_nodes::execute('course')['rows'], 'nodekey'));

        change_nodes::place('course', 0, ['three'], '');
        $this->assertSame(['one', 'two', 'three'], array_column(get_nodes::execute('course')['rows'], 'nodekey'));

        // Two nodes move together, in the order they have.
        change_nodes::place('course', 0, ['three', 'two'], '', 'one');
        $this->assertSame(['one', 'two', 'three'], array_column(get_nodes::execute('course')['rows'], 'nodekey'));
        change_nodes::place('course', 0, ['two', 'three'], 'one');
        $this->assertSame(['two', 'three', 'one'], array_column(get_nodes::execute('course')['rows'], 'nodekey'));

        $this->expectException(\invalid_parameter_exception::class);
        change_nodes::place('course', 0, ['one'], 'nothing');
    }

    /**
     * Each navigation is turned on and off by itself.
     */
    public function test_set_enabled(): void {
        $this->prepare();
        $this->assertTrue(\local_extendednav\menu_manager::is_enabled('course'));

        change_nodes::set_enabled('course', false);

        $this->assertFalse(\local_extendednav\menu_manager::is_enabled('course'));
        $this->assertTrue(\local_extendednav\menu_manager::is_enabled('mod_forum'), 'The others stay on.');
        change_nodes::set_enabled('course', true);
        $this->assertTrue(\local_extendednav\menu_manager::is_enabled('course'));

        $this->setUser($this->getDataGenerator()->create_user());
        $this->expectException(\required_capability_exception::class);
        change_nodes::set_enabled('course', false);
    }
}
