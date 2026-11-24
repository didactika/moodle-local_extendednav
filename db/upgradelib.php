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

    foreach ($menus as $menu) {
        $stored = json_decode((string)get_config('local_extendednav', 'nativenodes_' . $menu), true);
        if (!is_array($stored)) {
            continue;
        }
        $changed = false;
        foreach ($stored as $key => $item) {
            if (!ctype_digit((string)$key)) {
                continue;
            }
            $newkey = \local_extendednav\menu\secondary::key_of_path((string)($item['action'] ?? ''));
            unset($stored[$key]);
            $changed = true;
            if ($newkey === null || isset($stored[$newkey])) {
                $DB->delete_records('local_extendednav', ['menu' => $menu, 'nodekey' => (string)$key]);
                continue;
            }
            $stored[$newkey] = $item;
            foreach ($DB->get_records('local_extendednav', ['menu' => $menu, 'nodekey' => (string)$key]) as $row) {
                $taken = ['menu' => $menu, 'nodekey' => $newkey, 'contextid' => $row->contextid];
                if ($DB->record_exists('local_extendednav', $taken)) {
                    $DB->delete_records('local_extendednav', ['id' => $row->id]);
                } else {
                    $DB->set_field('local_extendednav', 'nodekey', $newkey, ['id' => $row->id]);
                }
            }
            $DB->set_field('local_extendednav', 'parentkey', $newkey, ['menu' => $menu, 'parentkey' => (string)$key]);
        }
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
