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

#[\PHPUnit\Framework\Attributes\CoversClass(\local_extendednav\local\url_helper::class)]
/**
 * Tests for the URL helper.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\local\url_helper
 */
final class url_helper_test extends \advanced_testcase {
    #[\PHPUnit\Framework\Attributes\DataProvider('normalise_provider')]
    /**
     * Typed URLs are normalised.
     *
     * @dataProvider normalise_provider
     * @param string $input URL as typed.
     * @param string|null $expected Normalised URL.
     */
    public function test_normalise(string $input, ?string $expected): void {
        $this->assertSame($expected, url_helper::normalise($input));
    }

    /**
     * Cases for {@see test_normalise()}.
     *
     * @return array
     */
    public static function normalise_provider(): array {
        return [
            'empty' => ['  ', null],
            'relative without slash' => ['course/view.php?id=2', '/course/view.php?id=2'],
            'relative' => ['/my/', '/my/'],
            'www' => ['www.example.com', 'https://www.example.com'],
            'protocol relative' => ['//example.com/a', 'https://example.com/a'],
            'absolute' => ['http://example.com/a', 'http://example.com/a'],
        ];
    }

    /**
     * Dangerous schemes are detected.
     */
    public function test_has_forbidden_scheme(): void {
        $this->assertTrue(url_helper::has_forbidden_scheme('javascript:alert(1)'));
        $this->assertTrue(url_helper::has_forbidden_scheme(' JavaScript:alert(1)'));
        $this->assertTrue(url_helper::has_forbidden_scheme('data:text/html,hi'));
        $this->assertFalse(url_helper::has_forbidden_scheme('https://example.com'));
        $this->assertFalse(url_helper::has_forbidden_scheme('/my/'));
    }

    /**
     * Site paths are built for URLs of the site only.
     */
    public function test_to_site_path(): void {
        global $CFG;

        $this->resetAfterTest();
        $this->assertSame('/my/', url_helper::to_site_path('/my/index.php'));
        $this->assertSame('/', url_helper::to_site_path('/index.php'));
        $this->assertSame('/course/view.php?id=2', url_helper::to_site_path($CFG->wwwroot . '/course/view.php?id=2#section-1'));
        $this->assertNull(url_helper::to_site_path('https://external.example.org/'));
        $this->assertNull(url_helper::to_site_path('javascript:alert(1)'));
        $this->assertNull(url_helper::to_site_path(''));

        // Moodle installed in a subdirectory.
        $CFG->wwwroot = 'https://lms.example.com/moodle';
        $this->assertSame('/my/', url_helper::to_site_path('https://lms.example.com/moodle/my/'));
        $this->assertSame('/', url_helper::to_site_path('https://lms.example.com/moodle'));
        $this->assertNull(url_helper::to_site_path('https://lms.example.com/other/my/'));
        $this->assertNull(url_helper::to_site_path('https://lms.example.com:8443/moodle/my/'));
        $this->assertSame('/my/', url_helper::to_site_path('/my/'));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('matches_provider')]
    /**
     * Rules match the same page, with the rule's query parameters.
     *
     * @dataProvider matches_provider
     * @param string $rule Site path of the rule.
     * @param string $current Site path of the page.
     * @param bool $expected Whether they match.
     */
    public function test_matches(string $rule, string $current, bool $expected): void {
        $this->assertSame($expected, url_helper::matches($rule, $current));
    }

    /**
     * Cases for {@see test_matches()}.
     *
     * @return array
     */
    public static function matches_provider(): array {
        return [
            'same page' => ['/my/courses.php', '/my/courses.php', true],
            'index page' => ['/my/', '/my/index.php', true],
            'dashboard does not cover my courses' => ['/my/', '/my/courses.php', false],
            'site home with a query' => ['/', '/?redirect=0', true],
            'site home does not cover everything' => ['/', '/course/view.php?id=2', false],
            'extra page parameters' => ['/course/view.php?id=5', '/course/view.php?id=5&section=2', true],
            'other course' => ['/course/view.php?id=5', '/course/view.php?id=6', false],
            'missing parameter' => ['/course/view.php?id=5', '/course/view.php', false],
            'rule without query' => ['/user/profile.php', '/user/profile.php?id=3', true],
            'no substring match' => ['/my/', '/local/thing/my/', false],
        ];
    }
}
