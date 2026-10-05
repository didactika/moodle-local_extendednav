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


/**
 * Extends the global navigation to block overridden URLs manually if hidden.
 *
 * @param \global_navigation $navigation
 */
function local_extendednav_extend_navigation(\global_navigation $navigation): void {
    global $PAGE, $DB, $USER;

    if (
        (defined('WS_SERVER') && WS_SERVER) ||
        (defined('AJAX_SCRIPT') && AJAX_SCRIPT) ||
        (defined('CLI_SCRIPT') && CLI_SCRIPT)
    ) {
        return;
    }

    if (!get_config('local_extendednav', 'enable_plugin')) {
        return;
    }

    if (!isloggedin() || isguestuser()) {
        return;
    }

    try {
        $cache = \cache::make('local_extendednav', 'nodes');
        $nodes = $cache->get('allnodes');

        if ($nodes === false) {
            $nodes = $DB->get_records('local_extendednav', null, 'sortorder DESC, id DESC');
            $cache->set('allnodes', $nodes);
        }
    } catch (\Throwable $e) {
        // Do nothing.

        return;
    }

    if (empty($nodes)) {
        return;
    }

    $currenturl = '';
    try {
        if (is_object($PAGE) && is_object($PAGE->url)) {
            $currenturl = $PAGE->url->out(false);
        }
    } catch (\Throwable $e) {
        unset($e);
        // Do nothing.
    }

    if (empty($currenturl)) {
        return;
    }

    $parsedcurrent = parse_url($currenturl);
    $currentpath = isset($parsedcurrent['path']) ? $parsedcurrent['path'] : '/';
    if (isset($parsedcurrent['query']) && $parsedcurrent['query'] !== '') {
        $currentpath .= '?' . $parsedcurrent['query'];
    }

    $immunepaths = [
        '/local/extendednav/',
        '/admin/',
        '/login/',
    ];

    foreach ($immunepaths as $ipath) {
        if (strpos($currentpath, $ipath) !== false) {
            return;
        }
    }

    if (is_siteadmin()) {
        return;
    }

    $fallbackcustom = trim((string) get_config('local_extendednav', 'fallbackurl'));
    $fallbackpath = '';

    if ($fallbackcustom !== '') {
        $parsedfallback = parse_url($fallbackcustom);
        $fallbackpath = isset($parsedfallback['path']) ? $parsedfallback['path'] : '';
        if (isset($parsedfallback['query']) && $parsedfallback['query'] !== '') {
            $fallbackpath .= '?' . $parsedfallback['query'];
        }
        if (!$fallbackpath) {
            $fallbackpath = $fallbackcustom;
        }

        if ($fallbackpath === '/') {
            if ($currentpath === '/' || strpos($currentpath, '/index.php') === 0) {
                return;
            }
        } else if (strpos($currentpath, $fallbackpath) !== false) {
            return;
        }
    }

    $allblockedpaths = [];
    $allwhitelistedpaths = [];

    foreach ($nodes as $cnode) {
        $allowed = true;

        if ($cnode->visibility == 0) {
            $allowed = false;
        } else if ($cnode->visibility == 2) {
            $allowed = false;
            if (!empty($cnode->roles)) {
                $roleids = explode(',', $cnode->roles);
                foreach ($roleids as $rid) {
                    if (!empty($rid) && user_has_role_assignment($USER->id, (int)$rid)) {
                        $allowed = true;
                        break;
                    }
                }
            }
        }

        $nodepaths = [];
        if (!empty($cnode->blockedurls)) {
            $spliturls = array_map('trim', explode(',', $cnode->blockedurls));
            foreach ($spliturls as $surl) {
                if ($surl !== '') {
                    $nodepaths[] = $surl;
                }
            }
        }

        if (!empty($cnode->url)) {
            $purl = parse_url($cnode->url);
            $wpath = isset($purl['path']) ? $purl['path'] : '';
            if (isset($purl['query']) && $purl['query'] !== '') {
                $wpath .= '?' . $purl['query'];
            }
            if ($wpath !== '' && !in_array($wpath, $nodepaths)) {
                $nodepaths[] = $wpath;
            }
        }

        if ($allowed) {
            foreach ($nodepaths as $wpath) {
                $allwhitelistedpaths[] = $wpath;
            }
        } else {
            foreach ($nodepaths as $bpath) {
                $allblockedpaths[] = $bpath;
            }
        }
    }

    $iscurrentblocked = false;
    foreach ($allblockedpaths as $bpath) {
        if ($bpath === '/') {
            if ($currentpath === '/' || strpos($currentpath, '/index.php') === 0) {
                $iscurrentblocked = true;
                break;
            }
            continue;
        }

        if (strpos($currentpath, $bpath) !== false) {
            $iscurrentblocked = true;
            break;
        }
    }

    if ($iscurrentblocked) {
        foreach ($allwhitelistedpaths as $wpath) {
            if ($wpath === '/') {
                if ($currentpath === '/' || strpos($currentpath, '/index.php') === 0) {
                    $iscurrentblocked = false;
                    break;
                }
            } else if (strpos($currentpath, $wpath) !== false) {
                $iscurrentblocked = false;
                break;
            }
        }
    }

    if ($iscurrentblocked) {
        $fallbackmy = '/my/index.php';
        $fallbackfront = '/?redirect=0';

        $myblocked = false;
        $frontblocked = false;

        foreach ($allblockedpaths as $bpath) {
            if ($bpath === '/') {
                $frontblocked = true;
                continue;
            }
            if (strpos($fallbackmy, $bpath) !== false || strpos('/my/', $bpath) !== false) {
                $myblocked = true;
            }
            if (strpos($fallbackfront, $bpath) !== false) {
                $frontblocked = true;
            }
        }

        foreach ($allwhitelistedpaths as $wpath) {
            if (strpos($fallbackmy, $wpath) !== false || strpos('/my/', $wpath) !== false) {
                $myblocked = false;
            }
            if (strpos($fallbackfront, $wpath) !== false) {
                $frontblocked = false;
            }
        }

        if (!$myblocked) {
            redirect(new \moodle_url('/my/index.php'));
        } else if (!$frontblocked) {
            redirect(new \moodle_url('/?redirect=0'));
        } else {
            if ($fallbackcustom !== '') {
                redirect(new \moodle_url($fallbackcustom));
            }
            throw new \moodle_exception('nopermissions', 'error', '', null, get_string('err_restricted_page', 'local_extendednav'));
        }
    }
}
