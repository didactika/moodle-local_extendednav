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
 * Base menu class.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_extendednav\menu;

/**
 * Base abstract class for scalable menu management.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /**
     * Gets the unique string key for this menu type (e.g. 'primary').
     *
     * @return string
     */
    abstract public function get_key(): string;

    /**
     * Gets the human-readable localized name of this menu.
     *
     * @return string
     */
    abstract public function get_name(): string;

    /**
     * Synchronizes native core nodes into the plugin database.
     */
    abstract public function sync_native_nodes(): void;

    /**
     * Gets all native nodes belonging to this menu as an array of objects.
     *
     * @return array Associative array of nodekey => object
     */
    abstract public function get_native_nodes(): array;

    /**
     * Injects the custom database nodes back into Moodle's native navigation structure.
     *
     * @param array $nodes Array of database nodes for this menu
     * @param mixed $context Optional context (like a hook object)
     */
    abstract public function inject_nodes(array $nodes, $context = null): void;

    /**
     * Gets the associated core config setting name (e.g. 'custommenuitems', 'customusermenuitems'), if any.
     *
     * @return string|null
     */
    public function get_config_name(): ?string {
        return null;
    }

    /**
     * Gets the prefix used when storing items from this config (e.g. 'cmenu_', 'umenu_').
     *
     * @return string
     */
    public function get_config_prefix(): string {
        return '';
    }

    /**
     * Synchronizes this menu's associated Moodle configuration into the database.
     */
    public function sync_from_config(): void {
        $configname = $this->get_config_name();
        $prefix = $this->get_config_prefix();
        if (empty($configname)) {
            return;
        }
        \local_extendednav\observer::sync_custom_menu($configname, $prefix, $this->get_key());
    }

    /**
     * Removes specified deleted node records from this menu's associated Moodle configuration.
     *
     * @param array $deletedrecords Array of record objects deleted from {local_extendednav}
     */
    public function remove_nodes_from_config(array $deletedrecords): void {
        $configname = $this->get_config_name();
        $prefix = $this->get_config_prefix();
        if (empty($configname) || empty($deletedrecords)) {
            return;
        }
        \local_extendednav\observer::remove_from_config_by_definition($configname, $prefix, $deletedrecords);
    }
}
