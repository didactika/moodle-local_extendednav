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

/**
 * URL normalisation and matching.
 *
 * A "site path" is a URL relative to the Moodle root ($CFG->wwwroot), such as
 * "/my/" or "/course/view.php?id=5". Index pages are written without
 * "index.php", so "/my/index.php" and "/my/" are the same site path.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class url_helper {
    /** @var string[] URL schemes that are never accepted. */
    private const FORBIDDEN_SCHEMES = ['javascript', 'data', 'vbscript', 'file'];

    /**
     * Normalises a URL typed by an administrator.
     *
     * Relative paths get a leading slash, "www." addresses get https:// and
     * protocol-relative addresses get https:.
     *
     * @param string|null $url URL as typed.
     * @return string|null Normalised URL, or null when empty.
     */
    public static function normalise(?string $url): ?string {
        $url = trim((string)$url);
        if ($url === '') {
            return null;
        }
        if (preg_match('~^[a-z][a-z0-9+.\-]*:~i', $url)) {
            return $url;
        }
        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }
        if (stripos($url, 'www.') === 0) {
            return 'https://' . $url;
        }
        if ($url[0] !== '/') {
            $url = '/' . $url;
        }
        return $url;
    }

    /**
     * Whether the URL uses a scheme that must never be saved.
     *
     * @param string $url Normalised URL.
     * @return bool
     */
    public static function has_forbidden_scheme(string $url): bool {
        $scheme = strtolower((string)parse_url(trim($url), PHP_URL_SCHEME));
        if ($scheme === '' && preg_match('~^\s*([a-z][a-z0-9+.\-]*)\s*:~i', $url, $matches)) {
            $scheme = strtolower($matches[1]);
        }
        return in_array($scheme, self::FORBIDDEN_SCHEMES, true);
    }

    /**
     * Whether the URL points to a page of this Moodle site.
     *
     * @param string|null $url URL as stored.
     * @return bool
     */
    public static function is_internal(?string $url): bool {
        return self::to_site_path($url) !== null;
    }

    /**
     * Converts a URL to its site path.
     *
     * @param string|null $url Relative or absolute URL.
     * @return string|null Site path, or null for an external or empty URL.
     */
    public static function to_site_path(?string $url): ?string {
        global $CFG;

        $url = self::normalise($url);
        if ($url === null || self::has_forbidden_scheme($url)) {
            return null;
        }
        if ($url[0] === '/') {
            return self::canonical($url);
        }

        $parts = parse_url($url);
        $root = parse_url($CFG->wwwroot);
        if ($parts === false || empty($parts['host']) || empty($root['host'])) {
            return null;
        }
        if (!in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }
        if (strcasecmp($parts['host'], $root['host']) !== 0 || ($parts['port'] ?? null) !== ($root['port'] ?? null)) {
            return null;
        }

        $rootpath = rtrim($root['path'] ?? '', '/');
        $path = $parts['path'] ?? '/';
        if ($rootpath !== '') {
            if ($path === $rootpath) {
                $path = '/';
            } else if (strpos($path, $rootpath . '/') === 0) {
                $path = substr($path, strlen($rootpath));
            } else {
                return null;
            }
        }
        if (isset($parts['query']) && $parts['query'] !== '') {
            $path .= '?' . $parts['query'];
        }
        return self::canonical($path);
    }

    /**
     * Whether the current page matches a rule.
     *
     * The page paths must be equal, and every query parameter of the rule must be
     * present in the page with the same value. "/course/view.php?id=5" matches
     * "/course/view.php?id=5&section=2" but not "/course/view.php?id=6".
     *
     * @param string $rule Site path of the rule.
     * @param string $current Site path of the current page.
     * @return bool
     */
    public static function matches(string $rule, string $current): bool {
        [$rulepath, $rulequery] = self::split(self::canonical($rule));
        [$currentpath, $currentquery] = self::split(self::canonical($current));
        if ($rulepath !== $currentpath) {
            return false;
        }

        parse_str($rulequery, $ruleparams);
        parse_str($currentquery, $currentparams);
        foreach ($ruleparams as $name => $value) {
            if (!array_key_exists($name, $currentparams) || $currentparams[$name] != $value) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns the path part of a site path.
     *
     * @param string $sitepath Site path.
     * @return string
     */
    public static function get_path(string $sitepath): string {
        return self::split(self::canonical($sitepath))[0];
    }

    /**
     * Rewrites a site path in its canonical form.
     *
     * @param string $sitepath Site path, possibly with a fragment or "index.php".
     * @return string
     */
    private static function canonical(string $sitepath): string {
        $sitepath = explode('#', $sitepath, 2)[0];
        [$path, $query] = self::split($sitepath);
        if ($path === '') {
            $path = '/';
        }
        if (substr($path, -10) === '/index.php') {
            $path = substr($path, 0, -9);
        }
        return $query === '' ? $path : $path . '?' . $query;
    }

    /**
     * Splits a site path into path and query.
     *
     * @param string $sitepath Site path.
     * @return string[] Path and query string.
     */
    private static function split(string $sitepath): array {
        $parts = explode('?', $sitepath, 2);
        return [$parts[0], $parts[1] ?? ''];
    }
}
