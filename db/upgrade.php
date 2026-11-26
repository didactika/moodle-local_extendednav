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
 * Upgrade steps for local_extendednav.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/upgradelib.php');

/**
 * Runs the upgrade steps for this plugin.
 *
 * Version 1.1.0 adds the context a node applies to and where a node of the secondary navigations goes.
 *
 * @param int $oldversion The version being upgraded from.
 * @return bool
 */
function xmldb_local_extendednav_upgrade($oldversion) {
    global $CFG, $DB;

    if ($oldversion < 2026112400) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_extendednav');

        // The context a node applies to: the whole site, or a category or a course as an exception.
        $field = new xmldb_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'menu');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Where a node of the secondary navigations goes: where Moodle puts it, or always or never in the More menu.
        $field = new xmldb_field('placement', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'popupheight');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $old = new xmldb_index('menu_nodekey', XMLDB_INDEX_UNIQUE, ['menu', 'nodekey']);
        if ($dbman->index_exists($table, $old)) {
            $dbman->drop_index($table, $old);
        }
        $new = new xmldb_index('menu_nodekey_context', XMLDB_INDEX_UNIQUE, ['menu', 'nodekey', 'contextid']);
        if (!$dbman->index_exists($table, $new)) {
            $dbman->add_index($table, $new);
        }
        $lookup = new xmldb_index('menu_context', XMLDB_INDEX_NOTUNIQUE, ['menu', 'contextid']);
        if (!$dbman->index_exists($table, $lookup)) {
            $dbman->add_index($table, $lookup);
        }

        upgrade_plugin_savepoint(true, 2026112400, 'local', 'extendednav');
    }

    if ($oldversion < 2026112500) {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_extendednav');

        // A node can be hidden from the site administrators too, who see every node by default.
        $field = new xmldb_field('hideadmins', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'placement');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026112500, 'local', 'extendednav');
    }

    if ($oldversion < 2026112501) {
        // The setting page is gone: the plugin keeps the value it had, and is on when it never had one.
        if (get_config('local_extendednav', 'enable_plugin') === false) {
            set_config('enable_plugin', 1, 'local_extendednav');
        }
        unset_config('hide_from_admins', 'local_extendednav');

        upgrade_plugin_savepoint(true, 2026112501, 'local', 'extendednav');
    }

    if ($oldversion < 2026112502) {
        // The site administration is read from its tree now: the nodes recorded from its bar are not needed any more. The
        // rows keep their keys, which are the names of the categories of the tree.
        unset_config('nativenodes_admin', 'local_extendednav');

        upgrade_plugin_savepoint(true, 2026112502, 'local', 'extendednav');
    }

    if ($oldversion < 2026112504) {
        // A node of the site administration is hidden from administrators too by default.
        $DB->set_field('local_extendednav', 'hideadmins', 1, ['menu' => 'admin']);

        upgrade_plugin_savepoint(true, 2026112504, 'local', 'extendednav');
    }

    if ($oldversion < 2026112505) {
        // The conditions of a node, as Moodle's Restrict access writes them.
        $dbman = $DB->get_manager();
        $table = new xmldb_table('local_extendednav');
        $field = new xmldb_field('availability', XMLDB_TYPE_TEXT, null, null, null, null, null, 'hideadmins');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2026112505, 'local', 'extendednav');
    }

    if ($oldversion < 2026112600) {
        // The items Moodle numbered are known by the path of their address, and each kind of activity has its own navigation.
        local_extendednav_upgrade_numbered_keys(['course', 'frontpage', 'category', 'activity']);
        local_extendednav_upgrade_split_activities();

        upgrade_plugin_savepoint(true, 2026112600, 'local', 'extendednav');
    }

    if ($oldversion < 2026112603) {
        // The guided tours of the plugin, for the sites that installed it before they existed.
        require_once($CFG->dirroot . '/local/extendednav/lib.php');
        local_extendednav_import_tours();

        upgrade_plugin_savepoint(true, 2026112603, 'local', 'extendednav');
    }

    if ($oldversion < 2026112604) {
        // The profile has no navigation of its own, each user arranges their preferences as changes of their own context, and
        // the tours are managed on the page of the tours of Moodle.
        $DB->delete_records('local_extendednav', ['menu' => 'profile']);
        unset_config('nativenodes_profile', 'local_extendednav');
        unset_config('enabled_profile', 'local_extendednav');
        unset_config('tourrequired', 'local_extendednav');
        $DB->delete_records('user_preferences', ['name' => 'local_extendednav_preferences']);
        if ($DB->get_manager()->table_exists('tool_usertours_tours')) {
            // The tours change: the one of the Navigation page leaves the preferences of each user to a tour of their own.
            $names = array_map(
                static fn(string $name): string => 'tour_' . $name . '_name,local_extendednav',
                ['mypreferences', 'context', 'preferences']
            );
            $tours = $DB->get_records_list('tool_usertours_tours', 'name', $names, '', 'id');
            foreach ($tours as $tour) {
                \tool_usertours\tour::instance((int)$tour->id)->remove();
            }
        }
        require_once($CFG->dirroot . '/local/extendednav/lib.php');
        local_extendednav_import_tours();
        \local_extendednav\local\node\repository::purge();

        upgrade_plugin_savepoint(true, 2026112604, 'local', 'extendednav');
    }

    if ($oldversion < 2026112605) {
        // The guided tour of the form of a node; the tours already there are left as they are.
        require_once($CFG->dirroot . '/local/extendednav/lib.php');
        local_extendednav_import_tours();

        upgrade_plugin_savepoint(true, 2026112605, 'local', 'extendednav');
    }

    if ($oldversion < 2026112606) {
        // The task that cleans the activity log comes off, as its setting does, until the setting is turned on.
        \local_extendednav\task\clean_activity_log::sync();

        upgrade_plugin_savepoint(true, 2026112606, 'local', 'extendednav');
    }

    if ($oldversion < 2026112607) {
        // A node of the site at the top of the primary navigation or the user menu that holds others is a submenu, which has no
        // URL of its own: the URL it kept from before is cleared, in one query.
        $parents = $DB->get_fieldset_sql(
            "SELECT DISTINCT parentkey
               FROM {local_extendednav}
              WHERE menu IN ('primary', 'usermenu') AND parentkey IS NOT NULL AND parentkey <> ''"
        );
        if ($parents) {
            [$insql, $params] = $DB->get_in_or_equal($parents, SQL_PARAMS_NAMED);
            $DB->set_field_select(
                'local_extendednav',
                'url',
                null,
                "menu IN ('primary', 'usermenu') AND (parentkey IS NULL OR parentkey = '') AND nodekey $insql",
                $params
            );
        }
        \local_extendednav\local\node\repository::purge();

        upgrade_plugin_savepoint(true, 2026112607, 'local', 'extendednav');
    }

    if ($oldversion < 2026112611) {
        // Visible to everyone has no limits now: a node of that visibility with access restrictions is restricted, so it keeps
        // being shown only to whoever meets them.
        $rows = $DB->get_recordset_select(
            'local_extendednav',
            'visibility = 1 AND availability IS NOT NULL',
            null,
            '',
            'id, availability'
        );
        $ids = [];
        foreach ($rows as $row) {
            if (trim((string)$row->availability) !== '') {
                $ids[] = (int)$row->id;
            }
        }
        $rows->close();
        foreach (array_chunk($ids, 500) as $chunk) {
            [$insql, $params] = $DB->get_in_or_equal($chunk);
            $DB->set_field_select('local_extendednav', 'visibility', 2, "id $insql", $params);
        }
        \local_extendednav\local\node\repository::purge();

        upgrade_plugin_savepoint(true, 2026112611, 'local', 'extendednav');
    }

    return true;
}
