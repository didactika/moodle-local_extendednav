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

/**
 * Privacy provider for local_extendednav
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_extendednav\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;
use local_extendednav\local\navigation\preferences_injector;

/**
 * Privacy API provider for local_extendednav.
 *
 * The plugin keeps one thing of each user: how they arranged their own page Preferences, as a user preference.
 *
 * @copyright  2026 Didactika
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\user_preference_provider {
    /**
     * Describes the data the plugin keeps.
     *
     * @param collection $collection The collection to add to.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference(preferences_injector::PREFERENCE, 'privacy:metadata:preference');
        return $collection;
    }

    /**
     * Exports how a user arranged their own page Preferences.
     *
     * @param int $userid The user.
     */
    public static function export_user_preferences(int $userid): void {
        $value = get_user_preferences(preferences_injector::PREFERENCE, null, $userid);
        if ($value !== null) {
            writer::export_user_preference(
                'local_extendednav',
                preferences_injector::PREFERENCE,
                $value,
                get_string('privacy:metadata:preference', 'local_extendednav')
            );
        }
    }
}
