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

namespace local_extendednav\local\io;

use local_extendednav\menu_manager;
use local_extendednav\local\node\access;
use local_extendednav\local\node\manager;
use local_extendednav\local\node\repository;

/**
 * Tests for the export and the import of the nodes that apply to a category or a course.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\local\io\importer
 */
final class importer_scope_test extends \advanced_testcase {
    /**
     * An exception is exported with the course it is for, and imported back into it.
     */
    public function test_an_exception_goes_out_and_comes_back(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course(['shortname' => 'ENG1']);
        menu_manager::get_menu('course')->record_seen(['grades' => (object)['text' => 'Grades', 'action' => '/grade/']]);
        $contextid = \context_course::instance($course->id)->id;
        manager::set_visibility('course', $contextid, ['grades'], access::VISIBILITY_HIDDEN);

        $nodes = $DB->get_records('local_extendednav', ['menu' => 'course'], 'contextid ASC, id ASC');
        $site = $DB->get_records('local_extendednav', ['menu' => 'course', 'contextid' => 0]);
        $exported = importer::export_nodes($nodes, $site);

        $this->assertSame(['', 'course:ENG1'], array_column($exported, 'scope'));
        $this->assertSame(0, $exported[1]['visibility']);

        $DB->delete_records('local_extendednav', ['menu' => 'course', 'contextid' => $contextid]);
        repository::purge();
        $this->assertSame(0, $DB->count_records('local_extendednav', ['menu' => 'course', 'contextid' => $contextid]));

        $result = (new importer('course', true))->import($exported);

        $this->assertSame([], $result['errors']);
        $row = $DB->get_record('local_extendednav', ['menu' => 'course', 'nodekey' => 'grades', 'contextid' => $contextid]);
        $this->assertSame(access::VISIBILITY_HIDDEN, (int)$row->visibility);
    }

    /**
     * A course that does not exist, and a navigation that has no exceptions, are refused.
     */
    public function test_the_scope_is_checked(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        menu_manager::get_menu('course')->record_seen(['grades' => (object)['text' => 'Grades', 'action' => '/grade/']]);

        $nowhere = (new importer('course', true))->import([
            ['nodekey' => 'grades', 'scope' => 'course:NOPE', 'visibility' => 0],
        ]);
        $this->assertNotEmpty($nowhere['errors']);

        $course = $this->getDataGenerator()->create_course(['shortname' => 'ENG2']);
        $primary = (new importer('primary', true))->import([
            ['nodekey' => 'x', 'title' => 'X', 'url' => '/x.php', 'scope' => 'course:' . $course->shortname],
        ]);
        $this->assertNotEmpty($primary['errors']);
    }

    /**
     * A category is found by its name, with the ones around it.
     */
    public function test_a_category_is_named_with_its_parents(): void {
        $this->resetAfterTest();
        $parent = $this->getDataGenerator()->create_category(['name' => 'Humanities']);
        $child = $this->getDataGenerator()->create_category(['name' => 'History', 'parent' => $parent->id]);
        $contextid = \context_coursecat::instance($child->id)->id;

        $this->assertSame('category:Humanities / History', importer::describe_scope($contextid));
        $this->assertSame($contextid, importer::resolve_scope('category:Humanities / History'));
        $this->assertNull(importer::resolve_scope('category:History'));
        $this->assertSame('', importer::describe_scope(0));
    }
}
