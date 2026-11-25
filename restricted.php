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
 * Page shown to users who opened a blocked page when every fallback page is blocked for them too.
 *
 * Pages of this plugin are never blocked, so this page cannot redirect to itself.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

// The page tells the user who is logged in that no page is open to them, and offers them to log out: it shows nothing of the
// site and changes nothing, so it is theirs alone, in their own context, and needs no capability. A guest is sent to log in.
require_login(null, false);
if (isguestuser()) {
    redirect(get_login_url());
}
$context = context_user::instance((int)$USER->id);
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/extendednav/restricted.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('restricted_title', 'local_extendednav'));
$PAGE->set_heading(get_string('restricted_title', 'local_extendednav'));

echo $OUTPUT->header();
echo $OUTPUT->notification(
    get_string('err_restricted_page', 'local_extendednav'),
    \core\output\notification::NOTIFY_WARNING,
    false
);
echo $OUTPUT->single_button(new moodle_url('/login/logout.php', ['sesskey' => sesskey()]), get_string('logout'), 'post');
echo $OUTPUT->footer();
