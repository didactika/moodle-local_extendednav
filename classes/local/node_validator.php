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

use local_extendednav\menu_manager;

/**
 * Validation rules shared by the node form, the YAML import and the config sync.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class node_validator {
    /** @var string Pattern of a FontAwesome icon, such as "fa-book" or "fa-solid fa-house". */
    private const ICON_FONTAWESOME = '~^(fa[a-z]?(-[a-z]+)?\s+)?fa-[a-z0-9-]+(\s+fa-[a-z0-9-]+)*$~';

    /** @var string Pattern of a Moodle pix icon, such as "i/home". */
    private const ICON_PIX = '~^[a-z0-9_-]+(/[a-z0-9_-]+)+$~';

    /** @var string[] Icon values that explicitly remove the icon. */
    private const ICON_NONE = ['none', 'null'];

    /**
     * Validates one node.
     *
     * @param \stdClass $node Node as it would be saved (see {@see node_manager::prepare()}).
     * @param \stdClass[] $others The other nodes of the same menu, keyed by nodekey.
     * @return array With keys 'errors' (field => message) and 'warnings' (list of messages).
     */
    public static function validate(\stdClass $node, array $others): array {
        $errors = [];
        $warnings = [];

        $menu = menu_manager::get_menu((string)$node->menu);
        if ($menu === null) {
            $errors['menu'] = get_string('err_invalid_menu', 'local_extendednav');
            return ['errors' => $errors, 'warnings' => $warnings];
        }

        $nodekey = (string)$node->nodekey;
        $isnative = $menu->is_native($nodekey);
        if ($nodekey === '') {
            $errors['nodekey'] = get_string('required');
        } else if (clean_param($nodekey, PARAM_ALPHANUMEXT) !== $nodekey) {
            $errors['nodekey'] = get_string('err_invalid_nodekey', 'local_extendednav');
        } else if (\core_text::strlen($nodekey) > 50) {
            $errors['nodekey'] = get_string('maximumchars', '', 50);
        } else if (isset($others[$nodekey])) {
            $errors['nodekey'] = get_string('err_duplicate_key', 'local_extendednav');
        }

        $title = (string)($node->title ?? '');
        if ($title === '' && !$isnative) {
            $errors['title'] = get_string('required');
        } else if (\core_text::strlen($title) > 255) {
            $errors['title'] = get_string('maximumchars', '', 255);
        } else if ($menu->get_key() === 'usermenu' && preg_match('~[|\r\n]~', $title)) {
            $errors['title'] = get_string('err_title_usermenu_chars', 'local_extendednav');
        }

        if (!isset($errors['title']) && clean_param($title, PARAM_TEXT) !== $title) {
            $errors['title'] = get_string('err_text_html', 'local_extendednav');
        }

        $tooltip = (string)($node->tooltip ?? '');
        if (\core_text::strlen($tooltip) > 255) {
            $errors['tooltip'] = get_string('maximumchars', '', 255);
        } else if (clean_param($tooltip, PARAM_TEXT) !== $tooltip) {
            $errors['tooltip'] = get_string('err_text_html', 'local_extendednav');
        }

        $url = (string)($node->url ?? '');
        if ($url === '' && !$isnative) {
            $errors['url'] = get_string('required');
        } else if ($url !== '') {
            if (url_helper::has_forbidden_scheme($url)) {
                $errors['url'] = get_string('err_url_forbidden', 'local_extendednav');
            } else if (preg_match('~[\s<>"]~', $url) || clean_param($url, PARAM_URL) === '') {
                $errors['url'] = get_string('err_url_invalid', 'local_extendednav');
            }
        }

        $icon = (string)($node->icon ?? '');
        if ($icon !== '' && !self::is_valid_icon($icon)) {
            $errors['icon'] = get_string('err_icon_invalid', 'local_extendednav');
        }

        $visibility = (int)$node->visibility;
        if (
            !in_array($visibility, [node_access::VISIBILITY_HIDDEN, node_access::VISIBILITY_ALL,
                node_access::VISIBILITY_ROLES], true)
        ) {
            $errors['visibility'] = get_string('err_invalid_visibility', 'local_extendednav');
        }

        $roles = node_access::split_list($node->roles ?? '');
        foreach ($roles as $role) {
            if (clean_param($role, PARAM_ALPHANUMEXT) !== $role) {
                $errors['roles'] = get_string('err_invalid_list', 'local_extendednav');
            }
        }
        if (!isset($errors['roles']) && $visibility === node_access::VISIBILITY_ROLES) {
            if (empty($roles)) {
                $errors['roles'] = get_string('required');
            } else {
                foreach (self::get_unknown_roles($roles) as $unknown) {
                    $warnings[] = get_string('warn_unknown_role', 'local_extendednav', $unknown);
                }
            }
        }

        $installed = get_string_manager()->get_list_of_translations();
        foreach (node_access::split_list($node->langs ?? '') as $lang) {
            if (!preg_match('/^[a-z0-9_]+$/', $lang)) {
                $errors['langs'] = get_string('err_invalid_list', 'local_extendednav');
            } else if (!isset($installed[$lang])) {
                $warnings[] = get_string('warn_unknown_lang', 'local_extendednav', $lang);
            }
        }

        $errors += self::validate_parent($node, $others);

        if (node_access::is_protected($node)) {
            if (
                $visibility !== node_access::VISIBILITY_ALL || !empty($node->blockaccess)
                    || !empty(node_access::split_list($node->langs ?? ''))
            ) {
                $errors['visibility'] = get_string('err_admin_hide', 'local_extendednav');
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Whether the icon is a FontAwesome class, a Moodle pix icon or "none".
     *
     * @param string $icon Icon value.
     * @return bool
     */
    public static function is_valid_icon(string $icon): bool {
        $icon = trim($icon);
        return in_array(strtolower($icon), self::ICON_NONE, true)
            || preg_match(self::ICON_FONTAWESOME, $icon)
            || preg_match(self::ICON_PIX, $icon);
    }

    /**
     * Whether the icon value explicitly removes the icon.
     *
     * @param string|null $icon Icon value.
     * @return bool
     */
    public static function is_no_icon(?string $icon): bool {
        return in_array(strtolower(trim((string)$icon)), self::ICON_NONE, true);
    }

    /**
     * Whether the icon value is a FontAwesome class.
     *
     * @param string|null $icon Icon value.
     * @return bool
     */
    public static function is_fontawesome(?string $icon): bool {
        return (bool)preg_match(self::ICON_FONTAWESOME, trim((string)$icon));
    }

    /**
     * Returns the role shortnames that do not exist on this site.
     *
     * @param string[] $shortnames Role shortnames.
     * @return string[]
     */
    public static function get_unknown_roles(array $shortnames): array {
        global $DB;

        if (empty($shortnames)) {
            return [];
        }
        $existing = $DB->get_fieldset_sql('SELECT shortname FROM {role}');
        return array_values(array_diff($shortnames, $existing));
    }

    /**
     * Validates the parent of a node: hierarchy depth and anti-lockout rules.
     *
     * @param \stdClass $node Node as it would be saved.
     * @param \stdClass[] $others The other nodes of the same menu, keyed by nodekey.
     * @return array Errors keyed by field.
     */
    private static function validate_parent(\stdClass $node, array $others): array {
        $nodekey = (string)$node->nodekey;
        $parentkey = (string)($node->parentkey ?? '');
        $haschildren = false;
        foreach ($others as $other) {
            if ((string)($other->parentkey ?? '') === $nodekey) {
                $haschildren = true;
                break;
            }
        }

        $isadmin = node_access::is_protected($node);
        if ($isadmin && $haschildren) {
            return ['parentkey' => get_string('err_admin_parent', 'local_extendednav')];
        }
        if ($parentkey === '') {
            return [];
        }
        if ($isadmin) {
            return ['parentkey' => get_string('err_admin_child', 'local_extendednav')];
        }
        if ($parentkey === $nodekey) {
            return ['parentkey' => get_string('err_parent_self', 'local_extendednav')];
        }

        $menu = menu_manager::get_menu((string)$node->menu);
        $parent = $others[$parentkey] ?? null;
        if ($parent === null && !$menu->is_native($parentkey)) {
            return ['parentkey' => get_string('err_parent_missing', 'local_extendednav', s($parentkey))];
        }
        if ($node->menu === 'primary' && $parentkey === 'siteadminnode') {
            return ['parentkey' => get_string('err_admin_parent', 'local_extendednav')];
        }
        if ($parent !== null && (string)($parent->parentkey ?? '') !== '') {
            return ['parentkey' => get_string('err_parent_thirdlevel', 'local_extendednav')];
        }
        if ($haschildren) {
            return ['parentkey' => get_string('err_parent_haschildren', 'local_extendednav')];
        }
        return [];
    }
}
