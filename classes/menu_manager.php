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
        return [
            'primary' => new \local_extendednav\menu\primary(),
            'usermenu' => new \local_extendednav\menu\usermenu(),
        ];
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
}
