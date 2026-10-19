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
 * The icons offered by the icon picker: Moodle's own icons and the FontAwesome icons Moodle ships.
 *
 * Moodle's icons come from its live FontAwesome icon map, which includes the icons of plugins. Moodle has no list
 * of the FontAwesome icons it ships, so they are read from the FontAwesome SCSS variables bundled with Moodle: every
 * name listed there has its glyph in the fonts Moodle serves. Names that share a glyph are listed once, with the
 * other names as aliases.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class icon_catalogue {
    /** @var string[] Where Moodle keeps the FontAwesome SCSS variables: before 5.1 in the Boost theme, then in lib/bundles. */
    private const VARIABLES = [
        'dirroot' => '/theme/boost/scss/fontawesome/_variables.scss',
        'root' => '/lib/bundles/fontawesome/scss/_variables.scss',
    ];

    /**
     * Returns every icon of the picker, cached until the next upgrade.
     *
     * @return array With 'moodle', 'fontawesome' and 'brands' lists of icons, each with value (what the icon field
     *               stores), classes (of the element that shows it), label and aliases.
     */
    public static function get_all(): array {
        $cache = \cache::make('local_extendednav', 'icons');
        $icons = $cache->get('catalogue');
        if (!is_array($icons)) {
            $icons = self::build();
            $cache->set('catalogue', $icons);
        }
        return $icons;
    }

    /**
     * Builds the lists.
     *
     * @return array As {@see get_all()}.
     */
    public static function build(): array {
        $variables = self::read_variables();
        return [
            'moodle' => self::get_moodle_icons(),
            'fontawesome' => $variables === null ? [] : self::get_fontawesome_icons($variables, 'fa-icons', ''),
            'brands' => $variables === null ? [] : self::get_fontawesome_icons($variables, 'fa-brand-icons', 'fa-brands'),
        ];
    }

    /**
     * Returns Moodle's icons from its FontAwesome icon map, so they look as on Moodle's pages.
     *
     * @return array[] Icons sorted by value.
     */
    private static function get_moodle_icons(): array {
        $map = \core\output\icon_system::instance(\core\output\icon_system::FONTAWESOME)->get_icon_name_map();
        $icons = [];
        foreach ($map as $key => $classes) {
            if (strpos((string)$classes, 'deprecated') !== false || !node_validator::is_valid_icon($key)) {
                continue;
            }
            $icons[] = ['value' => $key, 'classes' => self::markup_classes((string)$classes), 'label' => $key, 'aliases' => []];
        }
        usort($icons, static fn(array $a, array $b): int => strcmp($a['value'], $b['value']));
        return $icons;
    }

    /**
     * Returns the icons of one FontAwesome list, one for each glyph.
     *
     * @param string $variables Content of the FontAwesome SCSS variables.
     * @param string $list Name of the SCSS map: fa-icons or fa-brand-icons.
     * @param string $family Family class the field must store, empty for the default (solid) family.
     * @return array[] Icons sorted by label.
     */
    private static function get_fontawesome_icons(string $variables, string $list, string $family): array {
        preg_match_all('/^\$fa-var-([a-z0-9-]+):\s*\\\\([0-9a-f]+)/m', $variables, $matches, PREG_SET_ORDER);
        $codepoints = [];
        foreach ($matches as $match) {
            $codepoints[$match[1]] = $match[2];
        }
        if (!preg_match('/^\$' . preg_quote($list, '/') . ': \((.*?)^\);/ms', $variables, $body)) {
            return [];
        }
        preg_match_all('/"([a-z0-9-]+)": \$fa-var-([a-z0-9-]+)/', $body[1], $entries, PREG_SET_ORDER);

        $byglyph = [];
        foreach ($entries as [, $name, $variable]) {
            $glyph = $codepoints[$variable] ?? $variable;
            $byglyph[$glyph][] = $name;
        }
        $icons = [];
        foreach ($byglyph as $names) {
            // The SCSS lists the current name of an icon first and its older names after it.
            $name = array_shift($names);
            $value = trim($family . ' fa-' . $name);
            $icons[] = [
                'value' => $value,
                'classes' => 'icon fa ' . $value . ' fa-fw',
                'label' => $name,
                'aliases' => array_values($names),
            ];
        }
        usort($icons, static fn(array $a, array $b): int => strcmp($a['label'], $b['label']));
        return $icons;
    }

    /**
     * Reads the FontAwesome SCSS variables bundled with this Moodle version.
     *
     * @return string|null Content, or null when this Moodle version keeps them elsewhere.
     */
    private static function read_variables(): ?string {
        global $CFG;

        foreach (self::VARIABLES as $base => $path) {
            $file = ($CFG->$base ?? '') === '' ? '' : $CFG->$base . $path;
            if ($file !== '' && is_readable($file)) {
                return (string)file_get_contents($file);
            }
        }
        return null;
    }

    /**
     * Returns the classes core writes for a mapped icon.
     *
     * @param string $mapped Classes from the icon map, such as "fa-house" or "fa-regular fa-bell".
     * @return string
     */
    private static function markup_classes(string $mapped): string {
        $tokens = preg_split('/\s+/', trim($mapped));
        $prefix = array_intersect($tokens, node_icon::FAMILIES) === [] ? 'fa ' : '';
        return 'icon ' . $prefix . implode(' ', $tokens) . ' fa-fw';
    }
}
