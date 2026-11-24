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

namespace local_extendednav\local\navigation;

use local_extendednav\local\node\access;
use local_extendednav\local\node\repository;
use local_extendednav\local\node\text;
use local_extendednav\local\support\url_helper;
use local_extendednav\menu\preferences;
use local_extendednav\menu\secondary;
use local_extendednav\menu_manager;

/**
 * Applies the navigation of the preferences to the settings of a user, as Moodle builds them for the page Preferences: first
 * what the site sets (groups and items hidden, renamed, ordered or added), then, on the user's own preferences, what the user
 * chose for themselves (the items they hide and the order they give them), when they may.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class preferences_injector {
    /** @var string User preference that keeps what a user chose for their own preferences, as JSON. */
    public const PREFERENCE = 'local_extendednav_preferences';

    /** @var bool Whether what the user chose is applied; the page where they choose it shows what the site shows. */
    public static bool $personal = true;

    /** @var string Key of the items outside every group, which Moodle shows last, under Miscellaneous. */
    public const LOOSE = 'miscellaneous';

    /**
     * Reads the groups and items of the settings of a user.
     *
     * @param \navigation_node $settings The node that holds the groups of the preferences.
     * @return array By group key: node and items (by item key).
     */
    public static function read(\navigation_node $settings): array {
        $groups = [];
        $loose = [];
        foreach ($settings->children as $group) {
            if (!$group->has_children()) {
                // An item outside every group: Moodle shows it under Miscellaneous, at the end.
                $key = secondary::key_of($group);
                if ($key !== null && $key !== preferences::PERSONAL) {
                    $loose[$key] = $group;
                }
                continue;
            }
            $items = [];
            foreach ($group->children as $item) {
                $itemkey = secondary::key_of($item);
                if ($itemkey !== null && $itemkey !== preferences::PERSONAL) {
                    $items[$itemkey] = $item;
                }
            }
            // A group Moodle adds without a key (Roles) is known by its first item, the same in every language.
            $groupkey = secondary::key_of($group) ?? ($items ? 'group_' . array_key_first($items) : null);
            if ($groupkey !== null) {
                $groups[$groupkey] = ['node' => $group, 'items' => $items];
            }
        }
        if ($loose) {
            $groups[self::LOOSE] = ['node' => null, 'items' => $loose];
        }
        return $groups;
    }

    /**
     * Records the groups and items of the settings of a user, so the site can change them.
     *
     * @param array $groups As read() returns them.
     */
    public static function record(array $groups): void {
        $seen = [];
        foreach ($groups as $groupkey => $group) {
            $text = $group['node'] ? self::text_of($group['node']) : get_string('miscellaneous');
            $seen[$groupkey] = (object)['text' => $text, 'action' => '', 'parent' => ''];
            foreach ($group['items'] as $itemkey => $item) {
                $action = $item->action instanceof \moodle_url ? (string)url_helper::to_site_path($item->action->out(false)) : '';
                $seen[$itemkey] = (object)['text' => self::text_of($item), 'action' => $action, 'parent' => $groupkey];
            }
        }
        (new preferences())->record_seen($seen);
    }

    /**
     * Applies the changes to the settings of a user.
     *
     * @param \navigation_node $settings The node that holds the groups of the preferences.
     * @param \stdClass $user The user whose preferences they are.
     */
    public static function apply(\navigation_node $settings, \stdClass $user): void {
        global $USER;

        if (during_initial_install() || !get_config('local_extendednav', 'enable_plugin')) {
            return;
        }
        $groups = self::read($settings);
        if (!$groups) {
            return;
        }
        self::record($groups);

        // With the navigation turned off, the site changes nothing, and each user still orders their own page.
        $rows = menu_manager::is_enabled(preferences::KEY) ? repository::get_menu_nodes_by_key(preferences::KEY) : [];
        $own = self::$personal && (int)$user->id === (int)$USER->id && self::can_personalise();
        $personal = $own ? self::get_personal((int)$user->id) : ['hidden' => [], 'order' => []];

        // The items the site adds to a group.
        foreach ($rows as $key => $row) {
            $parent = (string)($row->parentkey ?? '');
            if (
                isset($groups[$key]) || !isset($groups[$parent]) || isset($groups[$parent]['items'][$key])
                    || trim((string)$row->url) === ''
            ) {
                continue;
            }
            $groups[$parent]['items'][$key] = \navigation_node::create(
                format_string(text::resolve($row->title)),
                new \moodle_url((string)$row->url),
                \navigation_node::TYPE_SETTING,
                null,
                $key
            );
        }

        foreach ($groups as $group) {
            foreach ($group['node'] ? [$group['node']] : $group['items'] as $node) {
                if ($node->parent) {
                    $node->remove();
                }
            }
        }
        // The items outside every group stay at the end, where Moodle shows them.
        $groupkeys = array_values(array_diff(self::order(array_keys($groups), $rows, $personal['order'][''] ?? []), [self::LOOSE]));
        if (isset($groups[self::LOOSE])) {
            $groupkeys[] = self::LOOSE;
        }
        $first = null;
        $fallback = null;
        foreach ($groupkeys as $groupkey) {
            if (!self::shown($groupkey, $rows, $personal['hidden'])) {
                continue;
            }
            $group = $groups[$groupkey]['node'];
            if ($group) {
                $fallback = $fallback ?? $group;
                self::rename($group, $rows[$groupkey] ?? null);
            }
            $items = $groups[$groupkey]['items'];
            foreach ($items as $item) {
                if ($item->parent) {
                    $item->remove();
                }
            }
            $added = 0;
            foreach (self::order(array_keys($items), $rows, $personal['order'][$groupkey] ?? []) as $itemkey) {
                if (self::shown($itemkey, $rows, $personal['hidden'])) {
                    self::rename($items[$itemkey], $rows[$itemkey] ?? null);
                    ($group ?? $settings)->add_node($items[$itemkey]);
                    $added++;
                }
            }
            if ($added && $group) {
                $settings->add_node($group);
                $first = $first ?? $group;
            }
        }

        // The way to order and hide their own preferences, in the first group, for whoever may.
        if ($own && $first === null && $fallback !== null) {
            // The user hid every item: the first group still holds the way back.
            $settings->add_node($fallback);
            $first = $fallback;
        }
        if ($own && $first !== null) {
            $first->add(
                get_string('personal_preferences', 'local_extendednav'),
                new \moodle_url('/local/extendednav/mypreferences.php'),
                \navigation_node::TYPE_SETTING,
                null,
                preferences::PERSONAL
            );
        }
    }

    /**
     * Whether the current user may order and hide their own preferences.
     *
     * @return bool
     */
    public static function can_personalise(): bool {
        global $USER;

        return isloggedin() && !isguestuser()
            && has_capability('local/extendednav:personalise', \context_user::instance((int)$USER->id));
    }

    /**
     * Returns what a user chose for their own preferences.
     *
     * @param int $userid User.
     * @return array With hidden (keys) and order (the keys of each group by the key of the group; the groups under '').
     */
    public static function get_personal(int $userid): array {
        $stored = json_decode((string)get_user_preferences(self::PREFERENCE, '', $userid), true);
        $stored = is_array($stored) ? $stored : [];
        $order = [];
        foreach ((array)($stored['order'] ?? []) as $groupkey => $keys) {
            $order[(string)$groupkey] = array_values(array_filter((array)$keys, 'is_string'));
        }
        return ['hidden' => array_values(array_filter((array)($stored['hidden'] ?? []), 'is_string')), 'order' => $order];
    }

    /**
     * Saves what a user chose for their own preferences.
     *
     * @param int $userid User.
     * @param array $personal With hidden and order, as get_personal() returns them.
     */
    public static function set_personal(int $userid, array $personal): void {
        $value = json_encode(['hidden' => array_values(array_unique($personal['hidden'])), 'order' => $personal['order']]);
        set_user_preference(self::PREFERENCE, $value, $userid);
    }

    /**
     * Orders keys as the site orders them, and then as the user ordered them.
     *
     * @param string[] $keys Keys.
     * @param \stdClass[] $rows Rows of the site, by key.
     * @param string[] $mine The order the user chose, if any.
     * @return string[]
     */
    public static function order(array $keys, array $rows, array $mine = []): array {
        usort($keys, static function (string $one, string $two) use ($rows): int {
            return [(int)($rows[$one]->sortorder ?? PHP_INT_MAX), $one] <=> [(int)($rows[$two]->sortorder ?? PHP_INT_MAX), $two];
        });
        $mine = array_values(array_intersect($mine, $keys));
        return $mine ? array_values(array_unique(array_merge($mine, $keys))) : $keys;
    }

    /**
     * Whether the site and the user let a group or an item show.
     *
     * @param string $key Key.
     * @param \stdClass[] $rows Rows of the site, by key.
     * @param string[] $hidden What the user hides.
     * @return bool
     */
    private static function shown(string $key, array $rows, array $hidden): bool {
        return (!isset($rows[$key]) || access::is_shown($rows[$key])) && !in_array($key, $hidden, true);
    }

    /**
     * Gives a node the title the site sets, if any.
     *
     * @param \navigation_node $node Node.
     * @param \stdClass|null $row Its row of the site.
     */
    private static function rename(\navigation_node $node, ?\stdClass $row): void {
        $title = $row === null ? '' : text::resolve($row->title ?? '');
        if ($title !== '') {
            $node->text = format_string($title);
        }
    }

    /**
     * Returns the plain text of a node.
     *
     * @param \navigation_node $node Node.
     * @return string
     */
    private static function text_of(\navigation_node $node): string {
        return trim(strip_tags((string)$node->text));
    }
}
