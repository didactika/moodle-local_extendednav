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
 * Core library functions for local_extendednav.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extends the global navigation to block overridden URLs manually if hidden.
 *
 * @param \global_navigation $navigation
 */
function local_extendednav_extend_navigation(\global_navigation $navigation): void {
    global $PAGE, $DB, $USER;
    
    if ((defined('WS_SERVER') && WS_SERVER) || 
        (defined('AJAX_SCRIPT') && AJAX_SCRIPT) || 
        (defined('CLI_SCRIPT') && CLI_SCRIPT)) {
        return;
    }

    if (!get_config('local_extendednav', 'enable_plugin')) {
        return;
    }

    if (!isloggedin() || isguestuser()) {
        return;
    }

    $cache = \cache::make('local_extendednav', 'nodes');
    $nodes = $cache->get('allnodes');
    
    if ($nodes === false) {
        try {
            $nodes = $DB->get_records('local_extendednav', null, 'sortorder DESC, id DESC');
        } catch (\moodle_exception $e) {
            $nodes = [];
        }
        $cache->set('allnodes', $nodes);
    }

    if (empty($nodes)) {
        return;
    }

    $current_url = '';
    try {
        if (is_object($PAGE) && is_object($PAGE->url)) {
            $current_url = $PAGE->url->out(false);
        }
    } catch (\moodle_exception $e) {
    }
    
    if (empty($current_url)) {
        return; 
    }

    $parsed_current = parse_url($current_url);
    $current_path = isset($parsed_current['path']) ? $parsed_current['path'] : '/';
    if (isset($parsed_current['query']) && $parsed_current['query'] !== '') {
        $current_path .= '?' . $parsed_current['query'];
    }

    $immune_paths = [
        '/local/extendednav/', 
        '/admin/',                  
        '/login/'                   
    ];
    
    foreach ($immune_paths as $ipath) {
        if (strpos($current_path, $ipath) !== false) {
            return;
        }
    }
    
    if (is_siteadmin()) {
        return;
    }

    $fallback_custom = trim((string) get_config('local_extendednav', 'fallbackurl'));
    $fallback_path = '';
    
    if ($fallback_custom !== '') {
        $parsed_fallback = parse_url($fallback_custom);
        $fallback_path = isset($parsed_fallback['path']) ? $parsed_fallback['path'] : '';
        if (isset($parsed_fallback['query']) && $parsed_fallback['query'] !== '') {
            $fallback_path .= '?' . $parsed_fallback['query'];
        }
        if (!$fallback_path) {
            $fallback_path = $fallback_custom; 
        }

        if ($fallback_path === '/') {
            if ($current_path === '/' || strpos($current_path, '/index.php') === 0) {
                return;
            }
        } else if (strpos($current_path, $fallback_path) !== false) {
            return;
        }
    }

    $all_blocked_paths = [];
    $all_whitelisted_paths = [];

    foreach ($nodes as $cnode) {
        $allowed = true;
        
        if ($cnode->visibility == 0) {
            $allowed = false;
        } else if ($cnode->visibility == 2) {
            $allowed = false;
            if (!empty($cnode->roles)) {
                $role_ids = explode(',', $cnode->roles);
                foreach ($role_ids as $rid) {
                    if (!empty($rid) && user_has_role_assignment($USER->id, (int)$rid)) {
                        $allowed = true;
                        break;
                    }
                }
            }
        }
        
        $target_url = null;
        if (!empty($cnode->url)) {
            $target_url = $cnode->url;
        } else if ($cnode->visibility != 1) { 
            try {
                $primary = new \core\navigation\views\primary($PAGE);
                $primary->initialise();
                $corenode = $primary->get($cnode->nodekey);
                if ($corenode && $corenode->action instanceof \moodle_url) {
                    $target_url = $corenode->action->out(false);
                }
            } catch (\moodle_exception $e) {
            }
        }

        if ($allowed) {
            if (!empty($target_url)) {
                $p_url = parse_url($target_url);
                $w_path = isset($p_url['path']) ? $p_url['path'] : '';
                if (isset($p_url['query']) && $p_url['query'] !== '') {
                    $w_path .= '?' . $p_url['query'];
                }
                if ($w_path !== '') {
                    $all_whitelisted_paths[] = $w_path;
                }
            }
        } else {
            if (!empty($cnode->blockedurls)) {
                $split_urls = array_map('trim', explode(',', $cnode->blockedurls));
                foreach ($split_urls as $s_url) {
                    if ($s_url !== '') {
                        $all_blocked_paths[] = $s_url;
                    }
                }
            }
        }
    }

    $is_current_blocked = false;
    foreach ($all_blocked_paths as $bpath) {
        if ($bpath === '/') { 
            if ($current_path === '/' || strpos($current_path, '/index.php') === 0) {
                $is_current_blocked = true;
                break;
            }
            continue;
        }

        if (strpos($current_path, $bpath) !== false) {
            $is_current_blocked = true;
            break;
        }
    }

    if ($is_current_blocked) {
        foreach ($all_whitelisted_paths as $wpath) {
            if ($wpath === '/') {
                if ($current_path === '/' || strpos($current_path, '/index.php') === 0) {
                    $is_current_blocked = false;
                    break;
                }
            } else if (strpos($current_path, $wpath) !== false) {
                $is_current_blocked = false;
                break;
            }
        }
    }

    if ($is_current_blocked) {
        if ($fallback_custom !== '') {
            redirect(new \moodle_url($fallback_custom));
        }

        $fallback_my = '/my/index.php';
        $fallback_front = '/?redirect=0';
        
        $my_blocked = false;
        $front_blocked = false;
        
        foreach ($all_blocked_paths as $bpath) {
            if ($bpath === '/') {
                $front_blocked = true;
                continue;
            }
            if (strpos($fallback_my, $bpath) !== false || strpos('/my/', $bpath) !== false) {
                $my_blocked = true;
            }
            if (strpos($fallback_front, $bpath) !== false) {
                $front_blocked = true;
            }
        }

        foreach ($all_whitelisted_paths as $wpath) {
            if (strpos($fallback_my, $wpath) !== false || strpos('/my/', $wpath) !== false) {
                $my_blocked = false;
            }
            if (strpos($fallback_front, $wpath) !== false) {
                $front_blocked = false;
            }
        }

        if (!$my_blocked && strpos($current_path, '/my/') === false) {
            redirect(new \moodle_url('/my/index.php'));
        } else if (!$front_blocked && strpos($current_path, 'redirect=0') === false && $current_path !== '/' && strpos($current_path, '/index.php') !== 0) {
            redirect(new \moodle_url('/?redirect=0'));
        } else {
            throw new \moodle_exception('nopermissions', 'error', '', null, get_string('err_restricted_page', 'local_extendednav'));
        }
    }
}
