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

/**
 * Tests for what a site administrator sees: every node, unless the plugin or the node says otherwise.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\local\node_access
 */
final class node_admin_access_test extends \advanced_testcase {
    /**
     * Builds a node record.
     *
     * @param array $values Fields to set.
     * @return \stdClass
     */
    private function node(array $values): \stdClass {
        return (object)($values + ['menu' => 'course', 'nodekey' => 'x', 'visibility' => 0, 'roles' => null, 'hideadmins' => 0]);
    }

    /**
     * An administrator sees a hidden node, a node restricted to roles they do not hold, and the others do not.
     */
    public function test_an_administrator_sees_every_node(): void {
        $this->resetAfterTest();
        $admin = get_admin();
        $user = $this->getDataGenerator()->create_user();
        $hidden = $this->node(['visibility' => node_access::VISIBILITY_HIDDEN]);
        $restricted = $this->node(['visibility' => node_access::VISIBILITY_ROLES, 'roles' => 'editingteacher']);

        $this->assertTrue(node_access::is_allowed($hidden, $admin->id));
        $this->assertTrue(node_access::is_allowed($restricted, $admin->id));
        $this->assertFalse(node_access::is_allowed($hidden, $user->id));
        $this->assertFalse(node_access::is_allowed($restricted, $user->id));
    }

    /**
     * The node says whether it is seen only because of the administrator.
     */
    public function test_admin_only_marks_what_others_do_not_see(): void {
        $this->resetAfterTest();
        $admin = get_admin();
        $hidden = $this->node(['visibility' => node_access::VISIBILITY_HIDDEN]);
        $open = $this->node(['visibility' => node_access::VISIBILITY_ALL]);

        $this->assertTrue(node_access::is_admin_only($hidden, $admin->id));
        $this->assertFalse(node_access::is_admin_only($open, $admin->id));
    }

    /**
     * A node can be hidden from the administrators too.
     */
    public function test_a_node_can_be_hidden_from_administrators(): void {
        $this->resetAfterTest();
        $admin = get_admin();
        $node = $this->node(['visibility' => node_access::VISIBILITY_HIDDEN, 'hideadmins' => 1]);

        $this->assertFalse(node_access::is_allowed($node, $admin->id));
        $this->assertFalse(node_access::is_admin_only($node, $admin->id));
    }

    /**
     * The setting of the plugin hides every node from the administrators too.
     */
    public function test_the_setting_applies_the_rules_to_administrators(): void {
        $this->resetAfterTest();
        $admin = get_admin();
        $node = $this->node(['visibility' => node_access::VISIBILITY_HIDDEN]);
        $this->assertTrue(node_access::is_allowed($node, $admin->id));

        set_config('hide_from_admins', 1, 'local_extendednav');

        $this->assertFalse(node_access::is_allowed($node, $admin->id));
    }

    /**
     * The language of a node still applies to administrators: it is not a permission.
     */
    public function test_the_language_still_applies(): void {
        $this->resetAfterTest();
        $admin = get_admin();
        $node = $this->node(['visibility' => node_access::VISIBILITY_ALL, 'langs' => 'es']);

        $this->assertTrue(node_access::is_shown($node, $admin->id, 'es'));
        $this->assertFalse(node_access::is_shown($node, $admin->id, 'en'));
    }
}
