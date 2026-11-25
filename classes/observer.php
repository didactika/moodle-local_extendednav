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

use local_extendednav\local\node\access;
use local_extendednav\local\node\manager;
use local_extendednav\local\node\repository;

/**
 * Event observers. They stay light: a change of a menu setting of Moodle queues its import (see
 * \local_extendednav\local\support\menu_config), a deleted role leaves the nodes restricted to it, and a deleted user takes
 * their own preferences with them.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {
    /**
     * Imports a custom menu setting again when an administrator changes it.
     *
     * @param \core\event\config_log_created $event The event.
     */
    public static function config_log_created(\core\event\config_log_created $event): void {
        if (\local_extendednav\local\support\menu_config::$issyncing) {
            return;
        }
        $plugin = $event->other['plugin'] ?? '';
        if (!empty($plugin) && $plugin !== 'core') {
            return;
        }
        // The import runs in a task, once for each setting however many times it is saved meanwhile: the observer stays light.
        $name = (string)($event->other['name'] ?? '');
        if (menu_manager::get_menu_by_config($name) !== null) {
            $task = new \local_extendednav\task\sync_custom_menu();
            $task->set_custom_data(['name' => $name]);
            \core\task\manager::queue_adhoc_task($task, true);
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
            $roles = access::split_list($record->roles);
            if (!in_array($shortname, $roles, true)) {
                continue;
            }
            $roles = array_values(array_diff($roles, [$shortname]));
            manager::update((object)['id' => $record->id, 'roles' => empty($roles) ? null : implode(',', $roles)]);
        }
    }

    /**
     * Forgets how a deleted user arranged their own preferences.
     *
     * @param \core\event\user_deleted $event The event.
     */
    public static function user_deleted(\core\event\user_deleted $event): void {
        global $DB;

        if ($DB->record_exists('local_extendednav', ['contextid' => $event->contextid])) {
            $DB->delete_records('local_extendednav', ['contextid' => $event->contextid]);
            repository::purge();
        }
    }
}
