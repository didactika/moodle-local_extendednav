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

use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_extendednav\local\node_access;
use local_extendednav\local\node_manager;
use local_extendednav\local\node_repository;

/**
 * The actions of the management page that change nodes without reloading it: show or hide, delete or reset, and move.
 *
 * @package    local_extendednav
 * @category   external
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class change_nodes extends node_service {
    /**
     * Describes set_visibility().
     *
     * @return external_function_parameters
     */
    public static function set_visibility_parameters(): external_function_parameters {
        return new external_function_parameters([
            'menu' => new external_value(PARAM_ALPHANUMEXT, 'Navigation key.'),
            'contextid' => new external_value(PARAM_INT, 'Context of the scope, 0 for the whole site.', VALUE_DEFAULT, 0),
            'nodekeys' => self::keys_structure(),
            'visibility' => new external_value(PARAM_INT, '0 hidden or 1 visible to everyone.'),
        ]);
    }

    /**
     * Shows or hides nodes.
     *
     * @param string $menu Navigation key.
     * @param int $contextid Context of the scope.
     * @param string[] $nodekeys Node keys.
     * @param int $visibility 0 or 1.
     * @return array The number of nodes changed.
     */
    public static function set_visibility(string $menu, int $contextid, array $nodekeys, int $visibility): array {
        $params = self::validate_parameters(
            self::set_visibility_parameters(),
            compact('menu', 'contextid', 'nodekeys', 'visibility')
        );
        self::require_manage();
        self::validate_scope($params['menu'], $params['contextid']);
        if (!in_array($params['visibility'], [node_access::VISIBILITY_HIDDEN, node_access::VISIBILITY_ALL], true)) {
            throw new \invalid_parameter_exception('Unsupported visibility.');
        }
        $count = node_manager::set_visibility($params['menu'], $params['contextid'], $params['nodekeys'], $params['visibility']);
        return ['count' => $count];
    }

    /**
     * Describes the result of the actions that count nodes.
     *
     * @return external_single_structure
     */
    public static function count_returns(): external_single_structure {
        return new external_single_structure(['count' => new external_value(PARAM_INT, 'Nodes changed.')]);
    }

    /**
     * Describes set_visibility()'s result.
     *
     * @return external_single_structure
     */
    public static function set_visibility_returns(): external_single_structure {
        return self::count_returns();
    }

    /**
     * Describes delete().
     *
     * @return external_function_parameters
     */
    public static function delete_parameters(): external_function_parameters {
        return new external_function_parameters([
            'menu' => new external_value(PARAM_ALPHANUMEXT, 'Navigation key.'),
            'contextid' => new external_value(PARAM_INT, 'Context of the scope, 0 for the whole site.', VALUE_DEFAULT, 0),
            'nodekeys' => self::keys_structure(),
        ]);
    }

    /**
     * Deletes nodes of the site, or resets the exceptions of a scope.
     *
     * @param string $menu Navigation key.
     * @param int $contextid Context of the scope.
     * @param string[] $nodekeys Node keys.
     * @return array The number of nodes deleted.
     */
    public static function delete(string $menu, int $contextid, array $nodekeys): array {
        global $DB;

        $params = self::validate_parameters(self::delete_parameters(), compact('menu', 'contextid', 'nodekeys'));
        self::require_manage();
        self::validate_scope($params['menu'], $params['contextid']);

        [$in, $inparams] = $DB->get_in_or_equal($params['nodekeys'] ?: [''], SQL_PARAMS_NAMED);
        $records = $DB->get_records_select(
            'local_extendednav',
            'menu = :menu AND contextid = :contextid AND nodekey ' . $in,
            ['menu' => $params['menu'], 'contextid' => $params['contextid']] + $inparams
        );
        if ($params['contextid'] === 0) {
            return ['count' => node_manager::delete($records)];
        }
        // An exception is deleted whatever the navigation says about the node: the scope goes back to what it inherits.
        $DB->delete_records_list('local_extendednav', 'id', array_keys($records));
        node_repository::purge();
        return ['count' => count($records)];
    }

    /**
     * Describes delete()'s result.
     *
     * @return external_single_structure
     */
    public static function delete_returns(): external_single_structure {
        return self::count_returns();
    }

    /**
     * Describes move().
     *
     * @return external_function_parameters
     */
    public static function move_parameters(): external_function_parameters {
        return new external_function_parameters([
            'menu' => new external_value(PARAM_ALPHANUMEXT, 'Navigation key.'),
            'contextid' => new external_value(PARAM_INT, 'Context of the scope, 0 for the whole site.', VALUE_DEFAULT, 0),
            'nodekey' => new external_value(PARAM_ALPHANUMEXT, 'Node key.'),
            'direction' => new external_value(PARAM_INT, '-1 to move it up, 1 to move it down.'),
        ]);
    }

    /**
     * Moves a node one place among its siblings.
     *
     * @param string $menu Navigation key.
     * @param int $contextid Context of the scope.
     * @param string $nodekey Node key.
     * @param int $direction -1 or 1.
     * @return array The number of nodes moved.
     */
    public static function move(string $menu, int $contextid, string $nodekey, int $direction): array {
        $params = self::validate_parameters(self::move_parameters(), compact('menu', 'contextid', 'nodekey', 'direction'));
        self::require_manage();
        self::validate_scope($params['menu'], $params['contextid']);
        if (!in_array($params['direction'], [-1, 1], true)) {
            throw new \invalid_parameter_exception('The direction is -1 or 1.');
        }
        node_manager::move($params['menu'], $params['contextid'], $params['nodekey'], $params['direction']);
        return ['count' => 1];
    }

    /**
     * Describes move()'s result.
     *
     * @return external_single_structure
     */
    public static function move_returns(): external_single_structure {
        return self::count_returns();
    }

    /**
     * Describes place().
     *
     * @return external_function_parameters
     */
    public static function place_parameters(): external_function_parameters {
        return new external_function_parameters([
            'menu' => new external_value(PARAM_ALPHANUMEXT, 'Navigation key.'),
            'contextid' => new external_value(PARAM_INT, 'Context of the scope, 0 for the whole site.', VALUE_DEFAULT, 0),
            'nodekey' => new external_value(PARAM_ALPHANUMEXT, 'Key of the node that moves.'),
            'beforekey' => new external_value(PARAM_ALPHANUMEXT, 'Sibling it goes before, or empty.', VALUE_DEFAULT, ''),
            'afterkey' => new external_value(PARAM_ALPHANUMEXT, 'Sibling it goes after, instead of before.', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Places a node before or after another one among its siblings, as when it is dragged.
     *
     * @param string $menu Navigation key.
     * @param int $contextid Context of the scope.
     * @param string $nodekey Key of the node that moves.
     * @param string $beforekey Key of the sibling it goes before, or empty.
     * @param string $afterkey Key of the sibling it goes after, or empty.
     * @return array The number of nodes moved.
     */
    public static function place(
        string $menu,
        int $contextid,
        string $nodekey,
        string $beforekey = '',
        string $afterkey = ''
    ): array {
        $params = self::validate_parameters(
            self::place_parameters(),
            compact('menu', 'contextid', 'nodekey', 'beforekey', 'afterkey')
        );
        self::require_manage();
        self::validate_scope($params['menu'], $params['contextid']);
        node_manager::place($params['menu'], $params['contextid'], $params['nodekey'], $params['beforekey'], $params['afterkey']);
        return ['count' => 1];
    }

    /**
     * Describes place()'s result.
     *
     * @return external_single_structure
     */
    public static function place_returns(): external_single_structure {
        return self::count_returns();
    }

    /**
     * Describes set_enabled().
     *
     * @return external_function_parameters
     */
    public static function set_enabled_parameters(): external_function_parameters {
        return new external_function_parameters([
            'menu' => new external_value(PARAM_ALPHANUMEXT, 'Navigation key.'),
            'value' => new external_value(PARAM_BOOL, 'On or off.'),
        ]);
    }

    /**
     * Turns the changes of one navigation on or off, so Moodle shows it as it draws it.
     *
     * @param string $menu Navigation key.
     * @param bool $value On or off.
     * @return array 1 when it is on, 0 when it is off.
     */
    public static function set_enabled(string $menu, bool $value): array {
        $params = self::validate_parameters(self::set_enabled_parameters(), compact('menu', 'value'));
        self::require_manage();
        self::validate_scope($params['menu'], 0);
        set_config('enabled_' . $params['menu'], $params['value'] ? 1 : 0, 'local_extendednav');
        node_repository::purge();
        return ['count' => $params['value'] ? 1 : 0];
    }

    /**
     * Describes set_enabled()'s result.
     *
     * @return external_single_structure
     */
    public static function set_enabled_returns(): external_single_structure {
        return self::count_returns();
    }
}
