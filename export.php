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
 * Export customized navigation nodes to YAML.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_extendednav_manage');

$all = optional_param('all', 0, PARAM_INT);
$nodeids = optional_param_array('nodeids', [], PARAM_INT);

if (!$all && empty($nodeids)) {
    redirect(new moodle_url('/local/extendednav/manage.php'), get_string('no_nodes_export', 'local_extendednav'), null, \core\output\notification::NOTIFY_ERROR);
}

if ($all) {
    $nodes = $DB->get_records('local_extendednav', null, 'sortorder ASC, id ASC');
    $filename = 'extendednav_config_all_' . date('Ymd_His') . '.yml';
} else {
    list($insql, $inparams) = $DB->get_in_or_equal($nodeids);
    $nodes = $DB->get_records_select('local_extendednav', "id $insql", $inparams, 'sortorder ASC, id ASC');
    $filename = 'extendednav_config_selected_' . date('Ymd_His') . '.yml';
}

if (empty($nodes)) {
    redirect(new moodle_url('/local/extendednav/manage.php'), get_string('no_nodes_export', 'local_extendednav'), null, \core\output\notification::NOTIFY_ERROR);
}

$export_data = ['nodes' => []];

foreach ($nodes as $n) {
    $node_data = [
        'nodekey'     => $n->nodekey,
        'title'       => $n->title,
        'url'         => $n->url,
        'icon'        => $n->icon,
        'visibility'  => (int)$n->visibility,
        'roles'       => $n->roles,
        'parentkey'   => $n->parentkey,
        'beforekey'   => $n->beforekey,
        'newwindow'   => (int)$n->newwindow,
        'blockedurls' => $n->blockedurls
    ];
    $export_data['nodes'][] = $node_data;
}

$yaml_content = \local_extendednav\yaml::dump($export_data);


header('Content-Type: application/x-yaml');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');
header('Content-Length: ' . strlen($yaml_content));
echo $yaml_content;
exit;
