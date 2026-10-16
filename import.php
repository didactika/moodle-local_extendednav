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
 * Imports navigation nodes from a YAML file.
 *
 * Every node in the file is validated first; if any node is invalid, nothing is imported.
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

admin_externalpage_setup('local_extendednav_manage');

$menukey = optional_param('menu', 'primary', PARAM_ALPHANUMEXT);
if (menu_manager::get_menu($menukey) === null) {
    $menukey = 'primary';
}

$url = new moodle_url('/local/extendednav/import.php', ['menu' => $menukey]);
$PAGE->set_url($url);
$PAGE->set_title(get_string('import_nodes', 'local_extendednav'));
$PAGE->set_heading(get_string('import_nodes', 'local_extendednav'));

$manageurl = new moodle_url('/local/extendednav/manage.php', ['menu' => $menukey]);
$form = new \local_extendednav\form\import_form($url);
$problems = [];

if ($form->is_cancelled()) {
    redirect($manageurl);
} else if ($data = $form->get_data()) {
    $content = $form->get_file_content('yamlfile');
    if ($content === false) {
        throw new \moodle_exception('err_invalid_yaml_file', 'local_extendednav');
    }

    try {
        $parsed = \local_extendednav\yaml::parse($content);
    } catch (\Throwable $e) {
        throw new \moodle_exception('err_invalid_yaml_format', 'local_extendednav', $url);
    }
    if (!isset($parsed['nodes']) || !is_array($parsed['nodes'])) {
        throw new \moodle_exception('err_invalid_yaml_structure', 'local_extendednav', $url);
    }

    $importer = new node_importer($menukey, $data->conflict_action === 'overwrite');
    $result = $importer->import($parsed['nodes']);
    if (empty($result['errors'])) {
        foreach ($result['warnings'] as $warning) {
            \core\notification::warning($warning);
        }
        redirect(
            $manageurl,
            get_string('import_success', 'local_extendednav', (object)$result['counts']),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
    $problems = $result['errors'];
}

echo $OUTPUT->header();

if (!empty($problems)) {
    echo $OUTPUT->notification(
        html_writer::tag('p', get_string('import_failed', 'local_extendednav')) . html_writer::alist(array_map('s', $problems)),
        \core\output\notification::NOTIFY_ERROR
    );
}

echo $OUTPUT->render_from_template('local_extendednav/import_intro', [
    'backurl' => $manageurl->out(false),
    'docurl' => (new moodle_url('/local/extendednav/documentation.php', ['menu' => $menukey]))->out(false),
]);
$form->display();
echo $OUTPUT->footer();
