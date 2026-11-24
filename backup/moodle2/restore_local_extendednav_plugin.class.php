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
 * Restores what a course changes in its navigations, for the course restored.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_local_extendednav_plugin extends restore_local_plugin {
    /**
     * Reads the nodes of the course from its backup.
     *
     * @return restore_path_element[]
     */
    protected function define_course_plugin_structure() {
        return [new restore_path_element('navigation_node', $this->get_pathfor('/navigation_node'))];
    }

    /**
     * Saves a node of the course for the course restored. A node that the course already sets, when the backup is restored
     * into an existing course, is kept as it is.
     *
     * @param array|\stdClass $data The node from the backup.
     */
    public function process_navigation_node($data) {
        global $DB;

        $data = (object)$data;
        $contextid = (int)$this->task->get_contextid();
        $key = ['menu' => $data->menu, 'nodekey' => $data->nodekey, 'contextid' => $contextid];
        if ($DB->record_exists('local_extendednav', $key)) {
            return;
        }
        unset($data->id);
        $data->contextid = $contextid;
        $data->availability = $this->restore_conditions($data->availability ?? null);
        $DB->insert_record('local_extendednav', $data);
        \local_extendednav\local\node\repository::purge();
    }

    /**
     * Points the conditions of a node at the groups and groupings of the course restored, and moves their dates as the
     * dates of the course move.
     *
     * @param string|null $json The conditions from the backup.
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
