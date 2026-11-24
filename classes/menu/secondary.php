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
 * One of the secondary navigations of Moodle: the one of a course, of the site home, of an activity, of a category
 * and of the site administration.
 *
 * Moodle has no list of the items of these navigations: they depend on the page, the user and the plugins. The plugin
 * does not build any page to find them. It records the items it sees when a page shows the navigation, once per item,
 * so listing them costs nothing in the pages of the users, and the nodes that were found can be ordered, renamed,
 * hidden and restricted from the management page.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class secondary extends base {
    /** @var string[] Origins of the secondary navigations, with the string that names each one. */
    public const ORIGINS = [
        'course' => 'secondary_course',
        'frontpage' => 'secondary_frontpage',
        'activity' => 'secondary_activity',
        'category' => 'secondary_category',
        'admin' => 'secondary_admin',
    ];

    /** @var string[] Origins in which a node can only be hidden and ordered, never renamed, re-linked or added. */
    public const ORDER_ONLY = ['admin'];

    /**
     * Creates the handler of one navigation.
     *
     * @param string $key One of the keys of {@see ORIGINS}.
     */
    public function __construct(
        /** @var string $key Origin key. */
        private readonly string $key
    ) {
    }

    /**
     * Returns the menu key.
     *
     * @return string
     */
    public function get_key(): string {
        return $this->key;
    }

    /**
     * Returns the localised name of the navigation.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string(self::ORIGINS[$this->key], 'local_extendednav');
    }

    /**
     * Whether nodes of this navigation can only be hidden and ordered.
     *
     * @return bool
     */
    public function is_order_only(): bool {
        return in_array($this->key, self::ORDER_ONLY, true);
    }

    /**
     * Returns the nodes Moodle has shown in this navigation, as recorded so far.
     *
     * @return \stdClass[] Keyed by node key, with key, text, action and icon.
     */
    public function get_native_nodes(): array {
        $nodes = [];
        foreach ($this->get_stored_nodes() as $key => $item) {
            $nodes[$key] = (object)['key' => $key, 'text' => $item['text'] ?: $key, 'action' => $item['action'], 'icon' => null];
        }
        return $nodes;
    }

    /**
     * The items of a Moodle setting do not apply here.
     *
     * @param string $line Line without the leading dashes.
     * @return array
     */
    public function parse_config_line(string $line): array {
        return ['text' => $line, 'url' => '', 'tooltip' => '', 'langs' => ''];
    }

    /**
     * Records the nodes a page shows in this navigation, and gives every new one its row.
     *
     * Nothing is written when every node was seen before, which is what happens in almost every page.
     *
     * @param \stdClass[] $seen Nodes seen on the page, keyed by node key, with text and action (a site path).
     * @return bool Whether a node was new.
     */
    public function record_seen(array $seen): bool {
        $stored = $this->get_stored_nodes();
        $new = array_diff_key($seen, $stored);
        if (empty($new)) {
            return false;
        }
        foreach ($new as $key => $node) {
            $stored[$key] = ['action' => (string)$node->action, 'text' => (string)$node->text];
        }
        set_config($this->config_name(), json_encode($stored), 'local_extendednav');
        $this->sync_native_nodes(false);
        return true;
    }

    /**
     * Forgets deleted nodes, so they come back as new ones if Moodle shows them again.
     *
     * @param string[] $nodekeys Node keys.
     */
    public function forget_native_nodes(array $nodekeys): void {
        $stored = array_diff_key($this->get_stored_nodes(), array_flip($nodekeys));
        set_config($this->config_name(), json_encode($stored), 'local_extendednav');
    }

    /**
     * Returns the name of the plugin setting that keeps the nodes found.
     *
     * @return string
     */
    private function config_name(): string {
        return 'nativenodes_' . $this->key;
    }

    /**
     * Reads the nodes found so far.
     *
     * @return array Keyed by node key, each with action and text.
     */
    private function get_stored_nodes(): array {
        $stored = json_decode((string)get_config('local_extendednav', $this->config_name()), true);
        $nodes = [];
        foreach (is_array($stored) ? $stored : [] as $key => $item) {
            $nodes[(string)$key] = ['action' => (string)($item['action'] ?? ''), 'text' => (string)($item['text'] ?? '')];
        }
        return $nodes;
    }
}
