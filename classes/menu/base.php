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

namespace local_extendednav\menu;

use local_extendednav\local\node_manager;
use local_extendednav\local\node_text;
use local_extendednav\observer;

/**
 * Base class of a menu the plugin manages.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class base {
    /**
     * Returns the menu key stored in the menu field (for example 'primary').
     *
     * @return string
     */
    abstract public function get_key(): string;

    /**
     * Returns the localised menu name.
     *
     * @return string
     */
    abstract public function get_name(): string;

    /**
     * Returns the core nodes of this menu.
     *
     * Each node is an object with key, text (a title or an "identifier,component"
     * string), action (a site path such as "/my/") and icon.
     *
     * @return \stdClass[] Keyed by node key.
     */
    abstract public function get_native_nodes(): array;

    /**
     * Splits one line of the menu's Moodle setting into its fields.
     *
     * @param string $line Line without the leading dashes.
     * @return array With keys text, url, tooltip and langs.
     */
    abstract public function parse_config_line(string $line): array;

    /**
     * Returns the Moodle setting that defines items of this menu, if any.
     *
     * @return string|null For example 'custommenuitems'.
     */
    public function get_config_name(): ?string {
        return null;
    }

    /**
     * Returns the prefix of the keys of nodes imported from the Moodle setting.
     *
     * @return string For example 'cmenu_'.
     */
    public function get_config_prefix(): string {
        return '';
    }

    /**
     * Builds the node key of an item of a Moodle menu setting.
     *
     * @param string $prefix Key prefix of the menu.
     * @param string $text Item text.
     * @return string
     */
    public static function make_key(string $prefix, string $text): string {
        $slug = preg_replace('/[^a-z0-9]/', '', strtolower($text));
        return $prefix . substr($slug, 0, 15);
    }

    /**
     * Returns one core node of this menu.
     *
     * @param string $nodekey Node key.
     * @return \stdClass|null
     */
    public function get_native_node(string $nodekey): ?\stdClass {
        return $this->get_native_nodes()[$nodekey] ?? null;
    }

    /**
     * Whether the key belongs to a core node of this menu.
     *
     * @param string $nodekey Node key.
     * @return bool
     */
    public function is_native(string $nodekey): bool {
        return $this->get_native_node($nodekey) !== null;
    }

    /**
     * Returns the core title and URL of every core node, for form placeholders.
     *
     * @return array Keyed by node key, each with 'title' (in the current language) and 'url'.
     */
    public function get_placeholders(): array {
        $placeholders = [];
        foreach ($this->get_native_nodes() as $key => $native) {
            $placeholders[$key] = [
                'title' => strip_tags(node_text::resolve($native->text)),
                'url' => (string)$native->action,
            ];
        }
        return $placeholders;
    }

    /**
     * Creates an empty row for every core node that does not have one yet.
     *
     * Rows are created without title, URL or icon, so the core values are used.
     * Existing rows are never changed.
     *
     * @param bool $events Whether to trigger node created events.
     */
    public function sync_native_nodes(bool $events = true): void {
        global $DB;

        $existing = $DB->get_records_menu('local_extendednav', ['menu' => $this->get_key()], '', 'nodekey, id');
        foreach ($this->get_native_nodes() as $key => $native) {
            if (isset($existing[$key])) {
                continue;
            }
            $record = node_manager::prepare(['nodekey' => $key, 'visibility' => 1], $this->get_key());
            node_manager::create($record, $events);
        }
    }

    /**
     * Imports the items of the menu's Moodle setting.
     *
     * @param bool $events Whether to trigger node events.
     */
    public function sync_from_config(bool $events = true): void {
        if ($this->get_config_name() !== null) {
            observer::sync_custom_menu($this, $events);
        }
    }

    /**
     * Makes sure core rows exist and the Moodle setting has been imported.
     *
     * The setting is only imported again when it changed since the last import, for
     * example when it was changed from the command line, which raises no event.
     */
    public function ensure_synced(): void {
        $this->sync_native_nodes();
        $configname = $this->get_config_name();
        if (
            $configname !== null && observer::get_config_hash($configname)
                !== get_config('local_extendednav', 'confighash_' . $configname)
        ) {
            $this->sync_from_config();
        }
    }

    /**
     * Removes deleted nodes from the menu's Moodle setting.
     *
     * @param \stdClass[] $records Deleted records.
     */
    public function remove_nodes_from_config(array $records): void {
        if ($this->get_config_name() !== null && !empty($records)) {
            observer::remove_from_config($this, $records);
        }
    }
}
