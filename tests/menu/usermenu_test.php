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

use local_extendednav\local\node_access;
use local_extendednav\local\node_repository;
use local_extendednav\menu_manager;

#[\PHPUnit\Framework\Attributes\CoversClass(\local_extendednav\menu\usermenu::class)]
/**
 * Tests for the user menu.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\menu\usermenu
 */
final class usermenu_test extends \advanced_testcase {
    /**
     * The user menu setting holds "text|url" lines of the visible top-level nodes only.
     */
    public function test_build_config_value(): void {
        $this->resetAfterTest();
        node_access::reset_caches();
        $this->setUser($this->getDataGenerator()->create_user());
        $generator = $this->getDataGenerator()->get_plugin_generator('local_extendednav');
        $generator->create_node(['menu' => 'usermenu', 'nodekey' => 'umenu_privatefilesmoo', 'visibility' => 0]);
        $generator->create_node(['menu' => 'usermenu', 'nodekey' => 'library', 'title' => 'Library', 'url' => '/library.php',
            'icon' => 'fa-book', 'tooltip' => 'Books']);
        $generator->create_node(['menu' => 'usermenu', 'nodekey' => 'library_es', 'title' => 'Biblioteca',
            'url' => '/library.php', 'langs' => 'es']);
        $generator->create_node(['menu' => 'usermenu', 'nodekey' => 'catalogue', 'title' => 'Catalogue',
            'url' => '/catalogue.php', 'parentkey' => 'library']);

        force_current_language('en');
        $value = menu_manager::get_menu('usermenu')->build_config_value(node_repository::get_menu_nodes('usermenu'));
        $lines = explode("\n", $value);

        $this->assertContains('profile,moodle|/user/profile.php', $lines, 'Core items keep their language string.');
        $this->assertContains('Library|/library.php', $lines, 'No icon or tooltip in the line.');
        $this->assertNotContains('privatefiles,moodle|/user/files.php', $lines);
        $this->assertNotContains('Biblioteca|/library.php', $lines);
        $this->assertNotContains('Catalogue|/catalogue.php', $lines);
    }

    /**
     * The core items are the lines of the default value of Moodle's user menu setting.
     */
    public function test_items_come_from_moodle(): void {
        $this->resetAfterTest();
        $menu = menu_manager::get_menu('usermenu');

        // Saved when the plugin was installed.
        $this->assertArrayHasKey('umenu_profilemoodle', $menu->get_native_nodes());

        unset_config('nativenodes_usermenu', 'local_extendednav');
        $this->setAdminUser();
        $menu->refresh_native_nodes();
        $default = admin_get_root(false, true)->locate('themesettingsadvanced')->settings->customusermenuitems;
        $this->assertSame($default->get_defaultsetting(), get_config('local_extendednav', 'nativenodes_usermenu'));

        set_config('nativenodes_usermenu', "badges,badges|/badges/mybadges.php\n", 'local_extendednav');
        $natives = $menu->get_native_nodes();
        $this->assertSame(['umenu_badgesbadges'], array_keys($natives));
        $this->assertSame('/badges/mybadges.php', $natives['umenu_badgesbadges']->action);
    }

    /**
     * Icons, tooltips and submenus are passed to the browser as template elements.
     */
    public function test_build_browser_templates(): void {
        global $PAGE;

        $this->resetAfterTest();
        node_access::reset_caches();
        $this->setUser($this->getDataGenerator()->create_user());
        $generator = $this->getDataGenerator()->get_plugin_generator('local_extendednav');
        $generator->create_node(['menu' => 'usermenu', 'nodekey' => 'library', 'title' => 'Library', 'url' => '/library.php',
            'icon' => 'fa-book', 'tooltip' => 'Books']);
        $generator->create_node(['menu' => 'usermenu', 'nodekey' => 'catalogue', 'title' => 'Catalogue',
            'url' => '/catalogue.php', 'parentkey' => 'library', 'display' => 1]);
        $generator->create_node(['menu' => 'usermenu', 'nodekey' => 'atlas', 'title' => 'Atlas',
            'url' => '/atlas.php', 'parentkey' => 'library', 'display' => 2, 'popupwidth' => 700, 'popupheight' => 500]);
        $generator->create_node(['menu' => 'usermenu', 'nodekey' => 'chat', 'title' => 'Chat', 'url' => '/chat.php',
            'display' => 2, 'popupwidth' => 800, 'popupheight' => 600]);

        $html = menu_manager::get_menu('usermenu')->build_browser_templates(
            node_repository::get_menu_nodes('usermenu'),
            $PAGE->get_renderer('core')
        );

        $this->assertStringContainsString('data-region="local-extendednav-node-fix"', $html);
        $this->assertStringContainsString('data-icon="fa-book"', $html);
        $this->assertStringContainsString('data-tooltip="Books"', $html);
        $this->assertStringContainsString('data-region="local-extendednav-submenu"', $html);
        $this->assertStringContainsString('Catalogue', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('data-extnav-display="popup" data-extnav-width="700" data-extnav-height="500"', $html);
        $this->assertMatchesRegularExpression('~data-url="[^"]*/chat\.php"[^>]*data-extnav-display="popup"'
            . '[^>]*data-extnav-width="800"[^>]*data-extnav-height="600"~', $html);
    }
}
