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

use local_extendednav\local\support\url_helper;

/**
 * One of the secondary navigations of Moodle: the one of a course, of the site home and of a category. Each kind of activity
 * has one of its own, see {@see activity}; the site administration is a tree of its own, see {@see admintree}.
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
        'category' => 'secondary_category',
    ];

    /** @var int Longest node key, the size of its column. */
    private const KEY_LENGTH = 50;

    /**
     * Creates the handler of one navigation.
     *
     * @param string $key One of the keys of {@see ORIGINS}, or the key of the navigation of a kind of activity.
     */
    public function __construct(
        /** @var string $key Navigation key. */
        protected readonly string $key
    ) {
    }

    /**
     * Returns the key of a node of a secondary navigation, the same on every page and for every user.
     *
     * Moodle numbers the nodes that a plugin adds without a key (the Reports and the Export of a forum, for example), and the
     * number changes from page to page. Such a node is known by the address it leads to instead: its path, written as a key
     * ("/mod/forum/report/summary/index.php" is "mod_forum_report_summary_index"). A node with neither has no key.
     *
     * @param \navigation_node $node A node of the navigation.
     * @return string|null
     */
    public static function key_of(\navigation_node $node): ?string {
        $key = (string)$node->key;
        if ($key !== '' && !ctype_digit($key)) {
            return $key;
        }
        if (!$node->action instanceof \moodle_url) {
            return null;
        }
        return self::key_of_path((string)url_helper::to_site_path($node->action->out(false)));
    }

    /**
     * Returns the nodes of a secondary navigation that can be changed, by the key that is the same on every page. The page of
     * the activity itself and the way into this plugin are left as they are.
     *
     * @param \navigation_node $view The navigation.
     * @return \navigation_node[]
     */
    public static function children_of(\navigation_node $view): array {
        $children = [];
        foreach ($view->children as $child) {
            $key = self::key_of($child);
            if ($key !== null && !in_array($key, ['modulepage', 'local_extendednav'], true)) {
                $children[$key] = $child;
            }
        }
        return $children;
    }

    /**
     * Describes nodes as they are recorded: the text and the address in the site.
     *
     * @param \navigation_node[] $children Nodes by key.
     * @return \stdClass[] By key, with text and action (a site path, or empty).
     */
    public static function describe(array $children): array {
        $seen = [];
        foreach ($children as $key => $child) {
            $seen[$key] = (object)[
                'text' => trim(strip_tags((string)$child->text)),
                'action' => $child->action instanceof \moodle_url
                    ? (url_helper::to_site_path($child->action->out(false)) ?? '') : '',
            ];
        }
        return $seen;
    }

    /**
     * Writes the path of an address of the site as a node key.
     *
     * @param string $path Site path of the address, such as /mod/forum/export.php?id=4.
     * @return string|null The key, or null when the path says nothing.
     */
    public static function key_of_path(string $path): ?string {
        $path = preg_replace('/\.php$/', '', (string)parse_url($path, PHP_URL_PATH));
        $key = trim((string)preg_replace('/[^a-z0-9]+/', '_', strtolower($path)), '_');
        return $key === '' ? null : substr($key, 0, self::KEY_LENGTH);
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
     * Returns the short name of the navigation: the word of Moodle for what it belongs to.
     *
     * @return string
     */
    public function get_short_name(): string {
        return get_string(['course' => 'course', 'category' => 'category', 'frontpage' => 'sitehome'][$this->key] ?? 'course');
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
        // A node is new when it was never seen, or when it came from an imported file and a page now gives its link.
        $new = array_filter($seen, static fn(\stdClass $node, string $key): bool => !isset($stored[$key])
            || ($stored[$key]['action'] === '' && (string)$node->action !== ''), ARRAY_FILTER_USE_BOTH);
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
