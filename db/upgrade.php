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
    global $DB;

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

    return true;
}
