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

namespace local_extendednav\menu;

/**
 * Scalable menu implementation for the Moodle User Menu.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class usermenu extends base {
    /**
     * Gets the key identifying the user menu.
     *
     * @return string
     */
    public function get_key(): string {
        return 'usermenu';
    }

    /**
     * Gets the human-readable localized name of this menu.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('user_menu', 'local_extendednav');
    }

    /**
     * Associated core configuration name for user menu items.
     *
     * @return string|null
     */
    public function get_config_name(): ?string {
        return 'customusermenuitems';
    }

    /**
     * Key prefix for items imported/synced from customusermenuitems.
     *
     * @return string
     */
    public function get_config_prefix(): string {
        return 'umenu_';
    }

    /**
     * Synchronizes native core nodes into the plugin database.
     */
    public function sync_native_nodes(): void {
        global $DB;

        $nodes = $this->get_native_nodes();
        $existing = $DB->get_records('local_extendednav', ['menu' => 'usermenu']);
        $existingbykey = [];
        foreach ($existing as $e) {
            $existingbykey[$e->nodekey] = $e;
        }

        $maxsort = (int)$DB->get_field_sql('SELECT MAX(sortorder) FROM {local_extendednav} WHERE menu = ?', ['usermenu']);

        foreach ($nodes as $key => $node) {
            if (!isset($existingbykey[$key])) {
                $maxsort++;
                $record = new \stdClass();
                $record->nodekey = $key;
                $record->menu = 'usermenu';
                $record->title = $node->text;
                $record->url = $node->action;
                $record->parentkey = null;
                $record->icon = $node->icon;
                $record->visibility = 1;
                $record->roles = null;
                $record->newwindow = 0;
                $record->sortorder = $maxsort;
                $record->id = (int)$DB->insert_record('local_extendednav', $record);
                \local_extendednav\event\node_created::create_from_record($record)->trigger();
            } else {
                $rec = $existingbykey[$key];
                if ($rec->url !== $node->action) {
                    $rec->url = $node->action;
                    $DB->update_record('local_extendednav', $rec);
                    \local_extendednav\event\node_updated::create_from_record($rec)->trigger();
                }
            }
        }
    }

    /**
     * Gets all native nodes belonging to this menu as an array of objects.
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
        foreach ($defaultitems as $line) {
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
     * Injects custom nodes into the user menu configuration.
     *
     * @param array $customnodes Array of custom nodes to inject
     * @param mixed $context Optional context
     */
    public function inject_nodes(array $customnodes, $context = null): void {
        global $CFG, $USER;

        if (empty($customnodes)) {
            return;
        }

        // Group nodes by parent.
        $childrenbyparent = [];
        $rootnodes = [];
        foreach ($customnodes as $n) {
            $allowed = true;
            if ($n->visibility == 0) {
                $allowed = false;
            } else if ($n->visibility == 2) {
                $allowed = false;
                if (!empty($n->roles)) {
                    $roleids = explode(',', $n->roles);
                    foreach ($roleids as $rid) {
                        if (!empty($rid) && user_has_role_assignment($USER->id, (int)$rid)) {
                            $allowed = true;
                            break;
                        }
                    }
                }
            }

            if ($allowed) {
                if (empty($n->parentkey)) {
                    $rootnodes[] = $n;
                } else {
                    $childrenbyparent[$n->parentkey][] = $n;
                }
            }
        }

        $newcustomusermenu = [];
        $nativenodes = $this->get_native_nodes();

        foreach ($rootnodes as $node) {
            $title = !empty($node->title) ? $node->title : ($nativenodes[$node->nodekey]->text ?? '');
            $url = !empty($node->url) ? $node->url : ($nativenodes[$node->nodekey]->action ?? '');
            $icon = (!empty($node->icon) && $node->icon !== 'none') ? $node->icon : '';

            if (empty($title) && empty($url)) {
                continue;
            }

            if (!empty($url)) {
                $line = $title . '|' . $url;
            } else {
                $line = $title;
            }

            if (!empty($icon)) {
                $line .= '|' . $icon;
            }

            $newcustomusermenu[] = $line;
        }
        $CFG->customusermenuitems = implode("\n", $newcustomusermenu);
    }

    /**
     * Injects custom nodes via the user menu extension hook.
     *
     * @param array $customnodes Array of custom nodes
     * @param mixed $hook The user menu hook
     */
    public function inject_nodes_via_hook(array $customnodes, $hook): void {
        unset($customnodes, $hook);
    }
}
