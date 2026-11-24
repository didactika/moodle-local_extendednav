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
 * The page where a user orders and hides the groups and items of their own page Preferences.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_extendednav\local\navigation\preferences_injector;

require_once(__DIR__ . '/../../config.php');

require_login(null, false);
if (isguestuser()) {
    throw new \moodle_exception('noguest');
}
$context = context_user::instance((int)$USER->id);
require_capability('local/extendednav:personalise', $context);

$url = new moodle_url('/local/extendednav/mypreferences.php');
$title = get_string('personal_preferences', 'local_extendednav');
$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');
// The guided tour cannot be skipped when the site asks for it: the styles hide its skip button.
if (get_config('local_extendednav', 'tourrequired')) {
    $PAGE->add_body_class('local-extendednav-tour-required');
}
$PAGE->set_title($title);
$PAGE->set_heading(fullname($USER));
$PAGE->navbar->add(get_string('preferences'), new moodle_url('/user/preferences.php'));
$PAGE->navbar->add($title, $url);

// The page shows what the site shows, before what the user chose, so the user can choose again.
preferences_injector::$personal = false;
$settings = $PAGE->settingsnav->find('usercurrentsettings', null);
$groups = $settings ? preferences_injector::read($settings) : [];
preferences_injector::$personal = true;

$personal = preferences_injector::get_personal((int)$USER->id);
$hidden = array_flip($personal['hidden']);
// The groups and items already stand in the order of the site; the order the user chose goes first.
$mine = static function (array $keys, array $order): array {
    return array_values(array_unique(array_merge(array_intersect($order, $keys), $keys)));
};
$rows = [];
$loose = null;
$groupindex = array_flip(array_keys($groups));
foreach ($mine(array_keys($groups), $personal['order'][''] ?? []) as $groupkey) {
    $items = [];
    $itemindex = array_flip(array_keys($groups[$groupkey]['items']));
    foreach ($mine(array_keys($groups[$groupkey]['items']), $personal['order'][$groupkey] ?? []) as $itemkey) {
        $items[] = [
            'key' => $itemkey,
            'siteindex' => $itemindex[$itemkey],
            'fixed' => false,
            'name' => trim(strip_tags((string)$groups[$groupkey]['items'][$itemkey]->text)),
            'shown' => !isset($hidden[$itemkey]),
        ];
    }
    $node = $groups[$groupkey]['node'];
    $row = [
        'key' => $groupkey,
        'siteindex' => $groupindex[$groupkey],
        'name' => $node ? trim(strip_tags((string)$node->text)) : get_string('miscellaneous'),
        'shown' => !isset($hidden[$groupkey]),
        'fixed' => $groupkey === preferences_injector::LOOSE,
        'items' => $items,
    ];
    // The items outside every group always come last, as Moodle shows them, so they are not moved among the groups.
    if ($groupkey === preferences_injector::LOOSE) {
        $loose = $row;
    } else {
        $rows[] = $row;
    }
}

$PAGE->requires->js_call_amd('local_extendednav/mypreferences', 'init');
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_extendednav/mypreferences', [
    'groups' => $rows,
    'loose' => $loose ? [$loose] : [],
    'empty' => !$rows && !$loose,
    'changed' => $personal['hidden'] || $personal['order'],
    'preferencesurl' => (new moodle_url('/user/preferences.php'))->out(false),
]);
echo $OUTPUT->footer();
