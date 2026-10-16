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

use local_extendednav\hooks;
use local_extendednav\local\node_access;
use local_extendednav\local\node_text;
use local_extendednav\local\node_validator;
use local_extendednav\local\url_helper;

/**
 * The primary navigation.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class primary extends base {
    /**
     * Returns the menu key.
     *
     * @return string
     */
    public function get_key(): string {
        return 'primary';
    }

    /**
     * Returns the localised menu name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('primary_nav', 'local_extendednav');
    }

    /**
     * Returns the Moodle setting that defines custom primary menu items.
     *
     * @return string|null
     */
    public function get_config_name(): ?string {
        return 'custommenuitems';
    }

    /**
     * Returns the prefix of nodes imported from the custom menu items setting.
     *
     * @return string
     */
    public function get_config_prefix(): string {
        return 'cmenu_';
    }

    /**
     * Splits a custom menu line: text|url|tooltip|languages.
     *
     * @param string $line Line without the leading dashes.
     * @return array With keys text, url, tooltip and langs.
     */
    public function parse_config_line(string $line): array {
        $bits = array_map('trim', explode('|', $line));
        return [
            'text' => $bits[0] ?? '',
            'url' => $bits[1] ?? '',
            'tooltip' => $bits[2] ?? '',
            'langs' => $bits[3] ?? '',
        ];
    }

    /** @var string Plugin setting that keeps the items Moodle adds to the primary navigation. */
    private const NATIVE_CONFIG = 'nativenodes_primary';

    /** @var array|null Items Moodle adds for the current user, found once per request. */
    private static ?array $discovered = null;

    /**
     * Returns the items Moodle adds to the primary navigation, as last found by {@see refresh_native_nodes()}.
     *
     * Moodle has no list of these items: core\navigation\views\primary::initialise() adds them depending on
     * the site settings and the user, and other plugins add theirs through the primary_extend hook. The plugin
     * keeps every item it has found, so an item Moodle stops adding can still be configured.
     *
     * @return \stdClass[] Keyed by node key.
     */
    public function get_native_nodes(): array {
        $stored = json_decode((string)get_config('local_extendednav', self::NATIVE_CONFIG), true);
        $nodes = [];
        foreach (is_array($stored) ? $stored : [] as $key => $item) {
            $nodes[$key] = (object)['key' => $key, 'text' => (string)$item['text'], 'action' => (string)$item['action'],
                'icon' => null];
        }
        return $nodes;
    }

    /**
     * Finds the items Moodle adds to the primary navigation for the current user.
     *
     * Builds Moodle's primary navigation without the plugin's changes. Moodle applies its own settings
     * there, such as Appearance > Navigation on the versions that have them, and the theme's removed items.
     *
     * @return \stdClass[] Keyed by node key, with key, text and action (a site path).
     */
    public function discover_native_nodes(): array {
        if (self::$discovered !== null) {
            return self::$discovered;
        }
        global $COURSE, $OUTPUT, $PAGE;

        $nodes = [];
        // Callbacks of other plugins read the global page while the navigation is built, so use this one meanwhile.
        // Moodle also points the global output and course at it, so they are put back with the page; otherwise
        // the page being printed loses its scripts.
        $previouspage = $PAGE;
        $previousoutput = $OUTPUT;
        $previouscourse = $COURSE;
        hooks::$discovering = true;
        try {
            $page = new \moodle_page();
            $page->set_context(\context_system::instance());
            $page->set_url('/');
            $PAGE = $page;
            $view = new \core\navigation\views\primary($page);
            $view->initialise();
            foreach ($view->children as $child) {
                if (!$child->key || !$child->action instanceof \moodle_url) {
                    continue;
                }
                // Moodle adds redirect=0 to the Home link only to stop the automatic redirection to the Dashboard.
                $url = new \moodle_url($child->action);
                $url->remove_params('redirect');
                $action = url_helper::to_site_path($url->out(false));
                $nodes[$child->key] = (object)['key' => $child->key, 'text' => trim(strip_tags((string)$child->text)),
                    'action' => $action ?? $url->out(false), 'icon' => null];
            }
        } catch (\Throwable $e) {
            $nodes = [];
        } finally {
            $PAGE = $previouspage;
            $OUTPUT = $previousoutput;
            $COURSE = $previouscourse;
            hooks::$discovering = false;
        }
        return self::$discovered = $nodes;
    }

    /**
     * Whether Moodle adds the item for the current user.
     *
     * @param string $nodekey Node key.
     * @return bool
     */
    public function is_shown_by_moodle(string $nodekey): bool {
        return isset($this->discover_native_nodes()[$nodekey]);
    }

    /**
     * Saves the items Moodle adds for the current user, keeping the ones found before.
     *
     * Titles and URLs are updated, so placeholders follow Moodle; items Moodle no longer adds are kept.
     */
    public function refresh_native_nodes(): void {
        $discovered = $this->discover_native_nodes();
        if (empty($discovered)) {
            return;
        }
        $stored = json_decode((string)get_config('local_extendednav', self::NATIVE_CONFIG), true);
        $stored = is_array($stored) ? $stored : [];
        foreach ($discovered as $key => $node) {
            $stored[$key] = ['text' => $node->text, 'action' => $node->action];
        }
        $json = json_encode($stored);
        if ($json !== get_config('local_extendednav', self::NATIVE_CONFIG)) {
            set_config(self::NATIVE_CONFIG, $json, 'local_extendednav');
        }
    }

    /**
     * Finds Moodle's items before creating their rows.
     *
     * @param bool $events Whether to trigger node created events.
     */
    public function sync_native_nodes(bool $events = true): void {
        $this->refresh_native_nodes();
        parent::sync_native_nodes($events);
    }

    /**
     * Forgets the items found in this request (for tests).
     */
    public static function reset_discovery(): void {
        self::$discovered = null;
    }

    /**
     * Applies the nodes to the primary navigation.
     *
     * Core nodes are hidden, renamed, re-linked or nested; custom nodes are added;
     * and the top level is ordered as in the plugin.
     *
     * @param \stdClass[] $customnodes Nodes of this menu, ordered by sortorder.
     * @param \core\navigation\views\primary $primarynav The primary navigation view.
     */
    public function inject_nodes(array $customnodes, \core\navigation\views\primary $primarynav): void {
        global $PAGE;

        $states = [];
        foreach ($customnodes as $cnode) {
            $states[$cnode->nodekey] = $this->build_state($cnode);
        }

        $needsscript = false;
        foreach ($states as $nodekey => $state) {
            $decoration = $this->render_icon($state->icon);
            $marker = '';
            if ($state->newwindow) {
                $marker = \html_writer::span('', 'local-extendednav-newwindow', ['hidden' => 'hidden']);
                $needsscript = true;
            }

            $existingnode = $primarynav->get($nodekey);
            if ($existingnode) {
                if (!$state->shown) {
                    $existingnode->remove();
                    continue;
                }
                if ($state->title !== '') {
                    $existingnode->text = format_string($state->title);
                }
                if ($state->url !== null) {
                    $existingnode->action = new \moodle_url($state->url);
                }
                $existingnode->text = $decoration . $existingnode->text . $marker;
            } else if ($state->shown && $state->title !== '' && $state->url !== null && !$this->is_native($nodekey)) {
                // A Moodle item that Moodle did not add (turned off in its settings) is never added by the plugin.
                $existingnode = \navigation_node::create(
                    $decoration . format_string($state->title) . $marker,
                    new \moodle_url($state->url),
                    \navigation_node::TYPE_SETTING,
                    null,
                    $nodekey,
                    null
                );
                $primarynav->add_node($existingnode);
            } else {
                continue;
            }

            if ($state->tooltip !== '') {
                $existingnode->title(format_string($state->tooltip, true, ['escape' => false]));
            }
        }

        // Move child nodes under their parents.
        foreach ($states as $nodekey => $state) {
            if (!$state->shown || $state->parentkey === null || $state->parentkey === $nodekey) {
                continue;
            }
            $node = $primarynav->get($nodekey);
            if (!$node) {
                continue;
            }

            $parentnode = $primarynav->get($state->parentkey);
            if (!$parentnode) {
                // The parent is hidden for this user, so its children are hidden too.
                if (isset($states[$state->parentkey]) && !$states[$state->parentkey]->shown) {
                    $node->remove();
                }
                continue;
            }

            $grandparent = $parentnode->parent;
            if ($grandparent === null || $grandparent->key === $primarynav->key) {
                $node->remove();
                $parentnode->add_node($node);
            }
        }

        // Order the top level as in the plugin; nodes the plugin does not know go last.
        $toplevel = [];
        foreach ($primarynav->children as $child) {
            $toplevel[$child->key] = $child;
        }
        foreach ($toplevel as $child) {
            $child->remove();
        }
        foreach ($customnodes as $cnode) {
            if (isset($toplevel[$cnode->nodekey]) && $states[$cnode->nodekey]->shown) {
                $primarynav->add_node($toplevel[$cnode->nodekey]);
                unset($toplevel[$cnode->nodekey]);
            }
        }
        foreach ($toplevel as $child) {
            if ($child->key && (!isset($states[$child->key]) || $states[$child->key]->shown)) {
                $primarynav->add_node($child);
            }
        }

        foreach ($primarynav->children as $child) {
            $child->icon = null;
        }

        if ($needsscript) {
            $PAGE->requires->js_call_amd('local_extendednav/primary', 'init');
        }
    }

    /**
     * Works out how one node is displayed for the current user.
     *
     * @param \stdClass $cnode Node record.
     * @return \stdClass With shown, title, url, icon, tooltip, parentkey and newwindow.
     */
    private function build_state(\stdClass $cnode): \stdClass {
        $state = new \stdClass();
        $state->shown = node_access::is_shown($cnode);
        $state->title = node_text::get_title($cnode);
        $state->tooltip = node_text::resolve($cnode->tooltip ?? '');
        $state->url = trim((string)($cnode->url ?? '')) === '' ? null : trim((string)$cnode->url);
        $state->icon = trim((string)($cnode->icon ?? ''));
        $state->parentkey = trim((string)($cnode->parentkey ?? '')) === '' ? null : trim((string)$cnode->parentkey);
        $state->newwindow = !empty($cnode->newwindow);

        if (node_access::is_protected($cnode)) {
            $state->parentkey = null;
        }
        if ($state->parentkey === 'siteadminnode') {
            $state->parentkey = null;
        }
        return $state;
    }

    /**
     * Renders the icon placed before the node text.
     *
     * @param string $icon FontAwesome class, pix icon, "none" or empty.
     * @return string HTML.
     */
    private function render_icon(string $icon): string {
        global $OUTPUT;

        if ($icon === '' || node_validator::is_no_icon($icon)) {
            return '';
        }
        if (node_validator::is_fontawesome($icon)) {
            return \html_writer::tag('i', '', ['class' => 'icon fa ' . $icon . ' fa-fw', 'aria-hidden' => 'true']) . ' ';
        }
        try {
            return $OUTPUT->pix_icon($icon, '') . ' ';
        } catch (\moodle_exception $e) {
            return '';
        }
    }
}
