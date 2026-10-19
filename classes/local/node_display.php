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
 * How a node's link opens: in the current window, a new window or a pop-up, as Moodle's URL resource offers.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class node_display {
    /** @var int The link opens in the current window. */
    public const CURRENT = 0;

    /** @var int The link opens in a new window. */
    public const NEW_WINDOW = 1;

    /** @var int The link opens in a pop-up window. */
    public const POPUP = 2;

    /** @var int Default pop-up width, as in Moodle's URL resource. */
    public const DEFAULT_WIDTH = 620;

    /** @var int Default pop-up height, as in Moodle's URL resource. */
    public const DEFAULT_HEIGHT = 450;

    /** @var int Smallest pop-up width or height. */
    public const MIN_SIZE = 100;

    /** @var int Largest pop-up width or height. */
    public const MAX_SIZE = 9999;

    /**
     * Returns the display options for a select element.
     *
     * @return string[] Keyed by display value.
     */
    public static function get_options(): array {
        return [
            self::CURRENT => get_string('display_current', 'local_extendednav'),
            self::NEW_WINDOW => get_string('display_new', 'local_extendednav'),
            self::POPUP => get_string('display_popup', 'local_extendednav'),
        ];
    }

    /**
     * Whether the value is a display option.
     *
     * @param int $display Display value.
     * @return bool
     */
    public static function is_valid(int $display): bool {
        return in_array($display, [self::CURRENT, self::NEW_WINDOW, self::POPUP], true);
    }

    /**
     * Whether the value is an allowed pop-up width or height.
     *
     * @param int|null $size Size in pixels.
     * @return bool
     */
    public static function is_valid_size(?int $size): bool {
        return $size !== null && $size >= self::MIN_SIZE && $size <= self::MAX_SIZE;
    }

    /**
     * Returns the attributes the local_extendednav/link_display module reads, or none for the current window.
     *
     * @param \stdClass $node Node record.
     * @return string[]
     */
    public static function get_link_attributes(\stdClass $node): array {
        $display = (int)($node->display ?? self::CURRENT);
        if ($display === self::NEW_WINDOW) {
            return ['data-extnav-display' => 'new'];
        }
        if ($display === self::POPUP) {
            return [
                'data-extnav-display' => 'popup',
                'data-extnav-width' => (string)(int)($node->popupwidth ?? self::DEFAULT_WIDTH),
                'data-extnav-height' => (string)(int)($node->popupheight ?? self::DEFAULT_HEIGHT),
            ];
        }
        return [];
    }
}
