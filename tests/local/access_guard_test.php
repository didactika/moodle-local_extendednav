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

#[\PHPUnit\Framework\Attributes\CoversClass(\local_extendednav\local\access_guard::class)]
/**
 * Tests for URL blocking and fallback pages.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\local\access_guard
 */
final class access_guard_test extends \advanced_testcase {
    /** @var \local_extendednav_generator Plugin generator. */
    private $navgenerator;

    /** @var \stdClass A student. */
    private $student;

    /** @var \stdClass A teacher. */
    private $teacher;

    /**
     * Creates a student and a teacher.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        node_access::reset_caches();

        $generator = $this->getDataGenerator();
        $this->navgenerator = $generator->get_plugin_generator('local_extendednav');
        $course = $generator->create_course();
        $this->student = $generator->create_user();
        $this->teacher = $generator->create_user();
        $generator->enrol_user($this->student->id, $course->id, 'student');
        $generator->enrol_user($this->teacher->id, $course->id, 'editingteacher');
    }

    /**
     * A hidden node blocks its URL only when "Block URL access" is set.
     */
    public function test_hidden_node_blocks_only_with_block_access(): void {
        $this->navgenerator->create_node(['nodekey' => 'secret', 'title' => 'Secret', 'url' => '/mod/page/view.php?id=7',
            'visibility' => 0, 'blockaccess' => 1]);
        $this->navgenerator->create_node(['nodekey' => 'quiet', 'title' => 'Quiet', 'url' => '/mod/page/view.php?id=8',
            'visibility' => 0, 'blockaccess' => 0]);

        $this->assertSame('/my/', access_guard::evaluate('/mod/page/view.php?id=7', $this->student->id));
        $this->assertNull(access_guard::evaluate('/mod/page/view.php?id=8', $this->student->id));
        $this->assertNull(access_guard::evaluate('/mod/page/view.php?id=9', $this->student->id));
    }

    /**
     * A restricted node blocks users without the role and lets the others in.
     */
    public function test_restricted_node(): void {
        $this->navgenerator->create_node(['nodekey' => 'staff', 'title' => 'Staff', 'url' => '/mod/page/view.php?id=7',
            'visibility' => 2, 'roles' => 'editingteacher', 'blockaccess' => 1]);

        $this->assertSame('/my/', access_guard::evaluate('/mod/page/view.php?id=7', $this->student->id));
        $this->assertNull(access_guard::evaluate('/mod/page/view.php?id=7', $this->teacher->id));
    }

    /**
     * A node the user is allowed opens a URL another node blocks.
     */
    public function test_allowed_node_opens_blocked_url(): void {
        $this->navgenerator->create_node(['nodekey' => 'staff', 'title' => 'Staff', 'url' => '/mod/page/view.php?id=7',
            'visibility' => 0, 'blockaccess' => 1]);
        $this->navgenerator->create_node(['nodekey' => 'public', 'title' => 'Public', 'url' => '/mod/page/view.php?id=7']);

        $this->assertNull(access_guard::evaluate('/mod/page/view.php?id=7', $this->student->id));
    }

    /**
     * Languages never take part in blocking.
     */
    public function test_languages_do_not_block(): void {
        $this->navgenerator->create_node(['nodekey' => 'ayuda', 'title' => 'Ayuda', 'url' => '/mod/page/view.php?id=7',
            'visibility' => 2, 'roles' => 'editingteacher', 'blockaccess' => 1, 'langs' => 'es']);

        // The menu shows the node to Spanish teachers only, but every teacher can open the URL.
        force_current_language('en');
        $this->assertNull(access_guard::evaluate('/mod/page/view.php?id=7', $this->teacher->id));
        $this->assertSame('/my/', access_guard::evaluate('/mod/page/view.php?id=7', $this->student->id));
    }

