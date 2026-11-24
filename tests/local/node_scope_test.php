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

namespace local_extendednav\local;

use local_extendednav\menu_manager;

/**
 * Tests for the nodes of a category or a course, which change what the site sets.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\local\node_repository
 * @covers     \local_extendednav\local\node_manager
 * @covers     \local_extendednav\local\node_list
 */
final class node_scope_test extends \advanced_testcase {
    /**
     * Records the nodes a course navigation shows, so each one has a row.
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
     * The nodes of the nearest context are the ones a page gets.
     */
    public function test_the_nearest_context_wins(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $category = $this->getDataGenerator()->create_category();
        $other = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $course = $this->getDataGenerator()->create_course(['category' => $category->id]);
        $this->see_nodes(['participants', 'grades']);

        $categorycontext = \context_coursecat::instance($category->id);
        $coursecontext = \context_course::instance($course->id);
        node_manager::set_visibility('course', $categorycontext->id, ['grades'], node_access::VISIBILITY_HIDDEN);
        node_manager::set_visibility('course', $coursecontext->id, ['grades'], node_access::VISIBILITY_ALL);

        $site = node_repository::get_scoped_nodes('course', \context_system::instance());
        $this->assertSame(node_access::VISIBILITY_ALL, (int)$site['grades']->visibility);
        $inside = node_repository::get_scoped_nodes('course', \context_course::instance($other->id));
        $this->assertSame(node_access::VISIBILITY_HIDDEN, (int)$inside['grades']->visibility, 'The category sets it.');
        $own = node_repository::get_scoped_nodes('course', $coursecontext);
        $this->assertSame(node_access::VISIBILITY_ALL, (int)$own['grades']->visibility, 'The course sets it again.');
    }

    /**
     * Changing a node in a scope leaves the node of the site alone.
     */
    public function test_an_exception_does_not_change_the_site(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $this->see_nodes(['grades']);
        $context = \context_course::instance($course->id);

        $this->assertSame(1, node_manager::set_visibility('course', $context->id, ['grades'], node_access::VISIBILITY_HIDDEN));

        $where = ['menu' => 'course', 'nodekey' => 'grades'];
        $site = $DB->get_record('local_extendednav', $where + ['contextid' => 0], '*', MUST_EXIST);
        $this->assertSame(node_access::VISIBILITY_ALL, (int)$site->visibility);
        $this->assertSame(1, $DB->count_records('local_extendednav', $where + ['contextid' => $context->id]));
        // The site list never shows the exceptions.
        $this->assertCount(1, array_filter(
            node_repository::get_menu_nodes('course'),
            static fn(\stdClass $node): bool => $node->nodekey === 'grades'
        ));
    }

    /**
     * An exception starts as a copy of the node the scope sees.
     */
    public function test_an_exception_starts_as_a_copy(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $this->see_nodes(['grades']);
        $site = node_repository::get_menu_nodes_by_key('course')['grades'];
        node_manager::update((object)['id' => $site->id, 'title' => 'Marks']);

        $context = \context_course::instance($course->id);
        $row = node_manager::ensure_row('course', 'grades', $context->id);

        $this->assertSame('Marks', $row->title);
        $this->assertSame($context->id, (int)$row->contextid);
        $this->assertNotSame((int)$site->id, (int)$row->id);
    }

    /**
     * A node moves one place among its siblings, and the first and the last stay where they are.
     */
    public function test_a_node_moves_among_its_siblings(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->see_nodes(['one', 'two', 'three']);

        node_manager::move('course', 0, 'three', -1);
        $this->assertSame(['one', 'three', 'two'], array_keys(node_list::get_scope_nodes('course', 0)));

        node_manager::move('course', 0, 'one', -1);
        $this->assertSame(['one', 'three', 'two'], array_keys(node_list::get_scope_nodes('course', 0)), 'The first one stays.');

        node_manager::move('course', 0, 'one', 1);
        $this->assertSame(['three', 'one', 'two'], array_keys(node_list::get_scope_nodes('course', 0)));
    }

    /**
     * A node is placed before a sibling, or after the last one, and only among its siblings.
     */
    public function test_a_node_is_placed_before_a_sibling(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->see_nodes(['one', 'two', 'three']);

        node_manager::place('course', 0, 'three', 'one');
        $this->assertSame(['three', 'one', 'two'], array_keys(node_list::get_scope_nodes('course', 0)));

        node_manager::place('course', 0, 'three', '');
        $this->assertSame(['one', 'two', 'three'], array_keys(node_list::get_scope_nodes('course', 0)));
    }

    /**
     * Moving a node in a scope gives the scope the order, and the site keeps its own.
     */
    public function test_moving_in_a_scope_sets_the_order_of_the_scope(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $this->see_nodes(['one', 'two', 'three']);
        $context = \context_course::instance($course->id);

        node_manager::move('course', $context->id, 'three', -1);

        $this->assertSame(['one', 'three', 'two'], array_keys(node_list::get_scope_nodes('course', $context->id)));
        $this->assertSame(['one', 'two', 'three'], array_keys(node_list::get_scope_nodes('course', 0)));
    }

    /**
     * Deleting a node of the site deletes its exceptions too.
     */
    public function test_deleting_a_node_deletes_its_exceptions(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $values = ['nodekey' => 'library', 'title' => 'Library', 'url' => '/library.php'];
        $record = node_manager::create(node_manager::prepare($values, 'course'));
        $context = \context_course::instance($course->id);
        node_manager::set_visibility('course', $context->id, ['library'], node_access::VISIBILITY_HIDDEN);
        $this->assertSame(2, $DB->count_records('local_extendednav', ['menu' => 'course', 'nodekey' => 'library']));

        node_manager::delete([$record]);

        $this->assertSame(0, $DB->count_records('local_extendednav', ['menu' => 'course', 'nodekey' => 'library']));
    }
}
