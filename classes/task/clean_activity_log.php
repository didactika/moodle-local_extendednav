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

/**
 * Deletes the entries of the activity log of the plugin older than the site keeps them.
 *
 * The entries are in the standard log of Moodle, which has no index on the component but one on the time. The task walks the
 * log by time, one day at a time, on that index: it reads the ids of the entries of the plugin of each day in batches and
 * deletes them by their primary key, and it remembers how far it got. The first run goes through the history in pieces, as
 * long as its time allows, and the next ones resume where it stopped; once the history is clean, a run only reads the day that
 * has just passed its retention.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class clean_activity_log extends \core\task\scheduled_task {
    /** @var string Setting that turns the clean-up on. */
    public const ENABLED = 'logretention_enabled';

    /** @var string Setting that keeps how long the entries are kept, in seconds. */
    public const RETENTION = 'logretention';

    /** @var string Setting that keeps up to when the log is clean. */
    public const DONE = 'logretention_done';

    /** @var int Width of each piece of time read, in seconds. */
    public const WINDOW = DAYSECS;

    /** @var int Entries read and deleted at once. */
    public const BATCH = 1000;

    /** @var int Seconds a run may take before it leaves the rest to the next one. */
    public const TIME_LIMIT = 300;

    /**
     * Returns the name of the task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_clean_activity_log', 'local_extendednav');
    }

    /**
     * Whether the clean-up is on and keeps the entries for some time.
     *
     * @return bool
     */
    public static function is_wanted(): bool {
        return (bool)get_config('local_extendednav', self::ENABLED) && (int)get_config('local_extendednav', self::RETENTION) > 0;
    }

    /**
     * Turns the task on or off as the settings ask, so it only runs while the clean-up is on.
     */
    public static function sync(): void {
        $task = \core\task\manager::get_scheduled_task(self::class);
        if (!$task) {
            return;
        }
        $disabled = !self::is_wanted();
        if ($task->get_disabled() !== $disabled) {
            $task->set_disabled($disabled);
            \core\task\manager::configure_scheduled_task($task);
        }
    }

    /**
     * Deletes the entries older than the retention.
     */
    public function execute(): void {
        if (!self::is_wanted()) {
            return;
        }
        $deleted = self::clean(time() - (int)get_config('local_extendednav', self::RETENTION), time() + self::TIME_LIMIT);
        mtrace('Activity log of the navigation: ' . $deleted . ' entries deleted.');
    }

    /**
     * Deletes the entries of the plugin older than a time, a day of the log at a time.
     *
     * @param int $before Entries created before this time are deleted.
     * @param int $until The run stops after this time, and the next one goes on from there.
     * @return int Entries deleted.
     */
    public static function clean(int $before, int $until = PHP_INT_MAX): int {
        global $DB;

        $from = (int)get_config('local_extendednav', self::DONE);
        if ($from === 0) {
            // The oldest entry of the log, read from the index on the time.
            $from = (int)$DB->get_field_sql('SELECT MIN(timecreated) FROM {logstore_standard_log}');
            if ($from === 0) {
                return 0;
            }
        }
        $deleted = 0;
        while ($from < $before && time() < $until) {
            $to = min($from + self::WINDOW, $before);
            do {
                // The ids of the entries of the plugin in the piece of time, through the index on the time, a batch at once.
                $ids = array_keys($DB->get_records_sql(
                    'SELECT id
                       FROM {logstore_standard_log}
                      WHERE timecreated >= :from AND timecreated < :to AND component = :component',
                    ['from' => $from, 'to' => $to, 'component' => 'local_extendednav'],
                    0,
                    self::BATCH
                ));
                if ($ids) {
                    $DB->delete_records_list('logstore_standard_log', 'id', $ids);
                    $deleted += count($ids);
                }
            } while (count($ids) === self::BATCH && time() < $until);
            if (count($ids) === self::BATCH) {
                // The time ran out in the middle of a piece: the next run goes on with it.
                break;
            }
            $from = $to;
            set_config(self::DONE, $from, 'local_extendednav');
        }
        return $deleted;
    }
}
