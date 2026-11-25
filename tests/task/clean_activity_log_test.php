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

namespace local_extendednav\task;

#[\PHPUnit\Framework\Attributes\CoversClass(\local_extendednav\task\clean_activity_log::class)]
/**
 * Tests for the task that cleans the activity log.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\task\clean_activity_log
 */
final class clean_activity_log_test extends \advanced_testcase {
    /**
     * Writes entries in the standard log, as old as asked.
     *
     * @param string $component Component of the entries.
     * @param int[] $days How many days old each entry is.
     */
    private function log(string $component, array $days): void {
        global $DB;

        $records = [];
        foreach ($days as $age) {
            $records[] = (object)[
                'eventname' => '\\' . $component . '\\event\\node_updated', 'component' => $component, 'action' => 'updated',
                'target' => 'node', 'crud' => 'u', 'edulevel' => 0, 'contextid' => 1, 'contextlevel' => CONTEXT_SYSTEM,
                'contextinstanceid' => 0, 'userid' => 2, 'anonymous' => 0, 'timecreated' => time() - $age * DAYSECS,
            ];
        }
        $DB->insert_records('logstore_standard_log', $records);
    }

    /**
     * Counts the entries of a component.
     *
     * @param string $component Component.
     * @return int
     */
    private function entries(string $component): int {
        global $DB;

        return $DB->count_records('logstore_standard_log', ['component' => $component]);
    }

    /**
     * Only the entries of the plugin older than the retention are deleted, a run goes on from where the last one stopped, and
     * a batch larger than one read is deleted whole.
     */
    public function test_old_entries_of_the_plugin_are_deleted(): void {
        global $DB;

        $this->resetAfterTest();
        $DB->delete_records('logstore_standard_log');
        $this->log('local_extendednav', array_merge([400, 380, 10, 1], array_fill(0, clean_activity_log::BATCH + 5, 200)));
        $this->log('core', [400, 200, 1]);

        $deleted = clean_activity_log::clean(time() - 100 * DAYSECS);
        $this->assertSame(clean_activity_log::BATCH + 7, $deleted);
        $this->assertSame(2, $this->entries('local_extendednav'), 'The recent entries stay.');
        $this->assertSame(3, $this->entries('core'), 'The entries of other components are never touched.');
        $done = (int)get_config('local_extendednav', clean_activity_log::DONE);
        $this->assertGreaterThan(0, $done, 'The task remembers how far it got.');

        // A run with no time left stops, and the next one goes on from there.
        $this->log('local_extendednav', [50]);
        $this->assertSame(0, clean_activity_log::clean(time() - 20 * DAYSECS, time() - 1));
        $this->assertSame(1, clean_activity_log::clean(time() - 20 * DAYSECS));
    }

    /**
     * The task is off until the clean-up is turned on, and goes off again with it.
     */
    public function test_the_task_follows_the_settings(): void {
        $this->resetAfterTest();
        $task = fn() => \core\task\manager::get_scheduled_task(clean_activity_log::class);
        $this->assertTrue($task()->get_disabled(), 'It starts off.');

        set_config(clean_activity_log::ENABLED, 1, 'local_extendednav');
        set_config(clean_activity_log::RETENTION, 2 * WEEKSECS, 'local_extendednav');
        clean_activity_log::sync();
        $this->assertFalse($task()->get_disabled());

        set_config(clean_activity_log::ENABLED, 0, 'local_extendednav');
        clean_activity_log::sync();
        $this->assertTrue($task()->get_disabled());
    }

    /**
     * The task reads the log through the index on the time, never the whole table.
     */
    public function test_the_log_is_read_through_its_index(): void {
        global $DB;

        $this->resetAfterTest();
        if ($DB->get_dbfamily() !== 'postgres') {
            $this->markTestSkipped('The plan is read from PostgreSQL.');
        }
        // A table of a few rows is read whole whatever its indexes: the planner is asked to use an index whenever one serves.
        $DB->execute('SET enable_seqscan = off');
        $plan = implode("\n", $DB->get_fieldset_sql(
            'EXPLAIN SELECT id FROM {logstore_standard_log}
              WHERE timecreated >= ? AND timecreated < ? AND component = ? LIMIT 1000',
            [time() - DAYSECS, time(), 'local_extendednav']
        ));
        $DB->execute('SET enable_seqscan = on');
        $this->assertStringContainsString('timecreated', $plan);
        $this->assertStringNotContainsString('Seq Scan', $plan);
    }
}
