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
use local_extendednav\local\node\repository;
use local_extendednav\menu\activity;

/**
 * Gives the changes of the single navigation of the activities, which the versions before 2.0 had, to the navigation of each
 * kind of activity, once, after the upgrade.
 *
 * The items of each kind are found first (see discovery). A change of an item of Moodle goes to every kind that has the item;
 * an item added goes to every kind; a change made for one activity goes to its kind only. A kind that already has a change for
 * the same item and context keeps its own. Until this ends, the pages of the activities apply the old changes.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class split_activity_navigation extends \core\task\adhoc_task {
    /** @var string[] Fields copied from a change of the old navigation. */
    private const FIELDS = ['nodekey', 'contextid', 'title', 'tooltip', 'url', 'icon', 'visibility', 'roles', 'blockaccess',
        'langs', 'parentkey', 'display', 'popupwidth', 'popupheight', 'placement', 'hideadmins', 'availability', 'sortorder',
        'timecreated', 'timemodified'];

    /**
     * Gives the changes to each kind of activity and removes the old navigation.
     */
    public function execute(): void {
        global $DB;

        \core\cron::setup_user(get_admin());
        $legacy = $DB->get_records('local_extendednav', ['menu' => activity::LEGACY], 'contextid ASC, sortorder ASC, id ASC');
        $legacynatives = json_decode((string)get_config('local_extendednav', 'nativenodes_' . activity::LEGACY), true) ?: [];
        if ($legacy) {
            discovery::all();
            $modules = $this->modules_of_activities($legacy);
            $transaction = $DB->start_delegated_transaction();
            foreach (array_keys(activity::types()) as $modname) {
                $this->give($legacy, $legacynatives, $modname, $modules);
            }
            if (get_config('local_extendednav', 'enabled_' . activity::LEGACY) === '0') {
                foreach (array_keys(activity::types()) as $modname) {
                    set_config('enabled_' . activity::key_for($modname), '0', 'local_extendednav');
                }
            }
            $DB->delete_records('local_extendednav', ['menu' => activity::LEGACY]);
            $transaction->allow_commit();
        }
        foreach (['nativenodes_', 'enabled_'] as $prefix) {
            unset_config($prefix . activity::LEGACY, 'local_extendednav');
        }
        unset_config('legacyactivity', 'local_extendednav');
        repository::purge();
    }

    /**
     * Gives the changes of the old navigation to the navigation of one kind of activity.
     *
     * @param \stdClass[] $legacy Changes of the old navigation.
     * @param array $legacynatives Items of Moodle the old navigation had, by key.
     * @param string $modname Name of the module.
     * @param string[] $modules Module of each activity context that has changes, by context id.
     */
    private function give(array $legacy, array $legacynatives, string $modname, array $modules): void {
        global $DB;

        $menu = new activity($modname);
        $natives = $menu->get_native_nodes();
        foreach ($legacy as $row) {
            // An item of Moodle the kind does not have, or a change made for an activity of another kind, stays out.
            if (isset($legacynatives[$row->nodekey]) && !isset($natives[$row->nodekey])) {
                continue;
            }
            if (isset($modules[(int)$row->contextid]) && $modules[(int)$row->contextid] !== $modname) {
                continue;
            }
            $record = (object)['menu' => $menu->get_key()];
            foreach (self::FIELDS as $field) {
                $record->$field = $row->$field ?? null;
            }
            $existing = $DB->get_record('local_extendednav', [
                'menu' => $record->menu, 'nodekey' => $record->nodekey, 'contextid' => $record->contextid,
            ]);
            if (!$existing) {
                $DB->insert_record('local_extendednav', $record);
            } else if (empty($existing->timemodified) || (int)$existing->timemodified <= (int)$existing->timecreated) {
                // The row of an item that a page or the search found, never changed: it takes the old change.
                $record->id = $existing->id;
                $DB->update_record('local_extendednav', $record);
            }
        }
    }

    /**
     * Returns the module of each activity that has a change of its own.
     *
     * @param \stdClass[] $legacy Changes of the old navigation.
     * @return string[] Module name by context id.
     */
    private function modules_of_activities(array $legacy): array {
        global $DB;

        $contextids = array_unique(array_filter(array_map(static fn(\stdClass $row): int => (int)$row->contextid, $legacy)));
        if (!$contextids) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);
        return $DB->get_records_sql_menu(
            "SELECT ctx.id, m.name
               FROM {context} ctx
               JOIN {course_modules} cm ON cm.id = ctx.instanceid
               JOIN {modules} m ON m.id = cm.module
              WHERE ctx.contextlevel = :level AND ctx.id $insql",
            ['level' => CONTEXT_MODULE] + $params
        );
    }
}
