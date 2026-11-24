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
 * The navigations of the site, and how they are listed on the pages of the plugin.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_extendednav;

/**
 * The navigations of the site, and how they are listed on the pages of the plugin.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class menu_manager {
    /** @var string[] The icon of Moodle that stands for each navigation in the column; a kind of activity has its own. */
    private const ICONS = [
        'primary' => 'i/menu', 'usermenu' => 'i/user', 'frontpage' => 'i/home', 'course' => 'i/course',
        'category' => 'i/withsubcat', 'admin' => 'i/settings',
    ];

    /**
     * Returns every navigation of the site: the primary navigation, the user menu, the secondary navigations of the site home,
     * a course and a category, one for each kind of activity that is turned on, and the site administration.
     *
     * @return \local_extendednav\menu\base[] Keyed by navigation key.
     */
    public static function get_menus(): array {
        $menus = [
            'primary' => new \local_extendednav\menu\primary(),
            'usermenu' => new \local_extendednav\menu\usermenu(),
        ];
        foreach (array_keys(\local_extendednav\menu\secondary::ORIGINS) as $origin) {
            $menus[$origin] = new \local_extendednav\menu\secondary($origin);
        }
        foreach (array_keys(\local_extendednav\menu\activity::types()) as $modname) {
            $menu = new \local_extendednav\menu\activity($modname);
            $menus[$menu->get_key()] = $menu;
        }
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
     * Returns the handler of one navigation, building only that one.
     *
     * @param string $key Navigation key.
     * @return \local_extendednav\menu\base|null
     */
    public static function get_menu(string $key): ?\local_extendednav\menu\base {
        switch ($key) {
            case 'primary':
                return new \local_extendednav\menu\primary();
            case 'usermenu':
                return new \local_extendednav\menu\usermenu();
            case \local_extendednav\menu\admintree::KEY:
                return new \local_extendednav\menu\admintree();
        }
        if (isset(\local_extendednav\menu\secondary::ORIGINS[$key])) {
            return new \local_extendednav\menu\secondary($key);
        }
        return \local_extendednav\menu\activity::from_key($key);
    }

    /**
     * Finds a menu handler by its associated Moodle configuration name.
     *
     * @param string $configname
     * @return \local_extendednav\menu\base|null
     */
    public static function get_menu_by_config(string $configname): ?\local_extendednav\menu\base {
        foreach (['primary', 'usermenu'] as $key) {
            $menu = self::get_menu($key);
            if ($menu->get_config_name() === $configname) {
                return $menu;
            }
        }
        return null;
    }

    /**
     * Returns the navigations of the kinds of activity, the ones the site uses first.
     *
     * @param string[]|null $only The navigations wanted, or null for all of them.
     * @return array Two lists of keys: used (kinds with an activity on the site) and others.
     */
    public static function get_activity_keys(?array $only = null): array {
        global $DB;

        $used = array_flip($DB->get_fieldset_sql(
            'SELECT DISTINCT m.name
               FROM {modules} m
               JOIN {course_modules} cm ON cm.module = m.id
              WHERE cm.deletioninprogress = 0'
        ));
        $keys = ['used' => [], 'others' => []];
        foreach (array_keys(\local_extendednav\menu\activity::types()) as $modname) {
            $key = \local_extendednav\menu\activity::key_for($modname);
            if ($only === null || in_array($key, $only, true)) {
                $keys[isset($used[$modname]) ? 'used' : 'others'][] = $key;
            }
        }
        return $keys;
    }

    /**
     * The navigations in groups, as the column of a page lists them: the ones of the whole site, the ones of the courses, the
     * ones of each kind of activity (the kinds the site uses first, the others folded) and the site administration. Each one
     * links to the same page for that navigation. A select lists the same navigations in the same groups.
     *
     * @param string $current Key of the navigation shown.
     * @param string $script Page the links open, such as /local/extendednav/manage.php.
     * @param array $params Other parameters of the links, such as the context of a category or a course.
     * @param string[]|null $only The navigations listed, or null for all of them.
     * @return array The groups (origins) and the same navigations as groups of options of a select (originoptions).
     */
    public static function get_origin_groups(string $current, string $script, array $params = [], ?array $only = null): array {
        $activities = self::get_activity_keys($only);
        $groups = [
            'origin_group_site' => [['primary', 'usermenu', 'frontpage'], []],
            'origin_group_course' => [['course', 'category'], []],
            'origin_group_activity' => [$activities['used'], $activities['others']],
            'origin_group_admin' => [['admin'], []],
        ];
        $item = static function (string $key) use ($current, $script, $params, $only): ?array {
            $menu = self::get_menu($key);
            if ($menu === null || ($only !== null && !in_array($key, $only, true))) {
                return null;
            }
            $activity = $menu instanceof \local_extendednav\menu\activity;
            return [
                'key' => $key,
                'name' => $activity ? $menu->get_short_name() : $menu->get_name(),
                'title' => $menu->get_name(),
                'url' => (new \moodle_url($script, $params + ['menu' => $key]))->out(false),
                'active' => $key === $current,
                'icon' => $activity ? 'monologo' : (self::ICONS[$key] ?? 'i/navigationitem'),
                'component' => $activity ? 'mod_' . $menu->get_modname() : 'core',
                'off' => !self::is_enabled($key),
            ];
        };
        $origins = [];
        $options = [];
        foreach ($groups as $string => [$shown, $folded]) {
            $items = array_values(array_filter(array_map($item, $shown)));
            $more = array_values(array_filter(array_map($item, $folded)));
            if (!$items && !$more) {
                continue;
            }
            $label = get_string($string, 'local_extendednav');
            $origins[] = [
                'label' => $label,
                'items' => $items,
                'more' => $more,
                'hasmore' => !empty($more),
                // The folded kinds open by themselves when the navigation shown is one of them.
                'moreopen' => in_array($current, array_column($more, 'key'), true),
                'morelabel' => get_string('origin_more_kinds', 'local_extendednav', count($more)),
                'moreid' => 'len-origins-' . preg_replace('/[^a-z]/', '', $string),
            ];
            $options[] = ['label' => $label, 'options' => array_map(static fn(array $entry): array => [
                'name' => $entry['title'], 'url' => $entry['url'], 'selected' => $entry['active'],
            ], array_merge($items, $more))];
        }
        return ['origins' => $origins, 'originoptions' => $options];
    }
}
