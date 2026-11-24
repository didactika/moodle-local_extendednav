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
use local_extendednav\local\activity_list;
use local_extendednav\local\node_manager;

/**
 * Tests for the activity log of a navigation.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\external\get_activity
 * @covers     \local_extendednav\local\activity_list
 */
final class get_activity_test extends \advanced_testcase {
    /**
     * Records the changes of two nodes of the primary navigation and one of the user menu in the standard log.
     *
     * @return \stdClass The node of the primary navigation that is changed and then deleted.
     */
    private function prepare(): \stdClass {
        $this->resetAfterTest();
        $this->preventResetByRollback();
        set_config('enabled_stores', 'logstore_standard', 'tool_log');
        set_config('buffersize', 0, 'logstore_standard');
        get_log_manager(true);
        $this->setAdminUser();

        $create = static fn(string $key, string $menu): \stdClass => node_manager::create(
            node_manager::prepare(['nodekey' => $key, 'title' => ucfirst($key), 'url' => '/my/'], $menu)
        );
        $node = $create('help_en', 'primary');
        // Two more nodes: one of the same navigation and one of another, which its log never shows.
        $create('guides', 'primary');
        $create('support', 'usermenu');
        node_manager::update((object)['id' => $node->id, 'title' => 'Help and contact']);
        return $node;
    }

    /**
     * The log of a navigation lists its changes, newest first, with the rows of each action.
     */
    public function test_the_log_of_a_navigation(): void {
        $this->prepare();

        $result = external_api::clean_returnvalue(get_activity::execute_returns(), get_activity::execute('primary'));

        $this->assertSame(3, $result['total']);
        $this->assertSame(['created' => 2, 'updated' => 1, 'deleted' => 0], $result['counts']);
        $this->assertTrue($result['haslog']);
        $this->assertSame('updated', $result['rows'][0]['action']);
        $this->assertSame('help_en', $result['rows'][0]['nodekey']);
        $this->assertNotSame('', $result['rows'][0]['editurl']);
        $this->assertSame(fullname(get_admin()), $result['rows'][0]['user']);
    }

    /**
     * The actions, the search, the period and the order narrow and sort the rows; the counts keep every action.
     */
    public function test_the_filters(): void {
        $this->prepare();

        $result = get_activity::execute('primary', '', 'created');
        $this->assertSame(2, $result['total']);
        $this->assertSame(['created' => 2, 'updated' => 1, 'deleted' => 0], $result['counts']);

        $this->assertSame(2, get_activity::execute('primary', 'help')['total']);
        $this->assertSame(3, get_activity::execute('primary', 'Admin')['total']);
        $this->assertSame(0, get_activity::execute('primary', 'nothing')['total']);

        $today = userdate(time(), '%Y-%m-%d');
        $this->assertSame(3, get_activity::execute('primary', '', '', $today, $today)['total']);
        $this->assertSame(0, get_activity::execute('primary', '', '', '2000-01-01', '2000-01-02')['total']);

        $ascending = get_activity::execute('primary', '', '', '', '', 'asc');
        $this->assertSame('created', $ascending['rows'][0]['action']);

        $page = get_activity::execute('primary', '', '', '', '', 'desc', 1, 2);
        $this->assertCount(1, $page['rows']);
    }

    /**
     * A deleted node is listed without the link to its form.
     */
    public function test_a_deleted_node_has_no_link(): void {
        $node = $this->prepare();
        node_manager::delete([$node]);

        $result = get_activity::execute('primary', '', 'deleted');

        $this->assertSame(1, $result['total']);
        $this->assertSame('', $result['rows'][0]['editurl']);
    }

    /**
     * Whoever may not manage the navigation cannot read its log.
     */
    public function test_the_capability_is_required(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(\required_capability_exception::class);
        get_activity::execute('primary');
    }

    /**
     * A day that is not a date is ignored.
     */
    public function test_day_start(): void {
        $this->assertNull(activity_list::day_start(''));
        $this->assertNull(activity_list::day_start('2026-02-30'));
        $this->assertNull(activity_list::day_start('yesterday'));
        $this->assertIsInt(activity_list::day_start('2026-02-28'));
    }
}
