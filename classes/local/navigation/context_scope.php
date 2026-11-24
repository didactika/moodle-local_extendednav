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

/**
 * A category or a course whose navigations are changed in place: which navigations it can change, who may change them and
 * the page where it is done. Everything inside the context follows its changes, and the rest follows the site.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class context_scope {
    /** @var string[] Navigations a category changes: its own and the ones of the courses and activities inside it. */
    private const CATEGORY_MENUS = ['category', 'course', 'activity'];

    /** @var string[] Navigations a course changes: its own and the ones of its activities. */
    private const COURSE_MENUS = ['course', 'activity'];

    /** @var \context The category or the course. */
    private \context $context;

    /**
     * Creates the scope of a context.
     *
     * @param \context $context A category or a course context.
     */
    private function __construct(\context $context) {
        $this->context = $context;
    }

    /**
     * The scope of a context, or null when the context is not a category or a course (the site course is not one).
     *
     * @param int $contextid Context id.
     * @return self|null
     */
    public static function from_id(int $contextid): ?self {
        $context = \context::instance_by_id($contextid, IGNORE_MISSING);
        if (!$context || !in_array($context->contextlevel, [CONTEXT_COURSECAT, CONTEXT_COURSE], true)) {
            return null;
        }
        if ($context->contextlevel === CONTEXT_COURSE && (int)$context->instanceid === (int)SITEID) {
            return null;
        }
        return new self($context);
    }

    /**
     * The context.
     *
     * @return \context
     */
    public function context(): \context {
        return $this->context;
    }

    /**
     * Whether it is a course.
     *
     * @return bool
     */
    public function is_course(): bool {
        return $this->context->contextlevel === CONTEXT_COURSE;
    }

    /**
     * The name of the category or the course, ready to show.
     *
     * @return string
     */
    public function name(): string {
        if ($this->is_course()) {
            return format_string(get_course($this->context->instanceid)->fullname, true, ['context' => $this->context]);
        }
        $category = \core_course_category::get($this->context->instanceid, IGNORE_MISSING, true);
        return $category ? $category->get_formatted_name() : $this->context->get_context_name(false, true);
    }

    /**
     * The navigations this context can change.
     *
     * @return string[] Navigation keys.
     */
    public function menus(): array {
        return $this->is_course() ? self::COURSE_MENUS : self::CATEGORY_MENUS;
    }

    /**
     * Whether a user may change the navigations of this context: whoever manages the whole navigation, or holds the
     * capability in this context.
     *
     * @return bool
     */
    public function can_manage(): bool {
        return has_capability('local/extendednav:manage', \context_system::instance())
            || has_capability('local/extendednav:managecontext', $this->context);
    }

    /**
     * Requires the user to be able to change the navigations of this context.
     */
    public function require_manage(): void {
        if (!has_capability('local/extendednav:manage', \context_system::instance())) {
            require_capability('local/extendednav:managecontext', $this->context);
        }
    }

    /**
     * The page where the navigations of this context are changed.
     *
     * @param string $menu Navigation key, or empty for the first one.
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
