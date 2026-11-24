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

namespace local_extendednav\local\listing;

use local_extendednav\menu_manager;
use local_extendednav\local\navigation\injector;
use local_extendednav\local\node\access;
use local_extendednav\local\node\manager;

/**
 * Tests for the list of the nodes of a navigation.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\local\listing\nodes
 * @covers     \local_extendednav\menu\secondary
 */
final class nodes_test extends \advanced_testcase {
    /**
     * Records nodes a course navigation shows.
     *
     * @param string[] $keys Node keys.
     */
    private function see_nodes(array $keys): void {
        $seen = [];
        foreach ($keys as $key) {
            $seen[$key] = (object)['text' => ucfirst($key), 'action' => '/' . $key . '/'];
        }
        menu_manager::get_menu('course')->record_seen($seen);
    }

    /**
     * Every node seen has a row, and a node seen again changes nothing.
     */
    public function test_nodes_seen_get_a_row_once(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $menu = menu_manager::get_menu('course');
        $seen = ['grades' => (object)['text' => 'Grades', 'action' => '/grade/']];

        $this->assertTrue($menu->record_seen($seen));
        $this->assertFalse($menu->record_seen($seen), 'Nothing is written when every node was seen before.');
        $this->assertSame(1, $DB->count_records('local_extendednav', ['menu' => 'course']));
        $this->assertSame('/grade/', $menu->get_native_node('grades')->action);
    }

    /**
     * The list is filtered by the search and by the visibility, and counts every visibility.
     */
    public function test_the_list_filters_and_counts(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->see_nodes(['participants', 'grades', 'badges']);
        manager::set_visibility('course', 0, ['badges'], access::VISIBILITY_HIDDEN);

        $all = nodes::get('course', 0);
        $this->assertSame(3, $all['total']);
        $this->assertSame(['all' => 3, 'visible' => 2, 'roles' => 0, 'hidden' => 1], $all['counts']);
        $this->assertTrue($all['reorderable']);

        $found = nodes::get('course', 0, 'grad');
        $this->assertSame(['grades'], array_column($found['rows'], 'nodekey'));
        $this->assertFalse($found['reorderable'], 'A part of the list is not moved.');

        $hidden = nodes::get('course', 0, '', '0');
        $this->assertSame(['badges'], array_column($hidden['rows'], 'nodekey'));
        $this->assertSame(3, $hidden['counts']['all'], 'The counts do not depend on the visibility chosen.');

        $both = nodes::get('course', 0, '', '0,1');
        $this->assertSame(3, $both['total']);
    }

    /**
     * The list is in pages, and the first and the last row say so.
     */
    public function test_the_list_has_pages(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->see_nodes(['one', 'two', 'three']);

        $page = nodes::get('course', 0, '', '', '', 1, 2);

        $this->assertSame(3, $page['total']);
        $this->assertSame(['three'], array_column($page['rows'], 'nodekey'));
        $this->assertTrue($page['rows'][0]['last']);
        $this->assertFalse(nodes::get('course', 0)['rows'][1]['first']);
        $this->assertTrue(nodes::get('course', 0)['rows'][0]['first']);
    }

    /**
     * Only the navigations of a course, an activity and a category have exceptions.
     */
    public function test_which_navigations_have_scopes(): void {
        $this->assertTrue(nodes::supports_scopes('course'));
        $this->assertTrue(nodes::supports_scopes('activity'));
        $this->assertTrue(nodes::supports_scopes('category'));
        $this->assertFalse(nodes::supports_scopes('primary'));
        $this->assertFalse(nodes::supports_scopes('admin'));
    }

    /**
     * The page of a context belongs to a navigation.
     */
    public function test_the_origin_of_a_page(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $category = $this->getDataGenerator()->create_category();
        $page = $this->getDataGenerator()->create_module('page', ['course' => $course->id]);

        $this->assertSame('course', injector::get_origin(\context_course::instance($course->id), (int)$course->id));
        $this->assertSame('frontpage', injector::get_origin(\context_course::instance(SITEID), SITEID));
        $this->assertSame('activity', injector::get_origin(\context_module::instance($page->cmid), (int)$course->id));
        $this->assertSame('category', injector::get_origin(\context_coursecat::instance($category->id), 0));
        // The site administration is shaped through its tree, not through its bar.
        $this->assertNull(injector::get_origin(\context_system::instance(), SITEID));
        $this->assertNull(injector::get_origin(\context_user::instance(get_admin()->id), SITEID));
    }
}
