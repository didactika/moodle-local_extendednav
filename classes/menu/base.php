<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_extendednav\menu;

defined('MOODLE_INTERNAL') || die();

/**
 * Base abstract class for scalable menu management.
 * @package local_extendednav
 */
abstract class base {
    /**
     * Gets the unique string key for this menu type (e.g. 'primary').
     * @return string
     */
    abstract public function get_key(): string;

    /**
     * Gets the human-readable localized name of this menu.
     * @return string
     */
    abstract public function get_name(): string;

    /**
     * Synchronizes native core nodes into the plugin database.
     */
    abstract public function sync_native_nodes(): void;

    /**
     * Gets all native nodes belonging to this menu as an array of objects.
     * @return array Associative array of nodekey => object
     */
    abstract public function get_native_nodes(): array;

    /**
     * Injects the custom database nodes back into Moodle's native navigation structure.
     * @param array $nodes Array of database nodes for this menu
     * @param mixed $context Optional context (like a hook object)
     */
    abstract public function inject_nodes(array $nodes, $context = null): void;
}
