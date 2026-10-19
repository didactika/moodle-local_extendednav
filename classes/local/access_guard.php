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

namespace local_extendednav\local;

use local_extendednav\menu_manager;

/**
 * Blocks access to the URLs of nodes the user is not allowed.
 *
 * A node blocks its URL for a user when the user is not allowed the node and
 * "Block URL access" is set. A node the user is allowed opens its URL again,
 * even when another node blocks it. Languages never take part in this.
 *
 * This only runs on pages that print a header, so it shapes navigation; it does
 * not replace capability checks.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class access_guard {
    /** @var string[] Path prefixes that are never blocked. */
    private const IMMUNE_PREFIXES = ['/admin/', '/login/', '/local/extendednav/'];

    /** @var string The fallbacks are blocked for every user. */
    public const FALLBACKS_BLOCKED = 'blocked';

    /** @var string The fallbacks are blocked for some users. */
    public const FALLBACKS_BLOCKED_FOR_SOME = 'blockedforsome';

    /**
     * Checks the page about to be printed and redirects or stops the user when it is blocked.
     *
     * @param \moodle_page $page The page being printed.
     */
    public static function check(\moodle_page $page): void {
        global $USER;

        if (!self::applies() || !$page->has_set_url()) {
            return;
        }

        $current = url_helper::to_site_path($page->url->out(false));
        if ($current === null) {
            return;
        }

        $target = self::evaluate($current, (int)$USER->id);
        if ($target === null) {
            return;
        }
        // With every fallback page blocked, the user lands on a page that explains it.
        redirect(new \moodle_url($target !== '' ? $target : '/local/extendednav/restricted.php'));
    }

    /**
     * Decides what happens to a user opening a page.
     *
     * @param string $current Site path of the page.
     * @param int $userid User id.
     * @return string|null Null when the page is allowed, the site path to redirect to,
     *                     or an empty string when every fallback page is blocked too.
     */
    public static function evaluate(string $current, int $userid): ?string {
        if (self::is_immune($current)) {
            return null;
        }

        $rules = self::get_user_rules(node_repository::get_all(), $userid);
        if (!self::is_blocked($current, $rules)) {
            return null;
        }

        foreach (self::get_fallbacks() as $fallback) {
            if (!url_helper::matches($fallback, $current) && !self::is_blocked($fallback, $rules)) {
                return $fallback;
            }
        }
        return '';
    }

    /**
     * Whether the path is never blocked.
     *
     * @param string $sitepath Site path.
     * @return bool
     */
    public static function is_immune(string $sitepath): bool {
        foreach (self::IMMUNE_PREFIXES as $prefix) {
            if (strpos($sitepath, $prefix) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the pages a blocked user is sent to, in order of preference.
     *
     * @return string[] Site paths.
     */
    public static function get_fallbacks(): array {
        $fallbacks = ['/my/', '/?redirect=0', '/my/courses.php'];
        $defaulthome = get_default_home_page_url();
        if ($defaulthome !== null) {
            $path = url_helper::to_site_path($defaulthome->out(false));
            if ($path !== null) {
                $fallbacks[] = $path;
            }
        }
        return array_values(array_unique($fallbacks));
    }

    /**
     * Returns the site paths a node controls: its own URL or its core URL.
     *
     * External URLs are never returned, so they never block a page of the site.
     *
     * @param \stdClass $node Node record.
     * @return string[] Site paths.
     */
    public static function get_node_paths(\stdClass $node): array {
        $url = trim((string)($node->url ?? ''));
        if ($url === '') {
            $menu = menu_manager::get_menu($node->menu);
            $native = $menu ? $menu->get_native_node($node->nodekey) : null;
            $url = $native ? (string)$native->action : '';
        }
        $path = url_helper::to_site_path($url);
        return $path === null ? [] : [$path];
    }

    /**
     * Builds the blocked and allowed paths for one user.
     *
     * @param \stdClass[] $nodes Node records.
     * @param int $userid User id.
     * @return array With keys 'blocked' and 'allowed', each a list of site paths.
     */
    public static function get_user_rules(array $nodes, int $userid): array {
        $rules = ['blocked' => [], 'allowed' => []];
        foreach ($nodes as $node) {
            $paths = self::get_node_paths($node);
            if (empty($paths)) {
                continue;
            }
            if (node_access::is_allowed($node, $userid)) {
                array_push($rules['allowed'], ...$paths);
            } else if (!empty($node->blockaccess)) {
                array_push($rules['blocked'], ...$paths);
            }
        }
        return $rules;
    }

    /**
     * Whether the rules block a path.
     *
     * @param string $sitepath Site path.
     * @param array $rules Rules from {@see get_user_rules()}.
     * @return bool
     */
    public static function is_blocked(string $sitepath, array $rules): bool {
        $blocked = false;
        foreach ($rules['blocked'] as $rule) {
            if (url_helper::matches($rule, $sitepath)) {
                $blocked = true;
                break;
            }
        }
        if (!$blocked) {
            return false;
        }
        foreach ($rules['allowed'] as $rule) {
            if (url_helper::matches($rule, $sitepath)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Checks whether a set of nodes would leave users without any fallback page.
     *
     * @param \stdClass[] $nodes Every node of every menu, as they would be saved.
     * @return string|null FALLBACKS_BLOCKED when every fallback is blocked for every user,
     *                     FALLBACKS_BLOCKED_FOR_SOME when it is for users without some role,
     *                     or null when users always have a fallback.
     */
    public static function check_fallbacks(array $nodes): ?string {
        $everyone = ['blocked' => [], 'allowed' => []];
        $some = ['blocked' => [], 'allowed' => []];
        foreach ($nodes as $node) {
            $paths = self::get_node_paths($node);
            if (empty($paths)) {
                continue;
            }
            $visibility = (int)$node->visibility;
            if ($visibility === node_access::VISIBILITY_ALL || node_access::is_protected($node)) {
                array_push($everyone['allowed'], ...$paths);
                array_push($some['allowed'], ...$paths);
            } else if (!empty($node->blockaccess)) {
                array_push($some['blocked'], ...$paths);
                if ($visibility === node_access::VISIBILITY_HIDDEN) {
                    array_push($everyone['blocked'], ...$paths);
                }
            }
        }

        $fallbacks = self::get_fallbacks();
        $allblocked = static function (array $rules) use ($fallbacks): bool {
            foreach ($fallbacks as $fallback) {
                if (!self::is_blocked($fallback, $rules)) {
                    return false;
                }
            }
            return true;
        };

        if ($allblocked($everyone)) {
            return self::FALLBACKS_BLOCKED;
        }
        if ($allblocked($some)) {
            return self::FALLBACKS_BLOCKED_FOR_SOME;
        }
        return null;
    }

    /**
     * Whether blocking applies to the current request and user.
     *
     * @return bool
     */
    private static function applies(): bool {
        if (
            during_initial_install() || (defined('CLI_SCRIPT') && CLI_SCRIPT)
                || (defined('AJAX_SCRIPT') && AJAX_SCRIPT) || (defined('WS_SERVER') && WS_SERVER)
        ) {
            return false;
        }
        if (!get_config('local_extendednav', 'enable_plugin')) {
            return false;
        }
        return isloggedin() && !isguestuser() && !is_siteadmin();
    }
}
