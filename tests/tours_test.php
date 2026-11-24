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

#[\PHPUnit\Framework\Attributes\CoversFunction('local_extendednav_import_tours')]
/**
 * Tests for the guided tours of the plugin.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::local_extendednav_import_tours
 */
final class tours_test extends \advanced_testcase {
    /**
     * The tours are added once, and their texts are strings of the plugin in every language.
     */
    public function test_the_tours_are_added_once(): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/local/extendednav/lib.php');
        $this->resetAfterTest();
        $DB->delete_records_select(
            'tool_usertours_tours',
            $DB->sql_like('name', ':name'),
            ['name' => '%,local_extendednav']
        );

        $this->assertSame(3, local_extendednav_import_tours());
        $this->assertSame(0, local_extendednav_import_tours(), 'A tour already there is left as it is.');

        $strings = [];
        foreach (glob($CFG->dirroot . '/local/extendednav/tours/*.json') as $file) {
            $tour = json_decode(file_get_contents($file));
            $strings[] = $tour->name;
            $strings[] = $tour->description;
            foreach ($tour->steps as $step) {
                $strings[] = $step->title;
                $strings[] = $step->content;
            }
        }
        foreach (['en', 'es', 'fr', 'it', 'pt'] as $lang) {
            $file = $CFG->dirroot . '/local/extendednav/lang/' . $lang . '/local_extendednav.php';
            $string = [];
            include($file);
            foreach ($strings as $name) {
                $this->assertArrayHasKey(explode(',', $name)[0], $string, $lang);
            }
        }
    }
}
