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

namespace local_extendednav\menu;

/**
 * The profile of a user: the boxes Moodle and the plugins show on it (User details, Course details, Reports...), to which the
 * site adds links of its own. Moodle places each box and each of its items after another one, so the boxes of Moodle are
 * kept as they are: the site adds items to them, ordered among themselves, for the users each one is meant for.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profile extends preferences {
    /** @var string Key of the navigation. */
    public const KEY = 'profile';

    /** @var string Plugin setting that keeps the boxes found. */
    protected const STORED = 'nativenodes_profile';

    /**
     * Returns the name of the navigation.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('profile_navigation', 'local_extendednav');
    }

    /**
     * Returns the short name of the navigation.
     *
     * @return string
     */
    public function get_short_name(): string {
        return get_string('profile');
    }
}
