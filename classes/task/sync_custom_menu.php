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

use local_extendednav\menu_manager;

/**
 * Imports a menu setting of Moodle again after an administrator changed it.
 *
 * The observer of the change only queues this task, so saving the setting stays as quick as Moodle makes it; the management
 * page imports the setting itself when it opens before the task runs.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_custom_menu extends \core\task\adhoc_task {
    /**
     * Returns the name of the task.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_sync_custom_menu', 'local_extendednav');
    }

    /**
     * Imports the setting named in the data of the task.
     */
    public function execute(): void {
        $menu = menu_manager::get_menu_by_config((string)($this->get_custom_data()->name ?? ''));
        if ($menu !== null) {
            $menu->sync_from_config();
        }
    }
}
