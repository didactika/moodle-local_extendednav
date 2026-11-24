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
 * Imports navigation nodes from a YAML file: for the whole site (where the file says, or into the categories and courses
 * chosen) or, from its own page, for one category, course or activity.
 *
 * Every node in the file is validated first; if any node is invalid, nothing is imported.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extendednav\local\io\importer;
use local_extendednav\local\navigation\context_scope;
use local_extendednav\menu_manager;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$menukey = optional_param('menu', 'primary', PARAM_ALPHANUMEXT);
$contextid = optional_param('contextid', 0, PARAM_INT);
$scope = $contextid ? context_scope::from_id($contextid) : null;
if ($scope !== null) {
    // In the category, the course or the activity, by whoever may change its navigations.
    require_login($scope->course(), false, $scope->cm());
    if ($scope->kind() === 'category') {
        $PAGE->set_context($scope->context());
        $PAGE->set_category_by_id($scope->context()->instanceid);
    }
    $scope->require_manage();
    if (!in_array($menukey, $scope->menus(), true)) {
        $menukey = $scope->menus()[0];
    }
    $PAGE->set_pagelayout($scope->is_activity() ? 'incourse' : 'admin');
    $manageurl = $scope->url($menukey);
    $url = new moodle_url('/local/extendednav/import.php', ['menu' => $menukey, 'contextid' => $contextid]);
} else {
    admin_externalpage_setup('local_extendednav_manage');
    if (menu_manager::get_menu($menukey) === null) {
        $menukey = 'primary';
    }
    $manageurl = new moodle_url('/local/extendednav/manage.php', ['menu' => $menukey]);
    $url = new moodle_url('/local/extendednav/import.php', ['menu' => $menukey]);
}

$PAGE->set_url($url);
$PAGE->set_title(get_string('import_nodes', 'local_extendednav'));
$PAGE->set_heading($scope ? $scope->name() : get_string('import_nodes', 'local_extendednav'));

$form = new \local_extendednav\form\import_form($url, ['sitewide' => $scope === null]);
$problems = [];

if ($form->is_cancelled()) {
    redirect($manageurl);
} else if ($data = $form->get_data()) {
    \core_php_time_limit::raise();
    raise_memory_limit(MEMORY_EXTRA);
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

    // The contexts the nodes go to: the one of the page, or the categories and courses chosen; none follows the file.
    $targets = [];
    if ($scope !== null) {
        $targets = [$contextid];
    } else {
        foreach ((array)($data->targetcategories ?? []) as $categoryid) {
            $targets[] = (int)\context_coursecat::instance((int)$categoryid)->id;
        }
        foreach ((array)($data->targetcourses ?? []) as $courseid) {
            if ((int)$courseid !== (int)SITEID) {
                $targets[] = (int)\context_course::instance((int)$courseid)->id;
            }
        }
    }
    $importer = new importer($menukey, $data->conflict_action === 'overwrite', $targets);
    $result = $importer->import($parsed['nodes']);
    if (empty($result['errors'])) {
        // Turning a navigation on or off is for the whole site only.
        if ($scope === null && !$targets) {
            foreach ((array)($parsed['navigations'] ?? []) as $key => $state) {
                if (menu_manager::get_menu((string)$key) !== null && is_array($state) && isset($state['enabled'])) {
                    set_config('enabled_' . $key, empty($state['enabled']) ? 0 : 1, 'local_extendednav');
                }
            }
        }
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
    'docurl' => (new moodle_url('/local/extendednav/documentation.php', ['menu' => $menukey]))->out(false),
]);
echo html_writer::start_div('len');
$form->display();
echo html_writer::end_div();
$PAGE->requires->js_call_amd('local_extendednav/local/select_menu', 'init');
echo $OUTPUT->footer();
