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
 * Primary navigation hooks.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_extendednav;

use core\hook\navigation\primary_extend;
use core_user\hook\extend_user_menu;
use core\hook\output\before_standard_top_of_body_html_generation;

/**
 * Hooks for primary and user navigation.
 */
class hooks {
        /** @var bool Skip hook loop barrier. */
    public static $skiphook = false;

    /**
     * Extends the primary navigation based on custom DB configuration.
     *
     * @param primary_extend $hook The navigation extension hook.
     */
    public static function extend_primary_navigation(primary_extend $hook): void {
        global $DB;

        if (self::$skiphook || !get_config('local_extendednav', 'enable_plugin')) {
            return;
        }

        if (isloggedin() && !isguestuser()) {
            $cache = \cache::make('local_extendednav', 'nodes');
            $customnodes = $cache->get('primarynodes');

            if ($customnodes === false) {
                try {
                    $customnodes = $DB->get_records('local_extendednav', ['menu' => 'primary'], 'sortorder ASC, id ASC');
                } catch (\moodle_exception $e) {
                    $customnodes = [];
                }
                $cache->set('primarynodes', $customnodes);
            }

            $primarymenu = \local_extendednav\menu_manager::get_menu('primary');
            if ($primarymenu) {
                $primarymenu->inject_nodes($customnodes, $hook);
            }
        }
    }

    /**
     * Extends the user menu based on custom DB configuration.
     *
     * @param extend_user_menu $hook The user menu extension hook.
     */
    public static function extend_user_menu(extend_user_menu $hook): void {
        global $DB;

        if (self::$skiphook || !get_config('local_extendednav', 'enable_plugin')) {
            return;
        }

        if (isloggedin() && !isguestuser()) {
            $cache = \cache::make('local_extendednav', 'nodes');
            $customnodes = $cache->get('usermenunodes');

            if ($customnodes === false) {
                try {
                    $customnodes = $DB->get_records('local_extendednav', ['menu' => 'usermenu'], 'sortorder ASC, id ASC');
                } catch (\moodle_exception $e) {
                    $customnodes = [];
                }
                $cache->set('usermenunodes', $customnodes);
            }

            $usermenu = \local_extendednav\menu_manager::get_menu('usermenu');
            if ($usermenu) {
                $usermenu->inject_nodes_via_hook($customnodes, $hook);
            }
        }
    }

