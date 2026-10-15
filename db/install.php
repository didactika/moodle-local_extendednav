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
 * Post-installation steps for local_extendednav.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Creates one empty row for every item Moodle adds to the menus and imports the
 * items already defined in Moodle's custom menu settings.
 *
 * Core rows are created empty, so core titles and URLs keep coming from Moodle
 * (and stay translated) until an administrator overrides them.
 *
 * @return bool
 */
function xmldb_local_extendednav_install() {
    global $USER;

    // Moodle's primary navigation items depend on the user, so they are found as the administrator,
    // who sees them all. From the command line nobody is logged in yet.
    $previous = $USER;
    $switch = !is_siteadmin() && get_admin();
    if ($switch) {
        \core\session\manager::set_user(get_admin());
    }
    try {
        foreach (\local_extendednav\menu_manager::get_menus() as $menu) {
            $menu->sync_native_nodes(false);
            $menu->sync_from_config(false);
        }
    } finally {
        if ($switch) {
            \core\session\manager::set_user($previous);
        }
    }
    return true;
}
