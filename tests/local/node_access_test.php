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

#[\PHPUnit\Framework\Attributes\CoversClass(\local_extendednav\local\node_access::class)]
/**
 * Tests for node visibility, roles and languages.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\local\node_access
 */
final class node_access_test extends \advanced_testcase {
    /**
     * Resets the request caches.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        node_access::reset_caches();
    }

    /**
     * Builds a node object.
     *
     * @param array $fields Fields to set.
     * @return \stdClass
     */
    private function make_node(array $fields): \stdClass {
        return (object)($fields + ['menu' => 'primary', 'nodekey' => 'custom', 'visibility' => 1, 'roles' => null,
            'langs' => null]);
    }

    /**
     * Visibility and roles decide whether a user is allowed a node.
     */
    public function test_is_allowed(): void {
        $generator = $this->getDataGenerator();
        $student = $generator->create_user();
        $teacher = $generator->create_user();
        $course = $generator->create_course();
        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');

        $all = $this->make_node(['visibility' => 1]);
        $hidden = $this->make_node(['visibility' => 0]);
        $teachers = $this->make_node(['visibility' => 2, 'roles' => 'editingteacher,teacher']);
        $noroles = $this->make_node(['visibility' => 2, 'roles' => '']);
        $authenticated = $this->make_node(['visibility' => 2, 'roles' => 'user']);

        $this->assertTrue(node_access::is_allowed($all, $student->id));
        $this->assertFalse(node_access::is_allowed($hidden, $student->id));
        $this->assertFalse(node_access::is_allowed($teachers, $student->id));
        $this->assertTrue(node_access::is_allowed($teachers, $teacher->id));
        $this->assertFalse(node_access::is_allowed($noroles, $teacher->id));
        // The default role for authenticated users counts without a role assignment.
        $this->assertTrue(node_access::is_allowed($authenticated, $student->id));
    }

    /**
     * Site administration can never be hidden.
     */
    public function test_site_administration_is_protected(): void {
        $node = $this->make_node(['nodekey' => 'siteadminnode', 'visibility' => 0, 'langs' => 'es']);
        $this->assertTrue(node_access::is_protected($node));
        $this->assertTrue(node_access::is_allowed($node, 0));
        $this->assertTrue(node_access::is_shown($node, 0, 'en'));

        $usermenu = $this->make_node(['menu' => 'usermenu', 'nodekey' => 'siteadminnode']);
        $this->assertFalse(node_access::is_protected($usermenu));
    }

    /**
     * Languages match the language or one of its parents.
     */
    public function test_language_matches(): void {
        $any = $this->make_node(['langs' => '']);
        $spanish = $this->make_node(['langs' => 'es']);
        $several = $this->make_node(['langs' => 'en, pt_br']);

        $this->assertTrue(node_access::language_matches($any, 'de'));
        $this->assertTrue(node_access::language_matches($spanish, 'es'));
        $this->assertTrue(node_access::language_matches($spanish, 'es_mx'));
        $this->assertFalse(node_access::language_matches($spanish, 'en'));
        $this->assertTrue(node_access::language_matches($several, 'pt_br'));
        $this->assertFalse(node_access::language_matches($several, 'pt'));
    }

    /**
     * A node is shown only when allowed and in the right language.
     */
    public function test_is_shown(): void {
        $user = $this->getDataGenerator()->create_user();
        $spanish = $this->make_node(['langs' => 'es']);
        $hidden = $this->make_node(['visibility' => 0]);

        $this->assertTrue(node_access::is_shown($spanish, $user->id, 'es'));
        $this->assertFalse(node_access::is_shown($spanish, $user->id, 'en'));
        $this->assertFalse(node_access::is_shown($hidden, $user->id, 'en'));
        // Language never changes whether the user is allowed the node.
        $this->assertTrue(node_access::is_allowed($spanish, $user->id));
    }

    /**
     * Lists are split and cleaned.
     */
    public function test_split_list(): void {
        $this->assertSame(['a', 'b'], node_access::split_list(' a, ,b,a '));
        $this->assertSame([], node_access::split_list(null));
    }
}
