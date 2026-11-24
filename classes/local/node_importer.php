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

use local_extendednav\menu_manager;

/**
 * Imports and exports nodes in the YAML file format.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class node_importer {
    /** @var string[] Keys written for each node, in order. */
    public const EXPORT_FIELDS = [
        'nodekey', 'menu', 'native', 'scope', 'order', 'title', 'tooltip', 'url', 'icon', 'visibility', 'roles',
        'blockaccess', 'langs', 'parentkey', 'display', 'popupwidth', 'popupheight', 'placement', 'hideadmins',
    ];

    /** @var string[] Fields written as whole numbers. */
    private const INT_FIELDS = ['native', 'order', 'visibility', 'blockaccess', 'display', 'placement', 'hideadmins'];

    /** @var string[] Fields written as whole numbers, or empty when not set. */
    private const OPTIONAL_INT_FIELDS = ['popupwidth', 'popupheight'];

    /** @var string Menu used for nodes that do not name one. */
    private string $defaultmenu;

    /** @var bool Whether nodes that already exist are replaced (true) or skipped (false). */
    private bool $overwrite;

    /**
     * Constructor.
     *
     * @param string $defaultmenu Menu used for nodes that do not name one.
     * @param bool $overwrite Whether nodes that already exist are replaced (true) or skipped (false).
     */
    public function __construct(string $defaultmenu, bool $overwrite) {
        $this->defaultmenu = $defaultmenu;
        $this->overwrite = $overwrite;
    }

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
        return $menu->is_native($row->nodekey) && (int)$row->visibility === node_access::VISIBILITY_ALL
            && empty($row->blockaccess) && empty($row->display) && empty($row->placement) && empty($row->hideadmins)
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
        foreach (node_manager::group_by_parent($menunodes, $depth) as $index => $node) {
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
     * Describes the category or the course a node applies to, as the file names it.
     *
     * @param int $contextid Context id, 0 for the whole site.
     * @return string Empty for the whole site, "course:shortname" or "category:Parent / Name".
     */
    public static function describe_scope(int $contextid): string {
        if ($contextid === 0) {
            return '';
        }
        $context = \context::instance_by_id($contextid, IGNORE_MISSING);
        if (!$context) {
            return '';
        }
        if ($context->contextlevel === CONTEXT_COURSE) {
            return 'course:' . get_course($context->instanceid)->shortname;
        }
        $category = \core_course_category::get($context->instanceid, IGNORE_MISSING, true);
        return $category ? 'category:' . $category->get_nested_name(false, ' / ') : '';
    }

    /**
     * Finds the category or the course a file names.
     *
     * @param string $scope As {@see describe_scope()} writes it.
     * @return int|null The context id, or null when nothing matches.
     */
    public static function resolve_scope(string $scope): ?int {
        global $DB;

        [$type, $name] = array_pad(explode(':', trim($scope), 2), 2, '');
        $name = trim($name);
        if ($type === 'course') {
            $id = $DB->get_field('course', 'id', ['shortname' => $name]);
            return $id ? (int)\context_course::instance((int)$id)->id : null;
        }
        if ($type === 'category') {
            foreach (\core_course_category::get_all(['returnhidden' => true]) as $category) {
                if ($category->get_nested_name(false, ' / ') === $name) {
                    return (int)\context_coursecat::instance((int)$category->id)->id;
                }
            }
        }
        return null;
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
        $node->scope = self::describe_scope((int)($node->contextid ?? 0));
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

    /**
     * Validates every node and, when all are valid, saves them.
     *
     * @param array $items Nodes read from the file.
     * @return array With 'errors' (list of messages, nothing saved when not empty), 'warnings'
     *               (list of messages) and 'counts' (created, updated and skipped).
     */
    public function import(array $items): array {
        $errors = [];
        $warnings = [];
        $counts = ['created' => 0, 'updated' => 0, 'skipped' => 0];

        $existing = [];
        foreach (node_repository::get_all() as $node) {
            $existing[$node->menu][$node->nodekey] = $node;
        }

        // The nodes of Moodle in the file are known as such before they are checked. A secondary navigation learns them from
        // the file, as it would from a page; a node of Moodle this site does not have (a page of a plugin that is not
        // installed) is left out with a warning, and the rest of the file is imported.
        $seen = [];
        foreach ($items as $index => $item) {
            if (!is_array($item) || empty($item['native']) || trim((string)($item['nodekey'] ?? '')) === '') {
                continue;
            }
            $menukey = trim((string)($item['menu'] ?? '')) ?: $this->defaultmenu;
            $menu = menu_manager::get_menu($menukey);
            $nodekey = trim((string)$item['nodekey']);
            if ($menu instanceof \local_extendednav\menu\secondary) {
                $seen[$menukey][$nodekey] = (object)['text' => '', 'action' => ''];
            } else if ($menu !== null && !$menu->is_native($nodekey)) {
                $warnings[] = $menukey . '/' . $nodekey . ': ' . get_string('warn_import_absent', 'local_extendednav');
                $counts['skipped']++;
                unset($items[$index]);
            }
        }
        foreach ($seen as $menukey => $nodes) {
            menu_manager::get_menu($menukey)->record_seen($nodes);
        }

        // The nodes of a category or a course are checked with the rest and saved after the ones of the site.
        $scoped = [];
        foreach ($items as $index => $item) {
            if (is_array($item) && trim((string)($item['scope'] ?? '')) !== '') {
                $scoped[$index] = $item;
                unset($items[$index]);
            }
        }
        $scopedrecords = $this->prepare_scoped($scoped, $errors, $warnings, $counts);

        // Build the records and drop the ones that are skipped.
        $batch = [];
        $orders = [];
        foreach (array_values($items) as $index => $item) {
            $label = get_string('import_entry', 'local_extendednav', $index + 1);
            if (!is_array($item) || !isset($item['nodekey']) || trim((string)$item['nodekey']) === '') {
                $errors[] = $label . ': ' . get_string('err_import_nodekey', 'local_extendednav');
                continue;
            }

            $menukey = isset($item['menu']) && trim((string)$item['menu']) !== '' ? trim((string)$item['menu'])
                : $this->defaultmenu;
            if (menu_manager::get_menu($menukey) === null) {
                $errors[] = $label . ': ' . get_string('err_invalid_menu', 'local_extendednav');
                continue;
            }

            $values = array_intersect_key($item, array_flip(node_manager::EDITABLE_FIELDS));
            $record = node_manager::prepare($values, $menukey);
            $label = $menukey . '/' . $record->nodekey;
            $order = $item['order'] ?? '';
            if ($order !== '' && $order !== null && !(is_numeric($order) && (int)$order == $order && (int)$order >= 1)) {
                $errors[] = $label . ' (order): ' . get_string('err_import_order', 'local_extendednav');
                continue;
            }
            if (isset($batch[$menukey][$record->nodekey])) {
                $errors[] = $label . ': ' . get_string('err_import_duplicate', 'local_extendednav');
                continue;
            }

            $current = $existing[$menukey][$record->nodekey] ?? null;
            if ($current !== null && !$this->overwrite) {
                $counts['skipped']++;
                continue;
            }
            if ($current !== null) {
                $record->id = $current->id;
            }
            $batch[$menukey][$record->nodekey] = $record;
            if ($order !== '' && $order !== null) {
                $orders[$menukey][$record->nodekey] = [(int)$order, $index];
            }
        }

        // Validate every record against the menu as it would be after the import.
        $final = $existing;
        foreach ($batch as $menukey => $records) {
            foreach ($records as $nodekey => $record) {
                $final[$menukey][$nodekey] = $record;
            }
        }
        foreach ($batch as $menukey => $records) {
            foreach ($records as $nodekey => $record) {
                $others = $final[$menukey];
                unset($others[$nodekey]);
                $result = node_validator::validate($record, $others);
                foreach ($result['errors'] as $field => $message) {
                    $errors[] = $menukey . '/' . $nodekey . ' (' . $field . '): ' . $message;
                }
                foreach ($result['warnings'] as $message) {
                    $warnings[] = $menukey . '/' . $nodekey . ': ' . $message;
                }
            }
        }

        $allnodes = [];
        foreach ($final as $records) {
            array_push($allnodes, ...array_values($records));
        }
        if (empty($errors) && access_guard::check_fallbacks($allnodes) === access_guard::FALLBACKS_BLOCKED) {
            $errors[] = get_string('err_fallbacks_blocked', 'local_extendednav');
        }
        if (!empty($errors)) {
            return ['errors' => $errors, 'warnings' => $warnings, 'counts' => $counts];
        }

        foreach ($batch as $menukey => $records) {
            $ids = [];
            foreach ($records as $nodekey => $record) {
                if (isset($record->id)) {
                    node_manager::update($record);
                    $counts['updated']++;
                } else {
                    $record = node_manager::create($record);
                    $counts['created']++;
                }
                $ids[$nodekey] = (int)$record->id;
            }
            if (!empty($orders[$menukey]) && $menukey === \local_extendednav\menu\admintree::KEY) {
                self::apply_sibling_orders($menukey, $orders[$menukey]);
            } else if (!empty($orders[$menukey])) {
                self::apply_orders($menukey, $orders[$menukey], $ids);
            }
        }
        foreach ($scopedrecords as $record) {
            if (isset($record->id)) {
                node_manager::update($record);
                $counts['updated']++;
            } else {
                node_manager::create($record);
                $counts['created']++;
            }
        }
        if (access_guard::check_fallbacks(node_repository::get_all()) === access_guard::FALLBACKS_BLOCKED_FOR_SOME) {
            $warnings[] = get_string('warn_fallbacks_blocked_for_some', 'local_extendednav');
        }
        return ['errors' => [], 'warnings' => $warnings, 'counts' => $counts];
    }

    /**
     * Checks the nodes of a file that apply to a category or a course, and builds the records to save.
     *
     * @param array $items The nodes of the file that name a scope, keyed by their place in the file.
     * @param array $errors The errors found, to add to.
     * @param array $warnings The warnings found, to add to.
     * @param array $counts The nodes kept, to add to.
     * @return \stdClass[] The records to save.
     */
    private function prepare_scoped(array $items, array &$errors, array &$warnings, array &$counts): array {
        $records = [];
        foreach ($items as $index => $item) {
            $label = get_string('import_entry', 'local_extendednav', $index + 1);
            $menukey = isset($item['menu']) && trim((string)$item['menu']) !== ''
                ? trim((string)$item['menu']) : $this->defaultmenu;
            $contextid = self::resolve_scope((string)$item['scope']);
            if (menu_manager::get_menu($menukey) === null) {
                $errors[] = $label . ': ' . get_string('err_invalid_menu', 'local_extendednav');
                continue;
            }
            if ($contextid === null || !node_list::supports_scopes($menukey)) {
                $errors[] = $label . ': ' . get_string('err_import_scope', 'local_extendednav', s((string)$item['scope']));
                continue;
            }
            $values = array_intersect_key($item, array_flip(node_manager::EDITABLE_FIELDS));
            $record = node_manager::prepare($values, $menukey);
            $record->contextid = $contextid;
            $label = $menukey . '@' . $item['scope'] . '/' . $record->nodekey;

            // The key of a node of a category or a course repeats the one of the site on purpose: it is checked against its own.
            $others = node_repository::get_exceptions($menukey, $contextid);
            $current = null;
            foreach ($others as $other) {
                if ($other->nodekey === $record->nodekey) {
                    $current = $other;
                }
            }
            if (isset($records[$menukey . '@' . $contextid . '/' . $record->nodekey])) {
                $errors[] = $label . ': ' . get_string('err_import_duplicate', 'local_extendednav');
                continue;
            }
            if ($current !== null && !$this->overwrite) {
                $counts['skipped']++;
                continue;
            }
            $others = array_column($others, null, 'nodekey');
            unset($others[$record->nodekey]);
            $result = node_validator::validate($record, $others);
            foreach ($result['errors'] as $field => $message) {
                $errors[] = $label . ' (' . $field . '): ' . $message;
            }
            foreach ($result['warnings'] as $message) {
                $warnings[] = $label . ': ' . $message;
            }
            $order = $item['order'] ?? '';
            if ($order !== '' && $order !== null && is_numeric($order)) {
                $record->sortorder = (int)$order;
            }
            if ($current !== null) {
                $record->id = $current->id;
            }
            $records[$menukey . '@' . $contextid . '/' . $record->nodekey] = $record;
        }
        return array_values($records);
    }

    /**
     * Moves imported nodes to the position given in the file.
     *
     * The other nodes keep their order around them. Nodes are placed by ascending position, so a file
     * exported from a whole menu is imported in the same order.
     *
     * @param string $menukey Menu key.
     * @param array[] $orders Position and file index, keyed by node key.
     * @param int[] $ids Saved node ids, keyed by node key.
     */
    private static function apply_orders(string $menukey, array $orders, array $ids): void {
        global $DB;

        uasort($orders, static fn(array $a, array $b): int => $a <=> $b);
        $placed = array_map(static fn(string $nodekey): int => $ids[$nodekey], array_keys($orders));
        $nodes = $DB->get_records(
            'local_extendednav',
            ['menu' => $menukey, 'contextid' => 0],
            '',
            'id, nodekey, parentkey, sortorder'
        );
        $list = array_values(array_diff(
            array_map(static fn(\stdClass $node): int => (int)$node->id, node_manager::group_by_parent($nodes)),
            $placed
        ));
        foreach ($orders as $nodekey => [$order]) {
            array_splice($list, min($order - 1, count($list)), 0, [$ids[$nodekey]]);
        }
        node_manager::reorder($menukey, $list);
    }

    /**
     * Moves imported nodes of a tree to their position among the others of their category, and remembers that those
     * categories are ordered by hand.
     *
     * @param string $menukey Menu key.
     * @param array[] $orders Position among the siblings and file index, keyed by node key.
     */
    private static function apply_sibling_orders(string $menukey, array $orders): void {
        $nodes = node_list::get_scope_nodes($menukey, 0);
        $groups = [];
        foreach ($nodes as $key => $node) {
            $groups[(string)($node->parentkey ?? '')][] = $key;
        }
        $touched = [];
        foreach ($groups as $parent => $keys) {
            $placed = array_values(array_intersect($keys, array_keys($orders)));
            if (empty($placed)) {
                continue;
            }
            usort($placed, static fn(string $a, string $b): int => $orders[$a] <=> $orders[$b]);
            $list = array_values(array_diff($keys, $placed));
            foreach ($placed as $key) {
                array_splice($list, min($orders[$key][0] - 1, count($list)), 0, [$key]);
            }
            $groups[$parent] = $list;
            $touched[] = (string)$parent;
        }
        if (empty($touched)) {
            return;
        }
        // The whole tree in its new order: each category keeps its place and its children take the order of the file.
        $order = [];
        $queues = $groups;
        foreach ($nodes as $key => $node) {
            $order[] = array_shift($queues[(string)($node->parentkey ?? '')]);
        }
        node_manager::save_order($menukey, 0, $order);
        foreach ($touched as $parent) {
            \local_extendednav\menu\admintree::mark_ordered($parent);
        }
    }
}
