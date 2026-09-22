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
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Top category for local plugins
    $ADMIN->add('localplugins', new admin_category('local_extendednav_cat', get_string('pluginname', 'local_extendednav')));

    // General settings page
    $settings = new admin_settingpage('local_extendednav_settings', get_string('pluginname', 'local_extendednav'));

    $setting = new admin_setting_configcheckbox(
        'local_extendednav/enable_plugin',
        get_string('enable_plugin', 'local_extendednav'),
        get_string('enable_plugin_desc', 'local_extendednav'),
        1
    );
    $settings->add($setting);
    
    // Setting global de fallback
    $setting = new admin_setting_configtext(
        'local_extendednav/fallbackurl',
        get_string('fallbackurl', 'local_extendednav'),
        get_string('fallbackurl_desc', 'local_extendednav'),
        '', // Default empty
        PARAM_RAW
    );
    $settings->add($setting);

    $ADMIN->add('local_extendednav_cat', $settings);

    // Management page
    $ADMIN->add('local_extendednav_cat', new admin_externalpage(
        'local_extendednav_manage',
        get_string('manage_nodes', 'local_extendednav'),
        new moodle_url('/local/extendednav/manage.php')
    ));
}