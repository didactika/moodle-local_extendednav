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
 * Steps of the upgrade of local_extendednav that need more than a line.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Gives a key that is the same on every page to the items that Moodle numbered.
 *
 * Moodle numbers the items a plugin adds to a secondary navigation without a key, and the number changes from page to page;
 * they are known by the path of their address now (see \local_extendednav\menu\secondary::key_of). The items recorded with a
 * number take that key, and so do their changes, unless the key is taken already.
 *
 * @param string[] $menus Keys of the secondary navigations.
 */
function local_extendednav_upgrade_numbered_keys(array $menus): void {
    global $DB;

    // The rows of the navigations, read in one query; what changes is worked out here and written in a few queries.
    $all = [];
    if ($menus) {
        $records = $DB->get_recordset_list('local_extendednav', 'menu', $menus, '', 'id, menu, nodekey, parentkey, contextid');
        foreach ($records as $record) {
            $all[$record->menu][$record->id] = $record;
        }
        $records->close();
    }
    foreach ($menus as $menu) {
        $stored = json_decode((string)get_config('local_extendednav', 'nativenodes_' . $menu), true);
        if (!is_array($stored)) {
            continue;
        }
        $rows = $all[$menu] ?? [];
        $bykey = [];
        foreach ($rows as $row) {
            $bykey[(string)$row->nodekey][(int)$row->contextid] = $row;
        }
        $changed = false;
        $delete = [];
        $rename = [];
        $parents = [];
        foreach ($stored as $key => $item) {
            if (!ctype_digit((string)$key)) {
                continue;
            }
            $newkey = \local_extendednav\menu\secondary::key_of_path((string)($item['action'] ?? ''));
            unset($stored[$key]);
            $changed = true;
            if ($newkey === null || isset($stored[$newkey])) {
                foreach ($bykey[(string)$key] ?? [] as $row) {
                    $delete[] = (int)$row->id;
                }
                continue;
            }
            $stored[$newkey] = $item;
            foreach ($bykey[(string)$key] ?? [] as $contextid => $row) {
                if (isset($bykey[$newkey][$contextid])) {
                    $delete[] = (int)$row->id;
                } else {
                    $rename[(int)$row->id] = $newkey;
                    $bykey[$newkey][$contextid] = $row;
                }
            }
            foreach ($rows as $row) {
                if ((string)$row->parentkey === (string)$key) {
                    $parents[(int)$row->id] = $newkey;
                }
            }
        }
        foreach (array_chunk($delete, 500) as $chunk) {
            $DB->delete_records_list('local_extendednav', 'id', $chunk);
        }
        \local_extendednav\local\node\manager::set_field_by_id('nodekey', $rename);
        \local_extendednav\local\node\manager::set_field_by_id('parentkey', $parents);
        if ($changed) {
            set_config('nativenodes_' . $menu, json_encode($stored), 'local_extendednav');
        }
    }
}

/**
 * Prepares the navigation of each kind of activity: the changes of the single navigation of the activities are given to each
 * kind by a task (see \local_extendednav\task\split_activity_navigation), and the pages keep applying them until it ends.
 */
function local_extendednav_upgrade_split_activities(): void {
    global $DB;

    if (!$DB->record_exists('local_extendednav', ['menu' => 'activity'])) {
        unset_config('nativenodes_activity', 'local_extendednav');
        unset_config('enabled_activity', 'local_extendednav');
        return;
    }
    set_config('legacyactivity', 1, 'local_extendednav');
    \core\task\manager::queue_adhoc_task(new \local_extendednav\task\split_activity_navigation(), true);
}
