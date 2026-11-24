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
 * Admin settings for local_extendednav.
 *
 * The settings page needs moodle/site:config. The management page needs
 * local/extendednav:manage, so it can be given to roles such as Manager.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig || has_capability('local/extendednav:manage', context_system::instance())) {
    // Appearance is visible to every user who can open an admin page, unlike Local plugins,
    // so administrators and managers find the plugin in the same place.
    $ADMIN->add('appearance', new admin_category('local_extendednav_cat', get_string('pluginname', 'local_extendednav')));

    $ADMIN->add('local_extendednav_cat', new admin_externalpage(
        'local_extendednav_manage',
        get_string('manage_nodes', 'local_extendednav'),
        new moodle_url('/local/extendednav/manage.php'),
        'local/extendednav:manage'
    ));
}

// The site administration as the plugin shapes it: names, hidden pages and the order of each category. Moodle is building the
// tree now, so the menus it draws from it already show the changes; the pages of the plugins loaded later are reached before
// the page is sent. An upgrade that has not added the fields of the plugin yet leaves the tree as it is.
try {
    \local_extendednav\menu\admintree::apply($ADMIN);
} catch (\dml_exception $e) {
    debugging('Extended navigation could not shape the site administration: ' . $e->getMessage(), DEBUG_DEVELOPER);
}
