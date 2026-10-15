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
            'url' => '/catalogue.php', 'parentkey' => 'library', 'newwindow' => 1]);

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
    }
}
