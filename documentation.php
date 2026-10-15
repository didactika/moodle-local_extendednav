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
 * Reference of the YAML format used by the import and export.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extendednav\local\node_importer;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/filelib.php');

admin_externalpage_setup('local_extendednav_manage');

$menukey = optional_param('menu', 'primary', PARAM_ALPHANUMEXT);
$PAGE->set_url(new moodle_url('/local/extendednav/documentation.php', ['menu' => $menukey]));
$PAGE->set_title(get_string('documentation', 'local_extendednav'));
$PAGE->set_heading(get_string('documentation', 'local_extendednav'));

$examplepath = __DIR__ . '/example.yaml';
$example = file_exists($examplepath) ? file_get_contents($examplepath) : '';

if (optional_param('example', 0, PARAM_BOOL) && $example !== '') {
    send_file($example, 'extendednav_example.yaml', 0, 0, true, true, 'application/yaml');
}

$fields = [];
foreach (node_importer::EXPORT_FIELDS as $field) {
    $fields[] = [
        'name' => $field,
        'required' => $field === 'nodekey',
        'description' => get_string('doc_f_' . $field, 'local_extendednav'),
    ];
}

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_extendednav/documentation', [
    'backurl' => (new moodle_url('/local/extendednav/import.php', ['menu' => $menukey]))->out(false),
    'downloadurl' => (new moodle_url('/local/extendednav/documentation.php', ['example' => 1]))->out(false),
    'fields' => $fields,
    'example' => $example,
]);
echo $OUTPUT->footer();
