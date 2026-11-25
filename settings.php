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

// The changes of the navigation of a course and of its activities travel with the backups of Moodle: whether they go in a backup
// and come back with a restore is set beside the other defaults of Moodle.
if ($hassiteconfig && $ADMIN->fulltree) {
    $backupdefaults = $ADMIN->locate('backupgeneralsettings');
    if ($backupdefaults instanceof admin_settingpage) {
        $backupdefaults->add(new admin_setting_configcheckbox(
            'local_extendednav/backup_navigation',
            get_string('backup_navigation', 'local_extendednav'),
            get_string('backup_navigation_desc', 'local_extendednav'),
            1
        ));
    }
    $restoredefaults = $ADMIN->locate('restoregeneralsettings');
    if ($restoredefaults instanceof admin_settingpage) {
        $restoredefaults->add(new admin_setting_configcheckbox(
            'local_extendednav/restore_navigation',
            get_string('restore_navigation', 'local_extendednav'),
            get_string('restore_navigation_desc', 'local_extendednav'),
            1
        ));
    }
}

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
    // The categories and courses that change a navigation, to look them up; each changes them on its own page.
    $ADMIN->add('local_extendednav_cat', new admin_externalpage(
        'local_extendednav_exceptions',
        get_string('exceptions_overview', 'local_extendednav'),
        new moodle_url('/local/extendednav/exceptions.php'),
        'local/extendednav:manage'
    ));
    // The changes to the navigation are a report, in a section of its own among the reports of the site.
    $ADMIN->add('reports', new admin_category('local_extendednav_reports', get_string('pluginname', 'local_extendednav')));
    $ADMIN->add('local_extendednav_reports', new admin_externalpage(
        'local_extendednav_activity',
        get_string('activity_report', 'local_extendednav'),
        new moodle_url('/local/extendednav/activity.php'),
        'local/extendednav:manage'
    ));
    // How long the activity log keeps its entries. Saving either setting turns the task that cleans it on or off.
    $logpage = new admin_settingpage(
        'local_extendednav_logsettings',
        get_string('logretention_page', 'local_extendednav'),
        'moodle/site:config'
    );
    if ($ADMIN->fulltree) {
        $enabled = new admin_setting_configcheckbox(
            'local_extendednav/' . \local_extendednav\task\clean_activity_log::ENABLED,
            get_string('logretention_enabled', 'local_extendednav'),
            get_string('logretention_enabled_desc', 'local_extendednav'),
            0
        );
        $enabled->set_updatedcallback([\local_extendednav\task\clean_activity_log::class, 'sync']);
        $logpage->add($enabled);
        $retention = new admin_setting_configduration(
            'local_extendednav/' . \local_extendednav\task\clean_activity_log::RETENTION,
            get_string('logretention', 'local_extendednav'),
            get_string('logretention_desc', 'local_extendednav'),
            52 * WEEKSECS,
            WEEKSECS
        );
        $retention->set_updatedcallback([\local_extendednav\task\clean_activity_log::class, 'sync']);
        $logpage->add($retention);
        if (method_exists($logpage, 'hide_if')) {
            $logpage->hide_if(
                'local_extendednav/' . \local_extendednav\task\clean_activity_log::RETENTION,
                'local_extendednav/' . \local_extendednav\task\clean_activity_log::ENABLED
            );
        }
    }
    $ADMIN->add('local_extendednav_reports', $logpage);
}

// The site administration as the plugin shapes it: names, hidden pages and the order of each category. Moodle is building the
// tree now, so the menus it draws from it already show the changes; the pages of the plugins loaded later are reached before
// the page is sent. An upgrade that has not added the fields of the plugin yet leaves the tree as it is.
try {
    \local_extendednav\menu\admintree::apply($ADMIN);
} catch (\dml_exception $e) {
    debugging(get_string('err_admintree_apply', 'local_extendednav', $e->getMessage()), DEBUG_DEVELOPER);
}
