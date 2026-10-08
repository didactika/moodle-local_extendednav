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

class primary extends base {
    public function get_key(): string {
        return 'primary';
    }

    public function get_name(): string {
        return get_string('primary_nav', 'local_extendednav');
    }

    public function sync_native_nodes(): void {
        global $DB;
        $dbnodesbykey = $DB->get_records_menu('local_extendednav', ['menu' => $this->get_key()], '', 'nodekey, id');
        $maxsort = (int)$DB->get_field_sql('SELECT MAX(sortorder) FROM {local_extendednav} WHERE menu = ?', [$this->get_key()]);

        \local_extendednav\hooks::$skiphook = true;
        $temppage = new \moodle_page();
        $temppage->set_context(\context_system::instance());
        $temppage->set_url(new \moodle_url('/'));
        $primary = new \core\navigation\views\primary($temppage);
        $primary->initialise();
        \local_extendednav\hooks::$skiphook = false;

        foreach ($primary->children as $cnode) {
            if ($cnode->key && !isset($dbnodesbykey[$cnode->key])) {
                $maxsort++;
                $rec = new \stdClass();
                $rec->menu = $this->get_key();
                $rec->nodekey = $cnode->key;
                $rec->visibility = 1;
                $rec->sortorder = $maxsort;
                $rec->newwindow = 0;
                $DB->insert_record('local_extendednav', $rec);
            }
        }
    }

    public function get_native_nodes(): array {
        $nodes = [];
        try {
            \local_extendednav\hooks::$skiphook = true;
            $temppage = new \moodle_page();
            $temppage->set_context(\context_system::instance());
            $temppage->set_url(new \moodle_url('/'));
            $primary = new \core\navigation\views\primary($temppage);
            $primary->initialise();
            foreach ($primary->children as $child) {
                if ($child->key) {
                    $nodes[$child->key] = $child;
                }
            }
        } catch (\Throwable $e) {
            unset($e);
        } finally {
            \local_extendednav\hooks::$skiphook = false;
        }
        return $nodes;
    }

