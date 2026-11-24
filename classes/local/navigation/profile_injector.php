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

namespace local_extendednav\local\navigation;

use core_user\output\myprofile\node;
use core_user\output\myprofile\tree;
use local_extendednav\local\node\access;
use local_extendednav\local\node\repository;
use local_extendednav\local\node\text;
use local_extendednav\menu\profile;
use local_extendednav\menu_manager;

/**
 * Adds the items of the site to the boxes of the profile of a user, and records the boxes it finds so the site can choose them.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class profile_injector {
    /**
     * Adds the items of the site to the profile.
     *
     * @param tree $tree The profile, as Moodle and the plugins have built it so far.
     * @param \stdClass $user The user whose profile it is.
     */
    public static function apply(tree $tree, \stdClass $user): void {
        if (during_initial_install() || !get_config('local_extendednav', 'enable_plugin')) {
            return;
        }
        $seen = [];
        foreach ($tree->categories as $name => $category) {
            $seen[(string)$name] = (object)['text' => trim(strip_tags((string)$category->title)), 'action' => '', 'parent' => ''];
        }
        if (!$seen) {
            return;
        }
        (new profile())->record_seen($seen);
        if (!menu_manager::is_enabled(profile::KEY)) {
            return;
        }

        $rows = array_filter(repository::get_menu_nodes_by_key(profile::KEY), static function (\stdClass $row) use ($seen): bool {
            return isset($seen[(string)$row->parentkey]) && trim((string)$row->url) !== '' && access::is_shown($row);
        });
        uasort($rows, static fn(\stdClass $one, \stdClass $two): int =>
            [(int)$one->sortorder, $one->nodekey] <=> [(int)$two->sortorder, $two->nodekey]);

        // The items of one box follow one another, in the order of the site.
        $last = [];
        foreach ($rows as $key => $row) {
            $parent = (string)$row->parentkey;
            $name = 'local_extendednav_' . $key;
            $tree->add_node(new node(
                $parent,
                $name,
                format_string(text::resolve($row->title)),
                $last[$parent] ?? null,
                new \moodle_url((string)$row->url)
            ));
            $last[$parent] = $name;
        }
    }
}
