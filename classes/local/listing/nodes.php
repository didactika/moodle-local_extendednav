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

namespace local_extendednav\local\listing;

use local_extendednav\menu\admintree;
use local_extendednav\menu_manager;
use local_extendednav\local\node\access;
use local_extendednav\local\node\availability;
use local_extendednav\local\node\icon;
use local_extendednav\local\node\manager;
use local_extendednav\local\node\repository;
use local_extendednav\local\node\text;

/**
 * The nodes of one navigation, as the management page lists them: filtered, with the counts of each visibility, in pages
 * and with what each row needs to draw itself.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class nodes {
    /** @var int Most rows a page of the list holds. */
    public const MAX_PAGE_SIZE = 100;

    /** @var string[] Kinds of restriction the list filters by, with the string that names each one, as its badges say. */
    public const RESTRICTIONS = [
        'roles' => 'flag_roles',
        'langs' => 'flag_langs',
        'blocked' => 'badge_blocked',
        'hideadmins' => 'flag_hideadmins',
        'conditions' => 'flag_conditions',
        'scheduled' => 'flag_scheduled',
        'ended' => 'flag_ended',
        'placement' => 'flag_placement',
        'protected' => 'flag_protected',
    ];

    /** @var int Longest search, in characters. */
    public const MAX_SEARCH = 255;

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
            return manager::group_by_parent_keyed(repository::get_menu_nodes_by_key($menukey), $depth);
        }
        $context = \context::instance_by_id($contextid, MUST_EXIST);
        return repository::get_scoped_nodes($menukey, $context);
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
     * @param string $origin native (nodes of Moodle) and custom (nodes added), separated by commas, or empty for both.
     * @param string $restriction Kinds of restriction (see RESTRICTIONS), separated by commas: the nodes with any of them.
     * @return array With total, counts, rows, reorderable and path.
     */
    public static function get(
        string $menukey,
        int $contextid,
        string $search = '',
        string $visibility = '',
        string $parent = '',
        int $page = 0,
        int $perpage = 0,
        string $origin = '',
        string $restriction = ''
    ): array {
        $menu = menu_manager::get_menu($menukey);
        if ($menu === null) {
            throw new \invalid_parameter_exception('Unknown navigation.');
        }
        // Moodle's own nodes are found before they are listed, so the list shows what Moodle shows.
        $menu->ensure_synced();

        // The search is only compared, never shown; any text is accepted, up to a length that is enough for a key or a link.
        $search = \core_text::substr(trim($search), 0, self::MAX_SEARCH);
        $istree = $menu instanceof admintree;
        $all = self::get_scope_nodes($menukey, $contextid);
        // In the tree a search looks in every level; without one, the list is the level chosen. The top level holds the
        // group General and the tabs of Moodle; General holds the other items of the top level, as Moodle groups them.
        $level = $istree && trim($search) === '';
        $general = $istree ? admintree::general_keys($all) : [];
        $ingeneral = $level && $parent === admintree::GENERAL;
        $nodes = self::filter($menu, $all, $search, $istree && !$level || $ingeneral ? '' : $parent, $level);
        if ($level && ($parent === '' || $ingeneral)) {
            $nodes = array_filter($nodes, static fn(\stdClass $node): bool => isset($general[$node->nodekey]) === $ingeneral);
        }

        $counts = ['all' => count($nodes), 'visible' => 0, 'roles' => 0, 'hidden' => 0, 'native' => 0, 'custom' => 0];
        $counts += array_fill_keys(array_map(static fn(string $kind): string => 'r_' . $kind, array_keys(self::RESTRICTIONS)), 0);
        $kinds = [];
        foreach ($nodes as $node) {
            $counts[['hidden', 'visible', 'roles'][(int)$node->visibility] ?? 'hidden']++;
            $counts[$menu->is_native($node->nodekey) ? 'native' : 'custom']++;
            $kinds[$node->nodekey] = self::restrictions_of($node);
            foreach ($kinds[$node->nodekey] as $kind) {
                $counts['r_' . $kind]++;
            }
        }
        $restrictions = array_values(array_intersect(array_keys(self::RESTRICTIONS), explode(',', $restriction)));
        if ($restrictions) {
            $nodes = array_filter(
                $nodes,
                static fn(\stdClass $node): bool => (bool)array_intersect($kinds[$node->nodekey] ?? [], $restrictions)
            );
        }
        $origins = array_intersect(['native', 'custom'], explode(',', $origin));
        if (count($origins) === 1) {
            $native = in_array('native', $origins, true);
            $nodes = array_filter($nodes, static fn(\stdClass $node): bool => $menu->is_native($node->nodekey) === $native);
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
        if ($level && $parent === '' && !empty($general) && empty($wanted) && count($origins) !== 1 && !$restrictions) {
            $rows[] = self::general_row();
        }
        foreach ($slice as $node) {
            $row = self::export_row($menu, $node, $contextid, $siblings);
            $row['haschildren'] = $istree && isset($parents[$node->nodekey]);
            $row['depth'] = $istree ? self::depth_of($all, $node) : ($row['child'] ? 1 : 0);
            $rows[] = $row;
        }
        // Moving a node only makes sense in a whole list, not in a part of it; in the tree, the whole level shown.
        $reorderable = trim($search) === '' && $visibility === '' && count($origins) !== 1 && !$restrictions
            && ($istree || $parent === '');
        $path = [];
        if ($ingeneral) {
            $path = [['key' => admintree::GENERAL, 'title' => get_string('general')]];
        } else if ($istree) {
            $path = self::path_of($menu, $all, $parent);
        }
        return [
            'total' => $total,
            'counts' => $counts,
            'rows' => $rows,
            'reorderable' => $reorderable,
            'path' => $path,
        ];
    }

    /**
     * Returns the row of the group General of the site administration: it opens the items of the top level that are not tabs,
     * and is not a node, so it has no actions.
     *
     * @return array
     */
    private static function general_row(): array {
        $title = get_string('general');
        return [
            'details' => [], 'flags' => [], 'id' => 0, 'nodekey' => admintree::GENERAL, 'title' => $title, 'native' => true,
            'url' => '', 'icon' => '', 'visibility' => access::VISIBILITY_ALL, 'visibilitylabel' => '',
            'visibilitydetail' => '', 'tone' => 'secondary',
            'roles' => '', 'langs' => '', 'parentkey' => '', 'child' => false, 'blocked' => false, 'unavailable' => false,
            'unknownroles' => '', 'adminonly' => false, 'hideadmins' => false, 'placement' => 0, 'placementlabel' => '',
            'exception' => false, 'inherited' => false, 'candelete' => false, 'canhide' => false, 'canshow' => false,
            'first' => true, 'last' => true, 'haschildren' => true, 'depth' => 0, 'virtual' => true,
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
                text::get_title($node, $native),
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
            $title = text::get_title($node, $menu->get_native_node($parent)) ?: $parent;
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
        $roles = access::split_list($node->roles ?? '');
        $isexception = (int)($node->contextid ?? 0) !== 0;
        $inherited = $isexception && (int)$node->contextid !== $contextid;
        $url = trim((string)($node->url ?? '')) !== '' ? (string)$node->url : (string)($native->action ?? '');
        $placement = (int)($node->placement ?? 0);
        $langs = access::split_list($node->langs ?? '');

        $visibilitykeys = [
            access::VISIBILITY_ALL => ['vis_all', 'success'],
            access::VISIBILITY_ROLES => ['vis_roles', 'warning'],
            access::VISIBILITY_HIDDEN => ['vis_hidden', 'danger'],
        ];
        [$visibilitystring, $tone] = $visibilitykeys[$visibility] ?? $visibilitykeys[access::VISIBILITY_HIDDEN];
        // A node for everyone that has conditions is shown only to whoever meets them.
        if ($visibility === access::VISIBILITY_ALL && trim((string)($node->availability ?? '')) !== '') {
            [$visibilitystring, $tone] = ['vis_conditional', 'warning'];
        }

        $protected = access::is_protected($node);
        // What narrows the visibility: a short flag for each kind of restriction, shown beside the visibility, and a whole line
        // for each, shown when the pointer is over them, so a long list of roles never fills the row.
        $flags = [];
        $details = [];
        $restrict = static function (string $flag, string $detail) use (&$flags, &$details): void {
            $flags[] = ['label' => get_string($flag, 'local_extendednav'), 'detail' => $detail];
            $details[] = $detail;
        };
        if ($visibility === access::VISIBILITY_ROLES && !empty($roles)) {
            $restrict('flag_roles', get_string('visibility_roles_line', 'local_extendednav', implode(', ', $roles)));
        }
        if (!empty($langs)) {
            $restrict('flag_langs', get_string('visibility_langs_line', 'local_extendednav', implode(', ', $langs)));
        }
        if (!empty($node->blockaccess) && $visibility !== access::VISIBILITY_ALL) {
            $restrict('badge_blocked', get_string('badge_blocked_detail', 'local_extendednav'));
        }
        if (!empty($node->hideadmins) && $visibility !== access::VISIBILITY_ALL) {
            $restrict('flag_hideadmins', get_string('hidden_from_admins', 'local_extendednav'));
        } else if (access::is_admin_only($node)) {
            $restrict('admin_only', get_string('admin_only_help', 'local_extendednav'));
        }
        if ($menu instanceof \local_extendednav\menu\primary && $native !== null && !$menu->is_shown_by_moodle($node->nodekey)) {
            $restrict('flag_unavailable', get_string('badge_unavailable', 'local_extendednav'));
        }
        if ($placement !== 0) {
            $where = $placement === 1 ? 'placement_more' : 'placement_main';
            $restrict($where, get_string($where . '_detail', 'local_extendednav'));
        }
        if (!empty($native->moodlehidden)) {
            $restrict('hidden_by_moodle', get_string('hidden_by_moodle_detail', 'local_extendednav'));
        }
        // The conditions of Moodle's Restrict access, in its words; a node with dates says whether it is still to come or over.
        $conditions = availability::describe($node);
        // The badge of the visibility says who sees the node; a node for everyone with conditions says them there.
        $visibilitydetail = get_string($visibilitystring . '_detail', 'local_extendednav');
        if ($conditions !== '' && $visibility === access::VISIBILITY_ALL) {
            $visibilitydetail .= ' ' . $conditions;
            $details[] = $conditions;
        } else if ($conditions !== '') {
            $restrict('flag_conditions', $conditions);
        }
        $period = availability::period($node);
        if ($period && $period['state'] === 'scheduled') {
            $restrict('flag_scheduled', get_string('period_scheduled', 'local_extendednav', userdate($period['from'])));
        } else if ($period && $period['state'] === 'ended') {
            $restrict('flag_ended', get_string('period_ended', 'local_extendednav', userdate($period['until'])));
        }
        if ($protected) {
            $restrict('flag_protected', get_string('flag_protected_help', 'local_extendednav'));
        }

        return [
            'details' => $details,
            'id' => (int)$node->id,
            'nodekey' => (string)$node->nodekey,
            'title' => text::get_title($node, $native) ?: (string)$node->nodekey,
            'native' => $native !== null,
            'url' => $url,
            'icon' => icon::render((string)($node->icon ?? '')),
            'visibility' => $visibility,
            'visibilitylabel' => get_string($visibilitystring, 'local_extendednav'),
            'visibilitydetail' => $visibilitydetail,
            'tone' => $tone,
            'roles' => implode(', ', $roles),
            'langs' => implode(', ', $langs),
            'parentkey' => (string)($node->parentkey ?? ''),
            'child' => !empty($node->parentkey),
            'blocked' => !empty($node->blockaccess) && $visibility !== access::VISIBILITY_ALL,
            // A node of Moodle that Moodle does not add right now, for example because it is turned off.
            'unavailable' => $menu instanceof \local_extendednav\menu\primary && $native !== null
                && !$menu->is_shown_by_moodle($node->nodekey),
            'unknownroles' => $visibility === access::VISIBILITY_ROLES ? implode(', ', array_map(
                static fn(string $role): string => get_string('unknown_role', 'local_extendednav', $role),
                array_diff($roles, array_keys(self::role_shortnames()))
            )) : '',
            'adminonly' => access::is_admin_only($node),
            'hideadmins' => !empty($node->hideadmins),
            'placement' => $placement,
            'placementlabel' => $placement === 0 ? '' : get_string(
                $placement === 1 ? 'placement_more' : 'placement_main',
                'local_extendednav'
            ),
            'exception' => $isexception && !$inherited,
            'inherited' => $inherited,
            'candelete' => $contextid !== 0 ? $isexception && !$inherited : $menu->can_delete($node->nodekey),
            'canhide' => !$protected && $visibility !== access::VISIBILITY_HIDDEN,
            'canshow' => !$protected && $visibility !== access::VISIBILITY_ALL,
            'first' => $siblings[$node->nodekey]['first'] ?? false,
            'last' => $siblings[$node->nodekey]['last'] ?? false,
            'flags' => $flags,
            'virtual' => false,
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
     * Returns the kinds of restriction a node has, as the menu Restriction of the list filters them.
     *
     * @param \stdClass $node The node.
     * @return string[] Keys of RESTRICTIONS.
     */
    public static function restrictions_of(\stdClass $node): array {
        $visibility = (int)$node->visibility;
        $kinds = [];
        if ($visibility === access::VISIBILITY_ROLES && !empty(access::split_list($node->roles ?? ''))) {
            $kinds[] = 'roles';
        }
        if (!empty(access::split_list($node->langs ?? ''))) {
            $kinds[] = 'langs';
        }
        if (!empty($node->blockaccess) && $visibility !== access::VISIBILITY_ALL) {
            $kinds[] = 'blocked';
        }
        if (!empty($node->hideadmins) && $visibility !== access::VISIBILITY_ALL) {
            $kinds[] = 'hideadmins';
        }
        if (trim((string)($node->availability ?? '')) !== '') {
            $kinds[] = 'conditions';
            $period = availability::period($node);
            if ($period && in_array($period['state'], ['scheduled', 'ended'], true)) {
                $kinds[] = $period['state'];
            }
        }
        if ((int)($node->placement ?? 0) !== 0) {
            $kinds[] = 'placement';
        }
        if (access::is_protected($node)) {
            $kinds[] = 'protected';
        }
        return $kinds;
    }

    /**
     * Returns the options of the menu Restriction: each kind of restriction with its name.
     *
     * @return array Each with key and label.
     */
    public static function restriction_options(): array {
        $options = [];
        foreach (self::RESTRICTIONS as $key => $string) {
            $options[] = ['key' => $key, 'label' => get_string($string, 'local_extendednav')];
        }
        return $options;
    }

    /**
     * Whether a navigation can have exceptions for a category, a course or an activity: the ones of the courses, of the
     * categories and of each kind of activity.
     *
     * @param string $menukey Navigation key.
     * @return bool
     */
    public static function supports_scopes(string $menukey): bool {
        return in_array($menukey, ['course', 'category'], true) || \local_extendednav\menu\activity::from_key($menukey) !== null;
    }
}