    public function inject_nodes(array $customnodes, $context = null): void {
        global $USER, $OUTPUT;
        // $context is the primary_extend hook
        if (!$context) {
            return;
        }
        $primarynav = $context->get_primaryview();

        $finalstates = [];
        foreach ($customnodes as $cnode) {
            $nodekey = $cnode->nodekey;
            $state = new \stdClass();
            $state->nodekey = $nodekey;

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

            $state->allowed = $allowed;
            $state->title = !empty($cnode->title) ? $cnode->title : null;

            $rawurl = !empty($cnode->url) ? trim((string)$cnode->url) : null;
            if ($rawurl !== null && $rawurl !== '') {
                if (!preg_match('/^[a-zA-Z0-9-]+:/', $rawurl)) {
                    if (strpos($rawurl, 'www.') === 0) {
                        $rawurl = 'http://' . $rawurl;
                    } else if (strpos($rawurl, '/') !== 0) {
                        $rawurl = '/' . $rawurl;
                    }
                }
            } else {
                $rawurl = null;
            }
            $state->url = $rawurl;

            $state->icon = null;
            $state->icon_set = false;
            if ($cnode->icon !== '' && $cnode->icon !== null) {
                $state->icon = $cnode->icon;
                $state->icon_set = true;
            }

            $state->parentkey = !empty($cnode->parentkey) ? $cnode->parentkey : null;

            $state->newwindow = false;
            if (isset($cnode->newwindow)) {
                $state->newwindow = (bool)$cnode->newwindow;
            }

            if ($nodekey === 'siteadminnode') {
                $state->allowed = true;
                $state->parentkey = null;
            }
            if ($state->parentkey === 'siteadminnode') {
                $state->parentkey = null;
            }

            $finalstates[$nodekey] = $state;
        }

        $scriptinjected = false;

        foreach ($finalstates as $nodekey => $state) {
            $stateiconhtml = '';
            if ($state->icon_set && $state->icon !== 'null' && $state->icon !== 'none') {
                if (strpos($state->icon, 'fa-') !== false || strpos($state->icon, 'fa ') !== false) {
                    $stateiconhtml = '<i class="icon fa ' . s($state->icon) . ' fa-fw" aria-hidden="true"></i> ';
                } else {
                    try {
                        if (is_object($OUTPUT) && method_exists($OUTPUT, 'pix_icon')) {
                            $stateiconhtml = $OUTPUT->pix_icon($state->icon, '') . ' ';
                        }
                    } catch (\moodle_exception $e) {
                        unset($e);
                    }
                }
            }

            $newwindowspan = '';
            $scripthtml = '';
            if ($state->newwindow) {
                $newwindowspan = '<span class="custom-target-blank" style="display:none;" aria-hidden="true"></span>';

                if (!$scriptinjected) {
                    $scripthtml = '<script>
                        if (!window.customNavScriptInjected) {
                            window.customNavScriptInjected = true;
                            var applyT = function() {
                                var els = document.querySelectorAll(".custom-target-blank");
                                for (var i = 0; i < els.length; i++) {
                                    var a = els[i].closest("a");
                                    if (a && a.getAttribute("target") !== "_blank") {
                                        a.setAttribute("target", "_blank");
                                    }
                                }
                            };
                            if (document.readyState === "loading") {
                                document.addEventListener("DOMContentLoaded", function() {
                                    applyT();
                                    setTimeout(applyT, 500);
                                });
                            } else {
                                applyT(); setTimeout(applyT, 500);
                            }
                            document.addEventListener("click", function(e) {
                                var t = e.target.closest("a");
                                if (t && t.querySelector(".custom-target-blank") && t.getAttribute("target") !== "_blank") {
                                    t.setAttribute("target", "_blank");
                                }
                            });
                        }
                    </script>';
                    $scriptinjected = true;
                }
            }

            $existingnode = $primarynav->get($nodekey);

            if ($existingnode) {
                if (!$state->allowed) {
                    $existingnode->remove();
                } else {
                    if (!empty($state->title)) {
                        $existingnode->text = format_string($state->title);
                    }
                    if (!empty($state->url)) {
                        $existingnode->action = new \moodle_url($state->url);
                    }
                    $existingnode->text = $scripthtml . $stateiconhtml . $existingnode->text . $newwindowspan;
                    $existingnode->icon = null;
                }
            } else if ($state->allowed) {
                if (empty($state->title) || empty($state->url)) {
                    continue;
                }

                $url = new \moodle_url($state->url);

                $node = \navigation_node::create(
                    $scripthtml . $stateiconhtml . format_string($state->title) . $newwindowspan,
                    $url,
                    \navigation_node::TYPE_SETTING,
                    null,
                    $nodekey,
                    null
                );

                $primarynav->add_node($node);
            }
        }

        foreach ($finalstates as $nodekey => $state) {
            if (!$state->allowed) {
                continue;
            }

            $node = $primarynav->get($nodekey);
            if (!$node) {
                continue;
            }

            if (!empty($state->parentkey) && $state->parentkey !== $nodekey) {
                $parentnode = $primarynav->get($state->parentkey);

                if (!$parentnode) {
                    $killedbyus = isset($finalstates[$state->parentkey]) && $finalstates[$state->parentkey]->allowed === false;

                    if ($killedbyus) {
                        $node->remove();
                    }
                    continue;
                }

                $grandpa = $parentnode->parent;
                if ($grandpa === null || $grandpa->key === $primarynav->key) {
                    $node->remove();
                    $parentnode->add_node($node);
                }
            }
        }

        $allnodes = [];
        foreach ($primarynav->children as $child) {
            $allnodes[$child->key] = $child;
        }

        foreach ($allnodes as $child) {
            $child->remove();
        }

        foreach ($customnodes as $dbnode) {
            if (isset($allnodes[$dbnode->nodekey]) && isset($finalstates[$dbnode->nodekey]) && $finalstates[$dbnode->nodekey]->allowed) {
                $primarynav->add_node($allnodes[$dbnode->nodekey]);
                unset($allnodes[$dbnode->nodekey]);
            }
        }

        foreach ($allnodes as $child) {
            if ($child->key && (!isset($finalstates[$child->key]) || $finalstates[$child->key]->allowed)) {
                $primarynav->add_node($child);
            }
        }

        foreach ($primarynav->children as $child) {
            $child->icon = null;
        }
    }
}
