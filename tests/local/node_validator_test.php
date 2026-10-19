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

#[\PHPUnit\Framework\Attributes\CoversClass(\local_extendednav\local\node_validator::class)]
/**
 * Tests for the shared validation rules.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\local\node_validator
 */
final class node_validator_test extends \advanced_testcase {
    /**
     * Finds Moodle's primary navigation items for the tests.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->getDataGenerator()->get_plugin_generator('local_extendednav')->ensure_native_nodes();
    }

    /**
     * Validates a node against the other nodes of its menu.
     *
     * @param array $data Submitted values.
     * @param string $menu Menu key.
     * @return array Result of the validator.
     */
    private function run_validation(array $data, string $menu = 'primary'): array {
        $record = node_manager::prepare($data + ['visibility' => 1], $menu);
        $others = node_repository::get_menu_nodes_by_key($menu);
        unset($others[$record->nodekey]);
        return node_validator::validate($record, $others);
    }

    /**
     * A complete custom node is valid.
     */
    public function test_valid_custom_node(): void {
        $this->resetAfterTest();
        $result = $this->run_validation(['nodekey' => 'help', 'title' => 'Help', 'url' => '/mod/page/view.php?id=2',
            'icon' => 'fa-circle-question']);
        $this->assertSame([], $result['errors']);
        $this->assertSame([], $result['warnings']);
    }

    /**
     * A pop-up needs a width and a height in range; other displays ignore them.
     */
    public function test_display(): void {
        $node = ['nodekey' => 'help', 'title' => 'Help', 'url' => '/help.php'];

        $this->assertSame([], $this->run_validation($node + ['display' => 2, 'popupwidth' => 620, 'popupheight' => 450])['errors']);
        $this->assertSame([], $this->run_validation($node + ['display' => 1, 'popupwidth' => 5])['errors']);

        $errors = $this->run_validation($node + ['display' => 2, 'popupwidth' => 99, 'popupheight' => ''])['errors'];
        $this->assertArrayHasKey('popupwidth', $errors);
        $this->assertArrayHasKey('popupheight', $errors);
        $this->assertArrayHasKey('popupwidth', $this->run_validation($node + ['display' => 2, 'popupwidth' => 10000,
            'popupheight' => 450])['errors']);
        $this->assertArrayHasKey('popupwidth', $this->run_validation($node + ['display' => 2, 'popupwidth' => '12.5',
            'popupheight' => 450])['errors']);
        $this->assertArrayHasKey('display', $this->run_validation($node + ['display' => 3])['errors']);
    }

    /**
     * Custom nodes need a title and a URL; core nodes do not.
     */
    public function test_required_fields(): void {
        $this->resetAfterTest();
        $errors = $this->run_validation(['nodekey' => 'help'])['errors'];
        $this->assertArrayHasKey('title', $errors);
        $this->assertArrayHasKey('url', $errors);

        $this->assertSame([], $this->run_validation(['nodekey' => 'myhome'])['errors']);
    }

    /**
     * Keys must be unique within a menu and use safe characters.
     */
    public function test_nodekey(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_extendednav');
        $generator->create_node(['nodekey' => 'help', 'title' => 'Help', 'url' => '/help.php']);

        $record = node_manager::prepare(['nodekey' => 'help', 'title' => 'Again', 'url' => '/a.php'], 'primary');
        $errors = node_validator::validate($record, node_repository::get_menu_nodes_by_key('primary'))['errors'];
        $this->assertSame(get_string('err_duplicate_key', 'local_extendednav'), $errors['nodekey']);

        // The same key is fine in the other menu.
        $this->assertArrayNotHasKey('nodekey', $this->run_validation(['nodekey' => 'help', 'title' => 'Help',
            'url' => '/help.php'], 'usermenu')['errors']);

        $errors = $this->run_validation(['nodekey' => 'my key', 'title' => 'T', 'url' => '/a.php'])['errors'];
        $this->assertArrayHasKey('nodekey', $errors);
    }