    /**
     * Blocked users are sent to the first fallback page they are allowed.
     */
    public function test_fallback_order(): void {
        $this->navgenerator->create_node(['nodekey' => 'secret', 'title' => 'Secret', 'url' => '/mod/page/view.php?id=7',
            'visibility' => 0, 'blockaccess' => 1]);
        $this->navgenerator->create_node(['nodekey' => 'myhome', 'visibility' => 0, 'blockaccess' => 1]);
        $this->assertSame('/?redirect=0', access_guard::evaluate('/mod/page/view.php?id=7', $this->student->id));
        $this->assertSame('/?redirect=0', access_guard::evaluate('/my/', $this->student->id));

        $this->navgenerator->create_node(['nodekey' => 'home', 'visibility' => 0, 'blockaccess' => 1]);
        $this->assertSame('/my/courses.php', access_guard::evaluate('/', $this->student->id));

        $this->navgenerator->create_node(['nodekey' => 'mycourses', 'visibility' => 0, 'blockaccess' => 1]);
        $this->assertSame('', access_guard::evaluate('/mod/page/view.php?id=7', $this->student->id));
    }

    /**
     * Blocking the Dashboard does not block My courses.
     */
    public function test_dashboard_does_not_cover_my_courses(): void {
        $this->navgenerator->create_node(['nodekey' => 'myhome', 'visibility' => 0, 'blockaccess' => 1]);
        $this->assertNull(access_guard::evaluate('/my/courses.php', $this->student->id));
        $this->assertSame('/?redirect=0', access_guard::evaluate('/my/index.php', $this->student->id));
    }

    /**
     * External URLs never block a page of the site, even when stored with "Block URL access".
     */
    public function test_external_url_never_blocks(): void {
        global $DB;

        // The external page has the same path as a page of this site.
        $DB->insert_record('local_extendednav', (object)['menu' => 'primary', 'nodekey' => 'partner', 'title' => 'Partner',
            'url' => 'https://partner.example.org/mod/page/view.php?id=7', 'visibility' => 0, 'blockaccess' => 1,
            'sortorder' => 50]);
        node_repository::purge();

        $this->assertNull(access_guard::evaluate('/mod/page/view.php?id=7', $this->student->id));
    }

    /**
     * Admin, login and plugin pages are never blocked.
     */
    public function test_immune_pages(): void {
        $this->navgenerator->create_node(['nodekey' => 'secret', 'title' => 'Secret', 'url' => '/login/index.php',
            'visibility' => 0, 'blockaccess' => 1]);
        $this->assertNull(access_guard::evaluate('/login/index.php', $this->student->id));
        $this->assertTrue(access_guard::is_immune('/admin/search.php'));
        $this->assertTrue(access_guard::is_immune('/local/extendednav/manage.php'));
        $this->assertFalse(access_guard::is_immune('/my/'));
    }

    /**
     * The fallback check reports when users would have no page to land on.
     */
    public function test_check_fallbacks(): void {
        $this->assertNull(access_guard::check_fallbacks(node_repository::get_all()));

        $this->navgenerator->create_node(['nodekey' => 'myhome', 'visibility' => 0, 'blockaccess' => 1]);
        $this->navgenerator->create_node(['nodekey' => 'home', 'visibility' => 0, 'blockaccess' => 1]);
        $this->navgenerator->create_node(['nodekey' => 'mycourses', 'visibility' => 2, 'roles' => 'editingteacher',
            'blockaccess' => 1]);
        $this->assertSame(access_guard::FALLBACKS_BLOCKED_FOR_SOME, access_guard::check_fallbacks(node_repository::get_all()));

        $this->navgenerator->create_node(['nodekey' => 'mycourses', 'visibility' => 0, 'blockaccess' => 1]);
        $this->assertSame(access_guard::FALLBACKS_BLOCKED, access_guard::check_fallbacks(node_repository::get_all()));
    }

    /**
     * A custom home page configured as a URL is also a fallback.
     */
    public function test_custom_default_home_page_is_a_fallback(): void {
        set_config('defaulthomepage', '/mod/page/view.php?id=3');
        $this->assertContains('/mod/page/view.php?id=3', access_guard::get_fallbacks());

        foreach (['myhome', 'home', 'mycourses'] as $key) {
            $this->navgenerator->create_node(['nodekey' => $key, 'visibility' => 0, 'blockaccess' => 1]);
        }
        $this->assertSame('/mod/page/view.php?id=3', access_guard::evaluate('/my/', $this->student->id));
        $this->assertNull(access_guard::check_fallbacks(node_repository::get_all()));
    }
}
