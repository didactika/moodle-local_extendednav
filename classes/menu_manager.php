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
 * Manager class for handling scalable menus.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_extendednav;

/**
 * Manager class for handling scalable menus.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class menu_manager {
    /**
     * Returns an array of all registered menu handler instances.
     *
     * @return \local_extendednav\menu\base[]
     */
    public static function get_menus(): array {
        $menus = [
            'primary' => new \local_extendednav\menu\primary(),
            'usermenu' => new \local_extendednav\menu\usermenu(),
        ];
        // The secondary navigations: course, site home, activity and category.
        foreach (array_keys(\local_extendednav\menu\secondary::ORIGINS) as $origin) {
            $menus[$origin] = new \local_extendednav\menu\secondary($origin);
        }
        // The site administration, with every category and page of its tree.
        $menus[\local_extendednav\menu\admintree::KEY] = new \local_extendednav\menu\admintree();
        return $menus;
    }

    /**
     * Whether the changes of a navigation are applied: the plugin is on and the navigation has not been turned off.
     *
     * @param string $key Navigation key.
     * @return bool
     */
    public static function is_enabled(string $key): bool {
        return (bool)get_config('local_extendednav', 'enable_plugin')
            && get_config('local_extendednav', 'enabled_' . $key) !== '0';
    }

    /**
     * Gets a specific menu handler by key.
     *
     * @param string $key
     * @return \local_extendednav\menu\base|null
     */
    public static function get_menu(string $key): ?\local_extendednav\menu\base {
        $menus = self::get_menus();
        return $menus[$key] ?? null;
    }

    /**
     * Finds a menu handler by its associated Moodle configuration name.
     *
     * @param string $configname
     * @return \local_extendednav\menu\base|null
     */
    public static function get_menu_by_config(string $configname): ?\local_extendednav\menu\base {
        foreach (self::get_menus() as $menu) {
            if ($menu->get_config_name() === $configname) {
                return $menu;
            }
        }
        return null;
    }

    /**
     * The navigations in groups, as the column of a page lists them: the ones of the whole site, the ones of the courses and the
     * site administration. Each one links to the same page for that navigation.
     *
     * @param string $current Key of the navigation shown.
     * @param string $script Page the links open, such as /local/extendednav/manage.php.
     * @return array The groups (origins) and the same navigations as options of a select (originoptions).
     */
    public static function get_origin_groups(string $current, string $script): array {
        $groups = [
            'origin_group_site' => ['primary', 'usermenu', 'frontpage'],
            'origin_group_course' => ['course', 'activity', 'category'],
            'origin_group_admin' => ['admin'],
        ];
        $origins = [];
        $options = [];
        foreach ($groups as $string => $keys) {
            $items = [];
            foreach ($keys as $key) {
                $menu = self::get_menu($key);
                if ($menu === null) {
                    continue;
                }
                $url = (new \moodle_url($script, ['menu' => $key]))->out(false);
                $items[] = ['key' => $key, 'name' => $menu->get_name(), 'url' => $url, 'active' => $key === $current];
                $options[] = ['name' => $menu->get_name(), 'url' => $url, 'selected' => $key === $current];
            }
            $origins[] = ['label' => get_string($string, 'local_extendednav'), 'items' => $items];
        }
        return ['origins' => $origins, 'originoptions' => $options];
    }
}
