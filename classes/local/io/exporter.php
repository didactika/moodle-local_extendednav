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

namespace local_extendednav\local\io;

use local_extendednav\menu_manager;
use local_extendednav\local\node\access;
use local_extendednav\local\node\manager;

/**
 * Writes a YAML file of one navigation or of several, in the one format the importer reads: every node names its navigation
 * and says whether it is one of Moodle, and the file says which navigations are on.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class exporter {
    /** @var string[] Keys written for each node, in order. */
    public const EXPORT_FIELDS = [
        'nodekey', 'menu', 'native', 'scope', 'order', 'title', 'tooltip', 'url', 'icon', 'visibility', 'roles',
        'blockaccess', 'langs', 'parentkey', 'display', 'popupwidth', 'popupheight', 'placement', 'hideadmins',
    ];

    /** @var string[] Fields written as whole numbers. */
    private const INT_FIELDS = ['native', 'order', 'visibility', 'blockaccess', 'display', 'placement', 'hideadmins'];

    /** @var string[] Fields written as whole numbers, or empty when not set. */
    private const OPTIONAL_INT_FIELDS = ['popupwidth', 'popupheight'];

    /**
     * Builds the file of one navigation or of several, in the one format the import reads.
     *
     * Every node names its navigation and, when it is one, says it is a node of Moodle; the file also says which navigations
     * are on. In the site administration only the nodes that were changed are written, and the ones of a category that was
     * ordered by hand, so the file does not repeat hundreds of pages as Moodle draws them.
     *
     * @param string[] $menukeys Navigation keys.
     * @return string|null The YAML text, or null when there is nothing to export.
     */
    public static function export_file(array $menukeys): ?string {
        global $DB;

        $nodes = [];
        $navigations = [];
        foreach ($menukeys as $menukey) {
            $menu = menu_manager::get_menu($menukey);
            if ($menu === null) {
                continue;
            }
            $menu->ensure_synced();
            $rows = $DB->get_records('local_extendednav', ['menu' => $menukey], 'contextid ASC, sortorder ASC, id ASC');
            if ($menukey === \local_extendednav\menu\admintree::KEY) {
                $ordered = \local_extendednav\menu\admintree::get_ordered_parents();
                $rows = array_filter($rows, static fn(\stdClass $row): bool => !self::is_untouched($row, $menu)
                    || isset($ordered[(string)($row->parentkey ?? '')]));
            }
            $site = array_filter($rows, static fn(\stdClass $row): bool => (int)$row->contextid === 0);
            $depth = $menukey === \local_extendednav\menu\admintree::KEY ? 20 : 2;
            array_push($nodes, ...self::export_nodes($rows, $site, $depth));
            $navigations[$menukey] = ['enabled' => menu_manager::is_enabled($menukey) ? 1 : 0];
        }
        if (empty($nodes)) {
            return null;
        }
        return \local_extendednav\yaml::dump(['format' => 2, 'navigations' => $navigations, 'nodes' => $nodes]);
    }

    /**
     * Whether a row is a node of Moodle that nothing has changed.
     *
     * @param \stdClass $row Row.
     * @param \local_extendednav\menu\base $menu Its navigation.
     * @return bool
     */
    private static function is_untouched(\stdClass $row, $menu): bool {
        foreach (['title', 'tooltip', 'url', 'icon', 'roles', 'langs'] as $field) {
            if (trim((string)($row->$field ?? '')) !== '') {
                return false;
            }
        }
        return $menu->is_native($row->nodekey) && (int)$row->visibility === access::VISIBILITY_ALL
            && empty($row->blockaccess) && empty($row->display) && empty($row->placement)
            && (int)$row->hideadmins === ($row->menu === \local_extendednav\menu\admintree::KEY ? 1 : 0)
            && (int)$row->contextid === 0;
    }

    /**
     * Returns the exported form of nodes of one menu, with their position in the menu.
     *
     * @param \stdClass[] $nodes Nodes to export.
     * @param \stdClass[] $menunodes Every node of their menu, to number the positions as the management page lists them.
     * @param int $depth Depth of the menu: 2 for a menu, more for the tree of the site administration, whose nodes are numbered
     *     among the others of their category, since a tree has a different number of pages on each site.
     * @return array[]
     */
    public static function export_nodes(array $nodes, array $menunodes, int $depth = 2): array {
        $positions = [];
        $siblings = [];
        foreach (manager::group_by_parent($menunodes, $depth) as $index => $node) {
            if ($depth > 2) {
                $parent = (string)($node->parentkey ?? '');
                $siblings[$parent] = ($siblings[$parent] ?? 0) + 1;
                $positions[(int)$node->id] = $siblings[$parent];
            } else {
                $positions[(int)$node->id] = $index + 1;
            }
        }
        // The exceptions of a category or a course are numbered among themselves.
        $ranks = [];
        $scoped = array_filter($nodes, static fn(\stdClass $node): bool => (int)($node->contextid ?? 0) !== 0);
        usort(
            $scoped,
            static fn(\stdClass $a, \stdClass $b): int => [(int)$a->sortorder, (int)$a->id] <=> [(int)$b->sortorder, (int)$b->id]
        );
        $counters = [];
        foreach ($scoped as $node) {
            $group = $node->menu . '@' . $node->contextid;
            $counters[$group] = ($counters[$group] ?? 0) + 1;
            $ranks[(int)$node->id] = $counters[$group];
        }
        $exported = [];
        foreach ($nodes as $node) {
            $exported[] = self::export_node($node, $positions[(int)$node->id] ?? $ranks[(int)$node->id] ?? null);
        }
        usort(
            $exported,
            static fn(array $a, array $b): int => [$a['menu'], $a['scope'], $a['parentkey'], $a['order']]
                <=> [$b['menu'], $b['scope'], $b['parentkey'], $b['order']]
        );
        return $exported;
    }

    /**
     * Returns the exported form of a node.
     *
     * @param \stdClass $node Node record.
     * @param int|null $order Position of the node in its menu, 1 being the first.
     * @return array
     */
    public static function export_node(\stdClass $node, ?int $order = null): array {
        $node = clone $node;
        $node->order = $order ?? 0;
        $node->scope = scope::describe((int)($node->contextid ?? 0));
        $menu = menu_manager::get_menu((string)$node->menu);
        $node->native = $menu !== null && $menu->is_native((string)$node->nodekey) ? 1 : 0;
        $data = [];
        foreach (self::EXPORT_FIELDS as $field) {
            $value = $node->$field ?? null;
            if (in_array($field, self::INT_FIELDS, true)) {
                $data[$field] = (int)$value;
            } else if (in_array($field, self::OPTIONAL_INT_FIELDS, true)) {
                $data[$field] = $value === null ? '' : (int)$value;
            } else {
                $data[$field] = $value === null ? '' : (string)$value;
            }
        }
        return $data;
    }
}
