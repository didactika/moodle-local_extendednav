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

namespace local_extendednav\menu;

use local_extendednav\local\node\access;
use local_extendednav\local\node\repository;
use local_extendednav\local\node\text;

/**
 * The site administration: every category of the administration tree and every page inside it, at any depth.
 *
 * Moodle builds the tree on every administration page, so its nodes are read from the tree itself: nothing is guessed and
 * nothing is built on purpose. A node can be renamed, hidden, restricted to roles or languages and ordered among the others of
 * its category, and a category can get links of its own. Their addresses stay those of Moodle, so no setting is ever lost.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admintree extends base {
    /** @var string Key of this navigation. */
    public const KEY = 'admin';

    /** @var string[] Nodes that are never hidden, so the way to this plugin is always there. */
    public const PROTECTED = ['appearance', 'local_extendednav_cat', 'local_extendednav_manage'];

    /** @var string Key of the group General of the list, which holds the items of the top level that are not tabs. */
    public const GENERAL = 'admingeneral';

    /** @var array|null The nodes of the tree, found once per request. */
    private static ?array $tree = null;

    /** @var string[] Keys of the links the plugin added to the tree of this request, which are not Moodle's. */
    private static array $added = [];

    /** @var string[] Keys of the nodes the plugin hid in the tree of this request, which Moodle does not hide. */
    private static array $hiddenbyplugin = [];

    /** @var bool Whether the changes were applied to the tree of this request. */
    private static bool $applied = false;

    /**
     * Returns the menu key.
     *
     * @return string
     */
    public function get_key(): string {
        return self::KEY;
    }

    /**
     * Returns the localised name of the navigation.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('secondary_admin', 'local_extendednav');
    }

    /**
     * The items of a Moodle setting do not apply here.
     *
     * @param string $line Line without the leading dashes.
     * @return array
     */
    public function parse_config_line(string $line): array {
        return ['text' => $line, 'url' => '', 'tooltip' => '', 'langs' => ''];
    }

    /**
     * Turns the name of a node of the tree into a node key; a few names of Moodle have a slash, which a key cannot have.
     *
     * @param string $name Name of the node in the tree.
     * @return string
     */
    public static function key_of(string $name): string {
        return str_replace('/', '-_-', $name);
    }

    /**
     * Turns a node key back into the name of the node in the tree.
     *
     * @param string $key Node key.
     * @return string
     */
    public static function name_of(string $key): string {
        return str_replace('-_-', '/', $key);
    }

    /**
     * Returns every node of the tree, in the order Moodle gives them, with its parent and its depth.
     *
     * The tree is the one of the request when Moodle has built it, which is the case on every administration page.
     *
     * @return \stdClass[] Keyed by node key, with key, text, action, parentkey, depth and category.
     */
    public function get_native_nodes(): array {
        global $CFG;

        if (self::$tree !== null) {
            return self::$tree;
        }
        require_once($CFG->libdir . '/adminlib.php');
        $root = admin_get_root(false, false);
        $nodes = [];
        $walk = static function ($category, string $parent, int $depth) use (&$walk, &$nodes): void {
            foreach ($category->get_children() as $child) {
                $key = self::key_of((string)$child->name);
                if (in_array($key, self::$added, true)) {
                    continue;
                }
                if (clean_param($key, PARAM_ALPHANUMEXT) !== $key || \core_text::strlen($key) > 50) {
                    continue;
                }
                $iscategory = $child instanceof \admin_category;
                $nodes[$key] = (object)[
                    'key' => $key,
                    'text' => strip_tags((string)$child->visiblename),
                    'action' => self::url_of($child),
                    'parentkey' => $parent,
                    'depth' => $depth,
                    'category' => $iscategory,
                    // Moodle hides some pages itself, such as the ones it opens after an upgrade.
                    'moodlehidden' => !empty($child->hidden) && !in_array($key, self::$hiddenbyplugin, true),
                    'icon' => null,
                ];
                if ($iscategory) {
                    $walk($child, $key, $depth + 1);
                }
            }
        };
        $walk($root, '', 0);
        return self::$tree = $nodes;
    }

    /**
     * Returns the site path of a node of the tree.
     *
     * @param object $part A category or a page of the tree.
     * @return string
     */
    private static function url_of($part): string {
        if ($part instanceof \admin_category) {
            return '/admin/category.php?category=' . $part->name;
        }
        if ($part instanceof \admin_externalpage) {
            $url = $part->url instanceof \moodle_url ? $part->url->out(false) : (string)$part->url;
            return \local_extendednav\local\support\url_helper::to_site_path($url) ?? $url;
        }
        return '/admin/settings.php?section=' . $part->name;
    }

    /**
     * Forgets the tree of this request (for tests).
     */
    public static function reset(): void {
        self::$tree = null;
        self::$applied = false;
        self::$added = [];
        self::$hiddenbyplugin = [];
    }

    /**
     * Gives every node of the tree its row, in one write, and keeps the parent of each row as the tree has it.
     *
     * @param bool $events Not used: the rows of Moodle's nodes are created without events.
     */
    public function sync_native_nodes(bool $events = true): void {
        global $DB;

        $natives = $this->get_native_nodes();
        $existing = $DB->get_records('local_extendednav', ['menu' => self::KEY, 'contextid' => 0], '', 'id, nodekey, parentkey');
        $bykey = [];
        foreach ($existing as $row) {
            $bykey[$row->nodekey] = $row;
        }
        $now = time();
        $new = [];
        $sortorder = (int)$DB->get_field_sql('SELECT MAX(sortorder) FROM {local_extendednav} WHERE menu = ?', [self::KEY]) + 1;
        foreach ($natives as $key => $native) {
            if (isset($bykey[$key])) {
                // A plugin can move its page to another category; the row follows it.
                if ((string)$bykey[$key]->parentkey !== $native->parentkey && $this->is_native_parent($bykey[$key])) {
                    $DB->set_field('local_extendednav', 'parentkey', $native->parentkey ?: null, ['id' => $bykey[$key]->id]);
                }
                continue;
            }
            $new[] = (object)[
                'menu' => self::KEY, 'contextid' => 0, 'nodekey' => $key, 'parentkey' => $native->parentkey ?: null,
                'visibility' => access::VISIBILITY_ALL, 'blockaccess' => 0, 'display' => 0, 'placement' => 0,
                'hideadmins' => 1, 'sortorder' => $sortorder++, 'timecreated' => $now, 'timemodified' => $now,
            ];
        }
        if (!empty($new)) {
            $DB->insert_records('local_extendednav', $new);
        }
        // A row that is not a node of the tree and has no title is left from a page that is not in the tree any more, such as
        // one of a plugin that was removed. The links and the categories of the plugin always have a title, so they stay.
        $orphans = array_filter($existing, static fn(\stdClass $row): bool => !isset($natives[$row->nodekey]));
        if (!empty($orphans)) {
            [$in, $params] = $DB->get_in_or_equal(array_keys($orphans), SQL_PARAMS_NAMED);
            $DB->delete_records_select('local_extendednav', "id $in AND (title IS NULL OR title = '')", $params);
        }
        if (!empty($new) || !empty($existing)) {
            repository::purge();
        }
    }

    /**
     * Whether a row is the one of a node of Moodle, whose parent is the one of the tree.
     *
     * @param \stdClass $row Row.
     * @return bool
     */
    private function is_native_parent(\stdClass $row): bool {
        return $this->is_native((string)$row->nodekey);
    }

    /**
     * Makes sure every node of the tree has its row.
     */
    public function ensure_synced(): void {
        $this->sync_native_nodes(false);
    }

    /**
     * Whether a node can be deleted: only the links of the plugin.
     *
     * @param string $nodekey Node key.
     * @return bool
     */
    public function can_delete(string $nodekey): bool {
        return !$this->is_native($nodekey);
    }

    /**
     * Applies the rows to the tree of the request: names, what is hidden, the order of each category, and the categories and
     * links added. A node of the plugin with a link is a page of the category it goes in; one without a link is a category of
     * its own, which Moodle shows as a folder that lists what is in it, or as a new tab at the top of the administration.
     *
     * It is called at the end of the settings of this plugin, while Moodle builds the tree, and again before the page is sent,
     * so the pages that other plugins add later are reached too. Applying it twice gives the same tree.
     *
     * @param object $root The root of the administration tree.
     */
    public static function apply($root): void {
        if (!\local_extendednav\menu_manager::is_enabled(self::KEY) || during_initial_install()) {
            return;
        }
        $rows = repository::get_menu_nodes_by_key(self::KEY);
        if (empty($rows)) {
            return;
        }
        // Every category and page of the tree by name, found in one walk instead of a search for each row.
        $parts = [];
        $walk = static function ($category) use (&$walk, &$parts): void {
            foreach ($category->get_children() as $child) {
                $parts[(string)$child->name] = $child;
                if ($child instanceof \admin_category) {
                    $walk($child);
                }
            }
        };
        $walk($root);

        self::add_parts($root, $rows, $parts);

        $parents = [];
        foreach ($rows as $key => $row) {
            $part = $parts[self::name_of($key)] ?? null;
            if (!$part) {
                continue;
            }
            $title = text::resolve($row->title ?? '');
            if ($title !== '') {
                $part->visiblename = format_string($title);
            }
            if (!in_array($key, self::PROTECTED, true) && !access::is_shown($row) && empty($part->hidden)) {
                $part->hidden = true;
                self::$hiddenbyplugin[] = $key;
            }
            $parents[(string)($row->parentkey ?? '')] = true;
        }
        $ordered = self::get_ordered_parents();
        foreach (array_keys($parents) as $parentkey) {
            if (!isset($ordered[$parentkey])) {
                continue;
            }
            $category = $parentkey === '' ? $root : ($parts[self::name_of($parentkey)] ?? null);
            if (!$category instanceof \admin_category) {
                continue;
            }
            $children = $category->get_children();
            $position = [];
            foreach ($children as $index => $child) {
                $row = $rows[self::key_of((string)$child->name)] ?? null;
                // The children the plugin does not know yet keep their place after the ones it orders.
                $position[$index] = [$row ? 0 : 1, $row ? (int)$row->sortorder : $index];
            }
            uksort($children, static fn($a, $b): int => $position[$a] <=> $position[$b]);
            $category->set_sorting(false);
            $category->children = array_values($children);
        }
    }

    /**
     * Adds the categories and the links of the plugin to the tree. A category can hold another one, so the rows are added in
     * rounds, each one adding what has its category in the tree already, until a round adds nothing.
     *
     * @param object $root The root of the administration tree.
     * @param \stdClass[] $rows The rows of the site administration, by node key.
     * @param array $parts Every category and page of the tree by name, to which the parts added are added.
     */
    private static function add_parts($root, array $rows, array &$parts): void {
        $pending = array_filter($rows, static fn(\stdClass $row, string $key): bool => !isset($parts[self::name_of($key)])
            && trim((string)($row->title ?? '')) !== '', ARRAY_FILTER_USE_BOTH);
        do {
            $added = 0;
            foreach ($pending as $key => $row) {
                $parentkey = (string)($row->parentkey ?? '');
                $parent = $parentkey === '' ? $root : ($parts[self::name_of($parentkey)] ?? null);
                if (!$parent instanceof \admin_category) {
                    continue;
                }
                $name = self::name_of($key);
                $title = format_string(text::resolve($row->title));
                if (trim((string)($row->url ?? '')) === '') {
                    $part = new \admin_category($name, $title);
                } else {
                    // A page at the top goes in General, where Moodle shows the pages of the top of the administration.
                    $part = new \admin_externalpage($name, $title, new \moodle_url((string)$row->url), 'moodle/site:configview');
                }
                $root->add($parent->name, $part);
                $parts[$name] = $part;
                self::$added[] = $key;
                unset($pending[$key]);
                $added++;
            }
        } while ($added > 0 && $pending);
    }

    /**
     * Applies the rows to the tree of the request when Moodle has built it, once, before the page is sent: the pages that
     * plugins load after this one are in the tree by then.
     */
    public static function apply_to_request(): void {
        global $ADMIN;

        if (!self::$applied && $ADMIN instanceof \admin_root && $ADMIN->loaded) {
            self::$applied = true;
            self::apply($ADMIN);
        }
    }

    /**
     * Returns the items of the top level that Moodle lists in the tab General of the site administration.
     *
     * Moodle makes a tab of a top-level category with 8 children or more, or with a child that has children of its own; every
     * other item of the top level, page or small category, goes in General.
     *
     * @param \stdClass[] $nodes Every node of the tree, keyed by node key.
     * @return array Keys of the items of General, as keys.
     */
    public static function general_keys(array $nodes): array {
        $children = [];
        foreach ($nodes as $key => $node) {
            $children[(string)($node->parentkey ?? '')][] = (string)$key;
        }
        $general = [];
        foreach ($children[''] ?? [] as $key) {
            $own = $children[$key] ?? [];
            $deep = array_filter($own, static fn(string $child): bool => !empty($children[$child]));
            if (count($own) < 8 && empty($deep)) {
                $general[$key] = true;
            }
        }
        return $general;
    }

    /**
     * Returns the categories whose children have been ordered by hand.
     *
     * @return array Parent keys as keys.
     */
    public static function get_ordered_parents(): array {
        $value = json_decode((string)get_config('local_extendednav', 'orderedparents_' . self::KEY), true);
        return is_array($value) ? array_fill_keys($value, true) : [];
    }

    /**
     * Remembers that the children of a category have been ordered by hand, so the order of Moodle is not used any more there.
     *
     * @param string $parentkey Key of the category, empty for the top level.
     */
    public static function mark_ordered(string $parentkey): void {
        $parents = array_keys(self::get_ordered_parents());
        if (!in_array($parentkey, $parents, true)) {
            $parents[] = $parentkey;
            set_config('orderedparents_' . self::KEY, json_encode($parents), 'local_extendednav');
        }
    }
}
