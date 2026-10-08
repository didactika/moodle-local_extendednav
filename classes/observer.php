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
    /**
     * Triggered when a config is updated.
     *
     * @param \core\event\config_updated $event
     */
    public static function config_updated(\core\event\config_updated $event): void {
        $plugin = $event->other['plugin'] ?? '';
        $name = $event->other['name'] ?? '';

        if ($plugin === 'core' && $name === 'custommenuitems') {
            self::sync_custom_menu('custommenuitems', 'cmenu_', 'primary');
        } else if ($plugin === 'core' && $name === 'customusermenuitems') {
            self::sync_custom_menu('customusermenuitems', 'umenu_', 'usermenu');
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
        $text = $CFG->{$configname} ?? '';
        $lines = explode("\n", $text);
        
        $depthkeys = [];
        $seenkeys = [];
        
        $sortorder = 0;

        foreach ($lines as $linenumber => $line) {
            $line = trim($line);
            if (strlen($line) == 0) {
                continue;
            }
            
            $settings = explode('|', $line);
            $rawtext = trim($settings[0]);
            
            $itemtext = ltrim($rawtext, '-');
            $itemurl = isset($settings[1]) ? trim($settings[1]) : '';
            $itemicon = isset($settings[2]) ? trim($settings[2]) : 'none';
            if ($itemicon === '') {
                $itemicon = 'none';
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
            
            if ($existing) {
                $existing->title = $itemtext;
                $existing->url = $itemurl;
                $existing->parentkey = $parentkey;
                $existing->sortorder = $sortorder;
                if (isset($settings[2])) {
                    $existing->icon = $itemicon;
                }
                $DB->update_record('local_extendednav', $existing);
            } else {
                $newnode = new \stdClass();
                $newnode->nodekey = $nodekey;
                $newnode->menu = $menutype;
                $newnode->title = $itemtext;
                $newnode->url = $itemurl;
                $newnode->parentkey = $parentkey;
                $newnode->icon = $itemicon;
                $newnode->visibility = 1;
                $newnode->newwindow = 0;
                $newnode->sortorder = $sortorder;
                $DB->insert_record('local_extendednav', $newnode);
            }
            
            $sortorder++;
        }
        
        $dbnodes = $DB->get_records_select('local_extendednav', "nodekey LIKE ? AND menu = ?", [$prefix . '%', $menutype]);
        foreach ($dbnodes as $dbn) {
            if (!isset($seenkeys[$dbn->nodekey])) {
                $DB->delete_records('local_extendednav', ['id' => $dbn->id]);
            }
        }

        try {
            \cache::make('local_extendednav', 'nodes')->purge();
        } catch (\Throwable $e) {
            unset($e);
        }
    }
}
