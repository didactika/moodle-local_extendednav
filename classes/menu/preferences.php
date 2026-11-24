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

use local_extendednav\local\node\manager;

/**
 * The page Preferences of a user: groups (User account, Badges, Blogs...) that hold items (Edit profile, Change password,
 * Forum preferences...), which Moodle and the plugins add to the settings of the user. The groups are the nodes of the top
 * level and the items their children, so both can be ordered, renamed and hidden, and items of the site can be added to a
 * group.
 *
 * Moodle has no list of them: they depend on the user and on the plugins. The plugin records the groups and items it sees when
 * the settings of a user are built, once each, as it does with the secondary navigations.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preferences extends base {
    /** @var string Key of the navigation. */
    public const KEY = 'preferences';

    /** @var string Plugin setting that keeps the groups and items found. */
    protected const STORED = 'nativenodes_preferences';

    /** @var string Key of the item of the plugin that opens the page where users order and hide their own preferences. */
    public const PERSONAL = 'local_extendednav_personal';

    /**
     * Returns the key of the navigation.
     *
     * @return string
     */
    public function get_key(): string {
        return static::KEY;
    }

    /**
     * Returns the name of the navigation.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('preferences_navigation', 'local_extendednav');
    }

    /**
     * Returns the short name of the navigation.
     *
     * @return string
     */
    public function get_short_name(): string {
        return get_string('preferences');
    }

    /**
     * The items of a Moodle setting do not apply here.
     *
     * @param string $line Line without the leading dashes.
     * @return array
     */
    public function parse_config_line(string $line): array {
        return ['text' => $line, 'url' => '', 'tooltip' => '', 'langs' => ''];
    }

    /**
     * Returns the groups and items found so far.
     *
     * @return \stdClass[] Keyed by node key, with key, text, action, parentkey and icon.
     */
    public function get_native_nodes(): array {
        $nodes = [];
        foreach ($this->get_stored() as $key => $item) {
            $nodes[$key] = (object)['key' => $key, 'text' => $item['text'] ?: $key, 'action' => $item['action'],
                'parentkey' => $item['parent'], 'icon' => null];
        }
        return $nodes;
    }

    /**
     * Records the groups and items a user's settings show, and gives every new one its row, in its group.
     *
     * @param \stdClass[] $seen By key, with text, action and parent (the key of the group, or empty for a group).
     * @return bool Whether something was new.
     */
    public function record_seen(array $seen): bool {
        $stored = $this->get_stored();
        $new = array_diff_key($seen, $stored);
        if (empty($new)) {
            return false;
        }
        foreach ($new as $key => $item) {
            $stored[$key] = ['text' => (string)$item->text, 'action' => (string)$item->action, 'parent' => (string)$item->parent];
        }
        set_config(static::STORED, json_encode($stored), 'local_extendednav');
        $this->sync_native_nodes(false);
        return true;
    }

    /**
     * Gives every group and item found a row of the site, in its group, without changing the rows that exist.
     *
     * @param bool $events Whether to trigger node created events.
     */
    public function sync_native_nodes(bool $events = true): void {
        global $DB;

        $existing = $DB->get_records_menu('local_extendednav', ['menu' => static::KEY, 'contextid' => 0], '', 'nodekey, id');
        foreach ($this->get_native_nodes() as $key => $native) {
            if (!isset($existing[$key])) {
                $record = manager::prepare(['nodekey' => $key, 'visibility' => 1, 'parentkey' => $native->parentkey], static::KEY);
                manager::create($record, $events);
            }
        }
    }

    /**
     * Forgets deleted groups and items, so they come back as new ones if Moodle shows them again.
     *
     * @param string[] $nodekeys Node keys.
     */
    public function forget_native_nodes(array $nodekeys): void {
        set_config(static::STORED, json_encode(array_diff_key($this->get_stored(), array_flip($nodekeys))), 'local_extendednav');
    }

    /**
     * Reads the groups and items found so far.
     *
     * @return array By key, with text, action and parent.
     */
    private function get_stored(): array {
        $stored = json_decode((string)get_config('local_extendednav', static::STORED), true);
        $nodes = [];
        foreach (is_array($stored) ? $stored : [] as $key => $item) {
            $nodes[(string)$key] = [
                'text' => (string)($item['text'] ?? ''),
                'action' => (string)($item['action'] ?? ''),
                'parent' => (string)($item['parent'] ?? ''),
            ];
        }
        return $nodes;
    }
}
