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

use local_extendednav\local\listing\nodes;
use local_extendednav\local\node\access;
use local_extendednav\local\node\manager;
use local_extendednav\local\node\repository;

#[\PHPUnit\Framework\Attributes\CoversNothing]
/**
 * Performance of the lists and of the navigations with thousands of nodes: the number of queries does not grow with the nodes,
 * and a page of the list is built in a time that a person does not notice.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversNothing
 */
final class performance_test extends \advanced_testcase {
    /** @var float Seconds a page of the list may take, with room for a slow machine of continuous integration. */
    private const SECONDS = 3.0;

    /**
     * Adds nodes of the site to a navigation, or exceptions to a context, at once.
     *
     * @param string $menu Navigation key.
     * @param int $count How many.
     * @param int $contextid Context of the exceptions, or 0 for the site.
     * @param string $prefix Start of the keys.
     */
    private function add_nodes(string $menu, int $count, int $contextid = 0, string $prefix = 'node'): void {
        global $DB;

        $records = [];
        $now = time();
        for ($i = 1; $i <= $count; $i++) {
            $record = manager::prepare([
                'nodekey' => $prefix . $i,
                'title' => 'Item ' . $i,
                'url' => '/item.php?id=' . $i,
                'visibility' => $i % 3 === 0 ? access::VISIBILITY_ROLES : access::VISIBILITY_ALL,
                'roles' => $i % 3 === 0 ? 'editingteacher' : '',
                'langs' => $i % 5 === 0 ? 'es' : '',
                'contextid' => $contextid,
            ], $menu);
            $record->contextid = $contextid;
            $record->sortorder = $i;
            $record->timecreated = $now;
            $record->timemodified = $now;
            $records[] = $record;
        }
        $DB->insert_records('local_extendednav', $records);
        repository::purge();
    }

    /**
     * Builds a page of the list, counting the queries and the time.
     *
     * @param callable $build What builds it.
     * @return array Queries, seconds and the result.
     */
    private function measure(callable $build): array {
        global $DB;

        $queries = $DB->perf_get_reads() + $DB->perf_get_writes();
        $start = microtime(true);
        $result = $build();
        return [$DB->perf_get_reads() + $DB->perf_get_writes() - $queries, microtime(true) - $start, $result];
    }

    /**
     * The list of a kind of activity with thousands of nodes, searched and filtered by restriction, is as fast as a short one.
     */
    public function test_the_list_of_a_kind_scales(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $this->add_nodes('mod_forum', 50);
        nodes::get('mod_forum', 0, '', '', '', 0, 12);
        [$few] = $this->measure(fn() => nodes::get('mod_forum', 0, 'item', '', '', 0, 12, '', 'roles'));

        $this->add_nodes('mod_forum', 3000, 0, 'many');
        nodes::get('mod_forum', 0, '', '', '', 0, 12);
        [$many, $seconds, $page] = $this->measure(fn() => nodes::get('mod_forum', 0, 'item', '', '', 0, 12, '', 'roles'));

        $this->assertSame(12, count($page['rows']));
        $this->assertGreaterThan(1000, $page['total']);
        $this->assertLessThanOrEqual($few + 2, $many, 'The queries do not grow with the nodes.');
        $this->assertLessThan(self::SECONDS, $seconds);
    }

    /**
     * A course with thousands of exceptions lists them as fast as a course with a few.
     */
    public function test_the_exceptions_of_a_course_scale(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $contextid = (int)\context_course::instance($course->id)->id;

        $this->add_nodes('course', 50, $contextid);
        nodes::get('course', $contextid, '', '', '', 0, 12);
        [$few] = $this->measure(fn() => nodes::get('course', $contextid, '', '', '', 0, 12));

        $this->add_nodes('course', 3000, $contextid, 'many');
        nodes::get('course', $contextid, '', '', '', 0, 12);
        [$many, $seconds] = $this->measure(fn() => nodes::get('course', $contextid, '', '', '', 0, 12));

        $this->assertLessThanOrEqual($few + 2, $many);
        $this->assertLessThan(self::SECONDS, $seconds);
    }

    /**
     * Deciding who sees thousands of nodes takes no query per node once the nodes are read.
     */
    public function test_who_sees_the_nodes_scales(): void {
        global $DB;

        $this->resetAfterTest();
        $this->add_nodes('mod_forum', 3000);
        $this->setUser($this->getDataGenerator()->create_user());
        $rows = repository::get_menu_nodes_by_key('mod_forum');
        access::is_shown(reset($rows));

        $reads = $DB->perf_get_reads();
        $start = microtime(true);
        $shown = count(array_filter($rows, static fn(\stdClass $row): bool => access::is_shown($row)));
        $seconds = microtime(true) - $start;

        $this->assertGreaterThan(0, $shown);
        $this->assertLessThan(count($rows), $shown, 'The nodes restricted to roles the user lacks are not shown.');
        $this->assertLessThanOrEqual(5, $DB->perf_get_reads() - $reads);
        $this->assertLessThan(self::SECONDS, $seconds);
    }
}
