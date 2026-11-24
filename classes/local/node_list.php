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

namespace local_extendednav\local;

use local_extendednav\menu\admintree;
use local_extendednav\menu_manager;

/**
 * The nodes of one navigation, as the management page lists them: filtered, with the counts of each visibility, in pages
 * and with what each row needs to draw itself.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class node_list {
    /** @var int Most rows a page of the list holds. */
    public const MAX_PAGE_SIZE = 100;

    /**
     * Returns the nodes of a navigation that apply to a scope, in the order the navigation has them.
     *
     * For the whole site these are the nodes of the site. For a category or a course they are the nodes of that context
     * and the ones it inherits, the nearest one of each key, which is what its pages show.
     *
     * @param string $menukey Navigation key.
     * @param int $contextid Context of the scope, 0 for the whole site.
     * @return \stdClass[] Records keyed by node key.
     */
    public static function get_scope_nodes(string $menukey, int $contextid): array {
        if ($contextid === 0) {
            // The administration is a tree of any depth; the menus have two levels of submenus.
            $depth = $menukey === admintree::KEY ? 20 : 2;
            return node_manager::group_by_parent_keyed(node_repository::get_menu_nodes_by_key($menukey), $depth);
        }
        $context = \context::instance_by_id($contextid, MUST_EXIST);
        return node_repository::get_scoped_nodes($menukey, $context);
    }

    /**
     * Returns one page of the list.
     *
     * @param string $menukey Navigation key.
     * @param int $contextid Context of the scope, 0 for the whole site.
     * @param string $search Text to find in the key, the title and the URL.
     * @param string $visibility 0, 1 and 2, separated by commas, to list those visibilities, or empty for all of them.
     * @param string $parent Key of a parent node, or empty for every node. In the site administration, which is a tree, the
     *     list is always one level of it: the children of this category, or the top level when it is empty.
     * @param int $page Page, from 0.
     * @param int $perpage Rows in a page, 0 for all of them.
     * @return array With total, counts, rows, reorderable and path.
     */
    public static function get(
        string $menukey,
        int $contextid,
        string $search = '',
        string $visibility = '',
        string $parent = '',
        int $page = 0,
        int $perpage = 12
    ): array {
        $menu = menu_manager::get_menu($menukey);
        if ($menu === null) {
            throw new \invalid_parameter_exception('Unknown navigation.');
        }
        // Moodle's own nodes are found before they are listed, so the list shows what Moodle shows.
        $menu->ensure_synced();

        $istree = $menu instanceof admintree;
        $all = self::get_scope_nodes($menukey, $contextid);
        // In the tree a search looks in every level; without one, the list is the level chosen.
        $level = $istree && trim($search) === '';
        $nodes = self::filter($menu, $all, $search, $istree && !$level ? '' : $parent, $level);

        $counts = ['all' => count($nodes), 'visible' => 0, 'roles' => 0, 'hidden' => 0];
        foreach ($nodes as $node) {
            $counts[['hidden', 'visible', 'roles'][(int)$node->visibility] ?? 'hidden']++;
        }
        $wanted = array_values(array_filter(explode(',', $visibility), static fn(string $value): bool => $value !== ''));
        if (!empty($wanted)) {
            $nodes = array_filter(
                $nodes,
                static fn(\stdClass $node): bool => in_array((string)(int)$node->visibility, $wanted, true)
            );
        }
        $nodes = array_values($nodes);
        $total = count($nodes);
        $perpage = $perpage === 0 ? max($total, 1) : min($perpage, self::MAX_PAGE_SIZE);
        $slice = array_slice($nodes, $page * $perpage, $perpage);

        $siblings = self::siblings($all);
        $parents = [];
        foreach ($all as $node) {
            if ((string)($node->parentkey ?? '') !== '') {
                $parents[(string)$node->parentkey] = true;
            }
        }
        $rows = [];
        foreach ($slice as $node) {
            $row = self::export_row($menu, $node, $contextid, $siblings);
            $row['haschildren'] = $istree && isset($parents[$node->nodekey]);
            $row['depth'] = $istree ? self::depth_of($all, $node) : ($row['child'] ? 1 : 0);
            $rows[] = $row;
        }
        // Moving a node only makes sense in a whole list, not in a part of it; in the tree, the whole level shown.
        $reorderable = trim($search) === '' && $visibility === '' && ($istree || $parent === '');
        return [
            'total' => $total,
            'counts' => $counts,
            'rows' => $rows,
            'reorderable' => $reorderable,
            'path' => $istree ? self::path_of($menu, $all, $parent) : [],
        ];
    }

    /**
     * Keeps the nodes that match a search and a parent.
     *
     * @param \local_extendednav\menu\base $menu The navigation.
     * @param \stdClass[] $nodes Nodes keyed by node key.
     * @param string $search Text to find in the key, the title and the URL.
     * @param string $parent Key of a parent node, or empty.
     * @param bool $exact Whether an empty parent means the top level, as in a tree, instead of every node.
     * @return \stdClass[]
     */
    private static function filter($menu, array $nodes, string $search, string $parent, bool $exact = false): array {
        $search = trim($search);
        return array_filter($nodes, static function (\stdClass $node) use ($menu, $search, $parent, $exact): bool {
            if (($parent !== '' || $exact) && (string)($node->parentkey ?? '') !== $parent) {
                return false;
            }
            if ($search === '') {
                return true;
            }
            $native = $menu->get_native_node($node->nodekey);
            $haystack = implode(' ', [
                $node->nodekey,
                node_text::get_title($node, $native),
                (string)($node->url ?? '') !== '' ? $node->url : ($native->action ?? ''),
            ]);
            return \core_text::strpos(\core_text::strtolower($haystack), \core_text::strtolower($search)) !== false;
        });
    }

    /**
     * Returns how deep a node is in the tree, 0 being the top level.
     *
     * @param \stdClass[] $all Every node, keyed by node key.
     * @param \stdClass $node The node.
     * @return int
     */
    private static function depth_of(array $all, \stdClass $node): int {
        $depth = 0;
        $parent = (string)($node->parentkey ?? '');
        while ($parent !== '' && isset($all[$parent]) && $depth < 20) {
            $depth++;
            $parent = (string)($all[$parent]->parentkey ?? '');
        }
        return $depth;
    }

    /**
     * Returns the categories from the top of the tree to a category, for the breadcrumb of the list.
     *
     * @param \local_extendednav\menu\base $menu The navigation.
     * @param \stdClass[] $all Every node, keyed by node key.
     * @param string $parent Key of the category, or empty for the top level.
     * @return array[] Each with key and title, from the top down.
     */
    private static function path_of($menu, array $all, string $parent): array {
        $path = [];
        while ($parent !== '' && isset($all[$parent]) && count($path) < 20) {
            $node = $all[$parent];
            $title = node_text::get_title($node, $menu->get_native_node($parent)) ?: $parent;
            array_unshift($path, ['key' => $parent, 'title' => $title]);
            $parent = (string)($node->parentkey ?? '');
        }
        return $path;
    }

    /**
     * Returns, for each node, whether it is the first and the last among its siblings, which are the nodes with its parent.
     *
     * @param \stdClass[] $nodes Every node of the scope, in order.
     * @return array Keyed by node key, each with first and last.
     */
    private static function siblings(array $nodes): array {
        $groups = [];
        foreach ($nodes as $node) {
            $groups[(string)($node->parentkey ?? '')][] = $node->nodekey;
        }
        $result = [];
        foreach ($groups as $keys) {
            foreach ($keys as $index => $key) {
                $result[$key] = ['first' => $index === 0, 'last' => $index === count($keys) - 1];
            }
        }
        return $result;
    }

    /**
     * Describes one node for the list.
     *
     * @param \local_extendednav\menu\base $menu The navigation.
     * @param \stdClass $node Node record.
     * @param int $contextid Context of the scope, 0 for the whole site.
     * @param array $siblings Position among the siblings of each node.
     * @return array
     */
    private static function export_row($menu, \stdClass $node, int $contextid, array $siblings): array {
        $native = $menu->get_native_node($node->nodekey);
        $visibility = (int)$node->visibility;
        $roles = node_access::split_list($node->roles ?? '');
        $isexception = (int)($node->contextid ?? 0) !== 0;
        $inherited = $isexception && (int)$node->contextid !== $contextid;
        $url = trim((string)($node->url ?? '')) !== '' ? (string)$node->url : (string)($native->action ?? '');
        $placement = (int)($node->placement ?? 0);
        $langs = node_access::split_list($node->langs ?? '');

        $visibilitykeys = [
            node_access::VISIBILITY_ALL => ['vis_all', 'success'],
            node_access::VISIBILITY_ROLES => ['vis_roles', 'warning'],
            node_access::VISIBILITY_HIDDEN => ['vis_hidden', 'danger'],
        ];
        [$visibilitystring, $tone] = $visibilitykeys[$visibility] ?? $visibilitykeys[node_access::VISIBILITY_HIDDEN];

        // What narrows the visibility, one short line each, in the order that matters.
        $details = [];
        if ($visibility === node_access::VISIBILITY_ROLES && !empty($roles)) {
            $details[] = get_string('visibility_roles_line', 'local_extendednav', implode(', ', $roles));
        }
        if (!empty($langs)) {
            $details[] = get_string('visibility_langs_line', 'local_extendednav', implode(', ', $langs));
        }
        if (!empty($node->blockaccess) && $visibility !== node_access::VISIBILITY_ALL) {
            $details[] = get_string('badge_blocked', 'local_extendednav');
        }
        if (!empty($node->hideadmins)) {
            $details[] = get_string('hidden_from_admins', 'local_extendednav');
        } else if (node_access::is_admin_only($node)) {
            $details[] = get_string('admin_only', 'local_extendednav');
        }
        if ($menu instanceof \local_extendednav\menu\primary && $native !== null && !$menu->is_shown_by_moodle($node->nodekey)) {
            $details[] = get_string('badge_unavailable', 'local_extendednav');
        }
        if ($placement !== 0) {
            $details[] = get_string($placement === 1 ? 'placement_more' : 'placement_main', 'local_extendednav');
        }
        if (!empty($native->moodlehidden)) {
            $details[] = get_string('hidden_by_moodle', 'local_extendednav');
        }

        return [
            'details' => $details,
            'id' => (int)$node->id,
            'nodekey' => (string)$node->nodekey,
            'title' => node_text::get_title($node, $native) ?: (string)$node->nodekey,
            'native' => $native !== null,
            'url' => $url,
            'icon' => node_icon::render((string)($node->icon ?? '')),
            'visibility' => $visibility,
            'visibilitylabel' => get_string($visibilitystring, 'local_extendednav'),
            'tone' => $tone,
            'roles' => implode(', ', $roles),
            'langs' => implode(', ', $langs),
            'parentkey' => (string)($node->parentkey ?? ''),
            'child' => !empty($node->parentkey),
            'blocked' => !empty($node->blockaccess) && $visibility !== node_access::VISIBILITY_ALL,
            // A node of Moodle that Moodle does not add right now, for example because it is turned off.
            'unavailable' => $menu instanceof \local_extendednav\menu\primary && $native !== null
                && !$menu->is_shown_by_moodle($node->nodekey),
            'unknownroles' => $visibility === node_access::VISIBILITY_ROLES ? implode(', ', array_map(
                static fn(string $role): string => get_string('unknown_role', 'local_extendednav', $role),
                array_diff($roles, array_keys(self::role_shortnames()))
            )) : '',
            'adminonly' => node_access::is_admin_only($node),
            'hideadmins' => !empty($node->hideadmins),
            'placement' => $placement,
            'placementlabel' => $placement === 0 ? '' : get_string(
                $placement === 1 ? 'placement_more' : 'placement_main',
                'local_extendednav'
            ),
            'exception' => $isexception && !$inherited,
            'inherited' => $inherited,
            'candelete' => $contextid !== 0 ? $isexception && !$inherited : $menu->can_delete($node->nodekey),
            'first' => $siblings[$node->nodekey]['first'] ?? false,
            'last' => $siblings[$node->nodekey]['last'] ?? false,
        ];
    }

    /**
     * Returns the shortnames of the roles of the site.
     *
     * @return array The shortnames as keys.
     */
    private static function role_shortnames(): array {
        static $names = null;
        if ($names === null) {
            $names = array_fill_keys(array_map(static fn(\stdClass $role): string => $role->shortname, get_all_roles()), true);
        }
        return $names;
    }

    /**
     * Whether a navigation can have exceptions for a category or a course.
     *
     * @param string $menukey Navigation key.
     * @return bool
     */
    public static function supports_scopes(string $menukey): bool {
        return in_array($menukey, ['course', 'activity', 'category'], true);
    }
}
