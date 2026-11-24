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
use local_extendednav\menu_manager;
use local_extendednav\local\node\access;
use local_extendednav\local\node\display;
use local_extendednav\local\node\repository;
use local_extendednav\local\node\text;

/**
 * Applies the nodes of the plugin to the secondary navigation of the page: the one of a course, of the site home, of an
 * activity, of a category or of the site administration.
 *
 * It works on the navigation Moodle has already built for the page, so it adds no page build, no query when nothing is
 * customised and one cache read when something is: the nodes of the site and, if any, the exceptions of the category
 * and the course of the page.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class injector {
    /**
     * Applies the nodes to the secondary navigation of the page, if it has one.
     */
    public static function apply(): void {
        global $PAGE;

        if (during_initial_install() || !get_config('local_extendednav', 'enable_plugin') || !$PAGE->has_secondary_navigation()) {
            return;
        }
        $origin = self::get_origin($PAGE->context, (int)$PAGE->course->id, $PAGE->cm ? (string)$PAGE->cm->modname : null);
        if ($origin === null) {
            return;
        }
        if (!menu_manager::is_enabled($origin)) {
            return;
        }
        $menu = menu_manager::get_menu($origin);
        if ($menu === null) {
            return;
        }
        $view = $PAGE->secondarynav;

        $children = secondary::children_of($view);
        $menu->record_seen(secondary::describe($children));

        // Until the changes of the single navigation of the activities are given to each kind (a task after the upgrade to
        // 2.0), the pages of the activities keep them.
        $source = $menu instanceof activity && get_config('local_extendednav', 'legacyactivity') ? activity::LEGACY : $origin;
        $nodes = repository::get_scoped_nodes($source, $PAGE->context);
        if (!empty($nodes)) {
            self::inject($menu, $nodes, $view, $children);
        }
    }

    /**
     * Returns the navigation a page belongs to: the one of its kind of activity on the page of an activity.
     *
     * @param \context $context Context of the page.
     * @param int $courseid Course of the page.
     * @param string|null $modname Module of the activity of the page, read from the context when it is not given.
     * @return string|null A navigation key, or null when the page has none of them.
     */
    public static function get_origin(\context $context, int $courseid, ?string $modname = null): ?string {
        global $DB;

        switch ($context->contextlevel) {
            case CONTEXT_COURSE:
                return $courseid === (int)SITEID ? 'frontpage' : 'course';
            case CONTEXT_MODULE:
                $modname ??= (string)$DB->get_field_sql(
                    'SELECT m.name FROM {modules} m JOIN {course_modules} cm ON cm.module = m.id WHERE cm.id = :cmid',
                    ['cmid' => $context->instanceid]
                );
                return $modname === '' ? null : activity::key_for($modname);
            case CONTEXT_COURSECAT:
                return 'category';
            default:
                return null;
        }
    }

    /**
     * Hides, renames, re-links, places, adds and orders the nodes of the navigation.
     *
     * @param secondary $menu Handler of the navigation.
     * @param \stdClass[] $nodes Records keyed by node key, in the order they must have.
     * @param \core\navigation\views\secondary $view The navigation of the page.
     * @param \navigation_node[] $children The nodes of the navigation, by the key they keep on every page.
     */
    private static function inject(
        secondary $menu,
        array $nodes,
        \core\navigation\views\secondary $view,
        array $children
    ): void {
        global $PAGE;

        $needsscript = false;
        $shown = [];
        foreach ($nodes as $key => $record) {
            $node = $children[$key] ?? null;
            if (!access::is_shown($record)) {
                if ($node) {
                    $node->remove();
                }
                continue;
            }
            $title = text::get_title($record);
            $url = trim((string)($record->url ?? '')) === '' ? null : trim((string)$record->url);
            if (!$node) {
                // A node Moodle does not show on this page is never added in its place; only nodes of the plugin are.
                if ($menu->is_native($key) || $title === '' || $url === null) {
                    continue;
                }
                $node = \navigation_node::create(
                    format_string($title),
                    new \moodle_url($url),
                    \navigation_node::TYPE_CUSTOM,
                    null,
                    $key
                );
                $node->showinsecondarynavigation = true;
                $view->add_node($node);
            } else {
                if ($title !== '') {
                    $node->text = format_string($title);
                }
                if ($url !== null) {
                    $node->action = new \moodle_url($url);
                }
            }
            $tooltip = text::resolve($record->tooltip ?? '');
            if ($tooltip !== '') {
                $node->title(format_string($tooltip, true, ['escape' => false]));
            }
            $attributes = display::get_link_attributes($record);
            if (!empty($attributes)) {
                // The navigation prints no link attributes, so they go on a hidden marker the script reads.
                $node->text .= \html_writer::span('', 'local-extendednav-display', ['hidden' => 'hidden'] + $attributes);
                $needsscript = true;
            }
            $placement = (int)($record->placement ?? 0);
            if ($placement === 1 || $placement === 2) {
                $node->set_force_into_more_menu($placement === 1);
            }
            $shown[$key] = $node;
        }

        // Order the navigation as the nodes are; the nodes the plugin does not know keep their place, after them. The page of
        // the activity itself stays the first one, as Moodle puts it.
        $others = [];
        foreach ($view->children as $child) {
            $others[secondary::key_of($child) ?? 'o' . spl_object_id($child)] = $child;
        }
        foreach ($others as $child) {
            $child->remove();
        }
        if (isset($others['modulepage'])) {
            $view->add_node($others['modulepage']);
            unset($others['modulepage']);
        }
        foreach ($shown as $key => $node) {
            $view->add_node($node);
            unset($others[$key]);
        }
        foreach ($others as $child) {
            $view->add_node($child);
        }

        if ($needsscript) {
            $PAGE->requires->js_call_amd('local_extendednav/menus/link_display', 'init');
        }
    }
}
