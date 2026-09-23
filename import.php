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
 * Import customized navigation nodes from YAML.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_extendednav_manage');

if (optional_param('example', 0, PARAM_INT)) {
    $example_path = __DIR__ . '/example.yml';
    if (file_exists($example_path)) {
        $example_content = file_get_contents($example_path);
        header('Content-Type: application/x-yaml');
        header('Content-Disposition: attachment; filename="extendednav_example.yml"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($example_content));
        echo $example_content;
        exit;
    }
}

$url = new moodle_url('/local/extendednav/import.php');
$PAGE->set_url($url);
$streditname = get_string('import_nodes', 'local_extendednav');
$PAGE->set_title($streditname);
$PAGE->set_heading($streditname);

$mform = new \local_extendednav\form\import_form($url);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/extendednav/manage.php'));
} else if ($data = $mform->get_data()) {

    $draftitemid = $data->yamlfile;
    $fs = get_file_storage();
    $context = \context_user::instance($USER->id);
    $files = $fs->get_area_files($context->id, 'user', 'draft', $draftitemid, 'id DESC', false);

    $content = false;
    if (!empty($files)) {
        $fileinfo = reset($files);
        if ($fileinfo && !$fileinfo->is_directory()) {
            $content = $fileinfo->get_content();
        }
    }

    if ($content === false) {
        throw new \moodle_exception('err_invalid_yaml_file', 'local_extendednav');
    }

    try {
        $parsed = \local_extendednav\yaml::parse($content);
    } catch (\Throwable $e) {
        throw new \moodle_exception('err_invalid_yaml_format', 'local_extendednav', '', $e->getMessage());
    }

    if (!is_array($parsed) || !isset($parsed['nodes']) || !is_array($parsed['nodes'])) {
        throw new \moodle_exception('err_invalid_yaml_structure', 'local_extendednav');
    }

    if ($data->importmode === 'overwrite') {
        $DB->delete_records('local_extendednav');
    }

    $max = $DB->get_field_sql('SELECT MAX(sortorder) FROM {local_extendednav}');
    $sortorder = $max !== false ? $max + 1 : 0;

    foreach ($parsed['nodes'] as $node) {
        if (!isset($node['nodekey'])) {
            continue;
        }

        $record = new stdClass();
        $record->nodekey = $node['nodekey'];
        $record->title = isset($node['title']) && $node['title'] !== '' && $node['title'] !== null ? $node['title'] : null;
        $record->url = isset($node['url']) && $node['url'] !== '' && $node['url'] !== null ? $node['url'] : null;
        $record->icon = isset($node['icon']) && $node['icon'] !== '' && $node['icon'] !== null ? $node['icon'] : null;
        $record->visibility = isset($node['visibility']) ? (int)$node['visibility'] : 1;
        $record->roles = isset($node['roles']) && $node['roles'] !== '' && $node['roles'] !== null ? $node['roles'] : null;
        $record->parentkey = isset($node['parentkey']) && $node['parentkey'] !== '' && $node['parentkey'] !== null ? $node['parentkey'] : null;
        $record->beforekey = isset($node['beforekey']) && $node['beforekey'] !== '' && $node['beforekey'] !== null ? $node['beforekey'] : null;
        $record->newwindow = isset($node['newwindow']) ? (int)$node['newwindow'] : 0;
        $record->blockedurls = isset($node['blockedurls']) && $node['blockedurls'] !== '' && $node['blockedurls'] !== null ? $node['blockedurls'] : null;
        
        $existing = $DB->get_record('local_extendednav', ['nodekey' => $record->nodekey], 'id');
        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('local_extendednav', $record);
        } else {
            $record->sortorder = $sortorder++;
            $DB->insert_record('local_extendednav', $record);
        }
    }

    try { \cache::make('local_extendednav', 'nodes')->purge(); } catch (\Throwable $e) {}
    theme_reset_all_caches();

    redirect(
        new moodle_url('/local/extendednav/manage.php'),
        get_string('import_success', 'local_extendednav'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo $OUTPUT->heading($streditname);

$example_url = new moodle_url('/local/extendednav/import.php', ['example' => 1]);

echo '<div class="alert alert-info border mb-4">';
echo '    <div class="row align-items-center">';
echo '        <div class="col-md-8">';
echo '            <h4 class="alert-heading"><i class="fa fa-info-circle mr-2"></i>'.get_string('import_instructions_title', 'local_extendednav').'</h4>';
echo '            <p class="mb-0">'.get_string('import_instructions_desc', 'local_extendednav').'</p>';
echo '        </div>';
echo '        <div class="col-md-4 text-right">';
echo '            <a href="'.$example_url->out().'" class="btn btn-info text-white" download="extendednav_example.yml">';
echo '                <i class="fa fa-download mr-1"></i>'.get_string('download_example', 'local_extendednav');
echo '            </a>';
echo '        </div>';
echo '    </div>';
echo '    <hr>';
echo '    <pre class="bg-light p-3 border rounded mb-0" style="font-size: 0.85rem;"><code>'.s(file_get_contents(__DIR__.'/example.yml')).'</code></pre>';
echo '</div>';

$mform->display();

echo $OUTPUT->footer();
