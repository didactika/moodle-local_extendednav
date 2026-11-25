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

namespace local_extendednav\form;

use local_extendednav\local\node\access;

#[\PHPUnit\Framework\Attributes\CoversClass(\local_extendednav\form\node_form::class)]
/**
 * Tests for the form of a node.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\form\node_form
 */
final class node_form_test extends \advanced_testcase {
    /**
     * Submits the form of a node with some values.
     *
     * @param \stdClass $node The node edited.
     * @param array $values The values sent.
     * @return \stdClass|null What the form gives to save.
     */
    private static function submit(\stdClass $node, array $values): ?\stdClass {
        node_form::mock_submit($values);
        $form = new node_form(
            new \moodle_url('/local/extendednav/edit.php', ['menu' => 'primary', 'id' => $node->id]),
            ['menu' => 'primary', 'node' => $node, 'contextid' => 0, 'locked' => []]
        );
        return $form->get_data();
    }

    /**
     * While Moodle's restricted access is turned off the form has no access restrictions, and saving a node keeps the ones
     * it has; while it is on, the editor decides them.
     */
    public function test_the_access_restrictions_are_kept_when_the_form_has_no_editor(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $conditions = json_encode(['op' => '&', 'c' => [['type' => 'date', 'd' => '>=', 't' => 1000]], 'showc' => [true]]);
        $node = $this->getDataGenerator()->get_plugin_generator('local_extendednav')->create_node([
            'nodekey' => 'dated', 'title' => 'Dated', 'url' => '/dated.php',
            'visibility' => access::VISIBILITY_ROLES, 'availability' => $conditions,
        ]);
        $values = ['id' => $node->id, 'nodekey' => 'dated', 'treetype' => 'page', 'title' => 'Dated again', 'url' => '/dated.php',
            'visibility' => access::VISIBILITY_ROLES, 'roles' => [], 'langs' => []];

        set_config('enableavailability', 0);
        $data = self::submit($node, $values);
        $this->assertNotNull($data, 'A node restricted only by its access restrictions is saved with them.');
        $this->assertSame($conditions, $data->availability);

        set_config('enableavailability', 1);
        $data = self::submit($node, $values + ['availabilityconditionsjson' => '']);
        $this->assertNull($data, 'With the editor, a restricted node with no roles and no restrictions is refused.');
    }
}
