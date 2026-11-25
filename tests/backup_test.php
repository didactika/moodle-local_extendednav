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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

#[\PHPUnit\Framework\Attributes\CoversClass(\backup_local_extendednav_plugin::class)]
/**
 * Tests for the changes of the navigation of a course in the backups of Moodle, and for the defaults that leave them out.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \backup_local_extendednav_plugin
 * @covers     \restore_local_extendednav_plugin
 */
final class backup_test extends \advanced_testcase {
    /**
     * Backs a course up and restores it as a new one.
     *
     * @param \stdClass $course The course.
     * @return int The context of the new course.
     */
    private function backup_and_restore(\stdClass $course): int {
        global $USER;

        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT,
            $USER->id
        );
        $folder = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        $newid = \restore_dbops::create_new_course('Restored', 'restored' . random_string(4), $course->category);
        $rc = new \restore_controller(
            $folder,
            $newid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $rc->execute_precheck();
        $rc->execute_plan();
        $rc->destroy();
        return (int)\context_course::instance($newid)->id;
    }

    /**
     * The changes of a course travel with its backup, unless the defaults of the backups or of the restores leave them out.
     */
    public function test_the_changes_travel_with_the_backup(): void {
        global $DB, $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->keeptempdirectoriesonbackup = true;
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_extendednav');
        $generator->create_node(['menu' => 'course', 'nodekey' => 'grades', 'visibility' => 0,
            'contextid' => \context_course::instance($course->id)->id]);

        $restored = $this->backup_and_restore($course);
        $this->assertTrue($DB->record_exists('local_extendednav', ['contextid' => $restored, 'nodekey' => 'grades']));

        set_config('backup_navigation', 0, 'local_extendednav');
        $restored = $this->backup_and_restore($course);
        $this->assertFalse($DB->record_exists('local_extendednav', ['contextid' => $restored]), 'The backup leaves them out.');

        set_config('backup_navigation', 1, 'local_extendednav');
        set_config('restore_navigation', 0, 'local_extendednav');
        $restored = $this->backup_and_restore($course);
        $this->assertFalse($DB->record_exists('local_extendednav', ['contextid' => $restored]), 'The restore leaves them out.');
    }
}
