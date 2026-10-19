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
 * Resolves node titles.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class node_text {
    /**
     * Converts a "identifier,component" title to the language string it names.
     *
     * This is the same format Moodle's custom menu settings accept (for example
     * "profile,moodle"). Any other text is returned unchanged.
     *
     * @param string|null $text Title as stored.
     * @return string
     */
    public static function resolve(?string $text): string {
        $text = trim((string)$text);
        if ($text === '') {
            return '';
        }

        $bits = explode(',', $text, 2);
        if (count($bits) !== 2) {
            return $text;
        }

        $identifier = trim($bits[0]);
        $component = trim($bits[1]) ?: 'core';
        if (clean_param($identifier, PARAM_STRINGID) === '' || clean_param($component, PARAM_COMPONENT) === '') {
            return $text;
        }
        if (!get_string_manager()->string_exists($identifier, $component)) {
            return $text;
        }
        return get_string($identifier, $component);
    }

    /**
     * Returns the title a node shows, falling back to the core title of a core node.
     *
     * @param \stdClass $node Node record.
     * @param \stdClass|null $native Core node definition, if the node is a core node.
     * @return string Resolved, unformatted title.
     */
    public static function get_title(\stdClass $node, ?\stdClass $native = null): string {
        $title = self::resolve($node->title ?? '');
        if ($title === '' && $native !== null) {
            $title = self::resolve($native->text);
        }
        return $title;
    }
}
