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
use local_extendednav\local\node_manager;

#[\PHPUnit\Framework\Attributes\CoversClass(\local_extendednav\menu\primary::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_extendednav\hooks::class)]
/**
 * Tests for the primary navigation.
 *
 * The primary navigation view runs the plugin's hook when it is initialised.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\menu\primary
 * @covers     \local_extendednav\hooks
 */
final class primary_test extends \advanced_testcase {
    /**
     * Logs in a user and resets the request state.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        node_access::reset_caches();
        hooks::reset();
        primary::reset_discovery();
        // From Moodle 5.3 the Home item is only added when this setting is on; earlier versions ignore it.
        set_config('enablemyhome', 1);
        $this->getDataGenerator()->get_plugin_generator('local_extendednav')->ensure_native_nodes();
        $this->setUser($this->getDataGenerator()->create_user());
    }

    /**
     * Builds the primary navigation of a page.
     *
     * @param string $url URL of the page.
     * @return \core\navigation\views\primary
     */
    private function build_primary(string $url = '/my/'): \core\navigation\views\primary {
        global $PAGE;

        $PAGE = new \moodle_page();
        $PAGE->set_context(\context_system::instance());
        $PAGE->set_url($url);
        $primary = new \core\navigation\views\primary($PAGE);
        $primary->initialise();
        return $primary;
    }

    /**
     * Returns the keys of the top-level nodes.
     *
     * @param \core\navigation\views\primary $primary Primary navigation.
     * @return string[]
     */
    private function get_top_keys(\core\navigation\views\primary $primary): array {
        $keys = [];
        foreach ($primary->children as $child) {
            $keys[] = $child->key;
        }
        return $keys;
    }

    /**
     * Core nodes keep their translated title while empty.
     */
    public function test_core_nodes_keep_core_titles(): void {
        $primary = $this->build_primary();
        $this->assertSame(get_string('myhome'), strip_tags($primary->get('myhome')->text));
    }

    /**
     * Core nodes can be hidden, renamed and given a tooltip; custom nodes are added in order.
     */
    public function test_inject_nodes(): void {
        $generator = $this->getDataGenerator()->get_plugin_generator('local_extendednav');
        $generator->create_node(['nodekey' => 'home', 'visibility' => 0]);
        $generator->create_node(['nodekey' => 'myhome', 'title' => 'My learning', 'tooltip' => 'Your courses and news']);
        $generator->create_node(['nodekey' => 'help', 'title' => 'Help', 'url' => '/help.php', 'icon' => 'fa-circle-question',
            'newwindow' => 1]);

        $primary = $this->build_primary();
        $keys = $this->get_top_keys($primary);

        $this->assertNotContains('home', $keys);
        $this->assertSame('My learning', strip_tags($primary->get('myhome')->text));
        $this->assertSame('Your courses and news', $primary->get('myhome')->get_title());
        $this->assertContains('help', $keys);
        $this->assertStringContainsString('fa-circle-question', $primary->get('help')->text);
        $this->assertStringContainsString('local-extendednav-newwindow', $primary->get('help')->text);
        $this->assertGreaterThan(array_search('myhome', $keys), array_search('help', $keys));
    }

    /**
     * Child nodes are moved under their parent, and hidden with it.
     */
    public function test_submenus(): void {
        $generator = $this->getDataGenerator()->get_plugin_generator('local_extendednav');
        $generator->create_node(['nodekey' => 'resources', 'title' => 'Resources', 'url' => '/resources.php']);
        $generator->create_node(['nodekey' => 'library', 'title' => 'Library', 'url' => '/library.php',
            'parentkey' => 'resources']);

        $primary = $this->build_primary();
        $this->assertNotContains('library', $this->get_top_keys($primary));
        $this->assertNotFalse($primary->get('resources')->get('library'));

        $generator->create_node(['nodekey' => 'resources', 'title' => 'Resources', 'url' => '/resources.php',
            'visibility' => 0]);
        hooks::reset();
        $primary = $this->build_primary();
        $this->assertFalse($primary->find('library', null));
    }

    /**
     * Language-specific nodes only appear in their language.
     */
    public function test_languages(): void {
        $generator = $this->getDataGenerator()->get_plugin_generator('local_extendednav');
        $generator->create_node(['nodekey' => 'ayuda', 'title' => 'Ayuda', 'url' => '/ayuda.php', 'langs' => 'es']);

        force_current_language('en');
        $this->assertNotContains('ayuda', $this->get_top_keys($this->build_primary()));
    }

    /**
     * The items come from Moodle's own primary navigation and follow its settings.
     */
    public function test_items_come_from_moodle(): void {
        $this->setAdminUser();
        $menu = new primary();
        primary::reset_discovery();
        $found = array_keys($menu->discover_native_nodes());
        $this->assertContains('myhome', $found);
        $this->assertContains('siteadminnode', $found);
        $this->assertSame('/my/', $menu->get_native_node('myhome')->action);
        $this->assertSame('/', $menu->get_native_node('home')->action, 'redirect=0 is not part of the item.');

        set_config('enabledashboard', 0);
        primary::reset_discovery();
        $this->assertFalse($menu->is_shown_by_moodle('myhome'));
        $this->assertTrue($menu->is_native('myhome'), 'An item Moodle stops adding is still known.');
    }

