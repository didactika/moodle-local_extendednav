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

namespace local_extendednav\local\node;

/**
 * What a change did to a node: the fields that changed, with their value before and after, as the events keep them and the
 * activity log shows them.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class changes {
    /** @var string[] The fields of a node a person changes, in the order the log lists them. */
    public const FIELDS = ['title', 'url', 'icon', 'tooltip', 'visibility', 'roles', 'langs', 'blockaccess', 'hideadmins',
        'availability', 'parentkey', 'placement', 'display', 'popupwidth', 'popupheight'];

    /** @var int Longest value kept for a field, so an event stays small. */
    private const MAX_VALUE = 500;

    /**
     * Lists the fields that differ between two versions of a node. A node created has no version before, and a node deleted
     * none after.
     *
     * @param \stdClass|null $before The node before the change.
     * @param \stdClass|null $after The node after the change.
     * @return array Lists of three values: the field, the value before and the value after, as text.
     */
    public static function between(?\stdClass $before, ?\stdClass $after): array {
        $changes = [];
        foreach (self::FIELDS as $field) {
            $was = self::value($before, $field);
            $now = self::value($after, $field);
            if ($was !== $now) {
                $changes[] = [$field, $was, $now];
            }
        }
        return $changes;
    }

    /**
     * Describes the changes in words, one line for each field.
     *
     * @param array $changes Changes as {@see between} returns them.
     * @return array Each with field (its name), before and after, in words.
     */
    public static function describe(array $changes): array {
        $lines = [];
        foreach ($changes as $change) {
            if (!is_array($change) || count($change) !== 3 || !in_array($change[0], self::FIELDS, true)) {
                continue;
            }
            [$field, $was, $now] = $change;
            $lines[] = [
                'field' => self::field_name($field),
                'before' => self::words($field, (string)$was),
                'after' => self::words($field, (string)$now),
            ];
        }
        return $lines;
    }

    /**
     * Reads a field of a node as text, as the database gives it.
     *
     * @param \stdClass|null $node The node.
     * @param string $field The field.
     * @return string
     */
    private static function value(?\stdClass $node, string $field): string {
        if ($node === null || !isset($node->$field)) {
            return '';
        }
        return \core_text::substr(trim((string)$node->$field), 0, self::MAX_VALUE);
    }

    /**
     * Returns the name of a field as the form of a node says it.
     *
     * @param string $field The field.
     * @return string
     */
    private static function field_name(string $field): string {
        return match ($field) {
            'roles' => get_string('roles', 'local_extendednav'),
            'availability' => get_string('accessrestrictions', 'availability'),
            'parentkey' => get_string('parentkey', 'local_extendednav'),
            default => get_string($field, 'local_extendednav'),
        };
    }

    /**
     * Says a value of a field in words.
     *
     * @param string $field The field.
     * @param string $value The value, as text.
     * @return string
     */
    private static function words(string $field, string $value): string {
        if ($value === '') {
            return get_string('change_empty', 'local_extendednav');
        }
        switch ($field) {
            case 'visibility':
                $keys = [access::VISIBILITY_ALL => 'vis_all', access::VISIBILITY_ROLES => 'vis_roles',
                    access::VISIBILITY_HIDDEN => 'vis_hidden'];
                return isset($keys[(int)$value]) ? get_string($keys[(int)$value], 'local_extendednav') : $value;
            case 'blockaccess':
            case 'hideadmins':
                return get_string((int)$value ? 'yes' : 'no');
            case 'placement':
                return get_string([0 => 'placement_default', 1 => 'placement_more', 2 => 'placement_main'][(int)$value]
                    ?? 'placement_default', 'local_extendednav');
            case 'display':
                return display::get_options()[(int)$value] ?? $value;
            case 'availability':
                $described = availability::describe((object)['availability' => $value]);
                return $described !== '' ? $described : get_string('change_set', 'local_extendednav');
            case 'roles':
            case 'langs':
                return implode(', ', access::split_list($value));
            default:
                return $value;
        }
    }
}