    /**
     * Dangerous and malformed URLs are rejected.
     */
    public function test_url(): void {
        $this->resetAfterTest();
        $errors = $this->run_validation(['nodekey' => 'x', 'title' => 'X', 'url' => 'javascript:alert(1)'])['errors'];
        $this->assertSame(get_string('err_url_forbidden', 'local_extendednav'), $errors['url']);

        $errors = $this->run_validation(['nodekey' => 'x', 'title' => 'X', 'url' => '/a page.php'])['errors'];
        $this->assertSame(get_string('err_url_invalid', 'local_extendednav'), $errors['url']);

        $this->assertSame([], $this->run_validation(['nodekey' => 'x', 'title' => 'X',
            'url' => 'https://docs.moodle.org/'])['errors']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('icon_provider')]
    /**
     * Icons must be FontAwesome classes, pix icons or "none".
     *
     * @dataProvider icon_provider
     * @param string $icon Icon value.
     * @param bool $valid Whether it is accepted.
     */
    public function test_icon(string $icon, bool $valid): void {
        $this->assertSame($valid, node_validator::is_valid_icon($icon));
    }

    /**
     * Cases for {@see test_icon()}.
     *
     * @return array
     */
    public static function icon_provider(): array {
        return [
            'fontawesome' => ['fa-book', true],
            'fontawesome with style' => ['fa-solid fa-house', true],
            'legacy fontawesome' => ['fa fa-book', true],
            'pix icon' => ['i/home', true],
            'none' => ['none', true],
            'html' => ['<b>x</b>', false],
            'script' => ['fa-book" onclick="x', false],
            'bare word' => ['book', false],
        ];
    }

    /**
     * Restricted nodes need roles; unknown roles and languages are warnings.
     */
    public function test_roles_and_languages(): void {
        $this->resetAfterTest();
        $errors = $this->run_validation(['nodekey' => 'x', 'title' => 'X', 'url' => '/a.php', 'visibility' => 2])['errors'];
        $this->assertSame(get_string('required'), $errors['roles']);

        $result = $this->run_validation(['nodekey' => 'x', 'title' => 'X', 'url' => '/a.php', 'visibility' => 2,
            'roles' => 'editingteacher,ghost', 'langs' => 'en,xx']);
        $this->assertSame([], $result['errors']);
        $this->assertEquals([
            get_string('warn_unknown_role', 'local_extendednav', 'ghost'),
            get_string('warn_unknown_lang', 'local_extendednav', 'xx'),
        ], $result['warnings']);
    }

    /**
     * Menus have one level of submenus, and parents must exist.
     */
    public function test_parent_rules(): void {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator()->get_plugin_generator('local_extendednav');
        $generator->create_node(['nodekey' => 'resources', 'title' => 'Resources', 'url' => '/r.php']);
        $generator->create_node(['nodekey' => 'library', 'title' => 'Library', 'url' => '/l.php', 'parentkey' => 'resources']);

        $base = ['title' => 'X', 'url' => '/x.php'];
        $this->assertSame([], $this->run_validation(['nodekey' => 'x', 'parentkey' => 'resources'] + $base)['errors']);
        $this->assertSame([], $this->run_validation(['nodekey' => 'x', 'parentkey' => 'myhome'] + $base)['errors']);

        $errors = $this->run_validation(['nodekey' => 'x', 'parentkey' => 'x'] + $base)['errors'];
        $this->assertSame(get_string('err_parent_self', 'local_extendednav'), $errors['parentkey']);

        $errors = $this->run_validation(['nodekey' => 'x', 'parentkey' => 'missing'] + $base)['errors'];
        $this->assertSame(get_string('err_parent_missing', 'local_extendednav', 'missing'), $errors['parentkey']);

        $errors = $this->run_validation(['nodekey' => 'x', 'parentkey' => 'library'] + $base)['errors'];
        $this->assertSame(get_string('err_parent_thirdlevel', 'local_extendednav'), $errors['parentkey']);

        $errors = $this->run_validation(['nodekey' => 'resources', 'parentkey' => 'myhome', 'title' => 'Resources',
            'url' => '/r.php'])['errors'];
        $this->assertSame(get_string('err_parent_haschildren', 'local_extendednav'), $errors['parentkey']);
    }

    /**
     * Site administration cannot be hidden, blocked, limited to languages or nested.
     */
    public function test_site_administration_anti_lockout(): void {
        $this->resetAfterTest();
        $errors = $this->run_validation(['nodekey' => 'siteadminnode', 'visibility' => 0])['errors'];
        $this->assertSame(get_string('err_admin_hide', 'local_extendednav'), $errors['visibility']);

        $errors = $this->run_validation(['nodekey' => 'siteadminnode', 'langs' => 'es'])['errors'];
        $this->assertSame(get_string('err_admin_hide', 'local_extendednav'), $errors['visibility']);

        $errors = $this->run_validation(['nodekey' => 'siteadminnode', 'parentkey' => 'home'])['errors'];
        $this->assertSame(get_string('err_admin_child', 'local_extendednav'), $errors['parentkey']);

        $errors = $this->run_validation(['nodekey' => 'x', 'title' => 'X', 'url' => '/x.php',
            'parentkey' => 'siteadminnode'])['errors'];
        $this->assertSame(get_string('err_admin_parent', 'local_extendednav'), $errors['parentkey']);
    }

    /**
     * User menu titles cannot break Moodle's "text|url" lines.
     */
    public function test_usermenu_title_characters(): void {
        $this->resetAfterTest();
        $errors = $this->run_validation(['nodekey' => 'x', 'title' => 'A|B', 'url' => '/x.php'], 'usermenu')['errors'];
        $this->assertSame(get_string('err_title_usermenu_chars', 'local_extendednav'), $errors['title']);
        $this->assertSame([], $this->run_validation(['nodekey' => 'x', 'title' => 'A|B', 'url' => '/x.php'])['errors']);
    }

    /**
     * Imported values get the same cleaning as the form.
     */
    public function test_text_and_lists(): void {
        $this->resetAfterTest();
        $errors = $this->run_validation(['nodekey' => 'x', 'title' => '<script>alert(1)</script>Hi', 'url' => '/x.php',
            'tooltip' => '<b>bold</b>', 'visibility' => 2, 'roles' => 'editing teacher', 'langs' => 'es,<x>'])['errors'];
        $this->assertSame(get_string('err_text_html', 'local_extendednav'), $errors['title']);
        $this->assertSame(get_string('err_text_html', 'local_extendednav'), $errors['tooltip']);
        $this->assertSame(get_string('err_invalid_list', 'local_extendednav'), $errors['roles']);
        $this->assertSame(get_string('err_invalid_list', 'local_extendednav'), $errors['langs']);

        // Multi-language spans are text, as in the form.
        $title = '<span lang="en" class="multilang">Help</span><span lang="es" class="multilang">Ayuda</span>';
        $this->assertArrayNotHasKey('title', $this->run_validation(['nodekey' => 'x', 'title' => $title,
            'url' => '/x.php'])['errors']);
    }

    /**
     * Unknown menus are rejected.
     */
    public function test_unknown_menu(): void {
        $this->resetAfterTest();
        $record = node_manager::prepare(['nodekey' => 'x', 'title' => 'X', 'url' => '/x.php'], 'sidebar');
        $errors = node_validator::validate($record, [])['errors'];
        $this->assertSame(get_string('err_invalid_menu', 'local_extendednav'), $errors['menu']);
    }
}
