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

/**
 * Exports navigation nodes as a YAML file.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extendednav\local\node_importer;
use local_extendednav\menu_manager;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/filelib.php');

admin_externalpage_setup('local_extendednav_manage');

$all = optional_param('all', 0, PARAM_BOOL);
$menukey = optional_param('menu', 'primary', PARAM_ALPHANUMEXT);
$nodeids = optional_param_array('nodeids', [], PARAM_INT);
if (menu_manager::get_menu($menukey) === null) {
    $menukey = 'primary';
}
$manageurl = new moodle_url('/local/extendednav/manage.php', ['menu' => $menukey]);

if ($all) {
    $nodes = $DB->get_records('local_extendednav', ['menu' => $menukey], 'sortorder ASC, id ASC');
    $filename = 'extendednav_' . $menukey . '_all_' . date('Ymd_His') . '.yaml';
} else {
    require_sesskey();
    $nodes = empty($nodeids) ? [] : $DB->get_records_list('local_extendednav', 'id', $nodeids, 'sortorder ASC, id ASC');
    $nodes = array_filter($nodes, static fn(\stdClass $node): bool => $node->menu === $menukey);
    $filename = 'extendednav_' . $menukey . '_selected_' . date('Ymd_His') . '.yaml';
}

if (empty($nodes)) {
    redirect($manageurl, get_string('no_nodes_export', 'local_extendednav'), null, \core\output\notification::NOTIFY_ERROR);
}

$content = \local_extendednav\yaml::dump(['nodes' => array_values(array_map([node_importer::class, 'export_node'], $nodes))]);

send_file($content, $filename, 0, 0, true, true, 'application/yaml');
