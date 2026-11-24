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
 * @covers     \local_extendednav\local\io\exporter
 * @covers     \local_extendednav\local\io\scope
 */
final class importer_scope_test extends \advanced_testcase {
    /**
     * Forgets the names of the scopes read by another test: the contexts of a test reuse the ids of the ones before.
     */
    protected function setUp(): void {
        parent::setUp();
        scope::reset();
    }

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
        $exported = exporter::export_nodes($nodes, $site);

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
     * An activity is written by its course and its ID number, or its number in the site, and found again by them.
     */
    public function test_the_scope_of_an_activity(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course(['shortname' => 'ENG1']);
        $named = $this->getDataGenerator()->create_module('forum', ['course' => $course->id, 'idnumber' => 'news']);
        $plain = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        scope::reset();

        $this->assertSame('activity:ENG1/news', scope::describe(\context_module::instance($named->cmid)->id));
        $this->assertSame('activity:ENG1/#' . $plain->cmid, scope::describe(\context_module::instance($plain->cmid)->id));
        scope::reset();
        $this->assertSame(\context_module::instance($named->cmid)->id, scope::resolve('activity:ENG1/news'));
        $this->assertSame(\context_module::instance($plain->cmid)->id, scope::resolve('activity:ENG1/#' . $plain->cmid));
        $this->assertNull(scope::resolve('activity:ENG1/nothing'));
        $this->assertNull(scope::resolve('activity:NONE/news'));
    }

    /**
     * The file of some contexts holds only their changes, and the nodes of a file go to the courses chosen, each as a change
     * of its own, leaving out the navigation of a category.
     */
    public function test_a_file_goes_to_the_contexts_chosen(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $from = $this->getDataGenerator()->create_course(['shortname' => 'FROM']);
        $one = $this->getDataGenerator()->create_course(['shortname' => 'ONE']);
        $two = $this->getDataGenerator()->create_course(['shortname' => 'TWO']);
        menu_manager::get_menu('course')->record_seen(['grades' => (object)['text' => 'Grades', 'action' => '/grade/']]);
        menu_manager::get_menu('category')->record_seen(['cohort' => (object)['text' => 'Cohorts', 'action' => '/cohort/']]);
        $fromcontext = \context_course::instance($from->id)->id;
        manager::set_visibility('course', $fromcontext, ['grades'], access::VISIBILITY_HIDDEN);
        manager::set_visibility('category', 0, ['cohort'], access::VISIBILITY_HIDDEN);

        $file = exporter::export_file(['course', 'category'], [$fromcontext]);
        $parsed = \local_extendednav\yaml::parse($file);
        $this->assertSame(['course:FROM'], array_values(array_unique(array_column($parsed['nodes'], 'scope'))));
        $this->assertNull(exporter::export_file(['category'], [$fromcontext]), 'The course changes no category.');

        $withsite = \local_extendednav\yaml::parse(exporter::export_file(['course', 'category'], [0, $fromcontext]));
        $targets = [\context_course::instance($one->id)->id, \context_course::instance($two->id)->id];
        $result = (new importer('course', true, $targets))->import($withsite['nodes']);

        $this->assertSame([], $result['errors']);
        foreach ($targets as $contextid) {
            $row = $DB->get_record('local_extendednav', ['menu' => 'course', 'nodekey' => 'grades', 'contextid' => $contextid]);
            $this->assertSame(access::VISIBILITY_HIDDEN, (int)$row->visibility);
            $this->assertFalse($DB->record_exists('local_extendednav', ['menu' => 'category', 'contextid' => $contextid]));
        }
        $this->assertGreaterThan(0, $result['counts']['skipped'], 'The navigation of a category is left out of a course.');
    }

    /**
     * A course or a user that does not exist is left out with a warning, and the rest is imported; a navigation that has no
     * exceptions is refused.
     */
    public function test_the_scope_is_checked(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        menu_manager::get_menu('course')->record_seen(['grades' => (object)['text' => 'Grades', 'action' => '/grade/']]);
        $course = $this->getDataGenerator()->create_course(['shortname' => 'ENG1']);

        $nowhere = (new importer('course', true))->import([
            ['nodekey' => 'grades', 'scope' => 'course:NOPE', 'visibility' => 0],
            ['nodekey' => 'grades', 'scope' => 'user:nobody', 'menu' => 'preferences', 'visibility' => 0],
            ['nodekey' => 'grades', 'scope' => 'course:ENG1', 'visibility' => 0],
        ]);
        $this->assertSame([], $nowhere['errors']);
        $this->assertCount(2, $nowhere['warnings']);
        $this->assertSame(2, $nowhere['counts']['skipped']);
        $contextid = \context_course::instance($course->id)->id;
        $this->assertTrue($DB->record_exists('local_extendednav', ['menu' => 'course', 'contextid' => $contextid]));

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

        $this->assertSame('category:Humanities / History', scope::describe($contextid));
        $this->assertSame($contextid, scope::resolve('category:Humanities / History'));
        $this->assertNull(scope::resolve('category:History'));
        $this->assertSame('', scope::describe(0));
    }
}
