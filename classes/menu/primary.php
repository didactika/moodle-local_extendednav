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
use local_extendednav\local\node_display;
use local_extendednav\local\node_icon;
use local_extendednav\local\node_text;
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
     * Only the URLs are kept, because a saved title would stay in one language. Once Moodle's items have been
     * found in this request, the text is Moodle's own, in the current language; otherwise it is the node key.
     *
     * @return \stdClass[] Keyed by node key.
     */
    public function get_native_nodes(): array {
        $nodes = [];
        foreach ($this->get_stored_nodes() as $key => $action) {
            $text = isset(self::$discovered[$key]) ? self::$discovered[$key]->text : $key;
            $nodes[$key] = (object)['key' => $key, 'text' => $text, 'action' => $action, 'icon' => null];
        }
        return $nodes;
    }

    /**
     * Returns the URL of every item found so far.
     *
     * @return string[] Site paths keyed by node key.
     */
    private function get_stored_nodes(): array {
        $stored = json_decode((string)get_config('local_extendednav', self::NATIVE_CONFIG), true);
        $actions = [];
        foreach (is_array($stored) ? $stored : [] as $key => $item) {
            $actions[(string)$key] = (string)($item['action'] ?? '');
        }
        return $actions;
    }

    /**
     * Saves the URL of every item found so far.
     *
     * @param string[] $actions Site paths keyed by node key.
     */
    private function save_stored_nodes(array $actions): void {
        $stored = [];
        foreach ($actions as $key => $action) {
            $stored[$key] = ['action' => $action];
        }
        $json = json_encode($stored);
        if ($json !== get_config('local_extendednav', self::NATIVE_CONFIG)) {
            set_config(self::NATIVE_CONFIG, $json, 'local_extendednav');
        }
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
        // Links are marked active by comparing them with the URL Moodle keeps for the request, which it would
        // otherwise take from this page.
        if ($previouspage->has_set_url()) {
            \navigation_node::override_active_url(new \moodle_url($previouspage->url));
        }
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
     * New items are added and changed URLs updated, so blocking follows Moodle; items Moodle no longer adds
     * are kept until they are deleted. Nothing is written when nothing changed.
     */
    public function refresh_native_nodes(): void {
        $discovered = $this->discover_native_nodes();
        if (empty($discovered)) {
            return;
        }
        $stored = $this->get_stored_nodes();
        foreach ($discovered as $key => $node) {
            $stored[$key] = $node->action;
        }
        $this->save_stored_nodes($stored);
    }

    /**
     * Whether the node can be deleted: plugin nodes, and Moodle's items that Moodle does not add right now.
     *
     * Site administration never can, as it cannot be hidden: it is missing only for users without access.
     *
     * @param string $nodekey Node key.
     * @return bool
     */
    public function can_delete(string $nodekey): bool {
        if (!$this->is_native($nodekey)) {
            return true;
        }
        return $nodekey !== 'siteadminnode' && !$this->is_shown_by_moodle($nodekey);
    }

    /**
     * Forgets deleted Moodle items, so they come back as new items if Moodle adds them again.
     *
     * @param string[] $nodekeys Node keys.
     */
    public function forget_native_nodes(array $nodekeys): void {
        $stored = $this->get_stored_nodes();
        $this->save_stored_nodes(array_diff_key($stored, array_flip($nodekeys)));
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

        // A node with submenu items opens its submenu, so how its link opens does not apply.
        $parents = [];
        foreach ($states as $nodekey => $state) {
            if ($state->shown && $state->parentkey !== null && $state->parentkey !== $nodekey) {
                $parents[$state->parentkey] = true;
            }
        }

        $needsscript = false;
        $needslabels = false;
        foreach ($states as $nodekey => $state) {
            $decoration = $this->render_icon($state->icon);
            $marker = '';
            if (!empty($state->linkattributes) && !isset($parents[$nodekey])) {
                // The primary navigation template prints no link attributes, so they go on a hidden marker in the text.
                $marker = \html_writer::span('', 'local-extendednav-display', ['hidden' => 'hidden'] + $state->linkattributes);
                $needsscript = true;
            }
            $needslabels = $needslabels || $decoration !== '' || $marker !== '';

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

        $this->activate_matching_node($customnodes, $states, $primarynav);

        if ($needslabels) {
            // Moodle 5.3 prints the text of top-level items as plain text; the module shows the icon and marker again.
            $PAGE->requires->js_call_amd('local_extendednav/primary_labels', 'init');
        }
        if ($needsscript) {
            $PAGE->requires->js_call_amd('local_extendednav/link_display', 'init');
        }
    }

    /**
     * Marks the node of the current page as active when Moodle would not find it.
     *
     * Moodle marks an item active only when its URL is exactly the page URL, so an item linking to
     * "/user/profile.php" is not active on "/user/profile.php?id=2". When no item is exact and the page did not
     * choose its tab, the first node shown whose URL matches the page as URL blocking does becomes the tab.
     *
     * @param \stdClass[] $customnodes Nodes of this menu, ordered by sortorder.
     * @param \stdClass[] $states Display states keyed by node key.
     * @param \core\navigation\views\primary $primarynav The primary navigation view.
     */
    private function activate_matching_node(array $customnodes, array $states, \core\navigation\views\primary $primarynav): void {
        global $PAGE;

        if (!$PAGE->has_set_url() || $PAGE->get_primary_activate_tab() !== null) {
            return;
        }
        $current = url_helper::to_site_path($PAGE->url->out(false));
        if ($current === null) {
            return;
        }
        $nodes = [];
        foreach ($primarynav->children as $child) {
            $nodes[] = $child;
            foreach ($child->children as $grandchild) {
                $nodes[] = $grandchild;
            }
        }
        foreach ($nodes as $node) {
            if ($node->action instanceof \moodle_url && $node->action->compare($PAGE->url, URL_MATCH_EXACT)) {
                return;
            }
        }
        foreach ($customnodes as $cnode) {
            $state = $states[$cnode->nodekey];
            $rule = $state->shown && $state->url !== null ? url_helper::to_site_path($state->url) : null;
            if ($rule !== null && $primarynav->find($cnode->nodekey, null) && url_helper::matches($rule, $current)) {
                $PAGE->set_primary_active_tab($cnode->nodekey);
                return;
            }
        }
    }

    /**
     * Works out how one node is displayed for the current user.
     *
     * @param \stdClass $cnode Node record.
     * @return \stdClass With shown, title, url, icon, tooltip, parentkey and linkattributes.
     */
    private function build_state(\stdClass $cnode): \stdClass {
        $state = new \stdClass();
        $state->shown = node_access::is_shown($cnode);
        $state->title = node_text::get_title($cnode);
        $state->tooltip = node_text::resolve($cnode->tooltip ?? '');
        $state->url = trim((string)($cnode->url ?? '')) === '' ? null : trim((string)$cnode->url);
        $state->icon = trim((string)($cnode->icon ?? ''));
        $state->parentkey = trim((string)($cnode->parentkey ?? '')) === '' ? null : trim((string)$cnode->parentkey);
        $state->linkattributes = node_display::get_link_attributes($cnode);

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
        $html = node_icon::render($icon);
        return $html === '' ? '' : $html . ' ';
    }
}
