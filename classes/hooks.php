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

class hooks {
    
    /**
     * Extends the primary navigation based on custom DB configuration.
     *
     * @param primary_extend $hook The navigation extension hook.
     */
    public static function extend_primary_navigation(primary_extend $hook): void {
        global $USER, $DB, $OUTPUT;

        if (!get_config('local_extendednav', 'enable_plugin')) {
            return;
        }
        
        $primarynav = $hook->get_primaryview();

        if (isloggedin() && !isguestuser()) {
            
            $cache = \cache::make('local_extendednav', 'nodes');
            $customnodes = $cache->get('allnodes');
            
            if ($customnodes === false) {
                try {
                    $customnodes = $DB->get_records('local_extendednav', null, 'sortorder DESC, id DESC');
                } catch (\moodle_exception $e) {
                    $customnodes = [];
                }
                $cache->set('allnodes', $customnodes);
            }

            $final_states = [];

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
                        $role_ids = explode(',', $cnode->roles);
                        foreach ($role_ids as $rid) {
                            if (!empty($rid) && user_has_role_assignment($USER->id, (int)$rid)) {
                                $allowed = true;
                                break;
                            }
                        }
                    }
                }
                
                $state->allowed = $allowed;
                $state->title = !empty($cnode->title) ? $cnode->title : null;
                $state->url = !empty($cnode->url) ? $cnode->url : null;
                
                $state->icon = null;
                $state->icon_set = false;
                if ($cnode->icon !== '' && $cnode->icon !== null) { 
                    $state->icon = $cnode->icon; 
                    $state->icon_set = true;
                }
                
                $state->beforekey = !empty($cnode->beforekey) ? $cnode->beforekey : null;
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

                $final_states[$nodekey] = $state;
            }

            $script_injected = false;

            foreach ($final_states as $nodekey => $state) {
                
                $state_icon_html = '';
                if ($state->icon_set && $state->icon !== 'null' && $state->icon !== 'none') {
                    if (strpos($state->icon, 'fa-') !== false || strpos($state->icon, 'fa ') !== false) {
                        $state_icon_html = '<i class="icon fa ' . s($state->icon) . ' fa-fw" aria-hidden="true"></i> ';
                    } else {
                        try {
                            if (is_object($OUTPUT) && method_exists($OUTPUT, 'pix_icon')) {
                                $state_icon_html = $OUTPUT->pix_icon($state->icon, '') . ' ';
                            }
                        } catch (\moodle_exception $e) { 
                        }
                    }
                }

                $newwindow_span = '';
                $script_html = '';
                if ($state->newwindow) {
                    $newwindow_span = '<span class="custom-target-blank" style="display:none;" aria-hidden="true"></span>';
                    
                    if (!$script_injected) {
                        $script_html = '<script>
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
                                    document.addEventListener("DOMContentLoaded", function() { applyT(); setTimeout(applyT, 500); });
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
                        $script_injected = true;
                    }
                }

                $existing_node = $primarynav->get($nodekey);

                if ($existing_node) {
                    if (!$state->allowed) {
                        $existing_node->remove();
                    } else {
                        if (!empty($state->title)) {
                            $existing_node->text = format_string($state->title);
                        }
                        if (!empty($state->url)) {
                            $existing_node->action = new \moodle_url($state->url);
                        }
                        $existing_node->text = $script_html . $state_icon_html . $existing_node->text . $newwindow_span;
                        $existing_node->icon = null;
                    }
                } else if ($state->allowed) {
                    if (empty($state->title) || empty($state->url)) {
                        continue;
                    }

                    $url = new \moodle_url($state->url);
                    
                    $node = \navigation_node::create(
                        $script_html . $state_icon_html . format_string($state->title) . $newwindow_span,
                        $url,
                        \navigation_node::TYPE_SETTING,
                        null,
                        $nodekey,
                        null
                    );
                    
                    $primarynav->add_node($node);
                }
            }
            
            foreach ($final_states as $nodekey => $state) {
                if (!$state->allowed) {
                    continue;
                }
                
                $node = $primarynav->get($nodekey);
                if (!$node) {
                    continue;
                }
                
                if (!empty($state->parentkey) && $state->parentkey !== $nodekey) {
                    
                    $parent_node = $primarynav->get($state->parentkey);
                    
                    if (!$parent_node) {
                        $killedByUs = isset($final_states[$state->parentkey]) && $final_states[$state->parentkey]->allowed === false;
                        
                        if ($killedByUs) {
                            $node->remove();
                        }
                        continue;
                    }
                    
                    $grandpa = $parent_node->parent;
                    if ($grandpa !== null && $grandpa->key !== $primarynav->key) {
                    } else {
                        $node->remove();
                        $parent_node->add_node($node);
                    }
                    
                }
                
                if (!empty($state->beforekey)) {
                    $assigned_parent = $node->parent; 
                    if ($assigned_parent) {
                        $sibling_exists = false;
                        if ($assigned_parent->children) {
                            foreach ($assigned_parent->children as $child) {
                                if ($child->key === $state->beforekey) {
                                    $sibling_exists = true;
                                    break;
                                }
                            }
                        }
                        
                        if ($sibling_exists) {
                            $node->remove();
                            try {
                                $assigned_parent->add_node($node, $state->beforekey);
                            } catch (\moodle_exception $e) {
                                $assigned_parent->add_node($node);
                            }
                        }
                    }
                }
            }

            foreach ($primarynav->children as $child) {
                $child->icon = null;
            }
        }
    }
}
