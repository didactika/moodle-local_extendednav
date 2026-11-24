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

namespace local_extendednav\external;

use core_external\external_api;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_extendednav\local\listing\nodes;
use local_extendednav\menu_manager;

/**
 * What the web services of the management page share: the check of who may use them, of the navigation and of the scope.
 *
 * This class is internal infrastructure and is not registered as a web-service function.
 *
 * @package    local_extendednav
 * @category   external
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class node_service extends external_api {
    /**
     * Requires the capability that manages the navigation.
     */
    protected static function require_manage(): void {
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('local/extendednav:manage', $context);
    }

    /**
     * Requires the right to change a navigation in a scope: the whole site needs the capability that manages the navigation;
     * a category or a course needs it, or the capability that manages the navigation of that context.
     *
     * @param string $menu Navigation key.
     * @param int $contextid Context of the scope, 0 for the whole site.
     */
    protected static function require_scope(string $menu, int $contextid): void {
        if ($contextid === 0) {
            self::require_manage();
            self::validate_scope($menu, 0);
            return;
        }
        $context = \context::instance_by_id($contextid);
        self::validate_context($context);
        if (!has_capability('local/extendednav:manage', \context_system::instance())) {
            require_capability('local/extendednav:managecontext', $context);
        }
        self::validate_scope($menu, $contextid);
    }

    /**
     * Checks a navigation and a scope.
     *
     * @param string $menu Navigation key.
     * @param int $contextid Context of the scope, 0 for the whole site.
     */
    protected static function validate_scope(string $menu, int $contextid): void {
        if (menu_manager::get_menu($menu) === null) {
            throw new \invalid_parameter_exception('Unknown navigation.');
        }
        if ($contextid === 0) {
            return;
        }
        if (!nodes::supports_scopes($menu)) {
            throw new \invalid_parameter_exception('This navigation has no exceptions.');
        }
        $context = \context::instance_by_id($contextid, IGNORE_MISSING);
        if (!$context || !in_array($context->contextlevel, [CONTEXT_COURSECAT, CONTEXT_COURSE], true)) {
            throw new \invalid_parameter_exception('An exception applies to a category or a course.');
        }
        if ($menu === 'category' && $context->contextlevel !== CONTEXT_COURSECAT) {
            throw new \invalid_parameter_exception('The navigation of a category has exceptions for categories only.');
        }
    }

    /**
     * Describes the keys of a list of nodes.
     *
     * @return external_multiple_structure
     */
    protected static function keys_structure(): external_multiple_structure {
        return new external_multiple_structure(new external_value(PARAM_ALPHANUMEXT, 'Node key.'), 'Node keys.');
    }

    /**
     * Describes a row of the list.
     *
     * @return external_single_structure
     */
    protected static function row_structure(): external_single_structure {
        $text = static fn(string $description): external_value => new external_value(PARAM_RAW, $description);
        $flag = static fn(string $description): external_value => new external_value(PARAM_BOOL, $description);
        return new external_single_structure([
            'details' => new external_multiple_structure(new external_value(PARAM_RAW, 'What narrows the visibility.')),
            'haschildren' => $flag('Whether the node is a category of the tree with nodes inside.'),
            'depth' => new external_value(PARAM_INT, 'How deep the node is, 0 being the top level.'),
            'id' => new external_value(PARAM_INT, 'Row identifier.'),
            'nodekey' => new external_value(PARAM_ALPHANUMEXT, 'Node key.'),
            'title' => $text('Title shown.'),
            'native' => $flag('Whether Moodle has the node.'),
            'url' => $text('Link of the node.'),
            'icon' => $text('Icon, drawn.'),
            'visibility' => new external_value(PARAM_INT, '0 hidden, 1 everyone, 2 roles.'),
            'visibilitylabel' => $text('Visibility, in words.'),
            'tone' => new external_value(PARAM_ALPHA, 'Colour of the visibility.'),
            'roles' => $text('Roles that see the node.'),
            'langs' => $text('Languages that see the node.'),
            'parentkey' => $text('Key of the parent node.'),
            'child' => $flag('Whether the node is a submenu item.'),
            'blocked' => $flag('Whether its address is blocked to who does not see it.'),
            'unavailable' => $flag('Whether Moodle does not add the node now.'),
            'unknownroles' => $text('Roles of the node that no longer exist.'),
            'adminonly' => $flag('Whether the current user sees it only for being an administrator.'),
            'hideadmins' => $flag('Whether it is hidden from administrators too.'),
            'placement' => new external_value(PARAM_INT, '0 where Moodle puts it, 1 More menu, 2 main bar.'),
            'placementlabel' => $text('Placement, in words.'),
            'exception' => $flag('Whether the scope sets the node.'),
            'inherited' => $flag('Whether the node comes from a context around the scope.'),
            'candelete' => $flag('Whether the node can be deleted, or the exception reset.'),
            'first' => $flag('Whether it is the first among its siblings.'),
            'last' => $flag('Whether it is the last among its siblings.'),
            'canhide' => $flag('Whether the node can be hidden.'),
            'canshow' => $flag('Whether the node can be shown to everyone.'),
            'flags' => new external_multiple_structure(new external_single_structure([
                'label' => $text('Kind of restriction, in a word or two.'),
            ]), 'Kinds of restriction, each explained by a line of details.'),
            'virtual' => $flag('Whether the row is a group of the list and not a node.'),
        ]);
    }
}
