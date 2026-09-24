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
            if (isset($data->conflict_action) && $data->conflict_action === 'skip') {
                continue;
            } else {
                // Default to overwrite if action is 'overwrite' or somehow omitted
                $record->id = $existing->id;
                $DB->update_record('local_extendednav', $record);
            }
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

$doc_url = new moodle_url('/local/extendednav/documentation.php');

echo '<div class="extendednav-import-page">';

$backurl = new moodle_url('/local/extendednav/manage.php');
echo '<div class="mb-4">';
echo '<a href="'.$backurl->out().'" class="btn btn-secondary">' . "\n";
echo '    <i class="fa fa-arrow-left mr-2"></i>' . get_string('back', 'moodle') . "\n";
echo '</a>';
echo '</div>';

echo '<div class="alert alert-info">';
echo '    <h5><i class="fa fa-info-circle mr-2"></i>'.get_string('import_instructions_title', 'local_extendednav').'</h5>';
echo '    <p>'.get_string('import_instructions_desc', 'local_extendednav').'</p>';
echo '    <ul>';
echo '        <li>'.get_string('yamlconfigfile', 'local_extendednav').' (<code>.yml</code>, <code>.yaml</code>)</li>';
echo '    </ul>';
echo '    <p>';
echo '        <a href="'.$doc_url->out().'" class="text-info font-weight-bold">';
echo '            <i class="fa fa-book mr-1"></i>'.get_string('view_documentation', 'local_extendednav');
echo '        </a>';
echo '    </p>';
echo '</div>';

echo '<div class="import-form-container card p-4 bg-light mb-4">';
$mform->display();
echo '</div>';

echo '</div>';

echo $OUTPUT->footer();
