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
use local_extendednav\local\node_text;
use local_extendednav\local\node_validator;

/**
 * The user menu.
 *
 * Moodle builds the user menu from the customusermenuitems setting, so the plugin
 * rewrites that setting for the current request. Moodle reads only "text|url"
 * from each line, so icons, tooltips, new windows and submenus are applied in the
 * browser by the local_extendednav/usermenu module.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class usermenu extends base {
    /** @var string[] Default lines of Moodle's customusermenuitems setting. */
    private const DEFAULT_ITEMS = [
        'profile,moodle|/user/profile.php',
        'grades,grades|/grade/report/mygrades.php',
        'calendar,core_calendar|/calendar/view.php?view=month',
        'privatefiles,moodle|/user/files.php',
        'reports,core_reportbuilder|/reportbuilder/index.php',
    ];

    /**
     * Returns the menu key.
     *
     * @return string
     */
    public function get_key(): string {
        return 'usermenu';
    }

    /**
     * Returns the localised menu name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('user_menu', 'local_extendednav');
    }

    /**
     * Returns the Moodle setting that defines user menu items.
     *
     * @return string|null
     */
    public function get_config_name(): ?string {
        return 'customusermenuitems';
    }

    /**
     * Returns the prefix of nodes imported from the user menu setting.
     *
     * @return string
     */
    public function get_config_prefix(): string {
        return 'umenu_';
    }

    /**
     * Splits a user menu line. Moodle reads it as text|url, so anything after the URL belongs to the URL.
     *
     * @param string $line Line without the leading dashes.
     * @return array With keys text, url, tooltip and langs.
     */
    public function parse_config_line(string $line): array {
        $bits = array_map('trim', explode('|', $line, 2));
        return ['text' => $bits[0] ?? '', 'url' => $bits[1] ?? '', 'tooltip' => '', 'langs' => ''];
    }

    /**
     * Returns the core user menu items, as Moodle defines them by default.
     *
     * @return \stdClass[] Keyed by node key.
     */
    public function get_native_nodes(): array {
        $nodes = [];
        foreach (self::DEFAULT_ITEMS as $line) {
            $item = $this->parse_config_line($line);
            $key = self::make_key($this->get_config_prefix(), $item['text']);
            $nodes[$key] = (object)['key' => $key, 'text' => $item['text'], 'action' => $item['url'], 'icon' => null];
        }
        return $nodes;
    }

    /**
     * Builds the customusermenuitems value for the current user.
     *
     * Only top-level nodes the user can see are included; child nodes are shown as
     * submenus by the browser module.
     *
     * @param \stdClass[] $customnodes Nodes of this menu, ordered by sortorder.
     * @return string
     */
    public function build_config_value(array $customnodes): string {
        $natives = $this->get_native_nodes();
        $lines = [];
        foreach ($customnodes as $node) {
            if (!empty($node->parentkey) || !node_access::is_shown($node)) {
                continue;
            }
            $native = $natives[$node->nodekey] ?? null;

            // Keep the "identifier,component" form of core items so Moodle translates them itself.
            if (trim((string)$node->title) !== '') {
                $title = format_string(node_text::resolve($node->title), true, ['escape' => false]);
            } else {
                $title = $native ? $native->text : '';
            }
            $url = trim((string)$node->url) !== '' ? trim((string)$node->url) : ($native ? $native->action : '');
            if ($title === '' || $url === '') {
                continue;
            }
            $lines[] = str_replace(["\r", "\n", '|'], ' ', $title) . '|' . $url;
        }
        return implode("\n", $lines);
    }

    /**
     * Builds the data the browser module needs: fixes for top-level items and submenu panels.
     *
     * @param \stdClass[] $customnodes Nodes of this menu, ordered by sortorder.
     * @param \renderer_base $output Renderer for the submenu template.
     * @return string HTML of the template elements, or an empty string when nothing is needed.
     */
    public function build_browser_templates(array $customnodes, \renderer_base $output): string {
        $natives = $this->get_native_nodes();
        $roots = [];
        $children = [];
        foreach ($customnodes as $node) {
            if (!node_access::is_shown($node)) {
                continue;
            }
            if (empty($node->parentkey)) {
                $roots[$node->nodekey] = $node;
            } else {
                $children[$node->parentkey][] = $node;
            }
        }

        $html = '';
        foreach ($roots as $nodekey => $node) {
            $haschildren = !empty($children[$nodekey]);
            $icon = trim((string)$node->icon);
            $tooltip = node_text::resolve($node->tooltip ?? '');
            $attributes = [
                'data-region' => 'local-extendednav-node-fix',
                'data-url' => $this->get_absolute_url($node, $natives[$nodekey] ?? null),
                'data-title' => strip_tags(format_string(node_text::get_title($node, $natives[$nodekey] ?? null))),
            ];
            if ($icon !== '' && node_validator::is_fontawesome($icon)) {
                $attributes['data-icon'] = $icon;
            }
            if (!empty($node->newwindow) && !$haschildren) {
                $attributes['data-newwindow'] = '1';
            }
            if ($tooltip !== '') {
                $attributes['data-tooltip'] = strip_tags(format_string($tooltip));
            }
            if (count($attributes) > 3) {
                $html .= \html_writer::tag('template', '', $attributes);
            }
        }

        foreach ($children as $parentkey => $items) {
            $parent = $roots[$parentkey] ?? null;
            if ($parent === null && !isset($natives[$parentkey])) {
                continue;
            }
            $parent = $parent ?? (object)['nodekey' => $parentkey, 'title' => null, 'url' => null];
            $parenttitle = strip_tags(format_string(node_text::get_title($parent, $natives[$parentkey] ?? null)));
            if ($parenttitle === '') {
                continue;
            }

            $context = ['panelid' => 'carousel-item-extnav-' . preg_replace('/[^a-zA-Z0-9]/', '', $parentkey),
                'title' => $parenttitle, 'items' => []];
            foreach ($items as $child) {
                $title = strip_tags(format_string(node_text::get_title($child, $natives[$child->nodekey] ?? null)));
                $url = $this->get_absolute_url($child, $natives[$child->nodekey] ?? null);
                if ($title === '' || $url === '') {
                    continue;
                }
                $icon = trim((string)$child->icon);
                $context['items'][] = [
                    'url' => $url,
                    'title' => $title,
                    'tooltip' => strip_tags(format_string(node_text::resolve($child->tooltip ?? ''))),
                    'icon' => ($icon !== '' && node_validator::is_fontawesome($icon)) ? $icon : false,
                    'newwindow' => !empty($child->newwindow),
                ];
            }
            if (empty($context['items'])) {
                continue;
            }

            $html .= \html_writer::tag('template', $output->render_from_template('local_extendednav/usermenu/panel', $context), [
                'data-region' => 'local-extendednav-submenu',
                'data-panel-id' => $context['panelid'],
                'data-trigger-url' => $this->get_absolute_url($parent, $natives[$parentkey] ?? null),
                'data-trigger-title' => $parenttitle,
            ]);
        }
        return $html;
    }

    /**
     * Returns the absolute URL of a node, as Moodle prints it in the user menu.
     *
     * @param \stdClass $node Node record.
     * @param \stdClass|null $native Core node definition, if any.
     * @return string Absolute URL, or an empty string.
     */
    private function get_absolute_url(\stdClass $node, ?\stdClass $native): string {
        $url = trim((string)($node->url ?? ''));
        if ($url === '' && $native !== null) {
            $url = (string)$native->action;
        }
        if ($url === '') {
            return '';
        }
        try {
            return (new \moodle_url($url))->out(false);
        } catch (\moodle_exception $e) {
            return '';
        }
    }
}
