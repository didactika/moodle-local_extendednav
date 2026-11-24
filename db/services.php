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

/**
 * External functions for local_extendednav.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_extendednav_get_icons' => [
        'classname' => \local_extendednav\external\get_icons::class,
        'description' => 'Lists the icons the icon picker offers: Moodle icons and FontAwesome icons.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/extendednav:manage',
    ],
    'local_extendednav_reorder_nodes' => [
        'classname' => \local_extendednav\external\reorder_nodes::class,
        'description' => 'Saves a new order for the nodes of one menu.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/extendednav:manage',
    ],
    'local_extendednav_get_nodes' => [
        'classname' => \local_extendednav\external\get_nodes::class,
        'methodname' => 'execute',
        'description' => 'Returns one page of the nodes of a navigation, with the counts of each visibility.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/extendednav:manage',
    ],
    'local_extendednav_get_activity' => [
        'classname' => \local_extendednav\external\get_activity::class,
        'methodname' => 'execute',
        'description' => 'Returns one page of the activity log of a navigation, with the rows of each action.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/extendednav:manage',
    ],
    'local_extendednav_get_exceptions' => [
        'classname' => \local_extendednav\external\get_exceptions::class,
        'methodname' => 'execute',
        'description' => 'Returns one page of the categories and courses that change a navigation.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/extendednav:manage',
    ],
    'local_extendednav_set_visibility' => [
        'classname' => \local_extendednav\external\change_nodes::class,
        'methodname' => 'set_visibility',
        'description' => 'Shows or hides nodes of a navigation.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/extendednav:manage',
    ],
    'local_extendednav_delete_nodes' => [
        'classname' => \local_extendednav\external\change_nodes::class,
        'methodname' => 'delete',
        'description' => 'Deletes nodes of the site, or resets the exceptions of a category or a course.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/extendednav:manage',
    ],
    'local_extendednav_move_node' => [
        'classname' => \local_extendednav\external\change_nodes::class,
        'methodname' => 'move',
        'description' => 'Moves a node one place among its siblings.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/extendednav:manage',
    ],
    'local_extendednav_place_node' => [
        'classname' => \local_extendednav\external\change_nodes::class,
        'methodname' => 'place',
        'description' => 'Places a node before another one among its siblings, as when it is dragged.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/extendednav:manage',
    ],
    'local_extendednav_set_enabled' => [
        'classname' => \local_extendednav\external\change_nodes::class,
        'methodname' => 'set_enabled',
        'description' => 'Turns the changes of one navigation on or off.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/extendednav:manage',
    ],
];
