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
use local_extendednav\local\node\access;
use local_extendednav\local\node\manager;
use local_extendednav\local\node\repository;

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
            'hideadmins' => new external_value(
                PARAM_INT,
                'When hiding: 1 to hide from administrators too, 0 not to, -1 to leave it as it is.',
                VALUE_DEFAULT,
                -1
            ),
        ]);
    }

    /**
     * Shows or hides nodes.
     *
     * @param string $menu Navigation key.
     * @param int $contextid Context of the scope.
     * @param string[] $nodekeys Node keys.
     * @param int $visibility 0 or 1.
     * @param int $hideadmins 1 to hide from administrators too, 0 not to, -1 to leave it.
     * @return array The number of nodes changed.
     */
    public static function set_visibility(
        string $menu,
        int $contextid,
        array $nodekeys,
        int $visibility,
        int $hideadmins = -1
    ): array {
        $params = self::validate_parameters(
            self::set_visibility_parameters(),
            compact('menu', 'contextid', 'nodekeys', 'visibility', 'hideadmins')
        );
        self::require_scope($params['menu'], $params['contextid'], 'changevisibility');
        if (!in_array($params['visibility'], [access::VISIBILITY_HIDDEN, access::VISIBILITY_ALL], true)) {
            throw new \invalid_parameter_exception('Unsupported visibility.');
        }
        $count = manager::set_visibility(
            $params['menu'],
            $params['contextid'],
            $params['nodekeys'],
            $params['visibility'],
            $params['hideadmins']
        );
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
        self::require_scope($params['menu'], $params['contextid'], 'deletenodes');

        [$in, $inparams] = $DB->get_in_or_equal($params['nodekeys'] ?: [''], SQL_PARAMS_NAMED);
        $records = $DB->get_records_select(
            'local_extendednav',
            'menu = :menu AND contextid = :contextid AND nodekey ' . $in,
            ['menu' => $params['menu'], 'contextid' => $params['contextid']] + $inparams
        );
        if ($params['contextid'] === 0) {
            return ['count' => manager::delete($records)];
        }
        // An exception is deleted whatever the navigation says about the node: the scope goes back to what it inherits.
        $DB->delete_records_list('local_extendednav', 'id', array_keys($records));
        repository::purge();
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
        self::require_scope($params['menu'], $params['contextid'], 'changeorder');
        if (!in_array($params['direction'], [-1, 1], true)) {
            throw new \invalid_parameter_exception('The direction is -1 or 1.');
        }
        manager::move($params['menu'], $params['contextid'], $params['nodekey'], $params['direction']);
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
            'nodekeys' => self::keys_structure(),
            'beforekey' => new external_value(PARAM_ALPHANUMEXT, 'Sibling they go before, or empty.', VALUE_DEFAULT, ''),
            'afterkey' => new external_value(PARAM_ALPHANUMEXT, 'Sibling they go after, instead of before.', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Places nodes, together, before or after another one among their siblings, as when they are dragged.
     *
     * @param string $menu Navigation key.
     * @param int $contextid Context of the scope.
     * @param string[] $nodekeys Keys of the nodes that move, all of them siblings.
     * @param string $beforekey Key of the sibling they go before, or empty.
     * @param string $afterkey Key of the sibling they go after, or empty.
     * @return array The number of nodes moved.
     */
    public static function place(
        string $menu,
        int $contextid,
        array $nodekeys,
        string $beforekey = '',
        string $afterkey = ''
    ): array {
        $params = self::validate_parameters(
            self::place_parameters(),
            compact('menu', 'contextid', 'nodekeys', 'beforekey', 'afterkey')
        );
        self::require_scope($params['menu'], $params['contextid'], 'changeorder');
        manager::place($params['menu'], $params['contextid'], $params['nodekeys'], $params['beforekey'], $params['afterkey']);
        return ['count' => count($params['nodekeys'])];
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
        repository::purge();
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
