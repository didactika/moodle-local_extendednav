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
 * Shows the icon of a node: a FontAwesome class, or a Moodle icon such as "core:i/home" (or "i/home" for core).
 *
 * Moodle icons are shown as the FontAwesome icon Moodle maps them to, so they have the same size as the other
 * menu icons; icons without one (image icons) are shown by Moodle.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class node_icon {
    /** @var string[] FontAwesome family classes, which mean the icon needs no default family. */
    public const FAMILIES = ['fa', 'fas', 'far', 'fab', 'fa-solid', 'fa-regular', 'fa-brands'];

    /**
     * Renders the icon.
     *
     * @param string|null $icon Icon value of the node.
     * @return string HTML, or an empty string when there is no icon.
     */
    public static function render(?string $icon): string {
        global $OUTPUT;

        $icon = trim((string)$icon);
        if ($icon === '' || node_validator::is_no_icon($icon) || !node_validator::is_valid_icon($icon)) {
            return '';
        }
        if (node_validator::is_fontawesome($icon)) {
            return \html_writer::tag('i', '', ['class' => 'icon fa ' . $icon . ' fa-fw', 'aria-hidden' => 'true']);
        }
        // Use the FontAwesome icon Moodle maps it to, at menu size: pix_icon() shows "b/" icons 64 pixels big.
        $classes = self::get_fontawesome_classes($icon);
        if ($classes !== null) {
            $tokens = preg_split('/\s+/', $classes);
            $family = array_intersect($tokens, self::FAMILIES) === [] ? 'fa ' : '';
            return \html_writer::tag('i', '', ['class' => 'icon ' . $family . $classes . ' fa-fw', 'aria-hidden' => 'true']);
        }
        [$component, $name] = self::split($icon);
        try {
            return $OUTPUT->pix_icon($name, '', $component === 'core' ? 'moodle' : $component);
        } catch (\moodle_exception $e) {
            return '';
        }
    }

    /**
     * Returns the FontAwesome classes of the icon, for menus that can only show FontAwesome icons.
     *
     * Moodle icons are turned into the classes Moodle's icon map gives them.
     *
     * @param string|null $icon Icon value of the node.
     * @return string|null Classes, or null when the icon has none.
     */
    public static function get_fontawesome_classes(?string $icon): ?string {
        $icon = trim((string)$icon);
        if ($icon === '' || node_validator::is_no_icon($icon) || !node_validator::is_valid_icon($icon)) {
            return null;
        }
        if (node_validator::is_fontawesome($icon)) {
            return $icon;
        }
        [$component, $name] = self::split($icon);
        $map = \core\output\icon_system::instance(\core\output\icon_system::FONTAWESOME)->get_icon_name_map();
        $classes = trim((string)($map[$component . ':' . $name] ?? ''));
        return $classes === '' ? null : $classes;
    }

    /**
     * Splits a Moodle icon into its component and name.
     *
     * @param string $icon Moodle icon, such as "core:i/home", "mod_forum:monologo" or "i/home".
     * @return string[] Component and name.
     */
    private static function split(string $icon): array {
        if (strpos($icon, ':') === false) {
            return ['core', $icon];
        }
        return explode(':', $icon, 2);
    }
}
