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

/**
 * Tests for the built-in YAML reader and writer.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\yaml
 */
final class yaml_test extends \basic_testcase {
    /**
     * Strings survive a write and read unchanged.
     *
     * @dataProvider string_provider
     * @param string $value String to write.
     */
    public function test_string_round_trip(string $value): void {
        $data = ['nodes' => [['nodekey' => 'x', 'title' => $value, 'visibility' => 1]]];
        $parsed = yaml::parse(yaml::dump($data, false), false);
        $this->assertSame($data, $parsed);
    }

    /**
     * Strings YAML could read as something else.
     *
     * @return array
     */
    public static function string_provider(): array {
        return [
            'plain' => ['Help'],
            'empty' => [''],
            'boolean word' => ['true'],
            'no' => ['no'],
            'null word' => ['null'],
            'tilde' => ['~'],
            'number' => ['42'],
            'decimal' => ['1.5'],
            'comment start' => ['#1 Resources'],
            'inner comment' => ['Item #2'],
            'colon space' => ['Note: read this'],
            'url' => ['https://example.com/a?b=1&c=2'],
            'quotes' => ['Say "hi" and \'bye\''],
            'backslash' => ['C:\\path\\to'],
            'leading dash' => ['- not a list'],
            'flow' => ['[x]'],
            'trailing space' => ['space '],
            'unicode' => ['Guías y ayuda ñ'],
            'line break' => ["two\nlines"],
            'language string' => ['profile,moodle'],
        ];
    }

    /**
     * The example file of the plugin can be read.
     */
    public function test_parse_example_file(): void {
        global $CFG;

        $parsed = yaml::parse(file_get_contents($CFG->dirroot . '/local/extendednav/example.yaml'), false);
        $this->assertArrayHasKey('nodes', $parsed);
        $this->assertSame('help_es', $parsed['nodes'][2]['nodekey']);
        $this->assertSame('Guías y datos de contacto', $parsed['nodes'][2]['tooltip']);
        $this->assertSame(1, $parsed['nodes'][3]['newwindow']);
        $this->assertSame('', $parsed['nodes'][0]['title']);
    }

    /**
     * Comments, quotes and a byte order mark are handled.
     */
    public function test_parse_details(): void {
        $text = "\xEF\xBB\xBF# Exported\r\nnodes:\r\n  - nodekey: a # comment\r\n    title: 'It''s'\r\n    url: \"/a.php\"\r\n";
        $this->assertSame(['nodes' => [['nodekey' => 'a', 'title' => "It's", 'url' => '/a.php']]], yaml::parse($text, false));
    }
}
