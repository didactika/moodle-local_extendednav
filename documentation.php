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
 * Extended Navigation documentation page.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_extendednav_manage');

$url = new moodle_url('/local/extendednav/documentation.php');
$PAGE->set_url($url);
$PAGE->set_title(get_string('documentation', 'local_extendednav'));
$PAGE->set_heading(get_string('documentation', 'local_extendednav'));

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

echo $OUTPUT->header();

$backurl = new moodle_url('/local/extendednav/import.php');
$downloadurl = new moodle_url('/local/extendednav/documentation.php', ['example' => 1]);

echo '<div class="extendednav-documentation-page">';

echo '<div class="mb-4">';
echo '<a href="'.$backurl->out().'" class="btn btn-secondary">' . "\n";
echo '    <i class="fa fa-arrow-left mr-2"></i>' . get_string('back', 'moodle') . "\n";
echo '</a>';
echo '</div>';

echo '<div class="card">';
echo '    <div class="card-header bg-primary text-white">';
echo '        <h4 class="mb-0"><i class="fa fa-book mr-2"></i>' . get_string('doc_schema_reference', 'local_extendednav') . '</h4>';
echo '    </div>';
echo '    <div class="card-body">';

echo '        <h5 class="mb-3">' . get_string('quick_links', 'local_extendednav') . '</h5>';
echo '        <ul class="list-unstyled mb-4">';
echo '            <li><a href="#structure"><i class="fa fa-chevron-right mr-2"></i>' . get_string('doc_structure', 'local_extendednav') . '</a></li>';
echo '            <li><a href="#fields"><i class="fa fa-chevron-right mr-2"></i>' . get_string('doc_fields_title', 'local_extendednav') . '</a></li>';
echo '            <li><a href="#example"><i class="fa fa-chevron-right mr-2"></i>' . get_string('doc_example_format', 'local_extendednav') . '</a></li>';
echo '        </ul>';

echo '        <div class="alert alert-success mb-4">';
echo '            <i class="fa fa-download mr-2"></i>';
echo '            <a href="'.$downloadurl->out().'" class="alert-link font-weight-bold">' . get_string('download_example', 'local_extendednav') . '</a>';
echo '            - ' . get_string('doc_download_desc', 'local_extendednav');
echo '        </div>';

echo '        <h4 id="structure" class="mt-4 mb-3 text-primary">' . get_string('doc_structure', 'local_extendednav') . '</h4>';
echo '        <p>' . get_string('doc_intro', 'local_extendednav') . '</p>';
echo '        <p class="text-muted"><i class="fa fa-info-circle mr-1"></i> ' . get_string('doc_example_tip', 'local_extendednav') . '</p>';

echo '        <h4 id="fields" class="mt-4 mb-3 text-primary">' . get_string('doc_fields_title', 'local_extendednav') . '</h4>';
echo '        <table class="table table-bordered table-striped">';
echo '            <thead class="thead-light">';
echo '                <tr>';
echo '                    <th>' . get_string('doc_field', 'local_extendednav') . '</th>';
echo '                    <th>' . get_string('doc_type', 'local_extendednav') . '</th>';
echo '                    <th>' . get_string('doc_desc', 'local_extendednav') . '</th>';
echo '                </tr>';
echo '            </thead>';
echo '            <tbody>';
echo '                <tr><td><code>nodekey</code></td><td><span class="badge badge-success">' . get_string('yes', 'moodle') . '</span></td><td>' . get_string('doc_f_nodekey', 'local_extendednav') . '</td></tr>';
echo '                <tr><td><code>title</code></td><td><span class="badge badge-secondary">' . get_string('no', 'moodle') . '</span></td><td>' . get_string('doc_f_title', 'local_extendednav') . '</td></tr>';
echo '                <tr><td><code>url</code></td><td><span class="badge badge-secondary">' . get_string('no', 'moodle') . '</span></td><td>' . get_string('doc_f_url', 'local_extendednav') . '</td></tr>';
echo '                <tr><td><code>icon</code></td><td><span class="badge badge-secondary">' . get_string('no', 'moodle') . '</span></td><td>' . get_string('doc_f_icon', 'local_extendednav') . '</td></tr>';
echo '                <tr><td><code>visibility</code></td><td><span class="badge badge-secondary">' . get_string('no', 'moodle') . '</span></td><td>' . get_string('doc_f_visibility', 'local_extendednav') . '</td></tr>';
echo '                <tr><td><code>roles</code></td><td><span class="badge badge-secondary">' . get_string('no', 'moodle') . '</span></td><td>' . get_string('doc_f_roles', 'local_extendednav') . '</td></tr>';
echo '                <tr><td><code>parentkey</code></td><td><span class="badge badge-secondary">' . get_string('no', 'moodle') . '</span></td><td>' . get_string('doc_f_parentkey', 'local_extendednav') . '</td></tr>';
echo '                <tr><td><code>beforekey</code></td><td><span class="badge badge-secondary">' . get_string('no', 'moodle') . '</span></td><td>' . get_string('doc_f_beforekey', 'local_extendednav') . '</td></tr>';
echo '                <tr><td><code>newwindow</code></td><td><span class="badge badge-secondary">' . get_string('no', 'moodle') . '</span></td><td>' . get_string('doc_f_newwindow', 'local_extendednav') . '</td></tr>';
echo '                <tr><td><code>blockedurls</code></td><td><span class="badge badge-secondary">' . get_string('no', 'moodle') . '</span></td><td>' . get_string('doc_f_blockedurls', 'local_extendednav') . '</td></tr>';
echo '            </tbody>';
echo '        </table>';

$example_content = file_exists(__DIR__ . '/example.yml') ? file_get_contents(__DIR__ . '/example.yml') : '';

echo '        <h4 id="example" class="mt-5 mb-3 text-primary">' . get_string('doc_example_format', 'local_extendednav') . '</h4>';
echo '        <pre class="bg-light p-3 rounded"><code>' . s($example_content) . '</code></pre>';

echo '    </div>';
echo '</div>';
echo '</div>';

echo $OUTPUT->footer();
