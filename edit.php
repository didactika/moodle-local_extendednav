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
 * Navigation node creation/editing interface.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_extendednav_manage');

$id = optional_param('id', 0, PARAM_INT);

$url = new moodle_url('/local/extendednav/edit.php');
if ($id) {
    $url->param('id', $id);
}

$PAGE->set_url($url);
$streditname = $id ? get_string('edit_node', 'local_extendednav') : get_string('add_node', 'local_extendednav');
$PAGE->set_title($streditname);
$PAGE->set_heading($streditname);

$mform = new \local_extendednav\form\node_form($url);

if ($id) {
    $node = $DB->get_record('local_extendednav', ['id' => $id], '*', MUST_EXIST);
    if (!empty($node->roles)) {
        $node->roles = explode(',', $node->roles);
    }
    $mform->set_data($node);
}

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/extendednav/manage.php'));
} else if ($data = $mform->get_data()) {
    
    $record = new stdClass();
    $record->nodekey = $data->nodekey;
    $record->title = !empty($data->title) ? $data->title : null;
    $record->url = !empty($data->url) ? $data->url : null;
    $record->icon = !empty($data->icon) ? $data->icon : null;
    $record->beforekey = !empty($data->beforekey) ? $data->beforekey : null;
    $record->parentkey = !empty($data->parentkey) ? $data->parentkey : null;
    $record->visibility = (int)$data->visibility;
    $record->newwindow = !empty($data->newwindow) ? 1 : 0;
    
    $blockedurls = null;
    if ($record->visibility != 1) { 
        try {
            $temp_page = new \moodle_page();
            $temp_page->set_context(\context_system::instance());
            $temp_page->set_url($PAGE->url);
            $primary = new \core\navigation\views\primary($temp_page);
            $primary->initialise();
            
            $corenode = $primary->get($record->nodekey);
            if ($corenode && $corenode->action instanceof \moodle_url) {
                $parsed_core = parse_url($corenode->action->out(false));
                $corepath = isset($parsed_core['path']) ? $parsed_core['path'] : '';
                if (isset($parsed_core['query']) && $parsed_core['query'] !== '') {
                    $corepath .= '?' . $parsed_core['query'];
                }
                
                if (!empty($corepath)) {
                    $blockedurls = $corepath;
                }
            }
        } catch (\Throwable $e) {}
    }
    
    if ($record->visibility != 1 && !empty($record->url)) {
        $parsed_custom = parse_url($record->url);
        $custompath = isset($parsed_custom['path']) ? $parsed_custom['path'] : '';
        if (isset($parsed_custom['query']) && $parsed_custom['query'] !== '') {
            $custompath .= '?' . $parsed_custom['query'];
        }

        if (!empty($custompath)) {
            if ($blockedurls && $blockedurls !== $custompath) {
                $blockedurls .= ',' . $custompath;
            } else {
                $blockedurls = $custompath;
            }
        }
    }

    $record->blockedurls = $blockedurls;
    
    if ($record->visibility == 2 && !empty($data->roles)) {
        $record->roles = implode(',', $data->roles);
    } else {
        $record->roles = null;
    }

    if ($id) {
        $record->id = $id;
        $DB->update_record('local_extendednav', $record);
    } else {
        $max = $DB->get_field_sql('SELECT MAX(sortorder) FROM {local_extendednav}');
        $record->sortorder = $max !== false ? $max + 1 : 0;
        $DB->insert_record('local_extendednav', $record);
    }
    
    try {
        \cache::make('local_extendednav', 'nodes')->purge();
    } catch (\Throwable $e) {}
    theme_reset_all_caches();

    redirect(new moodle_url('/local/extendednav/manage.php'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading($streditname);

$mform->display();

echo $OUTPUT->footer();