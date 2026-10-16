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

#[\PHPUnit\Framework\Attributes\CoversClass(\local_extendednav\local\node_manager::class)]
/**
 * Tests for saving, deleting and ordering nodes.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\local\node_manager
 */
final class node_manager_test extends \advanced_testcase {
    /**
     * Finds Moodle's primary navigation items for the tests.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->getDataGenerator()->get_plugin_generator('local_extendednav')->ensure_native_nodes();
    }

    /**
     * Submitted values are normalised.
     */
    public function test_prepare(): void {
        $this->resetAfterTest();

        $record = node_manager::prepare([
            'nodekey' => ' help ',
            'title' => '  ',
            'url' => 'course/view.php?id=2',
            'visibility' => '1',
            'roles' => ['editingteacher'],
            'langs' => ['es', 'pt_br', 'es'],
            'blockaccess' => 1,
            'parentkey' => '',
            'newwindow' => '1',
        ], 'primary');

        $this->assertSame('help', $record->nodekey);
        $this->assertNull($record->title);
        $this->assertSame('/course/view.php?id=2', $record->url);
        $this->assertNull($record->roles, 'Roles only apply to restricted nodes.');
        $this->assertSame('es,pt_br', $record->langs);
        $this->assertSame(0, $record->blockaccess, 'Visible nodes never block.');
        $this->assertNull($record->parentkey);
        $this->assertSame(1, $record->newwindow);
    }

    /**
     * "Block URL access" is kept only where it can apply.
     */
    public function test_prepare_block_access(): void {
        $this->resetAfterTest();

        $hidden = node_manager::prepare(['nodekey' => 'x', 'url' => '/x.php', 'visibility' => 0, 'blockaccess' => 1], 'primary');
        $this->assertSame(1, $hidden->blockaccess);

        $external = node_manager::prepare(['nodekey' => 'x', 'url' => 'https://example.org/', 'visibility' => 0,
            'blockaccess' => 1], 'primary');
        $this->assertSame(0, $external->blockaccess, 'External URLs cannot be blocked.');

        $core = node_manager::prepare(['nodekey' => 'myhome', 'visibility' => 2, 'roles' => 'manager',
            'blockaccess' => 1], 'primary');
        $this->assertSame(1, $core->blockaccess, 'Core nodes block their core URL.');
        $this->assertSame('manager', $core->roles);
    }

    /**
     * Creating and updating set timestamps and trigger events.
     */
    public function test_create_and_update(): void {
        $this->resetAfterTest();
        $sink = $this->redirectEvents();

        $created = node_manager::create(node_manager::prepare(
            ['nodekey' => 'help', 'title' => 'Help', 'url' => '/h.php'],
            'primary'
        ));
        $this->assertGreaterThan(0, $created->timecreated);
        $this->assertSame($created->timecreated, $created->timemodified);

        $updated = node_manager::update((object)['id' => $created->id, 'title' => 'Help desk']);
        $this->assertSame('Help desk', $updated->title);
        $this->assertSame('/h.php', $updated->url);

        $events = $sink->get_events();
        $this->assertInstanceOf(\local_extendednav\event\node_created::class, $events[0]);
        $this->assertInstanceOf(\local_extendednav\event\node_updated::class, $events[1]);
    }

    /**
     * New nodes go to the end of their menu.
     */
    public function test_create_appends(): void {
        global $DB;

        $this->resetAfterTest();
        $max = (int)$DB->get_field_sql('SELECT MAX(sortorder) FROM {local_extendednav} WHERE menu = ?', ['primary']);
        $node = node_manager::create(node_manager::prepare(
            ['nodekey' => 'last', 'title' => 'Last', 'url' => '/l.php'],
            'primary'
        ), false);
        $this->assertSame($max + 1, (int)$node->sortorder);
    }

    /**
     * Core nodes Moodle adds are not deleted.
     */
    public function test_delete_skips_core_nodes(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_extendednav');
        $custom = $generator->create_node(['nodekey' => 'help', 'title' => 'Help', 'url' => '/h.php']);
        $core = $DB->get_record('local_extendednav', ['menu' => 'primary', 'nodekey' => 'myhome']);

        $this->assertSame(1, node_manager::delete([$custom, $core]));
        $this->assertFalse($DB->record_exists('local_extendednav', ['id' => $custom->id]));
        $this->assertTrue($DB->record_exists('local_extendednav', ['id' => $core->id]));
    }

    /**
     * Nodes can be reordered; nodes missing from the list keep their order after the listed ones.
     */
    public function test_reorder(): void {
        global $DB;

        $this->resetAfterTest();
        $ids = array_map('intval', array_keys($DB->get_records(
            'local_extendednav',
            ['menu' => 'primary'],
            'sortorder ASC, id ASC',
            'id'
        )));
        $reversed = array_reverse($ids);

        node_manager::reorder('primary', $reversed);
        $this->assertSame($reversed, array_map('intval', array_keys($DB->get_records(
            'local_extendednav',
            ['menu' => 'primary'],
            'sortorder ASC',
            'id'
        ))));

        node_manager::reorder('primary', [$ids[1]]);
        $expected = array_merge([$ids[1]], array_values(array_diff($reversed, [$ids[1]])));
        $this->assertSame($expected, array_map('intval', array_keys($DB->get_records(
            'local_extendednav',
            ['menu' => 'primary'],
            'sortorder ASC',
            'id'
        ))));
    }

