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

namespace local_extendednav\local;

/**
 * One page of the activity log of a navigation: who created, changed or deleted each node, and when.
 *
 * The rows come from the standard log, narrowed by the action, by a period and by a search on the node key or the name of the
 * person, with the number of rows of each action for the menu of the actions.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class activity_list {
    /** @var string[] Actions the events of the nodes record. */
    public const ACTIONS = ['created', 'updated', 'deleted'];

    /** @var int Most rows in one page. */
    public const MAX_PAGE_SIZE = 100;

    /** @var int Longest search, in characters. */
    public const MAX_SEARCH = 255;

    /** @var string[] Colour of the badge of each action. */
    private const TONES = ['created' => 'success', 'updated' => 'info', 'deleted' => 'danger'];

    /**
     * Returns one page of the log.
     *
     * @param string $menu Navigation key.
     * @param string $search Part of a node key or of the name of a person.
     * @param string $status Actions, separated by commas, or empty for all of them.
     * @param string $datefrom First day, as YYYY-MM-DD, or empty.
     * @param string $dateto Last day, as YYYY-MM-DD, or empty.
     * @param string $direction asc or desc, by time.
     * @param int $page Page, from 0.
     * @param int $perpage Rows in a page, 0 for all of them.
     * @return array Total, counts of each action, whether the standard log records, and the rows.
     */
    public static function get(
        string $menu,
        string $search = '',
        string $status = '',
        string $datefrom = '',
        string $dateto = '',
        string $direction = 'desc',
        int $page = 0,
        int $perpage = 12
    ): array {
        global $DB;

        [$where, $params] = self::where($menu, $search, $datefrom, $dateto);
        $from = '{logstore_standard_log} log LEFT JOIN {user} u ON u.id = log.userid';

        // The rows of each action, whatever action is chosen, for the menu of the actions.
        $counts = array_fill_keys(self::ACTIONS, 0);
        $grouped = $DB->get_records_sql(
            "SELECT log.action, COUNT(1) AS total FROM $from WHERE $where GROUP BY log.action",
            $params
        );
        foreach ($grouped as $row) {
            if (isset($counts[$row->action])) {
                $counts[$row->action] = (int)$row->total;
            }
        }

        $actions = array_values(array_intersect(self::ACTIONS, explode(',', $status)));
        if ($actions) {
            [$insql, $inparams] = $DB->get_in_or_equal($actions, SQL_PARAMS_NAMED, 'act');
            $where .= " AND log.action $insql";
            $params += $inparams;
        }
        $total = (int)$DB->count_records_sql("SELECT COUNT(1) FROM $from WHERE $where", $params);

        $perpage = $perpage === 0 ? max($total, 1) : min(max($perpage, 1), self::MAX_PAGE_SIZE);
        $order = $direction === 'asc' ? 'ASC' : 'DESC';
        $userfields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
        $records = $DB->get_records_sql(
            "SELECT log.id, log.action, log.objectid, log.userid, log.timecreated, log.ip, log.other, $userfields
               FROM $from
              WHERE $where
           ORDER BY log.timecreated $order, log.id $order",
            $params,
            max($page, 0) * $perpage,
            $perpage
        );

        // The nodes that still exist open their form.
        $ids = array_filter(array_map(static fn(\stdClass $record): int => (int)$record->objectid, $records));
        $existing = $ids ? $DB->get_records_list('local_extendednav', 'id', array_unique($ids), '', 'id, menu, contextid') : [];

        $rows = [];
        foreach ($records as $record) {
            $rows[] = self::export_row($record, $existing);
        }
        return [
            'total' => $total,
            'counts' => $counts,
            'haslog' => self::standard_log_enabled(),
            'rows' => $rows,
        ];
    }

    /**
     * Builds the conditions of the log of a navigation, without the actions.
     *
     * @param string $menu Navigation key.
     * @param string $search Part of a node key or of the name of a person.
     * @param string $datefrom First day, as YYYY-MM-DD, or empty.
     * @param string $dateto Last day, as YYYY-MM-DD, or empty.
     * @return array The SQL and its parameters.
     */
    private static function where(string $menu, string $search, string $datefrom, string $dateto): array {
        global $DB;

        // The navigation is stored in the JSON of the field other, as "menu":"primary".
        $where = "log.component = :component AND " . $DB->sql_like('log.other', ':menu');
        $params = [
            'component' => 'local_extendednav',
            'menu' => '%' . $DB->sql_like_escape(substr(json_encode(['menu' => $menu]), 1, -1)) . '%',
        ];

        $search = \core_text::substr(trim($search), 0, self::MAX_SEARCH);
        if ($search !== '') {
            $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
            $where .= ' AND (' . $DB->sql_like('log.other', ':searchkey', false, false)
                . ' OR ' . $DB->sql_like($fullname, ':searchname', false, false) . ')';
            // The node key is the value of "nodekey" in the JSON of the field other.
            $params['searchkey'] = '%"nodekey":"%' . $DB->sql_like_escape($search) . '%';
            $params['searchname'] = '%' . $DB->sql_like_escape($search) . '%';
        }

        $first = self::day_start($datefrom);
        if ($first !== null) {
            $where .= ' AND log.timecreated >= :timefrom';
            $params['timefrom'] = $first;
        }
        $last = self::day_start($dateto);
        if ($last !== null) {
            // The last day counts whole: up to the start of the next one.
            $next = (new \DateTimeImmutable('@' . $last))->setTimezone(\core_date::get_user_timezone_object())->modify('+1 day');
            $where .= ' AND log.timecreated < :timeto';
            $params['timeto'] = $next->getTimestamp();
        }
        return [$where, $params];
    }

    /**
     * The start of a day typed as YYYY-MM-DD, in the timezone of the user.
     *
     * @param string $day The day.
     * @return int|null The timestamp, or null when the day is empty or not a date.
     */
    public static function day_start(string $day): ?int {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $day, $parts)) {
            return null;
        }
        if (!checkdate((int)$parts[2], (int)$parts[3], (int)$parts[1])) {
            return null;
        }
        return make_timestamp((int)$parts[1], (int)$parts[2], (int)$parts[3]);
    }

    /**
     * Turns a record of the log into a row of the table.
     *
     * @param \stdClass $record Record of the log, with the name of the person.
     * @param \stdClass[] $existing Nodes that still exist, keyed by id.
     * @return array
     */
    private static function export_row(\stdClass $record, array $existing): array {
        $other = \logstore_standard\log\store::decode_other($record->other);
        $other = is_array($other) ? $other : [];
        $action = in_array($record->action, self::ACTIONS, true) ? $record->action : '';
        $node = $existing[(int)$record->objectid] ?? null;
        $editurl = '';
        if ($node && $action !== 'deleted') {
            $editurl = (new \moodle_url('/local/extendednav/edit.php', ['id' => $node->id, 'menu' => $node->menu]))->out(false);
        }
        $hasuser = (int)$record->userid > 0 && $record->firstname !== null;
        return [
            'id' => (int)$record->id,
            'time' => userdate((int)$record->timecreated, get_string('strftimedatetimeshort', 'core_langconfig')),
            'user' => $hasuser ? fullname($record) : '',
            'userurl' => $hasuser ? (new \moodle_url('/user/profile.php', ['id' => $record->userid]))->out(false) : '',
            'action' => $action,
            'actionlabel' => $action !== '' ? get_string('action_' . $action, 'local_extendednav') : s($record->action),
            'tone' => self::TONES[$action] ?? 'secondary',
            'nodekey' => clean_param((string)($other['nodekey'] ?? ''), PARAM_ALPHANUMEXT),
            'editurl' => $editurl,
            'ip' => (string)($record->ip ?? ''),
        ];
    }

    /**
     * Whether the standard log records the events, so the log can have rows.
     *
     * @return bool
     */
    public static function standard_log_enabled(): bool {
        $stores = explode(',', (string)get_config('tool_log', 'enabled_stores'));
        return in_array('logstore_standard', $stores, true);
    }
}
