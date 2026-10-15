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

namespace local_extendednav;

use local_extendednav\local\node_access;
use local_extendednav\local\node_manager;
use local_extendednav\local\node_repository;
use local_extendednav\menu\base;

/**
 * Event observers and the synchronisation with Moodle's custom menu settings.
 *
 * Items of the custom menu items and user menu items settings are imported as
 * nodes, so they can be managed together with the core nodes. Deleting such a
 * node removes its line from the setting.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /** @var bool Set while the plugin writes a setting itself, to ignore its own changes. */
    private static bool $issyncing = false;

    /**
     * Imports a custom menu setting again when an administrator changes it.
     *
     * @param \core\event\config_log_created $event The event.
     */
    public static function config_log_created(\core\event\config_log_created $event): void {
        if (self::$issyncing) {
            return;
        }
        $plugin = $event->other['plugin'] ?? '';
        if (!empty($plugin) && $plugin !== 'core') {
            return;
        }
        $menu = menu_manager::get_menu_by_config($event->other['name'] ?? '');
        if ($menu !== null) {
            $menu->sync_from_config();
        }
    }

    /**
     * Removes a deleted role from every node restricted to it.
     *
     * @param \core\event\role_deleted $event The event.
     */
    public static function role_deleted(\core\event\role_deleted $event): void {
        global $DB;

        $shortname = (string)($event->other['shortname'] ?? '');
        if ($shortname === '') {
            return;
        }
        $like = $DB->sql_like('roles', ':shortname', false, false);
        $records = $DB->get_records_select(
            'local_extendednav',
            $like,
            ['shortname' => '%' . $DB->sql_like_escape($shortname) . '%']
        );
        foreach ($records as $record) {
            $roles = node_access::split_list($record->roles);
            if (!in_array($shortname, $roles, true)) {
                continue;
            }
            $roles = array_values(array_diff($roles, [$shortname]));
            node_manager::update((object)['id' => $record->id, 'roles' => empty($roles) ? null : implode(',', $roles)]);
        }
    }

    /**
     * Returns the hash of the current value of a Moodle menu setting.
     *
     * @param string $configname Setting name.
     * @return string
     */
    public static function get_config_hash(string $configname): string {
        return sha1(self::read_config($configname));
    }

    /**
     * Imports the lines of a menu's Moodle setting as nodes.
     *
     * Lines are matched to existing nodes by key, then by URL, then by title and URL,
     * so editing a line updates its node instead of creating another one. Nodes whose
     * line was removed are deleted. Core nodes only store a title or URL that differs
     * from the core one, so they keep following Moodle.
     *
     * @param base $menu The menu.
     * @param bool $events Whether to trigger node events.
     */
    public static function sync_custom_menu(base $menu, bool $events = true): void {
        global $DB;

        $configname = $menu->get_config_name();
        $prefix = $menu->get_config_prefix();
        $natives = $menu->get_native_nodes();
        $text = self::read_config($configname);

        $existing = $DB->get_records('local_extendednav', ['menu' => $menu->get_key()], 'sortorder ASC, id ASC');
        $matched = [];
        $seenkeys = [];
        $depthkeys = [];

        foreach (self::parse_lines($text) as $linenumber => $line) {
            $rawtext = $line;
            preg_match('/^(-*)/', $rawtext, $match);
            $depth = strlen($match[1] ?? '');
            $item = $menu->parse_config_line(ltrim($rawtext, '-'));
            if ($item['text'] === '' || strpos($item['text'], '###') === 0) {
                continue;
            }

            $nodekey = base::make_key($prefix, $item['text']);
            if ($nodekey === $prefix) {
                $nodekey = $prefix . 'item' . $linenumber;
            }
            $basekey = $nodekey;
            for ($count = 1; isset($seenkeys[$nodekey]); $count++) {
                $nodekey = $basekey . $count;
            }
            $seenkeys[$nodekey] = true;

            $parentkey = ($depth > 0 && isset($depthkeys[$depth - 1])) ? $depthkeys[$depth - 1] : null;
            $depthkeys = array_slice($depthkeys, 0, $depth, true);
            $depthkeys[$depth] = $nodekey;

            $values = [
                'title' => $item['text'],
                'url' => $item['url'] !== '' ? $item['url'] : null,
                'tooltip' => $item['tooltip'] !== '' ? $item['tooltip'] : null,
                'langs' => $item['langs'] !== '' ? implode(',', node_access::split_list($item['langs'])) : null,
                'parentkey' => $parentkey,
            ];
            if (isset($natives[$nodekey])) {
                if ($values['title'] === $natives[$nodekey]->text) {
                    $values['title'] = null;
                }
                if ($values['url'] === $natives[$nodekey]->action) {
                    $values['url'] = null;
                }
            }

            $record = self::find_match($existing, $matched, $nodekey, $prefix, $item);
            if ($record !== null) {
                $matched[$record->id] = true;
                $changes = ['id' => $record->id];
                foreach ($values as $field => $value) {
                    if ($record->$field !== $value) {
                        $changes[$field] = $value;
                    }
                }
                if (count($changes) > 1) {
                    node_manager::update((object)$changes, $events);
                }
            } else {
                $new = node_manager::prepare(['nodekey' => $nodekey, 'visibility' => 1] + $values, $menu->get_key());
                $new = node_manager::create($new, $events);
                $matched[$new->id] = true;
            }
        }

        // Delete the nodes whose line was removed from the setting (never core nodes).
        $removed = [];
        foreach ($existing as $record) {
            if (
                !isset($matched[$record->id]) && strpos($record->nodekey, $prefix) === 0
                    && !isset($natives[$record->nodekey])
            ) {
                $removed[] = $record;
            }
        }
        node_manager::delete($removed, $events, false);

        set_config('confighash_' . $configname, sha1($text), 'local_extendednav');
        node_repository::purge();
    }

    /**
     * Removes the lines of deleted nodes from a menu's Moodle setting.
     *
     * @param base $menu The menu.
     * @param \stdClass[] $records Deleted records.
     */
    public static function remove_from_config(base $menu, array $records): void {
        global $CFG;

        $configname = $menu->get_config_name();
        $prefix = $menu->get_config_prefix();
        $text = self::read_config($configname);
        if (trim($text) === '' || empty($records)) {
            return;
        }

        $deletedkeys = [];
        foreach ($records as $record) {
            $deletedkeys[$record->nodekey] = $record;
        }

        $kept = [];
        $seenkeys = [];
        $changed = false;
        foreach (explode("\n", $text) as $linenumber => $line) {
            $trimmed = trim($line);
            $item = $trimmed === '' ? null : $menu->parse_config_line(ltrim($trimmed, '-'));
            if ($item === null || $item['text'] === '' || strpos($item['text'], '###') === 0) {
                $kept[] = $line;
                continue;
            }

            $nodekey = base::make_key($prefix, $item['text']);
            if ($nodekey === $prefix) {
                $nodekey = $prefix . 'item' . $linenumber;
            }
            $basekey = $nodekey;
            for ($count = 1; isset($seenkeys[$nodekey]); $count++) {
                $nodekey = $basekey . $count;
            }
            $seenkeys[$nodekey] = true;

            if (isset($deletedkeys[$nodekey])) {
                $changed = true;
            } else {
                $kept[] = $line;
            }
        }

        if ($changed) {
            $newtext = implode("\n", $kept);
            self::$issyncing = true;
            try {
                set_config($configname, $newtext);
                $CFG->{$configname} = $newtext;
                set_config('confighash_' . $configname, sha1($newtext), 'local_extendednav');
            } finally {
                self::$issyncing = false;
            }
        }
    }

    /**
     * Reads a Moodle menu setting with normalised line endings.
     *
     * The value is read from the database, because the plugin empties the copy in
     * $CFG while a page is printed.
     *
     * @param string $configname Setting name.
     * @return string
     */
    private static function read_config(string $configname): string {
        global $DB;

        $value = $DB->get_field('config', 'value', ['name' => $configname]);
        return str_replace(["\r\n", "\r"], "\n", (string)$value);
    }

    /**
     * Returns the non-empty, trimmed lines of a setting, keyed by line number.
     *
     * @param string $text Setting value.
     * @return string[]
     */
    private static function parse_lines(string $text): array {
        $lines = [];
        foreach (explode("\n", $text) as $number => $line) {
            $line = trim($line);
            if ($line !== '') {
                $lines[$number] = $line;
            }
        }
        return $lines;
    }

    /**
     * Finds the node a line corresponds to.
     *
     * @param \stdClass[] $existing Nodes of the menu.
     * @param array $matched Ids already matched to another line.
     * @param string $nodekey Key built from the line.
     * @param string $prefix Key prefix of the menu.
     * @param array $item Parsed line.
     * @return \stdClass|null
     */
    private static function find_match(
        array $existing,
        array $matched,
        string $nodekey,
        string $prefix,
        array $item
    ): ?\stdClass {
        foreach ($existing as $record) {
            if (!isset($matched[$record->id]) && $record->nodekey === $nodekey) {
                return $record;
            }
        }
        if ($item['url'] !== '') {
            foreach ($existing as $record) {
                if (
                    !isset($matched[$record->id]) && strpos($record->nodekey, $prefix) === 0
                        && $record->url === $item['url']
                ) {
                    return $record;
                }
            }
        }
        foreach ($existing as $record) {
            $sameurl = $item['url'] === '' ? empty($record->url) : $record->url === $item['url'];
            if (!isset($matched[$record->id]) && $record->title === $item['text'] && $sameurl) {
                return $record;
            }
        }
        return null;
    }
}
