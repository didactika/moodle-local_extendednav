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

namespace local_extendednav\event;

/**
 * Event emitted when a navigation node is deleted.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class node_deleted extends \core\event\base {
    /**
     * Initialises the event metadata.
     */
    protected function init(): void {
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_extendednav';
    }

    /**
     * Gets the localised event name.
     *
     * @return string
     */
    public static function get_name(): string {
        return get_string('eventnodedeleted', 'local_extendednav');
    }

    /**
     * Gets the event description for the log store.
     *
     * @return string
     */
    public function get_description(): string {
        $menu = $this->other['menu'] ?? 'unknown';
        $nodekey = $this->other['nodekey'] ?? 'unknown';
        return "The user with id '{$this->userid}' deleted navigation node '{$nodekey}' " .
            "(id '{$this->objectid}') in menu '{$menu}'.";
    }

    /**
     * Gets a URL for the management page.
     *
     * @return \moodle_url
     */
    public function get_url(): \moodle_url {
        $menu = $this->other['menu'] ?? 'primary';
        return new \moodle_url('/local/extendednav/manage.php', ['menu' => $menu]);
    }

    /**
     * Helper to create an event instance from a database record.
     *
     * @param \stdClass $record Database record object.
     * @param int|null $actorid User id who performed the action.
     * @return self
     */
    public static function create_from_record(\stdClass $record, ?int $actorid = null): self {
        global $USER;
        $actorid = $actorid ?? (int)($USER->id ?? 0);
        return self::create([
            'context' => \context_system::instance(),
            'objectid' => (int)($record->id ?? 0),
            'userid' => $actorid,
            'other' => [
                'menu' => $record->menu ?? '',
                'nodekey' => $record->nodekey ?? '',
                'title' => (string)($record->title ?? ''),
                'url' => (string)($record->url ?? ''),
                'visibility' => (int)($record->visibility ?? 1),
                'parentkey' => (string)($record->parentkey ?? ''),
            ],
        ]);
    }
}
