<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_extendednav\menu;

defined('MOODLE_INTERNAL') || die();

/**
 * Usermenu class
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class usermenu extends base {
    /**
     * @return string
     */
    public function get_key(): string {
        return 'usermenu';
    }

    /**
     * @return string
     */
    public function get_name(): string {
        return get_string('user_menu', 'local_extendednav');
    }

    /**
     * @return string|null
     */
    public function get_config_name(): ?string {
        return 'customusermenuitems';
    }

    /**
     * @return string
     */
    public function get_config_prefix(): string {
        return 'umenu_';
    }

    /**
     * Syncs core native user menu nodes.
     */
    public function sync_native_nodes(): void {
        global $DB;
        $dbnodesbykey = $DB->get_records_menu('local_extendednav', ['menu' => $this->get_key()], '', 'nodekey, id');
        $maxsort = (int)$DB->get_field_sql('SELECT MAX(sortorder) FROM {local_extendednav} WHERE menu = ?', [$this->get_key()]);

        $nativenodes = $this->get_native_nodes();
        foreach ($nativenodes as $key => $node) {
            if (!isset($dbnodesbykey[$key])) {
                $maxsort++;
                $rec = new \stdClass();
                $rec->menu = $this->get_key();
                $rec->nodekey = $key;
                $rec->title = $node->text;
                $rec->url = $node->action;
                $rec->visibility = 1;
                $rec->sortorder = $maxsort;
                $rec->newwindow = 0;
                $DB->insert_record('local_extendednav', $rec);
            }
        }
    }

    /**
     * Returns default core Moodle user menu nodes.
     *
     * @return array
     */
    public function get_native_nodes(): array {
        $defaultitems = [
            'profile,moodle|/user/profile.php',
            'grades,grades|/grade/report/mygrades.php',
            'calendar,core_calendar|/calendar/view.php?view=month',
            'privatefiles,moodle|/user/files.php',
            'reports,core_reportbuilder|/reportbuilder/index.php',
        ];
        $nodes = [];
        foreach ($defaultitems as $linenumber => $line) {
            $settings = explode('|', $line);
            $rawtext = trim($settings[0]);
            $itemtext = ltrim($rawtext, '-');
            $itemurl = isset($settings[1]) ? trim($settings[1]) : '';
            $itemicon = isset($settings[2]) ? trim($settings[2]) : '';
            $slug = preg_replace('/[^a-z0-9]/', '', strtolower($itemtext));
            $nodekey = 'umenu_' . substr($slug, 0, 15);

            $std = new \stdClass();
            $std->text = $itemtext;
            $std->action = $itemurl;
            $std->icon = $itemicon;
            $std->key = $nodekey;
            $nodes[$nodekey] = $std;
        }
        return $nodes;
    }

    /**
     * Injects
     * @param array $customnodes
     * @param mixed $context
     */
    public function inject_nodes(array $customnodes, $context = null): void {
        global $CFG, $USER;

        if (empty($customnodes)) {
            return;
        }

        $newcustomusermenu = [];
        foreach ($customnodes as $umnode) {
            if (!empty($umnode->parentkey)) {
                continue;
            }

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
                $line = trim((string)$umnode->title);

                // If it is empty, fallback to the native node
                if ($line === '') {
                    $nativenodes = $this->get_native_nodes();
                    if (isset($nativenodes[$umnode->nodekey])) {
                        $native = $nativenodes[$umnode->nodekey];
                        $line = $native->text;
                        if (empty($umnode->url)) {
                            $umnode->url = $native->action;
                        }
                    }
                }

                $nativenodes = $this->get_native_nodes();
                $native = isset($nativenodes[$umnode->nodekey]) ? $nativenodes[$umnode->nodekey] : null;

                $urlpart = !empty($umnode->url) ? trim((string)$umnode->url) : ($native ? trim((string)$native->action) : '');
                if ($urlpart !== '') {
                    $line .= '|' . $urlpart;
                }

                $icon = trim((string)$umnode->icon);
                if (!empty($icon) && $icon !== 'none' && strpos($icon, 'fa-') === false && strpos($icon, 'fa ') === false) {
                    if ($urlpart === '') {
                        $line .= '|';
                    }
                    $line .= '|' . $icon;
                }

                $newcustomusermenu[] = $line;
            }
        }
        $CFG->customusermenuitems = implode("\n", $newcustomusermenu);
    }

    /**
     * Injects
     * @param array $customnodes
     * @param mixed $hook
     */
    public function inject_nodes_via_hook(array $customnodes, $hook): void {
        // Do nothing. Custom nodes are now injected via customusermenuitems so they obey sortorder natively.
    }
}
