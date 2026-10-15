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

#[\PHPUnit\Framework\Attributes\CoversClass(\local_extendednav\external\reorder_nodes::class)]
/**
 * Tests for the reorder web service.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\external\reorder_nodes
 */
final class reorder_nodes_test extends \advanced_testcase {
    /**
     * Returns the node ids of the primary menu in order.
     *
     * @return int[]
     */
    private function get_order(): array {
        global $DB;
        return array_map('intval', array_keys($DB->get_records(
            'local_extendednav',
            ['menu' => 'primary'],
            'sortorder ASC, id ASC',
            'id'
        )));
    }

    /**
     * A user with the capability can reorder the nodes.
     */
    public function test_reorder(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $roleid = $this->getDataGenerator()->create_role();
        assign_capability('local/extendednav:manage', CAP_ALLOW, $roleid, \context_system::instance());
        role_assign($roleid, $user->id, \context_system::instance());
        $this->setUser($user);

        $reversed = array_reverse($this->get_order());
        $result = reorder_nodes::execute('primary', $reversed);
        $result = \core_external\external_api::clean_returnvalue(reorder_nodes::execute_returns(), $result);

        $this->assertTrue($result['success']);
        $this->assertSame($reversed, $this->get_order());
    }

    /**
     * Users without the capability cannot reorder the nodes.
     */
    public function test_reorder_requires_capability(): void {
        $this->resetAfterTest();
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(\required_capability_exception::class);
        reorder_nodes::execute('primary', array_reverse($this->get_order()));
    }
}