    /**
     * Finding Moodle's items leaves the page being printed unchanged, so its scripts are still printed.
     */
    public function test_discovery_keeps_the_page_output(): void {
        global $COURSE, $OUTPUT, $PAGE;

        $this->setAdminUser();
        $PAGE->set_context(\context_system::instance());
        $PAGE->set_url('/local/extendednav/manage.php');
        $PAGE->initialise_theme_and_output();
        $page = $PAGE;
        $output = $OUTPUT;
        $course = $COURSE;

        primary::reset_discovery();
        (new primary())->discover_native_nodes();

        $this->assertSame($page, $PAGE);
        $this->assertSame($output, $OUTPUT);
        $this->assertSame($course, $COURSE);
    }

    /**
     * Only the URLs of Moodle's items are saved; their text is Moodle's own, in the current language.
     */
    public function test_item_texts_are_not_saved(): void {
        $this->setAdminUser();
        $menu = new primary();
        primary::reset_discovery();
        $menu->refresh_native_nodes();

        $stored = json_decode(get_config('local_extendednav', 'nativenodes_primary'), true);
        $this->assertSame(['action' => '/my/'], $stored['myhome']);
        $this->assertSame(get_string('myhome'), $menu->get_native_node('myhome')->text);

        primary::reset_discovery();
        $this->assertSame('myhome', $menu->get_native_node('myhome')->text, 'Without Moodle\'s text, the key is shown.');
    }

    /**
     * Items Moodle no longer adds can be deleted, and come back as new items when Moodle adds them again.
     */
    public function test_unavailable_items_can_be_deleted(): void {
        global $DB;

        $this->setAdminUser();
        $menu = new primary();
        set_config('enabledashboard', 0);
        primary::reset_discovery();
        $this->assertTrue($menu->can_delete('myhome'));
        $this->assertFalse($menu->can_delete('siteadminnode'));
        $this->assertFalse($menu->can_delete('home'), 'Moodle adds it.');

        // Users without access to Site administration do not get it, but it is still kept.
        $this->setUser($this->getDataGenerator()->create_user());
        primary::reset_discovery();
        $this->assertFalse($menu->is_shown_by_moodle('siteadminnode'));
        $this->assertFalse($menu->can_delete('siteadminnode'));
        $this->setAdminUser();
        primary::reset_discovery();

        $records = $DB->get_records('local_extendednav', ['menu' => 'primary'], '', '*');
        $this->assertSame(1, node_manager::delete($records, false));
        $this->assertFalse($DB->record_exists('local_extendednav', ['menu' => 'primary', 'nodekey' => 'myhome']));
        $this->assertTrue($DB->record_exists('local_extendednav', ['menu' => 'primary', 'nodekey' => 'siteadminnode']));
        $this->assertFalse($menu->is_native('myhome'));

        set_config('enabledashboard', 1);
        primary::reset_discovery();
        $menu->sync_native_nodes(false);
        $record = $DB->get_record('local_extendednav', ['menu' => 'primary', 'nodekey' => 'myhome'], '*', MUST_EXIST);
        $this->assertNull($record->title);
        $this->assertTrue($menu->is_native('myhome'));
    }

    /**
     * Finding Moodle's items leaves the URL Moodle uses to mark active links unchanged.
     */
    public function test_discovery_keeps_the_active_url(): void {
        global $PAGE;

        $this->setAdminUser();
        $PAGE->set_context(\context_system::instance());
        $PAGE->set_url('/local/extendednav/manage.php');
        primary::reset_discovery();
        (new primary())->discover_native_nodes();

        $node = \navigation_node::create('Manage', new \moodle_url('/local/extendednav/manage.php'));
        $this->assertTrue($node->check_if_active());
    }

    /**
     * A plugin item is the active tab on its page, even when the page has more parameters.
     */
    public function test_item_of_the_page_is_active(): void {
        global $PAGE;

        $generator = $this->getDataGenerator()->get_plugin_generator('local_extendednav');
        $generator->create_node(['nodekey' => 'profile', 'title' => 'Profile', 'url' => '/user/profile.php']);
        $generator->create_node(['nodekey' => 'mine', 'title' => 'Mine', 'url' => '/my/']);

        $this->build_primary('/user/profile.php?id=2');
        $this->assertSame('profile', $PAGE->get_primary_activate_tab());

        $this->build_primary('/user/profile.php?id=2#section');
        $this->assertSame('profile', $PAGE->get_primary_activate_tab(), 'The fragment does not matter.');

        // Moodle's exact match wins: the Dashboard links to /my/ too.
        $this->build_primary('/my/');
        $this->assertNull($PAGE->get_primary_activate_tab());

        $this->build_primary('/course/index.php');
        $this->assertNull($PAGE->get_primary_activate_tab());
    }

    /**
     * An item Moodle does not add is not added by the plugin, even with a title and a URL.
     */
    public function test_item_turned_off_in_moodle_is_not_added(): void {
        $generator = $this->getDataGenerator()->get_plugin_generator('local_extendednav');
        $generator->create_node(['nodekey' => 'myhome', 'title' => 'My learning', 'url' => '/my/']);
        set_config('enabledashboard', 0);

        $this->assertNotContains('myhome', $this->get_top_keys($this->build_primary()));
    }

    /**
     * Nothing changes when the plugin is disabled.
     */
    public function test_disabled(): void {
        set_config('enable_plugin', 0, 'local_extendednav');
        $generator = $this->getDataGenerator()->get_plugin_generator('local_extendednav');
        $generator->create_node(['nodekey' => 'home', 'visibility' => 0]);

        $this->assertContains('home', $this->get_top_keys($this->build_primary()));
    }
}
