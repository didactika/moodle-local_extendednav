<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_extendednav\local\navigation;

use local_extendednav\menu\activity;
use local_extendednav\menu_manager;

/**
 * A category, a course or an activity whose navigations are changed in place: which navigations it can change, who may
 * change them and the page where it is done. Everything inside the context follows its changes, and the rest follows the site.
 *
 * A category changes its own navigation, the one of the courses inside it and the one of each kind of activity; a course
 * changes its own navigation and the one of each kind of activity it has; an activity changes its own navigation.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class context_scope {
    /** @var string[] The fields of a node each kind of change touches, by the capability that allows it (local/extendednav:...). */
    public const CHANGES = [
        'changevisibility' => ['visibility'],
        'changeorder' => ['parentkey', 'placement'],
        'changetitles' => ['title', 'tooltip'],
        'changeicons' => ['icon', 'display', 'popupwidth', 'popupheight'],
        'changelinks' => ['url'],
        'changeconditions' => ['roles', 'langs', 'blockaccess', 'hideadmins', 'availability'],
    ];

    /** @var \context The category, the course or the activity. */
    private \context $context;

    /**
     * Creates the scope of a context.
     *
     * @param \context $context The category, the course or the activity.
     */
    private function __construct(\context $context) {
        $this->context = $context;
    }

    /**
     * Returns the scope of a context, or null when the context is not a category, a course or an activity of the site.
     *
     * @param int $contextid Context id.
     * @return self|null
     */
    public static function from_id(int $contextid): ?self {
        $context = \context::instance_by_id($contextid, IGNORE_MISSING);
        if (!$context || !in_array($context->contextlevel, [CONTEXT_COURSECAT, CONTEXT_COURSE, CONTEXT_MODULE], true)) {
            return null;
        }
        if ($context->contextlevel === CONTEXT_COURSE && (int)$context->instanceid === (int)SITEID) {
            return null;
        }
        return new self($context);
    }

    /**
     * Returns the context.
     *
     * @return \context
     */
    public function context(): \context {
        return $this->context;
    }

    /**
     * Whether the scope is a course.
     *
     * @return bool
     */
    public function is_course(): bool {
        return $this->context->contextlevel === CONTEXT_COURSE;
    }

    /**
     * Whether the scope is an activity.
     *
     * @return bool
     */
    public function is_activity(): bool {
        return $this->context->contextlevel === CONTEXT_MODULE;
    }

    /**
     * Returns the kind of the scope: category, course or activity.
     *
     * @return string
     */
    public function kind(): string {
        return match ($this->context->contextlevel) {
            CONTEXT_COURSE => 'course',
            CONTEXT_MODULE => 'activity',
            default => 'category',
        };
    }

    /**
     * Returns the activity of the scope, or null when the scope is not an activity.
     *
     * @return \cm_info|null
     */
    public function cm(): ?\cm_info {
        if (!$this->is_activity()) {
            return null;
        }
        [, $cm] = get_course_and_cm_from_cmid($this->context->instanceid);
        return $cm;
    }

    /**
     * Returns the course of the scope, or null for a category.
     *
     * @return \stdClass|null
     */
    public function course(): ?\stdClass {
        $coursecontext = $this->context->get_course_context(false);
        return $coursecontext ? get_course($coursecontext->instanceid) : null;
    }

    /**
     * Returns the name of the category, the course or the activity.
     *
     * @return string
     */
    public function name(): string {
        if ($this->is_activity()) {
            $cm = $this->cm();
            return format_string($cm->name, true, ['context' => $this->context]);
        }
        if ($this->is_course()) {
            return format_string(get_course($this->context->instanceid)->fullname, true, ['context' => $this->context]);
        }
        $category = \core_course_category::get($this->context->instanceid, IGNORE_MISSING, true);
        return $category ? $category->get_formatted_name() : $this->context->get_context_name(false, true);
    }

    /**
     * Returns the navigations the scope can change: an activity its own; a course its own and the one of each kind of
     * activity it has; a category its own, the one of the courses and the one of each kind of activity of the site.
     *
     * @return string[] Navigation keys.
     */
    public function menus(): array {
        if ($this->is_activity()) {
            return [activity::key_for($this->cm()->modname)];
        }
        if ($this->is_course()) {
            $kinds = array_keys(get_fast_modinfo($this->context->instanceid)->get_used_module_names());
            return array_merge(['course'], array_values(array_filter(
                array_map(static fn(string $modname): string => activity::key_for($modname), $kinds),
                static fn(string $key): bool => menu_manager::get_menu($key) !== null
            )));
        }
        return array_merge(['category', 'course'], array_map(
            static fn(string $modname): string => activity::key_for($modname),
            array_keys(activity::types())
        ));
    }

    /**
     * Logs the user in the context, as its own pages do: in the activity, in the course, or in the site with the category as
     * the context of the page.
     */
    public function login(): void {
        global $PAGE;

        if ($this->is_activity()) {
            $cm = $this->cm();
            require_login($cm->course, false, $cm);
        } else if ($this->is_course()) {
            require_login($this->context->instanceid);
        } else {
            require_login();
            $PAGE->set_context($this->context);
            $PAGE->set_category_by_id($this->context->instanceid);
        }
    }

    /**
     * Whether the current user may see the navigations of the scope: whoever may change them, or holds
     * local/extendednav:viewcontext there.
     *
     * @return bool
     */
    public function can_view(): bool {
        return $this->can_manage() || has_capability('local/extendednav:viewcontext', $this->context);
    }

    /**
     * Stops the page unless the current user may see the navigations of the scope.
     */
    public function require_view(): void {
        if (!$this->can_view()) {
            require_capability('local/extendednav:viewcontext', $this->context);
        }
    }

    /**
     * Whether the current user may make one kind of change in the scope: whoever manages the navigation of the site may make
     * every one; in the context, it takes local/extendednav:managecontext and the capability of that change.
     *
     * @param string $change A key of CHANGES, or addnodes or deletenodes.
     * @return bool
     */
    public function can(string $change): bool {
        if (has_capability('local/extendednav:manage', \context_system::instance())) {
            return true;
        }
        return has_capability('local/extendednav:managecontext', $this->context)
            && has_capability('local/extendednav:' . $change, $this->context);
    }

    /**
     * Returns what the current user may do in the scope, by kind of change.
     *
     * @return bool[] Keyed by the keys of CHANGES, addnodes and deletenodes.
     */
    public function abilities(): array {
        $abilities = [];
        foreach (array_merge(array_keys(self::CHANGES), ['addnodes', 'deletenodes']) as $change) {
            $abilities[$change] = $this->can($change);
        }
        return $abilities;
    }

    /**
     * Returns the fields of a node the current user may not change in the scope.
     *
     * @return string[]
     */
    public function locked_fields(): array {
        $locked = [];
        foreach (self::CHANGES as $change => $fields) {
            if (!$this->can($change)) {
                array_push($locked, ...$fields);
            }
        }
        return $locked;
    }

    /**
     * Whether the current user may change the navigations of the scope.
     *
     * @return bool
     */
    public function can_manage(): bool {
        return has_capability('local/extendednav:manage', \context_system::instance())
            || has_capability('local/extendednav:managecontext', $this->context);
    }

    /**
     * Stops the page or the service unless the current user may change the navigations of the scope.
     */
    public function require_manage(): void {
        if (!has_capability('local/extendednav:manage', \context_system::instance())) {
            require_capability('local/extendednav:managecontext', $this->context);
        }
    }

    /**
     * Returns the page of the navigations of the scope.
     *
     * @param string $menu Navigation shown, or empty for the first one.
     * @return \moodle_url
     */
    public function url(string $menu = ''): \moodle_url {
        $params = ['contextid' => $this->context->id];
        if ($menu !== '') {
            $params['menu'] = $menu;
        }
        return new \moodle_url('/local/extendednav/context.php', $params);
    }
}
