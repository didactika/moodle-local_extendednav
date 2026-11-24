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
 * Restores what a course and its activities change in their navigations, for the course and the activities restored.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_local_extendednav_plugin extends restore_local_plugin {
    /** @var int[] Nodes restored, whose conditions on grades and completion wait for the activities to be restored. */
    private array $restored = [];

    /**
     * Reads the nodes of the course from its backup.
     *
     * @return restore_path_element[]
     */
    protected function define_course_plugin_structure() {
        return [new restore_path_element('navigation_node', $this->get_pathfor('/navigation_node'))];
    }

    /**
     * Reads the nodes of an activity from its backup.
     *
     * @return restore_path_element[]
     */
    protected function define_module_plugin_structure() {
        return [new restore_path_element('navigation_node', $this->get_pathfor('/navigation_node'))];
    }

    /**
     * Saves a node for the course or the activity restored. A node they already set, when the backup is restored into an
     * existing course, is kept as it is.
     *
     * A backup made before each kind of activity had its own navigation names the single navigation of the activities: the
     * node goes to the navigation of the kind of the activity restored or, for a course, of each kind that has the item.
     *
     * @param array|\stdClass $data The node from the backup.
     */
    public function process_navigation_node($data) {
        global $DB;

        $data = (object)$data;
        $contextid = (int)$this->task->get_contextid();
        unset($data->id);
        $data->contextid = $contextid;
        $data->availability = $this->restore_conditions($data->availability ?? null);
        foreach ($this->menus_for($data) as $menu) {
            $data->menu = $menu;
            $same = ['menu' => $menu, 'nodekey' => $data->nodekey, 'contextid' => $contextid];
            if (!$DB->record_exists('local_extendednav', $same)) {
                $this->restored[] = (int)$DB->insert_record('local_extendednav', $data);
            }
        }
        \local_extendednav\local\node\repository::purge();
    }

    /**
     * Maps the grades and the activities of the conditions, once the whole course is restored.
     */
    public function after_restore_course() {
        $this->map_activity_conditions();
    }

    /**
     * Maps the grades and the activities of the conditions, once the activity is restored.
     */
    public function after_restore_module() {
        $this->map_activity_conditions();
    }

    /**
     * Returns the navigations a node of the backup goes to.
     *
     * @param \stdClass $data The node.
     * @return string[] Navigation keys.
     */
    private function menus_for(\stdClass $data): array {
        if ($data->menu !== \local_extendednav\menu\activity::LEGACY) {
            return [$data->menu];
        }
        if ($this->task instanceof restore_activity_task) {
            return [\local_extendednav\menu\activity::key_for($this->task->get_modulename())];
        }
        $menus = [];
        foreach (array_keys(\local_extendednav\menu\activity::types()) as $modname) {
            $menu = new \local_extendednav\menu\activity($modname);
            if ($menu->is_native($data->nodekey) || !$this->is_native_anywhere($data->nodekey)) {
                $menus[] = $menu->get_key();
            }
        }
        return $menus;
    }

    /**
     * Whether an item belongs to the navigation of some kind of activity.
     *
     * @param string $nodekey Node key.
     * @return bool
     */
    private function is_native_anywhere(string $nodekey): bool {
        foreach (array_keys(\local_extendednav\menu\activity::types()) as $modname) {
            if ((new \local_extendednav\menu\activity($modname))->is_native($nodekey)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Maps the conditions on a grade and on the completion of an activity of the nodes restored to the grade items and the
     * activities restored. A condition on something the backup did not hold keeps its value.
     */
    private function map_activity_conditions(): void {
        global $DB;

        foreach ($this->restored as $id) {
            $availability = $DB->get_field('local_extendednav', 'availability', ['id' => $id]);
            $tree = $availability ? json_decode($availability) : null;
            if (!$tree) {
                continue;
            }
            $walk = function (\stdClass $node) use (&$walk): void {
                foreach ($node->c ?? [] as $child) {
                    if (isset($child->c)) {
                        $walk($child);
                        continue;
                    }
                    $item = ['grade' => 'grade_item', 'completion' => 'course_module'][$child->type ?? ''] ?? null;
                    $field = ($child->type ?? '') === 'completion' ? 'cm' : 'id';
                    if ($item !== null && !empty($child->$field)) {
                        $mapped = $this->get_mappingid($item, (int)$child->$field);
                        if ($mapped) {
                            $child->$field = (int)$mapped;
                        }
                    }
                }
            };
            $walk($tree);
            $DB->set_field('local_extendednav', 'availability', json_encode($tree), ['id' => $id]);
        }
        $this->restored = [];
        \local_extendednav\local\node\repository::purge();
    }

    /**
     * Maps the groups and groupings of the conditions of a node to the ones restored, and moves its dates as the course
     * moves.
     *
     * @param string|null $json The conditions in the backup.
     * @return string|null
     */
    private function restore_conditions(?string $json): ?string {
        $tree = $json ? json_decode($json) : null;
        if (!$tree) {
            return null;
        }
        $walk = function (\stdClass $node) use (&$walk): void {
            foreach ($node->c ?? [] as $child) {
                if (isset($child->c)) {
                    $walk($child);
                    continue;
                }
                $type = $child->type ?? '';
                if (in_array($type, ['group', 'grouping'], true) && !empty($child->id)) {
                    $mapped = $this->get_mappingid($type, (int)$child->id);
                    if ($mapped) {
                        $child->id = (int)$mapped;
                    }
                } else if ($type === 'date' && isset($child->t)) {
                    $child->t = (int)$this->apply_date_offset((int)$child->t);
                }
            }
        };
        $walk($tree);
        return json_encode($tree);
    }
}
