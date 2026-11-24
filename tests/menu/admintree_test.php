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

use local_extendednav\local\node\access;
use local_extendednav\local\listing\nodes;
use local_extendednav\local\node\manager;
use local_extendednav\local\node\repository;
use local_extendednav\menu_manager;

/**
 * Tests for the site administration, shaped through every category and page of its tree.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\menu\admintree
 */
final class admintree_test extends \advanced_testcase {
    /**
     * Builds the tree as the administrator and gives every node its row.
     *
     * @return admintree
     */
    private function prepare(): admintree {
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();
        require_once($CFG->libdir . '/adminlib.php');
        admintree::reset();
        admin_get_root(true, false);
        $menu = menu_manager::get_menu(admintree::KEY);
        $menu->ensure_synced();
        return $menu;
    }

    /**
     * Every category and every page of the tree is a node, at any depth, with its parent.
     */
    public function test_every_level_of_the_tree_is_a_node(): void {
        global $DB;

        $menu = $this->prepare();
        $natives = $menu->get_native_nodes();

        $this->assertArrayHasKey('users', $natives);
        $this->assertSame('', $natives['users']->parentkey);
        $this->assertTrue($natives['users']->category);
        $this->assertArrayHasKey('accounts', $natives);
        $this->assertSame('users', $natives['accounts']->parentkey);
        $deepest = max(array_map(static fn(\stdClass $node): int => $node->depth, $natives));
        $this->assertGreaterThanOrEqual(3, $deepest, 'Pages deep in the tree are nodes too.');
        $this->assertSame(count($natives), $DB->count_records('local_extendednav', ['menu' => admintree::KEY]));

        $menu->ensure_synced();
        $this->assertSame(count($natives), $DB->count_records('local_extendednav', ['menu' => admintree::KEY]), 'Once.');
    }

    /**
     * The list shows one level of the tree, with the way back to the top, and a search looks in every level.
     */
    public function test_the_list_shows_one_level(): void {
        $this->prepare();

        $top = nodes::get(admintree::KEY, 0, '', '', '', 0, 0);
        $this->assertContains('users', array_column($top['rows'], 'nodekey'));
        $this->assertNotContains('accounts', array_column($top['rows'], 'nodekey'));
        $this->assertTrue($top['rows'][array_search('users', array_column($top['rows'], 'nodekey'))]['haschildren']);
        $this->assertTrue($top['reorderable']);

        $users = nodes::get(admintree::KEY, 0, '', '', 'users', 0, 0);
        $this->assertContains('accounts', array_column($users['rows'], 'nodekey'));
        $this->assertSame(['users'], array_column($users['path'], 'key'));

        $found = nodes::get(admintree::KEY, 0, 'accounts');
        $this->assertContains('accounts', array_column($found['rows'], 'nodekey'));
        $this->assertFalse($found['reorderable']);
    }

    /**
     * A page is renamed and hidden, and a category is ordered, in the tree of the request.
     */
    public function test_the_changes_reach_the_tree(): void {
        $this->prepare();
        $rows = repository::get_menu_nodes_by_key(admintree::KEY);
        manager::update((object)['id' => $rows['users']->id, 'title' => 'People']);
        manager::update((object)['id' => $rows['accounts']->id, 'visibility' => access::VISIBILITY_HIDDEN,
            'hideadmins' => 1]);
        manager::place(admintree::KEY, 0, ['users'], '', 'courses');

        admintree::reset();
        $root = admin_get_root(true, false);
        admintree::apply($root);

        $this->assertSame('People', $root->locate('users')->visiblename);
        $this->assertTrue($root->locate('accounts')->hidden);
        $names = array_map(static fn($part): string => $part->name, $root->get_children());
        $this->assertSame(array_search('courses', $names) + 1, array_search('users', $names));
        $this->assertArrayHasKey('', admintree::get_ordered_parents());
    }

