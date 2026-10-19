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

use local_extendednav\yaml;

#[\PHPUnit\Framework\Attributes\CoversClass(\local_extendednav\local\node_importer::class)]
/**
 * Tests for the YAML import and export of nodes.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\local\node_importer
 */
final class node_importer_test extends \advanced_testcase {
    /**
     * Finds Moodle's primary navigation items for the tests.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->getDataGenerator()->get_plugin_generator('local_extendednav')->ensure_native_nodes();
    }

    /**
     * Exported nodes can be imported again with the same values.
     */
    public function test_export_import_round_trip(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_extendednav');
        $generator->create_node(['nodekey' => 'help_es', 'title' => 'Ayuda: guías #1', 'tooltip' => 'Contacto',
            'url' => '/mod/page/view.php?id=2&x=1', 'icon' => 'fa-circle-question', 'visibility' => 2,
            'roles' => 'editingteacher,teacher', 'blockaccess' => 1, 'langs' => 'es,es_mx', 'display' => 2,
            'popupwidth' => 800, 'popupheight' => 600]);
        $generator->create_node(['nodekey' => 'myhome', 'title' => 'true']);
        // Put the custom node first, so the import has to move it back there.
        $help = $DB->get_record('local_extendednav', ['nodekey' => 'help_es']);
        $others = $DB->get_fieldset_select(
            'local_extendednav',
            'id',
            'menu = ? AND id <> ? ORDER BY sortorder',
            ['primary', $help->id]
        );
        node_manager::reorder('primary', array_merge([(int)$help->id], array_map('intval', $others)));

        $nodes = $DB->get_records('local_extendednav', ['menu' => 'primary'], 'sortorder ASC');
        $before = node_importer::export_nodes($nodes, $nodes);
        $yamltext = yaml::dump(['nodes' => $before], false);
        $this->assertSame(1, $before[0]['order']);
        $this->assertSame('help_es', $before[0]['nodekey']);

        $DB->delete_records('local_extendednav', ['nodekey' => 'help_es']);
        $DB->set_field('local_extendednav', 'title', null, ['nodekey' => 'myhome']);
        node_repository::purge();

        $parsed = yaml::parse($yamltext, false);
        $result = (new node_importer('primary', true))->import($parsed['nodes']);
        $this->assertSame([], $result['errors']);
        $this->assertSame(1, $result['counts']['created']);

        $nodes = $DB->get_records('local_extendednav', ['menu' => 'primary']);
        $after = node_importer::export_nodes($nodes, $nodes);
        $this->assertEquals($before, $after, 'Same values, in the same order.');
    }

    /**
     * Imported nodes go to the position of their order field; the others keep their order.
     */
    public function test_import_order(): void {
        global $DB;

        $this->resetAfterTest();
        $menu = static function () use ($DB): array {
            $nodes = node_manager::group_by_parent($DB->get_records('local_extendednav', ['menu' => 'primary']));
            return array_map(static fn(\stdClass $node): string => $node->nodekey, $nodes);
        };
        $existing = $menu();

        $result = (new node_importer('primary', true))->import([
            ['nodekey' => 'third', 'title' => 'Third', 'url' => '/third.php', 'order' => 3],
            ['nodekey' => 'first', 'title' => 'First', 'url' => '/first.php', 'order' => 1],
            ['nodekey' => 'last', 'title' => 'Last', 'url' => '/last.php'],
        ]);
        $this->assertSame([], $result['errors']);
        $expected = $existing;
        array_splice($expected, 0, 0, ['first']);
        array_splice($expected, 2, 0, ['third']);
        $expected[] = 'last';
        $this->assertSame($expected, $menu());

        // A replaced node without an order keeps its place.
        $result = (new node_importer('primary', true))->import([
            ['nodekey' => 'first', 'title' => 'First!', 'url' => '/first.php'],
        ]);
        $this->assertSame([], $result['errors']);
        $this->assertSame($expected, $menu());

        foreach ([0, -1, 'abc', 1.5] as $order) {
            $result = (new node_importer('primary', true))->import([['nodekey' => 'x', 'title' => 'X', 'url' => '/x.php',
                'order' => $order]]);
            $this->assertCount(1, $result['errors'], (string)$order);
        }
    }

    /**
     * Nothing is imported when any node is invalid.
     */
    public function test_import_is_all_or_nothing(): void {
        global $DB;

        $this->resetAfterTest();
        $count = $DB->count_records('local_extendednav');
        $result = (new node_importer('primary', false))->import([
            ['nodekey' => 'good', 'title' => 'Good', 'url' => '/good.php'],
            ['nodekey' => 'bad', 'title' => 'Bad', 'url' => 'javascript:alert(1)'],
            ['title' => 'No key'],
            ['nodekey' => 'x', 'menu' => 'sidebar', 'title' => 'X', 'url' => '/x.php'],
        ]);

        $this->assertCount(3, $result['errors']);
        $this->assertStringContainsString('primary/bad (url)', $result['errors'][2]);
        $this->assertSame($count, $DB->count_records('local_extendednav'));
    }

    /**
     * Existing nodes are kept or replaced, as chosen.
     */
    public function test_conflicts(): void {
        global $DB;

        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_extendednav');
        $generator->create_node(['nodekey' => 'help', 'title' => 'Help', 'url' => '/help.php']);
        $items = [['nodekey' => 'help', 'title' => 'New help', 'url' => '/help.php']];

        $result = (new node_importer('primary', false))->import($items);
        $this->assertSame(1, $result['counts']['skipped']);
        $this->assertSame('Help', $DB->get_field('local_extendednav', 'title', ['nodekey' => 'help']));

        $result = (new node_importer('primary', true))->import($items);
        $this->assertSame(1, $result['counts']['updated']);
        $this->assertSame('New help', $DB->get_field('local_extendednav', 'title', ['nodekey' => 'help']));
    }

    /**
     * Parents can be defined in the same file, and hierarchy rules apply to the whole file.
     */
    public function test_parents_in_the_same_file(): void {
        $this->resetAfterTest();
        $result = (new node_importer('primary', false))->import([
            ['nodekey' => 'child', 'title' => 'Child', 'url' => '/c.php', 'parentkey' => 'parent'],
            ['nodekey' => 'parent', 'title' => 'Parent', 'url' => '/p.php'],
        ]);
        $this->assertSame([], $result['errors']);

        $result = (new node_importer('primary', false))->import([
            ['nodekey' => 'grandchild', 'title' => 'Grandchild', 'url' => '/g.php', 'parentkey' => 'child'],
        ]);
        $this->assertStringContainsString(get_string('err_parent_thirdlevel', 'local_extendednav'), $result['errors'][0]);
    }

    /**
     * Importing cannot leave every user without a fallback page.
     */
    public function test_import_checks_fallbacks(): void {
        $this->resetAfterTest();
        $items = [];
        foreach (['myhome', 'home', 'mycourses'] as $key) {
            $items[] = ['nodekey' => $key, 'visibility' => 0, 'blockaccess' => 1];
        }
        $result = (new node_importer('primary', true))->import($items);
        $this->assertSame([get_string('err_fallbacks_blocked', 'local_extendednav')], $result['errors']);
    }

    /**
     * Unknown roles are imported with a warning.
     */
    public function test_unknown_role_warning(): void {
        $this->resetAfterTest();
        $result = (new node_importer('primary', false))->import([
            ['nodekey' => 'x', 'title' => 'X', 'url' => '/x.php', 'visibility' => 2, 'roles' => 'ghost'],
        ]);
        $this->assertSame([], $result['errors']);
        $this->assertStringContainsString('ghost', $result['warnings'][0]);
    }
}
