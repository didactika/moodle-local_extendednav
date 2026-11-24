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

namespace local_extendednav\local\node;

/**
 * The conditions of a node, written and checked with Moodle's "Restrict access": dates, groups, groupings, profile fields and
 * whatever condition plugins the site has, as for an activity.
 *
 * A node of a course (an exception of the course navigation) is checked in that course, so its groups and groupings apply;
 * every other node is checked in the site course. A node without conditions costs nothing; the result of a node is kept for
 * the rest of the request.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class availability extends \core_availability\info {
    /** @var string The starting point of the date conditions. */
    public const DATE_FROM = '>=';

    /** @var string The end of the date conditions. */
    public const DATE_UNTIL = '<';

    /** @var bool[] Whether each node is available, keyed by node id and user. */
    private static array $results = [];

    /** @var \stdClass The node. */
    private \stdClass $node;

    /**
     * Creates the conditions of a node.
     *
     * @param \stdClass $node Node record, with its availability.
     */
    public function __construct(\stdClass $node) {
        $this->node = $node;
        parent::__construct(self::course_of($node), true, self::json_of($node));
    }

    /**
     * Whether the conditions of a node let a user see it. A node without conditions, or a site where conditions are turned
     * off, lets everyone see it.
     *
     * @param \stdClass $node Node record.
     * @param int|null $userid User id, defaults to the current user.
     * @return bool
     */
    public static function allows(\stdClass $node, ?int $userid = null): bool {
        global $CFG, $USER;

        if (self::json_of($node) === null || empty($CFG->enableavailability)) {
            return true;
        }
        $userid = $userid ?? (int)$USER->id;
        $key = ((int)($node->id ?? 0) ?: spl_object_id($node)) . '@' . $userid;
        if (!isset(self::$results[$key])) {
            $information = '';
            self::$results[$key] = (new self($node))->is_available($information, false, $userid);
        }
        return self::$results[$key];
    }

    /**
     * The conditions of a node in words, as Moodle writes them for an activity, without markup.
     *
     * @param \stdClass $node Node record.
     * @return string Empty when the node has none.
     */
    public static function describe(\stdClass $node): string {
        if (self::json_of($node) === null) {
            return '';
        }
        try {
            $info = new self($node);
            $html = self::format_info($info->get_full_information(), $info->get_course());
        } catch (\Throwable $e) {
            return get_string('conditions_invalid', 'local_extendednav');
        }
        // One line: the items of Moodle's list joined by semicolons, without its markup.
        $text = html_entity_decode(strip_tags(preg_replace('~</li>~i', '; ', $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return rtrim(trim(preg_replace(['/\s+/', '/\s+;/'], [' ', ';'], $text)), '; ');
    }

    /**
     * The period the dates of the conditions give, when they are at the top of the conditions: the first moment the node
     * is shown, the moment it stops, and where now is in that period.
     *
     * @param \stdClass $node Node record.
     * @param int|null $now The moment, defaults to now.
     * @return array|null With from, until (0 when not set) and state: scheduled, current or ended; null without dates.
     */
    public static function period(\stdClass $node, ?int $now = null): ?array {
        $json = self::json_of($node);
        $tree = $json === null ? null : json_decode($json);
        // Dates joined with "and" bound the period; inside an "or" or a negation they do not.
        if (!$tree || ($tree->op ?? '') !== '&' || empty($tree->c)) {
            return null;
        }
        $from = 0;
        $until = 0;
        foreach ($tree->c as $condition) {
            if (($condition->type ?? '') !== 'date' || !isset($condition->t)) {
                continue;
            }
            if (($condition->d ?? '') === self::DATE_FROM) {
                $from = max($from, (int)$condition->t);
            } else if (($condition->d ?? '') === self::DATE_UNTIL) {
                $until = $until ? min($until, (int)$condition->t) : (int)$condition->t;
            }
        }
        if (!$from && !$until) {
            return null;
        }
        $now = $now ?? time();
        $state = 'current';
        if ($from && $now < $from) {
            $state = 'scheduled';
        } else if ($until && $now >= $until) {
            $state = 'ended';
        }
        return ['from' => $from, 'until' => $until, 'state' => $state];
    }

    /**
     * Checks the conditions a form or a file gives.
     *
     * @param string|null $json The conditions, as Moodle's editor writes them.
     * @return string|null The conditions to save (null when there are none), as given.
     * @throws \coding_exception When they are not valid conditions.
     */
    public static function clean(?string $json): ?string {
        $json = trim((string)$json);
        if ($json === '') {
            return null;
        }
        $tree = new \core_availability\tree(json_decode($json));
        return $tree->is_empty() ? null : $json;
    }

    /**
     * Forgets the results of this request, after the nodes or the users change.
     */
    public static function reset(): void {
        self::$results = [];
    }

    /**
     * The course a node is checked in: the course of its exception, or the site course.
     *
     * @param \stdClass $node Node record.
     * @return \stdClass
     */
    private static function course_of(\stdClass $node): \stdClass {
        global $SITE;

        $contextid = (int)($node->contextid ?? 0);
        if ($contextid) {
            $context = \context::instance_by_id($contextid, IGNORE_MISSING);
            $coursecontext = $context ? $context->get_course_context(false) : false;
            if ($coursecontext) {
                return get_course($coursecontext->instanceid);
            }
        }
        return $SITE;
    }

    /**
     * The conditions of a node, or null when it has none.
     *
     * @param \stdClass $node Node record.
     * @return string|null
     */
    private static function json_of(\stdClass $node): ?string {
        $json = trim((string)($node->availability ?? ''));
        return $json === '' ? null : $json;
    }

    /**
     * Gets the context the conditions are checked in.
     *
     * @return \context
     */
    public function get_context() {
        return \context_course::instance((int)$this->get_course()->id);
    }

    /**
     * The name of the node, for the messages of Moodle.
     *
     * @return string
     */
    protected function get_thing_name() {
        return format_string((string)($this->node->title ?? '') ?: (string)$this->node->nodekey);
    }

    /**
     * Saves new conditions of the node, as a restore that moves dates does.
     *
     * @param string $availability The conditions.
     */
    protected function set_in_database($availability) {
        global $DB;

        if (!empty($this->node->id)) {
            $DB->set_field('local_extendednav', 'availability', $availability, ['id' => $this->node->id]);
            repository::purge();
        }
    }

    /**
     * The capability that sees a node whatever its conditions say: whoever manages the navigation.
     *
     * @return string
     */
    protected function get_view_hidden_capability() {
        return 'local/extendednav:manage';
    }
}
