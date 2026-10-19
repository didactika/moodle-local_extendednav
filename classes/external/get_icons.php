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
use local_extendednav\local\icon_catalogue;

/**
 * Lists the icons of the icon picker. The list is the same for everyone, so the browser loads it once and searches it.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_icons extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Returns the icons.
     *
     * @return array
     */
    public static function execute(): array {
        self::validate_parameters(self::execute_parameters(), []);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/extendednav:manage', $context);

        return icon_catalogue::get_all();
    }

    /**
     * Describes the return value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        $list = static fn(string $description): external_multiple_structure => new external_multiple_structure(
            new external_single_structure([
                'value' => new external_value(PARAM_TEXT, 'What the icon field stores'),
                'classes' => new external_value(PARAM_TEXT, 'Classes of the element that shows the icon'),
                'label' => new external_value(PARAM_TEXT, 'Name of the icon'),
                'aliases' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Other name of the icon'),
                    'Other names of the icon, for the search'
                ),
            ]),
            $description
        );
        return new external_single_structure([
            'moodle' => $list('Moodle icons, from its icon map'),
            'fontawesome' => $list('FontAwesome icons, empty when this Moodle version keeps the list elsewhere'),
            'brands' => $list('FontAwesome brand icons, empty when this Moodle version keeps the list elsewhere'),
        ]);
    }
}
