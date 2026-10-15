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

use local_extendednav\local\node_manager;

/**
 * Test data generator for local_extendednav.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_extendednav_generator extends component_generator_base {
    /**
     * Creates a node, or updates the core node with the same key.
     *
     * @param array $data Node fields; 'menu' defaults to primary, 'visibility' to 1.
     * @return \stdClass The saved record.
     */
    public function create_node(array $data): \stdClass {
        global $DB;

        $menu = $data['menu'] ?? 'primary';
        $record = node_manager::prepare($data + ['visibility' => 1], $menu);
        if (isset($data['sortorder'])) {
            $record->sortorder = (int)$data['sortorder'];
        }

        $existing = $DB->get_record('local_extendednav', ['menu' => $menu, 'nodekey' => $record->nodekey]);
        if ($existing) {
            $record->id = $existing->id;
            return node_manager::update($record, false);
        }
        return node_manager::create($record, false);
    }
}
