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
    /** @var string Key of the page that lists the kinds of activity, which is no navigation. */
    public const ACTIVITIES = 'activities';

    /** @var string[] The icon of Moodle that stands for each navigation in the column; a kind of activity has its own. */
    private const ICONS = [
        'primary' => 'i/menu', 'usermenu' => 'i/user', 'preferences' => 't/preferences',
        'frontpage' => 'i/home', 'course' => 'i/course',
        'category' => 'i/withsubcat', 'admin' => 'i/settings',
    ];

    /** @var int|null Version of the plugin files, read once per request. */
    private static ?int $diskversion = null;

    /**
     * Whether the plugin changes anything in this request: it is on, and its database is the one its files expect.
     *
     * While an upgrade of the plugin is pending the files are new and the tables old, so nothing of the plugin may read
     * them, not even on the page of the upgrade itself: Moodle shows the site as it is without the plugin until then.
     *
     * @return bool
     */
    public static function is_on(): bool {
        return !during_initial_install() && !self::upgrade_pending()
            && (bool)get_config('local_extendednav', 'enable_plugin');
    }

    /**
     * Whether the plugin files are newer than its database: the upgrade has not run yet, or has not finished.
     *
     * @return bool
     */
    public static function upgrade_pending(): bool {
        if (self::$diskversion === null) {
            $plugin = new \stdClass();
            require(__DIR__ . '/../version.php');
            self::$diskversion = (int)$plugin->version;
        }
        return (int)get_config('local_extendednav', 'version') !== self::$diskversion;
    }

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
            \local_extendednav\menu\preferences::KEY => new \local_extendednav\menu\preferences(),
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
        return self::is_on()
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
            case \local_extendednav\menu\preferences::KEY:
                return new \local_extendednav\menu\preferences();
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
     * The navigations as the column of a page lists them, in one list with no groups: the ones of the whole site, the ones of
     * the courses, Activities, which opens the list of the kinds, and the site administration. The select of a narrow screen
     * lists the same, by their short names. A page of few navigations (a category, a course) lists the kinds it uses one by one
     * instead of Activities.
     *
     * @param string $current Key of the navigation shown.
     * @param string $script Page the links open, such as /local/extendednav/manage.php.
     * @param array $params Other parameters of the links, such as the context of a category or a course.
     * @param string[]|null $only The navigations listed, or null for all of them.
     * @return array The entries of the column (origins) and the options of the select (originoptions).
     */
    public static function get_origin_groups(string $current, string $script, array $params = [], ?array $only = null): array {
        $item = static function (string $key) use ($current, $script, $params, $only): ?array {
            $menu = self::get_menu($key);
            if ($menu === null || ($only !== null && !in_array($key, $only, true))) {
                return null;
            }
            $activity = $menu instanceof \local_extendednav\menu\activity;
            return [
                'key' => $key,
                'name' => $menu->get_name(),
                'short' => $menu->get_short_name(),
                'url' => (new \moodle_url($script, $params + ['menu' => $key]))->out(false),
                'active' => $key === $current,
                'icon' => $activity ? 'monologo' : (self::ICONS[$key] ?? 'i/navigationitem'),
                'component' => $activity ? 'mod_' . $menu->get_modname() : 'core',
                'off' => !self::is_enabled($key),
            ];
        };
        $items = static fn(array $keys): array => array_values(array_filter(array_map($item, $keys)));

        $origins = $items(['primary', 'usermenu', 'preferences', 'frontpage', 'course', 'category']);
        $activities = self::get_activity_keys($only);
        $kinds = array_merge($items($activities['used']), $items($activities['others']));
        if ($kinds && $only !== null) {
            // A course or a category lists the few kinds it uses.
            $origins = array_merge($origins, $kinds);
        } else if ($kinds) {
            // The kinds of the site are many: one entry, Activities, opens their list, and stays open on the page of one of them.
            $label = get_string('origin_group_activity', 'local_extendednav');
            $origins[] = [
                'key' => self::ACTIVITIES,
                'name' => $label,
                'short' => $label,
                'url' => (new \moodle_url($script, $params + ['menu' => self::ACTIVITIES]))->out(false),
                'active' => $current === self::ACTIVITIES || str_starts_with($current, \local_extendednav\menu\activity::PREFIX),
                'icon' => 'i/activities',
                'component' => 'core',
                'off' => false,
            ];
        }
        $origins = array_merge($origins, $items(['admin']));
        $options = array_map(static fn(array $entry): array => ['name' => $entry['short'], 'url' => $entry['url'],
            'selected' => $entry['active']], $origins);
        return ['origins' => $origins, 'originoptions' => $options];
    }

    /**
     * Returns the kinds of activity as the list of the kinds shows them, the ones the site uses first: each with its icon, how
     * many activities of the site are of that kind and in how many courses, what the site changes in its navigation (the items
     * hidden, restricted, renamed and added), how many courses, categories or activities change it on their own, and when it
     * was last changed.
     *
     * @param string $script Page each kind opens, such as /local/extendednav/manage.php.
     * @param array $params Other parameters of the links.
     * @return array The rows.
     */
    public static function get_kind_rows(string $script, array $params = []): array {
        global $DB;

        $usage = $DB->get_records_sql(
            'SELECT m.name, COUNT(cm.id) AS activities, COUNT(DISTINCT cm.course) AS courses
               FROM {modules} m
               JOIN {course_modules} cm ON cm.module = m.id
              WHERE cm.deletioninprogress = 0
           GROUP BY m.name'
        );
        $like = $DB->sql_like('menu', ':prefix');
        $prefix = ['prefix' => \local_extendednav\menu\activity::PREFIX . '%'];
        // The exceptions: how many contexts change each navigation, and when they last did.
        $exceptions = $DB->get_records_sql(
            "SELECT menu, COUNT(DISTINCT contextid) AS contexts, MAX(timemodified) AS lastchange
               FROM {local_extendednav}
              WHERE $like AND contextid > 0
           GROUP BY menu",
            $prefix
        );
        // The rows of the site, few for each kind: what they change is read from them.
        $site = [];
        $records = $DB->get_recordset_select(
            'local_extendednav',
            "$like AND contextid = 0",
            $prefix,
            '',
            'id, menu, nodekey, title, visibility, availability, langs, timecreated, timemodified'
        );
        foreach ($records as $record) {
            $site[$record->menu][] = $record;
        }
        $records->close();

        $rows = [];
        foreach (\local_extendednav\menu\activity::types() as $modname => $name) {
            $menu = new \local_extendednav\menu\activity($modname);
            $key = $menu->get_key();
            $natives = $menu->get_native_nodes();
            $changes = ['hidden' => 0, 'restricted' => 0, 'renamed' => 0, 'added' => 0];
            $last = (int)($exceptions[$key]->lastchange ?? 0);
            foreach ($site[$key] ?? [] as $record) {
                $native = isset($natives[$record->nodekey]);
                if (!$native) {
                    $changes['added']++;
                } else if (trim((string)$record->title) !== '') {
                    $changes['renamed']++;
                }
                $visibility = (int)$record->visibility;
                if ($visibility === \local_extendednav\local\node\access::VISIBILITY_HIDDEN) {
                    $changes['hidden']++;
                } else if (
                    $visibility === \local_extendednav\local\node\access::VISIBILITY_ROLES
                    || !empty($record->availability) || !empty($record->langs)
                ) {
                    $changes['restricted']++;
                }
                // A row Moodle's item got when it was found is no change until it is saved again.
                if (!$native || (int)$record->timemodified > (int)$record->timecreated) {
                    $last = max($last, (int)$record->timemodified);
                }
            }
            $badges = [];
            foreach (array_filter($changes) as $change => $count) {
                $badges[] = ['label' => get_string('kinds_' . $change, 'local_extendednav', $count)];
            }
            $activities = (int)($usage[$modname]->activities ?? 0);
            $rows[] = [
                'key' => $key,
                'name' => $name,
                'component' => 'mod_' . $modname,
                'url' => (new \moodle_url($script, $params + ['menu' => $key]))->out(false),
                'activities' => $activities,
                'usage' => $activities ? get_string('kinds_usage', 'local_extendednav', (object)[
                    'activities' => $activities,
                    'courses' => (int)$usage[$modname]->courses,
                ]) : get_string('kinds_unused', 'local_extendednav'),
                'itemslabel' => get_string('kinds_items_count', 'local_extendednav', count($natives)),
                'changes' => $badges,
                'exceptions' => (int)($exceptions[$key]->contexts ?? 0),
                'lastchange' => $last ? userdate($last, get_string('strftimedatefullshort', 'core_langconfig')) : '',
                'off' => !self::is_enabled($key),
            ];
        }
        usort($rows, static fn(array $one, array $two): int => [$two['activities'] > 0, $one['name']]
            <=> [$one['activities'] > 0, $two['name']]);
        return $rows;
    }
}
