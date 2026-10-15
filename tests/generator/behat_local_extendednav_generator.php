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
 * Behat data generator for local_extendednav.
 *
 * Adds the "the following "local_extendednav > nodes" exist:" step.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_extendednav_generator extends behat_generator_base {
    /**
     * Returns the entities this generator creates.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'nodes' => [
                'singular' => 'node',
                'datagenerator' => 'node',
                'required' => ['nodekey'],
            ],
        ];
    }
}