    /**
     * The way to this plugin is never hidden, and a link of the plugin goes in its category.
     */
    public function test_protected_nodes_and_links(): void {
        $this->prepare();
        $rows = repository::get_menu_nodes_by_key(admintree::KEY);
        manager::update((object)['id' => $rows['appearance']->id, 'visibility' => access::VISIBILITY_HIDDEN,
            'hideadmins' => 1]);
        manager::create(manager::prepare(['nodekey' => 'staffguide', 'title' => 'Staff guide',
            'url' => '/mod/page/view.php?id=3', 'parentkey' => 'users'], admintree::KEY));

        admintree::reset();
        $root = admin_get_root(true, false);
        admintree::apply($root);

        $this->assertFalse((bool)$root->locate('appearance')->hidden);
        $link = $root->locate('staffguide');
        $this->assertInstanceOf(\admin_externalpage::class, $link);
        $this->assertSame('users', $root->locate('users')->name);
        $this->assertFalse(menu_manager::get_menu(admintree::KEY)->is_native('staffguide'));
    }

    /**
     * A node of the plugin without a link is a category, at the top or inside another one, and holds the nodes put in it; a
     * link at the top, with no category to go in, is left out.
     */
    public function test_categories_of_the_plugin(): void {
        $this->prepare();
        // Added in the order that needs rounds: the link before its category, the category before its parent.
        $nodes = [
            ['extportal', 'Portal', 'https://example.com/', 'extmoretools'],
            ['extmoretools', 'More tools', '', 'exttools'],
            ['exttools', 'Tools', '', ''],
            ['extstray', 'Stray link', '/my/', ''],
        ];
        foreach ($nodes as [$key, $title, $url, $parent]) {
            $data = ['nodekey' => $key, 'title' => $title, 'url' => $url, 'parentkey' => $parent];
            manager::create(manager::prepare($data, admintree::KEY));
        }
        // The rows of the tree are synchronised on every page: the categories of the plugin, which have no address, stay.
        menu_manager::get_menu(admintree::KEY)->ensure_synced();
        $this->assertNotEmpty(repository::get_menu_nodes_by_key(admintree::KEY)['exttools'] ?? null);

        admintree::reset();
        $root = admin_get_root(true, false);
        admintree::apply($root);

        $tools = $root->locate('exttools');
        $this->assertInstanceOf(\admin_category::class, $tools);
        $this->assertSame('Tools', $tools->visiblename);
        $this->assertInstanceOf(\admin_category::class, $root->locate('extmoretools'));
        $inside = array_map(static fn($part): string => $part->name, $root->locate('extmoretools')->get_children());
        $this->assertContains('extportal', $inside);
        $stray = $root->locate('extstray');
        $this->assertInstanceOf(\admin_externalpage::class, $stray, 'A link at the top goes in General.');
        $this->assertContains('extstray', array_map(static fn($part): string => $part->name, $root->get_children()));
        $tree = menu_manager::get_menu(admintree::KEY);
        $this->assertFalse($tree->is_native('exttools'), 'A category of the plugin is not native.');

        // Applying again gives the same tree.
        admintree::apply($root);
        $this->assertCount(1, array_filter($root->get_children(), static fn($part): bool => $part->name === 'exttools'));
    }

    /**
     * A node without a link is valid in the site administration only, where it is a category.
     */
    public function test_only_the_site_administration_takes_a_node_without_a_link(): void {
        $this->prepare();
        $category = manager::prepare(['nodekey' => 'tools', 'title' => 'Tools', 'url' => ''], admintree::KEY);
        $this->assertArrayNotHasKey('url', \local_extendednav\local\node\validator::validate($category, [])['errors']);
        $item = manager::prepare(['nodekey' => 'tools', 'title' => 'Tools', 'url' => ''], 'primary');
        $this->assertArrayHasKey('url', \local_extendednav\local\node\validator::validate($item, [])['errors']);
    }

    /**
     * Turning the navigation off leaves the tree as Moodle draws it.
     */
    public function test_a_navigation_turned_off_changes_nothing(): void {
        $this->prepare();
        $rows = repository::get_menu_nodes_by_key(admintree::KEY);
        manager::update((object)['id' => $rows['users']->id, 'title' => 'People']);
        set_config('enabled_' . admintree::KEY, 0, 'local_extendednav');

        admintree::reset();
        $root = admin_get_root(true, false);
        admintree::apply($root);

        $this->assertNotSame('People', $root->locate('users')->visiblename);
    }

    /**
     * The names of Moodle with a slash become keys and back.
     */
    public function test_keys_and_names(): void {
        $this->assertSame('tool_usertours-_-tours', admintree::key_of('tool_usertours/tours'));
        $this->assertSame('tool_usertours/tours', admintree::name_of('tool_usertours-_-tours'));
    }
}
