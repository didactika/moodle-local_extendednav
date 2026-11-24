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
 * Privacy provider for local_extendednav
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_extendednav\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy API provider for local_extendednav.
 *
 * The plugin keeps one thing of each user: how they arranged their own page Preferences (the sections and items they hide,
 * order or move), as changes of the navigation in the context of the user.
 *
 * @copyright  2026 Didactika
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describes the data the plugin keeps.
     *
     * @param collection $collection The collection to add to.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_extendednav', [
            'contextid' => 'privacy:metadata:local_extendednav:contextid',
            'nodekey' => 'privacy:metadata:local_extendednav:nodekey',
            'visibility' => 'privacy:metadata:local_extendednav:visibility',
            'parentkey' => 'privacy:metadata:local_extendednav:parentkey',
            'sortorder' => 'privacy:metadata:local_extendednav:sortorder',
            'timemodified' => 'privacy:metadata:local_extendednav:timemodified',
        ], 'privacy:metadata:local_extendednav');
        return $collection;
    }

    /**
     * Returns the context of a user who arranged their preferences.
     *
     * @param int $userid The user.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $contextlist->add_from_sql(
            'SELECT DISTINCT ctx.id
               FROM {context} ctx
               JOIN {local_extendednav} n ON n.contextid = ctx.id
              WHERE ctx.contextlevel = :level AND ctx.instanceid = :userid',
            ['level' => CONTEXT_USER, 'userid' => $userid]
        );
        return $contextlist;
    }

    /**
     * Adds the user of a user context who arranged their preferences.
     *
     * @param userlist $userlist The list to fill.
     */
    public static function get_users_in_context(userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if ($context instanceof \context_user && $DB->record_exists('local_extendednav', ['contextid' => $context->id])) {
            $userlist->add_user((int)$context->instanceid);
        }
    }

    /**
     * Exports how the user arranged their preferences.
     *
     * @param approved_contextlist $contextlist The contexts approved.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_user || (int)$context->instanceid !== (int)$contextlist->get_user()->id) {
                continue;
            }
            $rows = $DB->get_records(
                'local_extendednav',
                ['contextid' => $context->id],
                'sortorder',
                'nodekey, visibility, parentkey, sortorder, timemodified'
            );
            $data = array_map(static fn(\stdClass $row): \stdClass => (object)[
                'item' => $row->nodekey,
                'shown' => transform::yesno((int)$row->visibility !== 0),
                'section' => (string)$row->parentkey,
                'position' => (int)$row->sortorder,
                'timemodified' => transform::datetime($row->timemodified),
            ], array_values($rows));
            writer::with_context($context)->export_data(
                [get_string('pluginname', 'local_extendednav')],
                (object)['preferences' => $data]
            );
        }
    }

    /**
     * Deletes how every user of a context arranged their preferences.
     *
     * @param \context $context The context.
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context instanceof \context_user) {
            $DB->delete_records('local_extendednav', ['contextid' => $context->id]);
            \local_extendednav\local\node\repository::purge();
        }
    }

    /**
     * Deletes how a user arranged their preferences.
     *
     * @param approved_contextlist $contextlist The contexts approved.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        foreach ($contextlist->get_contexts() as $context) {
            if ($context instanceof \context_user && (int)$context->instanceid === (int)$contextlist->get_user()->id) {
                self::delete_data_for_all_users_in_context($context);
            }
        }
    }

    /**
     * Deletes how the users of a list arranged their preferences.
     *
     * @param approved_userlist $userlist The users approved.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        $context = $userlist->get_context();
        if ($context instanceof \context_user && in_array((int)$context->instanceid, $userlist->get_userids())) {
            self::delete_data_for_all_users_in_context($context);
        }
    }
}
