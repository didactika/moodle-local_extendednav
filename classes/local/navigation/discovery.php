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

namespace local_extendednav\local\navigation;

use local_extendednav\menu\activity;
use local_extendednav\menu\secondary;

/**
 * Finds the items of the navigation of each kind of activity by building it, as Moodle does for a page, for activities of the
 * site. A plugin can add an item to some activities only (a report to the assignments that use it), so an item is listed as
 * soon as one activity of its kind has it. The navigation is built, never shown: nothing is sent to the browser.
 *
 * The pages record the items they show too (see injector); this finds the ones no page has shown yet, after an upgrade, after
 * a plugin is installed, or when a manager asks for it.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class discovery {
    /** @var int Activities of one kind looked at, the most recent ones. */
    public const LIMIT = 50;

    /**
     * Looks at the activities of one kind and records the items of their navigation.
     *
     * @param string $modname Name of the module, such as forum.
     * @param int $limit Most activities looked at.
     * @param float $budget Most seconds spent.
     * @return array With activities (looked at) and found (items new to the navigation of the kind).
     */
    public static function kind(string $modname, int $limit = self::LIMIT, float $budget = 20.0): array {
        global $DB;

        $menu = activity::from_key(activity::key_for($modname));
        if ($menu === null) {
            return ['activities' => 0, 'found' => 0];
        }
        $before = count($menu->get_native_nodes());
        $cmids = array_keys($DB->get_records_sql(
            'SELECT cm.id
               FROM {course_modules} cm
               JOIN {modules} m ON m.id = cm.module
              WHERE m.name = :modname AND cm.course <> :site AND cm.deletioninprogress = 0
           ORDER BY cm.id DESC',
            ['modname' => $modname, 'site' => SITEID],
            0,
            $limit
        ));
        $started = microtime(true);
        $seen = [];
        $looked = 0;
        foreach ($cmids as $cmid) {
            if (microtime(true) - $started > $budget) {
                break;
            }
            $seen += self::nodes_of((int)$cmid);
            $looked++;
        }
        $menu->record_seen($seen);
        return ['activities' => $looked, 'found' => count($menu->get_native_nodes()) - $before];
    }

    /**
     * Looks at the activities of every kind the site uses.
     *
     * @param float $budget Most seconds spent in all.
     * @return array By module name, what {@see kind} returned.
     */
    public static function all(float $budget = 300.0): array {
        $started = microtime(true);
        $results = [];
        foreach (array_keys(activity::types()) as $modname) {
            $left = $budget - (microtime(true) - $started);
            if ($left <= 0) {
                break;
            }
            $results[$modname] = self::kind($modname, self::LIMIT, min($left, 30.0));
        }
        return $results;
    }

    /**
     * Builds the secondary navigation of one activity, as its page would, and describes its items.
     *
     * Moodle and its plugins read the page being built from the global $PAGE while they add their items, so the page of the
     * activity takes its place while the navigation is built and gives it back afterwards. An activity that cannot be built
     * (its plugin fails, for example) gives no items.
     *
     * @param int $cmid Activity.
     * @return \stdClass[] By key, with text and action.
     */
    public static function nodes_of(int $cmid): array {
        global $PAGE;

        $saved = $PAGE;
        try {
            [$course, $cm] = get_course_and_cm_from_cmid($cmid);
            $page = new \moodle_page();
            $page->set_cm($cm, $course);
            $page->set_url(new \moodle_url('/mod/' . $cm->modname . '/view.php', ['id' => $cm->id]));
            $page->set_pagelayout('incourse');
            $PAGE = $page;
            $view = new \core\navigation\views\secondary($page);
            $view->initialise();
            return secondary::describe(secondary::children_of($view));
        } catch (\Throwable $e) {
            debugging(
                get_string('err_discovery_activity', 'local_extendednav', (object)['cmid' => $cmid, 'error' => $e->getMessage()]),
                DEBUG_DEVELOPER
            );
            return [];
        } finally {
            $PAGE = $saved;
        }
    }
}
