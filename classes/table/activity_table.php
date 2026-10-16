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

namespace local_extendednav\table;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/tablelib.php');

/**
 * Modern paginated SQL table for local_extendednav activity logs.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity_table extends \table_sql {
    /** @var array<int, \core\event\base|null> Restored events keyed by log id. */
    private array $events = [];

    /** @var array<int, string|false> User names keyed by user id. */
    private array $usernames = [];

    /** @var string[] Actions the node events record. */
    public const ACTIONS = ['created', 'updated', 'deleted'];

    /**
     * Creates an activity table constrained to navigation events.
     *
     * @param \moodle_url $baseurl Base URL retaining table state.
     * @param string $menu Menu key scope (e.g. 'primary', 'usermenu').
     * @param array $filters Filter parameters.
     */
    public function __construct(\moodle_url $baseurl, string $menu, array $filters = []) {
        global $DB;

        parent::__construct('local_extendednav_activity');

        $this->define_columns([
            'timecreated',
            'fullnameuser',
            'action',
            'eventname',
            'description',
            'ip',
        ]);
        $this->define_headers([
            get_string('time'),
            get_string('fullnameuser'),
            get_string('action'),
            get_string('eventname'),
            get_string('description'),
            get_string('ip_address'),
        ]);

        $this->sortable(true, 'timecreated', SORT_DESC);
        $nonsortablecolumns = [
            'fullnameuser',
            'action',
            'eventname',
            'description',
            'ip',
        ];
        foreach ($nonsortablecolumns as $column) {
            $this->no_sorting($column);
        }

        $this->collapsible(false);
        $this->is_downloadable(false);
        $this->pageable(true);
        $this->define_baseurl($baseurl);
        $this->set_attribute('id', 'local-extendednav-activity-table');
        $this->set_attribute('class', 'generaltable table-hover mb-0 local-extendednav-activity');
        foreach (['timecreated', 'fullnameuser', 'action', 'ip'] as $column) {
            $this->column_class($column, 'text-nowrap');
        }

        $where = "log.component = 'local_extendednav'";
        $params = [];

        if ($menu !== '') {
            // The menu is stored inside the JSON of the "other" field, as "menu":"primary".
            $where .= ' AND ' . $DB->sql_like('log.other', ':menuparam');
            $params['menuparam'] = '%' . $DB->sql_like_escape(substr(json_encode(['menu' => $menu]), 1, -1)) . '%';
        }

        if (!empty($filters['datefrom'])) {
            $where .= ' AND log.timecreated >= :filterdatefrom';
            $params['filterdatefrom'] = (int)$filters['datefrom'];
        }

        if (!empty($filters['dateto'])) {
            $selecteddate = (new \DateTimeImmutable('@' . (int)$filters['dateto']))
                ->setTimezone(\core_date::get_user_timezone_object());
            $requestedend = $selecteddate->setTime(0, 0)->modify('+1 day')->getTimestamp();
            $where .= ' AND log.timecreated < :filterdateto';
            $params['filterdateto'] = $requestedend;
        }

        if (in_array($filters['action'] ?? '', self::ACTIONS, true)) {
            $where .= ' AND log.action = :filteraction';
            $params['filteraction'] = $filters['action'];
        }

        $countsql = 'SELECT COUNT(log.id) FROM {logstore_standard_log} log WHERE ' . $where;

        $this->set_count_sql($countsql, $params);
        $this->set_sql(
            'log.*',
            '{logstore_standard_log} log',
            $where,
            $params
        );
    }

    /**
     * Renders formatted creation timestamp.
     *
     * @param \stdClass $row Standard log record.
     * @return string
     */
    public function col_timecreated($row): string {
        return userdate(
            (int)$row->timecreated,
            get_string('strftimedatetimeaccurate', 'core_langconfig')
        );
    }

    /**
     * Renders user who performed the action.
     *
     * @param \stdClass $row Standard log record.
     * @return string
     */
    public function col_fullnameuser($row): string {
        $username = $this->get_user_name((int)$row->userid);
        return $username
            ? $this->user_link((int)$row->userid, $username, (int)$row->courseid)
            : '-';
    }

    /**
     * Renders styled action badge.
     *
     * @param \stdClass $row Standard log record.
     * @return string
     */
    public function col_action($row): string {
        $variant = match ($row->action) {
            'created' => 'success',
            'updated' => 'info',
            'deleted' => 'danger',
            default => 'secondary',
        };
        $text = in_array($row->action, self::ACTIONS, true) ? get_string('action_' . $row->action, 'local_extendednav')
            : s($row->action);
        return \html_writer::span($text, 'badge badge-pill rounded-pill badge-' . $variant . ' text-bg-' . $variant);
    }

    /**
     * Starts the table without the paging bar, which the page shows in the card footer.
     */
    public function start_html() {
        $usepages = $this->use_pages;
        $this->use_pages = false;
        parent::start_html();
        $this->use_pages = $usepages;
    }

    /**
     * Ends the table without the paging bar, which the page shows in the card footer.
     */
    public function finish_html() {
        $usepages = $this->use_pages;
        $this->use_pages = false;
        parent::finish_html();
        $this->use_pages = $usepages;
    }

    /**
     * Returns the paging bar of the table, once it has been printed.
     *
     * @return string HTML, or an empty string when everything fits in one page.
     */
    public function get_paging_bar(): string {
        global $OUTPUT;

        if (!$this->use_pages || $this->totalrows <= $this->pagesize) {
            return '';
        }
        $bar = new \paging_bar($this->totalrows, $this->currpage, $this->pagesize, $this->baseurl);
        $bar->pagevar = $this->request[TABLE_VAR_PAGE];
        return $OUTPUT->render($bar);
    }

    /**
     * Renders localised event name.
     *
     * @param \stdClass $row Standard log record.
     * @return string
     */
    public function col_eventname($row): string {
        $event = $this->get_event($row);
        if (!$event) {
            return s($row->eventname);
        }

        $name = $event->get_name();
        if ($url = $event->get_url()) {
            return \html_writer::link($url, $name);
        }

        return $name;
    }

    /**
     * Renders event description.
     *
     * @param \stdClass $row Standard log record.
     * @return string
     */
    public function col_description($row): string {
        $event = $this->get_event($row);
        return $event ? format_text($event->get_description(), FORMAT_PLAIN) : '-';
    }

    /**
     * Renders IP address with lookup link.
     *
     * @param \stdClass $row Standard log record.
     * @return string
     */
    public function col_ip($row): string {
        if (empty($row->ip)) {
            return '-';
        }

        return \html_writer::link(new \moodle_url('/iplookup/index.php', [
            'popup' => 1,
            'ip' => $row->ip,
            'user' => (int)$row->userid,
        ]), s($row->ip));
    }

    /**
     * Restores event object from standard log record.
     *
     * @param \stdClass $row Standard log record.
     * @return \core\event\base|null
     */
    private function get_event(\stdClass $row): ?\core\event\base {
        $id = (int)$row->id;
        if (array_key_exists($id, $this->events)) {
            return $this->events[$id];
        }

        $data = (array)$row;
        $extra = [
            'origin' => $data['origin'],
            'ip' => $data['ip'],
            'realuserid' => $data['realuserid'] ?? null,
        ];
        $data['other'] = \logstore_standard\log\store::decode_other($data['other']);
        if (!is_array($data['other'])) {
            $data['other'] = [];
        }
        unset($data['id'], $data['origin'], $data['ip'], $data['realuserid']);

        return $this->events[$id] = \core\event\base::restore($data, $extra) ?: null;
    }

    /**
     * Gets and caches user display name.
     *
     * @param int $userid
     * @return string|false
     */
    private function get_user_name(int $userid) {
        if ($userid <= 0) {
            return false;
        }
        if (array_key_exists($userid, $this->usernames)) {
            return $this->usernames[$userid];
        }

        $fields = \core_user\fields::for_name()->get_sql('', false, '', '', false)->selects;
        $user = \core_user::get_user($userid, $fields);
        $this->usernames[$userid] = $user ? fullname($user) : false;

        return $this->usernames[$userid];
    }

    /**
     * Builds profile link.
     *
     * @param int $userid
     * @param string $name
     * @param int $courseid
     * @return string
     */
    private function user_link(int $userid, string $name, int $courseid): string {
        $params = ['id' => $userid];
        if ($courseid > 0) {
            $params['course'] = $courseid;
        }

        return \html_writer::link(new \moodle_url('/user/view.php', $params), $name);
    }
}
