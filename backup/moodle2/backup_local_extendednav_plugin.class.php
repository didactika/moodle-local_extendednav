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
 * The exceptions of a course and of its activities travel in their backups: what they change in their navigations.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_local_extendednav_plugin extends backup_local_plugin {
    /**
     * Adds the nodes the course sets in its navigations to the backup of the course.
     *
     * @return backup_plugin_element
     */
    protected function define_course_plugin_structure() {
        return $this->define_nodes();
    }

    /**
     * Adds the nodes the activity sets in its navigation to the backup of the activity.
     *
     * @return backup_plugin_element
     */
    protected function define_module_plugin_structure() {
        return $this->define_nodes();
    }

    /**
     * Describes the nodes of the context backed up: the course or the activity.
     *
     * @return backup_plugin_element
     */
    private function define_nodes() {
        $plugin = $this->get_plugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());
        $plugin->add_child($wrapper);

        $node = new backup_nested_element('navigation_node', ['id'], [
            'menu', 'nodekey', 'title', 'tooltip', 'url', 'icon', 'visibility', 'roles', 'blockaccess', 'langs', 'parentkey',
            'display', 'popupwidth', 'popupheight', 'placement', 'hideadmins', 'availability', 'sortorder',
            'timecreated', 'timemodified',
        ]);
        $wrapper->add_child($node);
        // The site chooses, among the defaults of the backups, whether the changes of the navigation go in them.
        if (get_config('local_extendednav', 'backup_navigation') !== '0') {
            $node->set_source_table('local_extendednav', ['contextid' => backup::VAR_CONTEXTID], 'sortorder ASC, id ASC');
        }

        return $plugin;
    }
}
