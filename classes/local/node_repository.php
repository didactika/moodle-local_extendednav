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

/**
 * Cached read access to the navigation nodes.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class node_repository {
    /** @var string Cache key holding every node. */
    private const CACHE_KEY = 'allnodes';

    /**
     * Returns every node of every menu that applies to the whole site, ordered by menu, sortorder and id.
     *
     * The nodes that are exceptions for a category or a course are not here: there can be many, and a page
     * needs only the ones of its own contexts, see {@see get_scoped_nodes()}.
     *
     * @return \stdClass[] Records keyed by id.
     */
    public static function get_all(): array {
        global $DB;

        $cache = \cache::make('local_extendednav', 'nodes');
        $nodes = $cache->get(self::CACHE_KEY);
        if ($nodes === false) {
            $nodes = $DB->get_records('local_extendednav', ['contextid' => 0], 'menu ASC, sortorder ASC, id ASC');
            $cache->set(self::CACHE_KEY, $nodes);
        }
        return $nodes;
    }

    /**
     * Returns the nodes of one menu, ordered by sortorder and id.
     *
     * @param string $menu Menu key (primary or usermenu).
     * @return \stdClass[] Records keyed by id.
     */
    public static function get_menu_nodes(string $menu): array {
        return array_filter(self::get_all(), static fn(\stdClass $node): bool => $node->menu === $menu);
    }

    /**
     * Returns the nodes of one menu keyed by node key.
     *
     * @param string $menu Menu key (primary or usermenu).
     * @return \stdClass[] Records keyed by nodekey.
     */
    public static function get_menu_nodes_by_key(string $menu): array {
        $bykey = [];
        foreach (self::get_menu_nodes($menu) as $node) {
            $bykey[$node->nodekey] = $node;
        }
        return $bykey;
    }

    /**
     * Returns the nodes of one menu that apply to a context, one for each node key.
     *
     * A node can be set for the whole site and, as an exception, for a category or a course, and everything inside
     * it. The node of the context nearest to the page wins, so a course can differ from its category and the category
     * from the site. Nothing but the cache is read when the menu has no exception, which is the usual case.
     *
     * @param string $menu Menu key.
     * @param \context $context Context of the page.
     * @return \stdClass[] Records keyed by node key, ordered as the nearest context orders them.
     */
    public static function get_scoped_nodes(string $menu, \context $context): array {
        $nodes = self::get_menu_nodes_by_key($menu);
        $withexceptions = self::get_exception_contexts($menu);
        if (empty($withexceptions)) {
            return $nodes;
        }
        // From the outermost context to the page, so the nearest one is applied last.
        $path = array_reverse($context->get_parent_context_ids(true));
        foreach ($path as $contextid) {
            if (!isset($withexceptions[$contextid])) {
                continue;
            }
            $exceptions = self::get_exceptions($menu, (int)$contextid);
            foreach ($exceptions as $node) {
                $nodes[$node->nodekey] = $node;
            }
            // The context that sets any node sets the order of the navigation.
            $order = array_map(static fn(\stdClass $node): string => $node->nodekey, $exceptions);
            $nodes = array_replace(array_flip($order), $nodes);
            $nodes = array_filter($nodes, static fn($node): bool => $node instanceof \stdClass);
        }
        return $nodes;
    }

    /**
     * Returns the contexts that have exceptions in a menu.
     *
     * @param string $menu Menu key.
     * @return int[] Context ids as keys.
     */
    public static function get_exception_contexts(string $menu): array {
        global $DB;

        $cache = \cache::make('local_extendednav', 'nodes');
        $key = 'contexts_' . $menu;
        $contexts = $cache->get($key);
        if ($contexts === false) {
            $ids = $DB->get_fieldset_select('local_extendednav', 'DISTINCT contextid', 'menu = :menu AND contextid <> 0', ['menu' => $menu]);
            $contexts = array_fill_keys(array_map('intval', $ids), 1);
            $cache->set($key, $contexts);
        }
        return $contexts;
    }

    /**
     * Returns the exceptions one context sets in a menu, ordered by sortorder and id.
     *
     * @param string $menu Menu key.
     * @param int $contextid Context id.
     * @return \stdClass[] Records.
     */
    public static function get_exceptions(string $menu, int $contextid): array {
        global $DB;

        $cache = \cache::make('local_extendednav', 'nodes');
        $key = 'exceptions_' . $menu . '_' . $contextid;
        $nodes = $cache->get($key);
        if ($nodes === false) {
            $nodes = $DB->get_records('local_extendednav', ['menu' => $menu, 'contextid' => $contextid], 'sortorder ASC, id ASC');
            $cache->set($key, $nodes);
        }
        return $nodes;
    }

    /**
     * Clears the node cache. Call it after any change to the table.
     */
    public static function purge(): void {
        \cache::make('local_extendednav', 'nodes')->purge();
    }
}