    /**
     * Injects JS and submenu templates into the user menu carousel.
     *
     * @param before_standard_top_of_body_html_generation $hook
     */
    public static function before_top_of_body(\core\hook\output\before_standard_top_of_body_html_generation $hook): void {
        global $PAGE, $USER, $DB;

        if (!isloggedin() || isguestuser() || !get_config('local_extendednav', 'enable_plugin')) {
            return;
        }

        $cache = \cache::make('local_extendednav', 'nodes');
        $customnodes = $cache->get('usermenunodes');

        if ($customnodes === false) {
            try {
                $customnodes = $DB->get_records('local_extendednav', ['menu' => 'usermenu'], 'sortorder ASC, id ASC');
            } catch (\moodle_exception $e) {
                $customnodes = [];
            }
            $cache->set('usermenunodes', $customnodes);
        }

        if (empty($customnodes)) {
            return;
        }

        // Group nodes by parent.
        $childrenbyparent = [];
        $allowedparents = [];
        foreach ($customnodes as $umnode) {
            $allowed = true;
            if ($umnode->visibility == 0) {
                $allowed = false;
            } else if ($umnode->visibility == 2) {
                $allowed = false;
                if (!empty($umnode->roles)) {
                    $roleids = explode(',', $umnode->roles);
                    foreach ($roleids as $rid) {
                        if (!empty($rid) && user_has_role_assignment($USER->id, (int)$rid)) {
                            $allowed = true;
                            break;
                        }
                    }
                }
            }

            if ($allowed) {
                if (empty($umnode->parentkey)) {
                    $allowedparents[$umnode->nodekey] = $umnode;
                } else {
                    $childrenbyparent[$umnode->parentkey][] = $umnode;
                }
            }
        }

        $templates = '';
        $hastemplates = false;

        $usermenu = \local_extendednav\menu_manager::get_menu('usermenu');
        $nativenodes = $usermenu ? $usermenu->get_native_nodes() : [];

        // GENERATE TEMPLATES FOR ROOT NODE FIXES (ICONS AND NEW WINDOW)
        foreach ($allowedparents as $nodekey => $node) {
            $icon = trim((string)$node->icon);
            // If it has children, it's a submenu trigger, so newwindow shouldn't apply to it.
            $haschildren = isset($childrenbyparent[$nodekey]) && !empty($childrenbyparent[$nodekey]);
            $newwindow = (!empty($node->newwindow) && !$haschildren) ? 1 : 0;
            
            $needsicon = (!empty($icon) && $icon !== 'none' && (strpos($icon, 'fa-') !== false || strpos($icon, 'fa ') !== false));
            $needsnewwindow = ($newwindow === 1);
            
            if ($needsicon || $needsnewwindow) {
                $url = trim((string)$node->url);
                $title = trim((string)$node->title) ?: (isset($nativenodes[$nodekey]) ? $nativenodes[$nodekey]->text : '');
                if ($title !== '') {
                    $titlebits = explode(',', $title, 2);
                    if (count($titlebits) == 2 && clean_param($titlebits[0], PARAM_STRINGID) !== '' && clean_param($titlebits[1], PARAM_COMPONENT) !== '') {
                        $title = get_string($titlebits[0], $titlebits[1]);
                    }
                }

                $urlAttr = !empty($url) ? 'data-url="' . s($url) . '"' : '';
                $titleAttr = !empty($title) ? 'data-title="' . s($title) . '"' : '';
                
                $iconAttr = $needsicon ? 'data-icon="' . s($icon) . '"' : '';
                $windowAttr = $needsnewwindow ? 'data-newwindow="1"' : '';
                
                $templates .= '<template data-region="local-extendednav-node-fix" ' . $iconAttr . ' ' . $windowAttr . ' ' . $urlAttr . ' ' . $titleAttr . '></template>';
                $hastemplates = true;
            }
        }

        foreach ($childrenbyparent as $parentkey => $children) {
            // Find parent title and url for JS trigger matching.
            $ptitle = '';
            $purl = '';
            if (isset($allowedparents[$parentkey])) {
                $p = $allowedparents[$parentkey];
                $ptitle = trim((string)$p->title);
                $purl = trim((string)$p->url);
                if ($ptitle === '' && isset($nativenodes[$parentkey])) {
                    $ptitle = $nativenodes[$parentkey]->text;
                }
                if ($purl === '' && isset($nativenodes[$parentkey])) {
                    $purl = $nativenodes[$parentkey]->action;
                }
            } else if (isset($nativenodes[$parentkey])) {
                $ptitle = $nativenodes[$parentkey]->text;
                $purl = $nativenodes[$parentkey]->action;
            }

            if (empty($ptitle)) {
                continue;
            }

            $titlebits = explode(',', $ptitle, 2);
            if (count($titlebits) == 2 && clean_param($titlebits[0], PARAM_STRINGID) !== '' && clean_param($titlebits[1], PARAM_COMPONENT) !== '') {
                $ptitle = get_string($titlebits[0], $titlebits[1]);
            }

            $items = [];
            foreach ($children as $child) {
                $ctitle = trim((string)$child->title);
                $curl = trim((string)$child->url);
                $cicon = trim((string)$child->icon);

                if ($ctitle === '' && isset($nativenodes[$child->nodekey])) {
                    $ctitle = $nativenodes[$child->nodekey]->text;
                }
                if ($curl === '' && isset($nativenodes[$child->nodekey])) {
                    $curl = $nativenodes[$child->nodekey]->action;
                }
                if ($ctitle === '') {
                    continue;
                }
                $titlebits = explode(',', $ctitle, 2);
                if (count($titlebits) == 2 && clean_param($titlebits[0], PARAM_STRINGID) !== '' && clean_param($titlebits[1], PARAM_COMPONENT) !== '') {
                    $ctitle = get_string($titlebits[0], $titlebits[1]);
                }

                $items[] = [
                    'url' => $curl,
                    'title' => $ctitle,
                    'icon' => (!empty($cicon) && $cicon !== 'none') ? $cicon : false,
                    'newwindow' => !empty($child->newwindow),
                ];
            }

            if (!empty($items)) {
                $panelid = 'carousel-item-extnav-' . preg_replace('/[^a-zA-Z0-9]/', '', $parentkey);
                $html = $hook->renderer->render_from_template('local_extendednav/usermenu/panel', [
                    'panelid' => $panelid,
                    'title' => $ptitle,
                    'items' => $items,
                ]);
                $triggerAttr = !empty($purl) ? 'data-trigger-url="' . s($purl) . '"' : 'data-trigger-title="' . s($ptitle) . '"';
                $templates .= '<template data-region="local-extendednav-submenu" data-panel-id="' . s($panelid) . '" ' . $triggerAttr . '>' . $html . '</template>';
                $hastemplates = true;
            }
        }

        if ($hastemplates) {
            $hook->add_html($templates);
            $PAGE->requires->js_call_amd('local_extendednav/usermenu', 'init');
        }
    }

    /**
     * Executes before the standard HTML head is rendered.
     * This is early enough to modify $CFG variables before the theme renders the header.
     *
     * @param \core\hook\output\before_standard_head_html_generation $hook
     */
    public static function before_standard_head(\core\hook\output\before_standard_head_html_generation $hook): void {
        global $DB, $USER;

        if (self::$skiphook || !get_config('local_extendednav', 'enable_plugin')) {
            return;
        }

        if (!isloggedin() || isguestuser()) {
            return;
        }

        $cache = \cache::make('local_extendednav', 'nodes');
        $customnodes = $cache->get('usermenunodes');

        if ($customnodes === false) {
            try {
                $customnodes = $DB->get_records('local_extendednav', ['menu' => 'usermenu'], 'sortorder ASC, id ASC');
            } catch (\moodle_exception $e) {
                $customnodes = [];
            }
            $cache->set('usermenunodes', $customnodes);
        }

        $usermenu = \local_extendednav\menu_manager::get_menu('usermenu');
        if ($usermenu) {
            $usermenu->inject_nodes($customnodes);
        }
    }
}
