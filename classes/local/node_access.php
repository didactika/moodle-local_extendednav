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

namespace local_extendednav\local;

/**
 * Decides which nodes a user can see.
 *
 * Visibility and roles decide whether a user is allowed a node. Languages only
 * decide whether an allowed node is shown in the menu; they never affect access
 * to the node's URL, because users can change their language themselves.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class node_access {
    /** @var int The node is not visible to anyone. */
    public const VISIBILITY_HIDDEN = 0;

    /** @var int The node is visible to everyone. */
    public const VISIBILITY_ALL = 1;

    /** @var int The node is visible to the selected roles only. */
    public const VISIBILITY_ROLES = 2;

    /** @var array Role shortnames per user id, loaded once per request. */
    private static array $userroles = [];

    /**
     * Clears the per-request role cache.
     */
    public static function reset_caches(): void {
        self::$userroles = [];
    }

    /**
     * Splits a comma-separated list into its trimmed, non-empty, unique values.
     *
     * @param string|null $value Comma-separated list.
     * @return string[]
     */
    public static function split_list(?string $value): array {
        $items = array_map('trim', explode(',', (string)$value));
        return array_values(array_unique(array_filter($items, static fn(string $item): bool => $item !== '')));
    }

    /**
     * Returns the shortnames of every role the user holds in any context.
     *
     * Logged-in users also hold the default role for authenticated users, which
     * Moodle gives them without a role assignment.
     *
     * @param int $userid User id.
     * @return string[]
     */
    public static function get_user_roles(int $userid): array {
        global $CFG, $DB;

        if (!array_key_exists($userid, self::$userroles)) {
            $roles = [];
            if ($userid > 0) {
                $sql = 'SELECT DISTINCT r.shortname
                          FROM {role} r
                          JOIN {role_assignments} ra ON ra.roleid = r.id
                         WHERE ra.userid = :userid';
                $roles = $DB->get_fieldset_sql($sql, ['userid' => $userid]);

                if (!empty($CFG->defaultuserroleid) && !isguestuser($userid)) {
                    $default = $DB->get_field('role', 'shortname', ['id' => $CFG->defaultuserroleid]);
                    if ($default !== false) {
                        $roles[] = $default;
                    }
                }
            }
            self::$userroles[$userid] = array_values(array_unique($roles));
        }
        return self::$userroles[$userid];
    }

    /**
     * Whether the node is protected against being hidden (anti-lockout).
     *
     * @param \stdClass $node Node record.
     * @return bool
     */
    public static function is_protected(\stdClass $node): bool {
        return $node->menu === 'primary' && $node->nodekey === 'siteadminnode';
    }

    /**
     * Whether visibility and roles allow the user this node.
     *
     * @param \stdClass $node Node record.
     * @param int|null $userid User id, defaults to the current user.
     * @return bool
     */
    public static function is_allowed(\stdClass $node, ?int $userid = null): bool {
        global $USER;

        if (self::is_protected($node)) {
            return true;
        }

        $visibility = (int)$node->visibility;
        if ($visibility === self::VISIBILITY_ALL) {
            return true;
        }
        if ($visibility !== self::VISIBILITY_ROLES) {
            return false;
        }

        $allowed = self::split_list($node->roles ?? '');
        if (empty($allowed)) {
            return false;
        }
        $userroles = self::get_user_roles($userid ?? (int)$USER->id);
        return !empty(array_intersect($allowed, $userroles));
    }

    /**
     * Whether the node's language list includes the language or one of its parents.
     *
     * An empty list means every language. Spanish (es) also covers Spanish - Mexico (es_mx).
     *
     * @param \stdClass $node Node record.
     * @param string|null $lang Language code, defaults to the current language.
     * @return bool
     */
    public static function language_matches(\stdClass $node, ?string $lang = null): bool {
        $langs = self::split_list($node->langs ?? '');
        if (empty($langs)) {
            return true;
        }

        foreach (self::get_language_chain($lang ?? current_language()) as $candidate) {
            if (in_array($candidate, $langs, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Whether the node is shown in the user's menu.
     *
     * @param \stdClass $node Node record.
     * @param int|null $userid User id, defaults to the current user.
     * @param string|null $lang Language code, defaults to the current language.
     * @return bool
     */
    public static function is_shown(\stdClass $node, ?int $userid = null, ?string $lang = null): bool {
        if (self::is_protected($node)) {
            return true;
        }
        return self::is_allowed($node, $userid) && self::language_matches($node, $lang);
    }

    /**
     * Returns the language followed by its parent languages, most specific first.
     *
     * @param string $lang Language code.
     * @return string[]
     */
    public static function get_language_chain(string $lang): array {
        $chain = [];
        $stringmanager = get_string_manager();
        while ($lang !== '' && !in_array($lang, $chain, true)) {
            $chain[] = $lang;
            $parent = '';
            if ($stringmanager->translation_exists($lang, false)) {
                $parent = (string)$stringmanager->get_string('parentlanguage', 'langconfig', null, $lang);
            }
            if ($parent === '' && strpos($lang, '_') !== false) {
                $parent = substr($lang, 0, strrpos($lang, '_'));
            }
            $lang = $parent;
        }
        return $chain;
    }
}
