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

namespace local_extendednav\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_extendednav\local\navigation\preferences_injector;

/**
 * Saves the order and the items a user hides on their own page Preferences.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class set_personal extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'hidden' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Key of a group or an item the user hides.'),
                'What the user hides.',
                VALUE_DEFAULT,
                []
            ),
            'order' => new external_multiple_structure(
                new external_single_structure([
                    'group' => new external_value(PARAM_TEXT, 'Key of the group, or empty for the order of the groups.'),
                    'keys' => new external_multiple_structure(new external_value(PARAM_TEXT, 'Key.'), 'Keys in order.'),
                ]),
                'The order the user gives the groups and the items of each one.',
                VALUE_DEFAULT,
                []
            ),
        ]);
    }

    /**
     * Saves what the current user chose. Empty lists take the page back to what the site shows.
     *
     * @param string[] $hidden Keys the user hides.
     * @param array $order The order of the groups and of the items of each one.
     * @return array The number of things hidden.
     */
    public static function execute(array $hidden = [], array $order = []): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), compact('hidden', 'order'));
        $context = \context_user::instance((int)$USER->id);
        self::validate_context($context);
        require_capability('local/extendednav:personalise', $context);

        $orders = [];
        foreach ($params['order'] as $entry) {
            $orders[(string)$entry['group']] = array_values(array_unique(array_map('strval', $entry['keys'])));
        }
        $hidden = array_values(array_unique(array_map('strval', $params['hidden'])));
        if (!$hidden && !$orders) {
            unset_user_preference(preferences_injector::PREFERENCE);
        } else {
            preferences_injector::set_personal((int)$USER->id, ['hidden' => $hidden, 'order' => $orders]);
        }
        return ['count' => count($hidden)];
    }

    /**
     * Describes the result.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'count' => new external_value(PARAM_INT, 'The number of groups and items hidden.'),
        ]);
    }
}
