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

use core\hook\navigation\primary_extend;
use core\hook\output\before_http_headers;
use core\hook\output\before_standard_head_html_generation;
use core\hook\output\before_standard_top_of_body_html_generation;
use local_extendednav\local\access_guard;
use local_extendednav\local\node_repository;

/**
 * Hook callbacks.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hooks {
    /** @var bool Whether the user menu setting has been rewritten in this request. */
    private static bool $usermenuapplied = false;

    /** @var string|null Value of custommenuitems before the plugin emptied it. */
    private static ?string $origcustommenu = null;

    /** @var string|null Value of customusermenuitems before the plugin rewrote it. */
    private static ?string $origcustomusermenu = null;

    /**
     * Resets the request state.
     */
    public static function reset(): void {
        self::$usermenuapplied = false;
        self::$origcustommenu = null;
        self::$origcustomusermenu = null;
    }

    /**
     * Blocks the page about to be printed when the user is not allowed it.
     *
     * @param before_http_headers $hook The hook.
     */
    public static function before_http_headers(before_http_headers $hook): void {
        global $PAGE;
        access_guard::check($PAGE);
    }

    /**
     * Applies the plugin nodes to the primary navigation and the user menu.
     *
     * Boost-based themes build the user menu right after the primary navigation and
     * before the page head, so the user menu is rewritten here too.
     *
     * @param primary_extend $hook The hook.
     */
    public static function extend_primary_navigation(primary_extend $hook): void {
        global $CFG;

        if (!self::is_active()) {
            return;
        }
        self::backup_core_menus();

        $primary = menu_manager::get_menu('primary');
        $primary->inject_nodes(node_repository::get_menu_nodes('primary'), $hook->get_primaryview());

        // The custom menu items are plugin nodes now; stop Moodle from adding them a second time.
        $CFG->custommenuitems = '';

        self::apply_user_menu();
    }

    /**
     * Rewrites the user menu for themes that build it after the page head.
     *
     * @param before_standard_head_html_generation $hook The hook.
     */
    public static function before_standard_head(before_standard_head_html_generation $hook): void {
        if (self::is_active()) {
            self::backup_core_menus();
            self::apply_user_menu();
        }
    }

    /**
     * Restores the real menu settings on admin settings pages and adds the user menu extras.
     *
     * @param before_standard_top_of_body_html_generation $hook The hook.
     */
    public static function before_top_of_body(before_standard_top_of_body_html_generation $hook): void {
        global $CFG, $PAGE;

        // The admin settings form must show the real settings, not the per-request copies.
        if (self::is_admin_settings_page()) {
            if (self::$origcustommenu !== null) {
                $CFG->custommenuitems = self::$origcustommenu;
            }
            if (self::$origcustomusermenu !== null) {
                $CFG->customusermenuitems = self::$origcustomusermenu;
            }
        }

        if (!self::is_active()) {
            return;
        }
        $usermenu = menu_manager::get_menu('usermenu');
        $html = $usermenu->build_browser_templates(node_repository::get_menu_nodes('usermenu'), $hook->renderer);
        if ($html !== '') {
            $hook->add_html($html);
            $PAGE->requires->js_call_amd('local_extendednav/usermenu', 'init');
        }
    }

    /**
     * Whether the plugin changes the navigation in this request.
     *
     * @return bool
     */
    private static function is_active(): bool {
        if (during_initial_install() || !get_config('local_extendednav', 'enable_plugin')) {
            return false;
        }
        return isloggedin() && !isguestuser();
    }

    /**
     * Rewrites customusermenuitems for the current user, once per request.
     */
    private static function apply_user_menu(): void {
        global $CFG;

        if (self::$usermenuapplied) {
            return;
        }
        self::$usermenuapplied = true;
        $usermenu = menu_manager::get_menu('usermenu');
        $CFG->customusermenuitems = $usermenu->build_config_value(node_repository::get_menu_nodes('usermenu'));
    }

    /**
     * Keeps the real menu settings, so admin settings pages can show them.
     */
    private static function backup_core_menus(): void {
        global $CFG;

        if (self::$origcustommenu === null && isset($CFG->custommenuitems)) {
            self::$origcustommenu = (string)$CFG->custommenuitems;
        }
        if (self::$origcustomusermenu === null && isset($CFG->customusermenuitems) && !self::$usermenuapplied) {
            self::$origcustomusermenu = (string)$CFG->customusermenuitems;
        }
    }

    /**
     * Whether the current request is an admin settings page.
     *
     * @return bool
     */
    private static function is_admin_settings_page(): bool {
        global $PAGE;

        if (!$PAGE->has_set_url()) {
            return false;
        }
        return strpos($PAGE->url->get_path(), '/admin/settings.php') !== false;
    }
}
