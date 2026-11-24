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

namespace local_extendednav\external;

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_extendednav\local\listing\activity;

/**
 * One page of the activity log of a navigation, with the rows of each action.
 *
 * @package    local_extendednav
 * @category   external
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_activity extends node_service {
    /**
     * Describes the function parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'menu' => new external_value(PARAM_ALPHANUMEXT, 'Navigation key.'),
            'search' => new external_value(PARAM_RAW, 'Part of a node key or of the name of a person.', VALUE_DEFAULT, ''),
            'status' => new external_value(
                PARAM_TEXT,
                'Actions separated by commas: created, updated, deleted. Empty for all of them.',
                VALUE_DEFAULT,
                ''
            ),
            'datefrom' => new external_value(PARAM_ALPHANUMEXT, 'First day, as YYYY-MM-DD, or empty.', VALUE_DEFAULT, ''),
            'dateto' => new external_value(PARAM_ALPHANUMEXT, 'Last day, as YYYY-MM-DD, or empty.', VALUE_DEFAULT, ''),
            'direction' => new external_value(PARAM_ALPHA, 'asc or desc, by time.', VALUE_DEFAULT, 'desc'),
            'page' => new external_value(PARAM_INT, 'Page, from 0.', VALUE_DEFAULT, 0),
            'perpage' => new external_value(PARAM_INT, 'Rows per page, from 1 to 100, or 0 for all.', VALUE_DEFAULT, 12),
        ]);
    }

    /**
     * Returns one page of the log.
     *
     * @param string $menu Navigation key.
     * @param string $search Part of a node key or of a name.
     * @param string $status Actions separated by commas.
     * @param string $datefrom First day.
     * @param string $dateto Last day.
     * @param string $direction asc or desc.
     * @param int $page Page, from 0.
     * @param int $perpage Rows per page.
     * @return array
     */
    public static function execute(
        string $menu,
        string $search = '',
        string $status = '',
        string $datefrom = '',
        string $dateto = '',
        string $direction = 'desc',
        int $page = 0,
        int $perpage = 12
    ): array {
        $params = self::validate_parameters(
            self::execute_parameters(),
            compact('menu', 'search', 'status', 'datefrom', 'dateto', 'direction', 'page', 'perpage')
        );
        self::require_manage();
        self::validate_scope($params['menu'], 0);
        if ($params['page'] < 0 || $params['perpage'] < 0 || $params['perpage'] > activity::MAX_PAGE_SIZE) {
            throw new \invalid_parameter_exception('Page must be non-negative and perpage between 0 and 100.');
        }
        return activity::get(
            $params['menu'],
            $params['search'],
            $params['status'],
            $params['datefrom'],
            $params['dateto'],
            $params['direction'],
            $params['page'],
            $params['perpage']
        );
    }

    /**
     * Describes the function result.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        $text = static fn(string $description): external_value => new external_value(PARAM_RAW, $description);
        return new external_single_structure([
            'total' => new external_value(PARAM_INT, 'Rows that match the search and the filters.'),
            'counts' => new external_single_structure([
                'created' => new external_value(PARAM_INT, 'Rows of nodes created.'),
                'updated' => new external_value(PARAM_INT, 'Rows of nodes changed.'),
                'deleted' => new external_value(PARAM_INT, 'Rows of nodes deleted.'),
            ]),
            'haslog' => new external_value(PARAM_BOOL, 'Whether the standard log records the events.'),
            'rows' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Id of the record of the log.'),
                'time' => $text('When it happened.'),
                'user' => $text('Who did it, or empty.'),
                'userurl' => $text('Profile of that person, or empty.'),
                'action' => new external_value(PARAM_ALPHA, 'created, updated or deleted.'),
                'actionlabel' => $text('Name of the action.'),
                'tone' => new external_value(PARAM_ALPHA, 'Colour of the badge of the action.'),
                'nodekey' => new external_value(PARAM_ALPHANUMEXT, 'Key of the node.'),
                'editurl' => $text('Form of the node, while it exists, or empty.'),
                'ip' => $text('IP address.'),
            ])),
        ]);
    }
}
