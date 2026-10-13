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

/**
 * Event observer for extendednav.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /** @var bool Prevent recursive sync when updating configs from the plugin */
    public static $issyncing = false;

    /**
     * Triggered when a config log is created.
     *
     * @param \core\event\config_log_created $event
     */
    public static function config_log_created(\core\event\config_log_created $event): void {
        if (self::$issyncing) {
            return;
        }

        $name = $event->other['name'] ?? '';
        $menu = menu_manager::get_menu_by_config($name);
        if ($menu) {
            $menu->sync_from_config();
        }
    }

    /**
     * Backward compatibility callback.
     *
     * @param \core\event\base $event
     */
    public static function config_updated(\core\event\base $event): void {
        if (self::$issyncing) {
            return;
        }

        $name = $event->other['name'] ?? '';
        $menu = menu_manager::get_menu_by_config($name);
        if ($menu) {
            $menu->sync_from_config();
        }
    }

    /**
     * Removes deleted navigation nodes from Moodle core menu configurations.
     *
     * @param string $menutype
     * @param array $deletedrecords
     */
    public static function remove_from_config(string $menutype, array $deletedrecords): void {
        $menu = menu_manager::get_menu($menutype);
        if ($menu) {
            $menu->remove_nodes_from_config($deletedrecords);
        }
    }

    /**
     * Removes deleted navigation nodes from a specified config by definition.
     *
     * @param string $configname
     * @param string $prefix
     * @param array $deletedrecords
     */
    public static function remove_from_config_by_definition(string $configname, string $prefix, array $deletedrecords): void {
        global $CFG;

        if (empty($deletedrecords)) {
            return;
        }

        $text = get_config('core', $configname);
        if ($text === false || $text === null) {
            $text = $CFG->{$configname} ?? '';
        }
        if (trim((string)$text) === '') {
            return;
        }

        $lines = explode("\n", (string)$text);
        $newlines = [];
        $seenkeys = [];
        $changed = false;

        foreach ($lines as $linenumber => $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            $settings = explode('|', $trimmed);
            $rawtext = trim($settings[0]);
            $itemtext = ltrim($rawtext, '-');
            if ($itemtext === '' || $itemtext === '###' || strpos($itemtext, '###') === 0) {
                $newlines[] = $line;
                continue;
            }
            $itemurl = isset($settings[1]) ? trim($settings[1]) : '';

            $slug = preg_replace('/[^a-z0-9]/', '', strtolower($itemtext));
            if (empty($slug)) {
                $slug = 'item' . $linenumber;
            }
            $nodekey = $prefix . substr($slug, 0, 15);

            $count = 1;
            $basekey = $nodekey;
            while (isset($seenkeys[$nodekey])) {
                $nodekey = $basekey . $count;
                $count++;
            }
            $seenkeys[$nodekey] = true;

            $shouldremove = false;
            foreach ($deletedrecords as $rec) {
                if (!empty($rec->nodekey) && $rec->nodekey === $nodekey) {
                    $shouldremove = true;
                    break;
                }
                if (!empty($rec->title) && strcasecmp(trim($rec->title), $itemtext) === 0) {
                    $recurls = [];
                    if (!empty($rec->url)) {
                        $recurls[] = rtrim(trim($rec->url), '/');
                    }
                    $lineurls = [];
                    if (!empty($itemurl)) {
                        $lineurls[] = rtrim(trim($itemurl), '/');
                    }
                    if (!empty(array_intersect($recurls, $lineurls)) || (empty($rec->url) && empty($itemurl))) {
                        $shouldremove = true;
                        break;
                    }
                }
            }

            if ($shouldremove) {
                $changed = true;
            } else {
                $newlines[] = $line;
            }
        }

        if ($changed) {
            $newtext = implode("\n", $newlines);
            self::$issyncing = true;
            try {
                set_config($configname, $newtext);
                $CFG->{$configname} = $newtext;
            } finally {
                self::$issyncing = false;
            }
        }
    }

    /**
     * Synchronizes Moodle's native custom menu configs to the plugin database.
     *
     * @param string $configname
     * @param string $prefix
     * @param string $menutype
     */
    public static function sync_custom_menu(string $configname, string $prefix, string $menutype): void {
        global $CFG, $DB;
        $text = get_config('core', $configname);
        if ($text === false || $text === null) {
            $text = $CFG->{$configname} ?? '';
        }
        $lines = explode("\n", (string)$text);

        $depthkeys = [];
        $seenkeys = [];

        $maxsort = (int)$DB->get_field_sql('SELECT MAX(sortorder) FROM {local_extendednav} WHERE menu = ?', [$menutype]);

        foreach ($lines as $linenumber => $line) {
            $line = trim($line);
            if (strlen($line) == 0) {
                continue;
            }

            $settings = explode('|', $line);
            $rawtext = trim($settings[0]);

            $itemtext = ltrim($rawtext, '-');
            if ($itemtext === '' || $itemtext === '###' || strpos($itemtext, '###') === 0) {
                continue;
            }
            $itemurl = isset($settings[1]) ? trim($settings[1]) : '';
            $itemicon = isset($settings[2]) ? trim($settings[2]) : null;
            if ($itemicon === '' || $itemicon === 'none') {
                $itemicon = null;
            }

            preg_match('/^(\-*)/', $rawtext, $match);
            $depth = strlen($match[1] ?? '');

            $slug = preg_replace('/[^a-z0-9]/', '', strtolower($itemtext));
            if (empty($slug)) {
                $slug = 'item' . $linenumber;
            }
            $nodekey = $prefix . substr($slug, 0, 15);

            $count = 1;
            $basekey = $nodekey;
            while (isset($seenkeys[$nodekey])) {
                $nodekey = $basekey . $count;
                $count++;
            }
            $seenkeys[$nodekey] = true;

            $parentkey = '';
            if ($depth > 0 && isset($depthkeys[$depth - 1])) {
                $parentkey = $depthkeys[$depth - 1];
            }

            $depthkeys[$depth] = $nodekey;

            $existing = $DB->get_record('local_extendednav', ['nodekey' => $nodekey, 'menu' => $menutype]);
            if (!$existing && !empty($itemurl)) {
                $sql = 'menu = :menu AND nodekey NOT LIKE :prefix AND ' . $DB->sql_compare_text('url') . ' = ' . $DB->sql_compare_text(':url');
                $existing = $DB->get_record_select('local_extendednav', $sql, ['menu' => $menutype, 'prefix' => $prefix . '%', 'url' => $itemurl], '*', IGNORE_MULTIPLE);
            }

            if ($existing) {
                $changed = false;
                if ($existing->title !== $itemtext && !empty($itemtext)) {
                    $existing->title = $itemtext;
                    $changed = true;
                }
                if ($existing->url !== $itemurl && !empty($itemurl)) {
                    $existing->url = $itemurl;
                    $changed = true;
                }
                if (!empty($parentkey) && empty($existing->parentkey)) {
                    $existing->parentkey = $parentkey;
                    $changed = true;
                }
                if ($itemicon !== null && empty($existing->icon)) {
                    $existing->icon = $itemicon;
                    $changed = true;
                }
                if ($changed) {
                    $DB->update_record('local_extendednav', $existing);
                }
            } else {
                $maxsort++;
                $newnode = new \stdClass();
                $newnode->nodekey = $nodekey;
                $newnode->menu = $menutype;
                $newnode->title = $itemtext;
                $newnode->url = $itemurl;
                $newnode->parentkey = $parentkey;
                $newnode->icon = $itemicon;
                $newnode->visibility = 1;
                $newnode->newwindow = 0;
                $newnode->sortorder = $maxsort;
                $DB->insert_record('local_extendednav', $newnode);
            }
        }

        try {
            \cache::make('local_extendednav', 'nodes')->purge();
        } catch (\Throwable $e) {
            unset($e);
        }
    }
}
