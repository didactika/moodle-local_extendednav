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

    return true;
}
