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

use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_extendednav\local\navigation\discovery;
use local_extendednav\menu\activity;

/**
 * Looks at the activities of one kind for the items of their navigation, when a manager asks for it from the list of that
 * kind. A short look (the most recent activities, for a few seconds); the task of every night looks further.
 *
 * @package    local_extendednav
 * @category   external
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class discover_nodes extends node_service {
    /** @var int Activities looked at from the page. */
    private const LIMIT = 30;

    /** @var float Seconds spent at most from the page. */
    private const BUDGET = 8.0;

    /**
     * Describes the function parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'menu' => new external_value(PARAM_ALPHANUMEXT, 'Navigation of a kind of activity, such as mod_forum.'),
        ]);
    }

    /**
     * Looks at the activities of the kind.
     *
     * @param string $menu Navigation key.
     * @return array
     */
    public static function execute(string $menu): array {
        $params = self::validate_parameters(self::execute_parameters(), compact('menu'));
        self::require_manage();
        $kind = activity::from_key($params['menu']);
        if ($kind === null) {
            throw new \invalid_parameter_exception('Not the navigation of a kind of activity.');
        }
        return discovery::kind($kind->get_modname(), self::LIMIT, self::BUDGET);
    }

    /**
     * Describes the function result.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'activities' => new external_value(PARAM_INT, 'Activities looked at.'),
            'found' => new external_value(PARAM_INT, 'Items new to the navigation of the kind.'),
        ]);
    }
}
