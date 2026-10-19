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

#[\PHPUnit\Framework\Attributes\CoversClass(\local_extendednav\local\icon_catalogue::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_extendednav\local\node_icon::class)]
/**
 * Tests for the icons of the icon picker and how node icons are shown.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\local\icon_catalogue
 * @covers     \local_extendednav\local\node_icon
 */
final class icon_catalogue_test extends \advanced_testcase {
    /**
     * Moodle's icons come from its icon map, with the classes Moodle shows them with.
     */
    public function test_moodle_icons(): void {
        $icons = array_column(icon_catalogue::build()['moodle'], null, 'value');
        $map = \core\output\icon_system::instance(\core\output\icon_system::FONTAWESOME)->get_icon_name_map();

        $this->assertArrayHasKey('core:i/home', $icons);
        $this->assertStringContainsString(trim(explode(' ', trim($map['core:i/home']))[0]), $icons['core:i/home']['classes']);
        foreach (array_keys($icons) as $value) {
            $this->assertTrue(node_validator::is_valid_icon($value), $value);
        }
    }

    /**
     * FontAwesome icons are listed once per glyph, with their other names as aliases, and every value is valid.
     */
    public function test_fontawesome_icons(): void {
        $catalogue = icon_catalogue::build();
        $solid = array_column($catalogue['fontawesome'], null, 'label');
        $brands = array_column($catalogue['brands'], null, 'value');

        $this->assertGreaterThan(1000, count($solid));
        $this->assertSame('fa-house', $solid['house']['value']);
        $this->assertContains('home', $solid['house']['aliases']);
        $this->assertArrayNotHasKey('home', $solid, 'An alias is not listed again.');
        $this->assertArrayHasKey('fa-brands fa-github', $brands);
        $this->assertSame('icon fa fa-brands fa-github fa-fw', $brands['fa-brands fa-github']['classes']);

        $values = array_merge(array_column($catalogue['fontawesome'], 'value'), array_column($catalogue['brands'], 'value'));
        $this->assertSame(count($values), count(array_unique($values)));
        foreach ($values as $value) {
            $this->assertTrue(node_validator::is_fontawesome($value), $value);
        }
    }

    /**
     * The list is built once and then read from the cache.
     */
    public function test_cached(): void {
        $this->resetAfterTest();
        $first = icon_catalogue::get_all();
        $this->assertSame($first, \cache::make('local_extendednav', 'icons')->get('catalogue'));
        $this->assertSame($first, icon_catalogue::get_all());
    }

    /**
     * Node icons are shown as FontAwesome icons or Moodle icons, and turned into FontAwesome where needed.
     */
    public function test_node_icons(): void {
        $map = \core\output\icon_system::instance(\core\output\icon_system::FONTAWESOME)->get_icon_name_map();

        $this->assertStringContainsString('fa-book', node_icon::render('fa-book'));
        $this->assertNotSame('', node_icon::render('core:i/home'));
        $this->assertSame(node_icon::render('i/home'), node_icon::render('core:i/home'), 'Core icons need no component.');
        $big = node_icon::render('core:b/document-properties');
        $this->assertStringContainsString('fa-wrench', $big);
        $this->assertStringNotContainsString('iconsize-big', $big, 'Big Moodle icons are shown at menu size.');
        $this->assertSame('', node_icon::render('none'));
        $this->assertSame('', node_icon::render('<b>x</b>'));

        $this->assertSame('fa-brands fa-github', node_icon::get_fontawesome_classes('fa-brands fa-github'));
        $this->assertSame(trim($map['core:i/home']), node_icon::get_fontawesome_classes('core:i/home'));
        $this->assertSame(trim($map['core:i/home']), node_icon::get_fontawesome_classes('i/home'));
        $this->assertNull(node_icon::get_fontawesome_classes('core:i/doesnotexist'));
        $this->assertNull(node_icon::get_fontawesome_classes(''));
    }
}