    /**
     * Returns the node keys of the primary menu in the order they are shown.
     *
     * @return string[]
     */
    private function get_shown_keys(): array {
        global $DB;

        $nodes = $DB->get_records('local_extendednav', ['menu' => 'primary']);
        return array_map(static fn(\stdClass $node): string => $node->nodekey, node_manager::group_by_parent($nodes));
    }

    /**
     * Creates top-level nodes a, b and c, and children b1 and b2 of b, in that order.
     */
    private function create_tree(): void {
        global $DB;

        $DB->delete_records_select('local_extendednav', 'menu = ? AND nodekey <> ?', ['primary', 'siteadminnode']);
        $DB->delete_records('local_extendednav', ['nodekey' => 'siteadminnode']);
        $generator = $this->getDataGenerator()->get_plugin_generator('local_extendednav');
        $generator->create_node(['nodekey' => 'b1', 'title' => 'B1', 'url' => '/b1.php', 'sortorder' => 0]);
        $generator->create_node(['nodekey' => 'a', 'title' => 'A', 'url' => '/a.php', 'sortorder' => 1]);
        $generator->create_node(['nodekey' => 'b', 'title' => 'B', 'url' => '/b.php', 'sortorder' => 2]);
        $generator->create_node(['nodekey' => 'c', 'title' => 'C', 'url' => '/c.php', 'sortorder' => 3]);
        $generator->create_node(['nodekey' => 'b2', 'title' => 'B2', 'url' => '/b2.php', 'sortorder' => 4]);
        $DB->set_field_select('local_extendednav', 'parentkey', 'b', "nodekey IN ('b1', 'b2')");
        node_repository::purge();
    }

    /**
     * Children are shown right after their parent, in their own order.
     */
    public function test_group_by_parent(): void {
        $this->resetAfterTest();
        $this->create_tree();
        $this->assertSame(['a', 'b', 'b1', 'b2', 'c'], $this->get_shown_keys());

        // A child whose parent is missing is shown as a top-level node.
        $orphan = (object)['id' => 99, 'nodekey' => 'x', 'parentkey' => 'missing', 'sortorder' => 0];
        $this->assertSame('x', node_manager::group_by_parent([$orphan])[0]->nodekey);
    }

    /**
     * A node given a parent becomes its last child; a node that loses its parent stays below the group.
     */
    public function test_place_after_parent_change(): void {
        global $DB;

        $this->resetAfterTest();
        $this->create_tree();

        $before = $DB->get_record('local_extendednav', ['menu' => 'primary', 'nodekey' => 'a']);
        $DB->set_field('local_extendednav', 'parentkey', 'b', ['id' => $before->id]);
        node_manager::place_after_parent_change($before, 'b');
        $this->assertSame(['b', 'b1', 'b2', 'a', 'c'], $this->get_shown_keys());

        $before = $DB->get_record('local_extendednav', ['menu' => 'primary', 'nodekey' => 'b1']);
        $DB->set_field('local_extendednav', 'parentkey', null, ['id' => $before->id]);
        node_manager::place_after_parent_change($before, '');
        $this->assertSame(['b', 'b2', 'a', 'b1', 'c'], $this->get_shown_keys());
    }

    /**
     * Deleting a parent makes its children top-level nodes where the parent was.
     */
    public function test_delete_parent_keeps_children_in_place(): void {
        global $DB;

        $this->resetAfterTest();
        $this->create_tree();
        node_manager::delete([$DB->get_record('local_extendednav', ['menu' => 'primary', 'nodekey' => 'b'])]);

        $this->assertSame(['a', 'b1', 'b2', 'c'], $this->get_shown_keys());
        $this->assertSame(0, $DB->count_records_select('local_extendednav', 'parentkey IS NOT NULL'));
    }

    /**
     * The installation creates empty rows for the items Moodle adds.
     */
    public function test_install_creates_empty_core_rows(): void {
        global $DB;

        $natives = \local_extendednav\menu_manager::get_menu('primary')->get_native_nodes();
        $this->assertArrayHasKey('myhome', $natives);
        $this->assertArrayHasKey('siteadminnode', $natives);
        foreach (array_keys($natives) as $key) {
            $record = $DB->get_record('local_extendednav', ['menu' => 'primary', 'nodekey' => $key], '*', MUST_EXIST);
            $this->assertNull($record->title, $key);
            $this->assertNull($record->url, $key);
            $this->assertEquals(1, $record->visibility, $key);
        }
        $this->assertSame(5, $DB->count_records_select(
            'local_extendednav',
            'menu = ? AND title IS NULL AND url IS NULL',
            ['usermenu']
        ));
    }
}
