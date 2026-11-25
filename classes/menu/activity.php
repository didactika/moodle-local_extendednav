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
 * The secondary navigation of one kind of activity: forums, assignments, quizzes... Each kind has its own items, from Moodle
 * and from the plugins that extend it (the report of an assignment, the subscriptions of a forum), so each kind is a
 * navigation of its own, with the key mod_ and the name of the module ("mod_forum").
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class activity extends secondary {
    /** @var string Start of the key of the navigation of a kind of activity. */
    public const PREFIX = 'mod_';

    /** @var string Key of the single navigation of every activity, before each kind had its own. */
    public const LEGACY = 'activity';

    /** @var string[]|null Names of the modules of the site, by name, for the request. */
    private static ?array $types = null;

    /**
     * Creates the navigation of one kind of activity.
     *
     * @param string $modname Name of the module, such as forum.
     */
    public function __construct(
        /** @var string $modname Name of the module. */
        private readonly string $modname
    ) {
        parent::__construct(self::PREFIX . $modname);
    }

    /**
     * Returns the navigation of a key, or null when the key is not one of a kind of activity of the site.
     *
     * @param string $key A navigation key, such as mod_forum.
     * @return self|null
     */
    public static function from_key(string $key): ?self {
        if (!str_starts_with($key, self::PREFIX)) {
            return null;
        }
        $modname = substr($key, strlen(self::PREFIX));
        return isset(self::types()[$modname]) ? new self($modname) : null;
    }

    /**
     * Returns the key of the navigation of a kind of activity.
     *
     * @param string $modname Name of the module.
     * @return string
     */
    public static function key_for(string $modname): string {
        return self::PREFIX . $modname;
    }

    /**
     * Returns the kinds of activity of the site that are turned on, by name of the module, with their name in the language of
     * the user, in alphabetical order of that name.
     *
     * @return string[]
     */
    public static function types(): array {
        global $DB;

        if (self::$types === null) {
            $types = [];
            $modnames = $DB->get_fieldset_select('modules', 'name', 'visible = 1');
            foreach ($modnames as $modname) {
                if (\core_component::get_component_directory('mod_' . $modname) !== null) {
                    $types[$modname] = get_string('modulename', 'mod_' . $modname);
                }
            }
            \core_collator::asort($types);
            self::$types = $types;
        }
        return self::$types;
    }

    /**
     * Forgets the kinds of activity read, for the tests and after a module is turned on or off.
     */
    public static function reset(): void {
        self::$types = null;
    }

    /**
     * Returns the name of the module.
     *
     * @return string
     */
    public function get_modname(): string {
        return $this->modname;
    }

    /**
     * Returns the name of the navigation: the name of the kind of activity.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('activity_navigation_of', 'local_extendednav', self::types()[$this->modname] ?? $this->modname);
    }

    /**
     * Returns the short name of the navigation, as the column of the navigations lists it under Activities.
     *
     * @return string
     */
    public function get_short_name(): string {
        return self::types()[$this->modname] ?? $this->modname;
    }
}
