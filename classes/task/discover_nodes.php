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

namespace local_extendednav\task;

use local_extendednav\local\navigation\discovery;

/**
 * Finds, once a day, the items that Moodle and its plugins add to the navigation of each kind of activity, looking at the
 * most recent activities of each kind as an administrator, who sees every item.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class discover_nodes extends \core\task\scheduled_task {
    /**
     * Returns the name of the task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_discover_nodes', 'local_extendednav');
    }

    /**
     * Looks at the activities of every kind.
     */
    public function execute(): void {
        if (!\local_extendednav\menu_manager::is_on()) {
            return;
        }
        \core\cron::setup_user(get_admin());
        foreach (discovery::all() as $modname => $result) {
            mtrace("  {$modname}: {$result['activities']} activities, {$result['found']} new items");
        }
    }
}
