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
     * Returns every node of every menu, ordered by menu, sortorder and id.
     *
     * @return \stdClass[] Records keyed by id.
     */
    public static function get_all(): array {
        global $DB;

        $cache = \cache::make('local_extendednav', 'nodes');
        $nodes = $cache->get(self::CACHE_KEY);
        if ($nodes === false) {
            $nodes = $DB->get_records('local_extendednav', null, 'menu ASC, sortorder ASC, id ASC');
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
     * Clears the node cache. Call it after any change to the table.
     */
    public static function purge(): void {
        \cache::make('local_extendednav', 'nodes')->purge();
    }
}
