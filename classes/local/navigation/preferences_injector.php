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
 * Applies the navigation of the preferences to the settings of a user, as Moodle builds them for the page Preferences.
 *
 * The sections (User account, Badges...) and their items are the folders of the navigation, as in the site administration:
 * the site hides, renames and orders them, moves items from one section to another, creates sections of its own and adds
 * links to them. Each user arranges their own page in the same way, as exceptions of their own context: which sections and
 * items it shows, in which order and in which section. The plugin adds its own section, Extended navigation, with the way to
 * arrange the page.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class preferences_injector {
    /** @var string Key of the items outside every section, which Moodle shows last, under Miscellaneous. */
    public const LOOSE = 'miscellaneous';

    /** @var string[] The names Moodle gives the sections and items in this request, in the language of the page, by key. */
    public static array $texts = [];

    /** @var string Key of the section of the plugin. */
    public const SECTION = 'local_extendednav';

    /**
     * Reads the sections and items of the settings of a user.
     *
     * @param \navigation_node $settings The node that holds the sections of the preferences.
     * @return array By section key: node (null for the loose items) and items (by item key).
     */
    public static function read(\navigation_node $settings): array {
        $groups = [];
        $loose = [];
        foreach ($settings->children as $group) {
            if (!$group->has_children()) {
                // An item outside every section: Moodle shows it under Miscellaneous, at the end.
                $key = secondary::key_of($group);
                if ($key !== null) {
                    $loose[$key] = $group;
                }
                continue;
            }
            $items = [];
            foreach ($group->children as $item) {
                $itemkey = secondary::key_of($item);
                if ($itemkey !== null) {
                    $items[$itemkey] = $item;
                }
            }
            // A section Moodle adds without a key (Roles) is known by its first item, the same in every language.
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
     * Records the sections and items of the settings of a user, so the site can change them.
     *
     * @param array $groups As read() returns them.
     */
    public static function record(array $groups): void {
        $seen = [];
        foreach ($groups as $groupkey => $group) {
            $text = $group['node'] ? self::text_of($group['node']) : get_string('miscellaneous');
            $seen[$groupkey] = (object)['text' => $text, 'action' => '', 'parent' => ''];
            self::$texts[$groupkey] = $text;
            foreach ($group['items'] as $itemkey => $item) {
                $action = $item->action instanceof \moodle_url ? (string)url_helper::to_site_path($item->action->out(false)) : '';
                $seen[$itemkey] = (object)['text' => self::text_of($item), 'action' => $action, 'parent' => $groupkey];
                self::$texts[$itemkey] = self::text_of($item);
            }
        }
        (new preferences())->record_seen($seen);
    }

    /**
     * Applies the changes of the site and of the user to the settings of a user.
     *
     * @param \navigation_node $settings The node that holds the sections of the preferences.
     * @param \stdClass $user The user whose preferences they are.
     */
    public static function apply(\navigation_node $settings, \stdClass $user): void {
        if (!\local_extendednav\menu_manager::is_on() || isguestuser($user)) {
            return;
        }
        $groups = self::read($settings);
        $section = self::section($user);
        if ($section !== null) {
            $groups[self::SECTION] = $section;
        }
        if (!$groups) {
            return;
        }
        self::record($groups);
        // Moodle shows the items outside every section last, under Miscellaneous. They go in a section of their own, with the same
        // name, so it can be ordered, renamed and hidden as any other.
        if (isset($groups[self::LOOSE])) {
            $groups[self::LOOSE]['node'] = \navigation_node::create(
                get_string('miscellaneous'),
                null,
                \navigation_node::TYPE_CONTAINER,
                null,
                self::LOOSE
            );
        }

        $context = \context_user::instance((int)$user->id);
        // With the navigation turned off, the site changes nothing, and each user still arranges their own page.
        $rows = menu_manager::is_enabled(preferences::KEY)
            ? repository::get_scoped_nodes(preferences::KEY, $context)
            : array_filter(repository::get_scoped_nodes(preferences::KEY, $context), static fn(\stdClass $row): bool =>
                (int)($row->contextid ?? 0) === (int)$context->id);
        $natives = (new preferences())->get_native_nodes();

        // The sections and links the site adds.
        foreach ($rows as $key => $row) {
            if (isset($natives[$key]) || isset($groups[$key])) {
                continue;
            }
            $url = trim((string)$row->url);
            $parent = (string)($row->parentkey ?? '');
            if ($url === '' && $parent === '') {
                $node = \navigation_node::create(
                    format_string(text::resolve($row->title)),
                    null,
                    \navigation_node::TYPE_CONTAINER,
                    null,
                    $key
                );
                $groups[$key] = ['node' => $node, 'items' => []];
            }
        }
        $items = [];
        $home = [];
        foreach ($groups as $groupkey => $group) {
            foreach ($group['items'] as $itemkey => $item) {
                $items[$itemkey] = $item;
                $home[$itemkey] = $groupkey;
            }
        }
        foreach ($rows as $key => $row) {
            if (!isset($natives[$key]) && !isset($items[$key]) && !isset($groups[$key]) && trim((string)$row->url) !== '') {
                $items[$key] = \navigation_node::create(
                    format_string(text::resolve($row->title)),
                    new \moodle_url((string)$row->url),
                    \navigation_node::TYPE_SETTING,
                    null,
                    $key
                );
                $home[$key] = (string)($row->parentkey ?? '');
            }
        }

        // Each item goes to the section its row names, when that section is there; otherwise it stays where Moodle put it.
        $members = array_fill_keys(array_keys($groups), []);
        foreach ($items as $key => $item) {
            $target = (string)($rows[$key]->parentkey ?? '');
            $target = $target !== '' && isset($groups[$target]) ? $target : $home[$key];
            if (isset($groups[$target])) {
                $members[$target][] = $key;
            }
        }

        foreach ($groups as $group) {
            foreach ($group['node'] ? [$group['node']] : $group['items'] as $node) {
                if ($node->parent) {
                    $node->remove();
                }
            }
        }
        foreach ($items as $item) {
            if ($item->parent) {
                $item->remove();
            }
        }

        foreach (self::order(array_keys($groups), $rows) as $groupkey) {
            if (!self::shown($groupkey, $rows)) {
                continue;
            }
            $group = $groups[$groupkey]['node'];
            if ($group) {
                self::rename($group, $rows[$groupkey] ?? null);
            }
            $added = 0;
            foreach (self::order($members[$groupkey], $rows) as $itemkey) {
                if (self::shown($itemkey, $rows)) {
                    self::rename($items[$itemkey], $rows[$itemkey] ?? null);
                    ($group ?? $settings)->add_node($items[$itemkey]);
                    $added++;
                }
            }
            if ($added && $group) {
                $settings->add_node($group);
            }
        }
    }

    /**
     * The section of the plugin, with the way to arrange the page: for the user, their own; for whoever manages the navigation
     * of the site, the one of the user whose preferences they look at.
     *
     * @param \stdClass $user The user whose preferences they are.
     * @return array|null As read() returns a section, or null when nobody may arrange this page here.
     */
    private static function section(\stdClass $user): ?array {
        global $USER;

        $scope = context_scope::from_id((int)\context_user::instance((int)$user->id)->id);
        if ($scope === null || !$scope->can_manage()) {
            return null;
        }
        $own = (int)$user->id === (int)$USER->id;
        $node = \navigation_node::create(
            get_string('pluginname', 'local_extendednav'),
            null,
            \navigation_node::TYPE_CONTAINER,
            null,
            self::SECTION
        );
        $item = $node->add(
            get_string($own ? 'personal_preferences' : 'personal_preferences_of', 'local_extendednav', fullname($user)),
            $scope->url(preferences::KEY),
            \navigation_node::TYPE_SETTING,
            null,
            preferences::PERSONAL
        );
        return ['node' => $node, 'items' => [preferences::PERSONAL => $item]];
    }

    /**
     * Orders keys as the rows order them: the ones with a row first, in the order of the rows, then the others as they came.
     *
     * @param string[] $keys Keys.
     * @param \stdClass[] $rows Rows that apply, by key, in their order.
     * @return string[]
     */
    public static function order(array $keys, array $rows): array {
        $position = array_flip(array_keys($rows));
        $start = array_flip($keys);
        usort($keys, static function (string $one, string $two) use ($position, $start): int {
            return [$position[$one] ?? PHP_INT_MAX, $start[$one]] <=> [$position[$two] ?? PHP_INT_MAX, $start[$two]];
        });
        return $keys;
    }

    /**
     * Whether the rows let a section or an item show. The way to arrange the page is never hidden.
     *
     * @param string $key Key.
     * @param \stdClass[] $rows Rows that apply, by key.
     * @return bool
     */
    private static function shown(string $key, array $rows): bool {
        if ($key === self::SECTION || $key === preferences::PERSONAL || !isset($rows[$key])) {
            return true;
        }
        // What a user hides on their own page is hidden from them, administrators too.
        $row = $rows[$key];
        $contextid = (int)($row->contextid ?? 0);
        $user = $contextid > 0 ? \context::instance_by_id($contextid, IGNORE_MISSING) : null;
        if ($user instanceof \context_user && (int)$row->visibility === access::VISIBILITY_HIDDEN) {
            return false;
        }
        return access::is_shown($row);
    }

    /**
     * Gives a node the title its row sets, if any.
     *
     * @param \navigation_node $node Node.
     * @param \stdClass|null $row Its row.
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
