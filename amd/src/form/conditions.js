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
 * The conditions a node outside a course cannot use. Moodle's editor of Restrict access offers every condition the site has
 * enabled; a grade or the completion of an activity needs the activities of one course, so a node of the site or of a
 * category does not offer them. The editor reads whether a condition can be added when its dialog opens, so turning that
 * off once the editor is set up is enough; a condition saved before keeps showing, so it can still be removed.
 *
 * @module     local_extendednav/form/conditions
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @type {Number} How long the editor is waited for, in milliseconds. */
const WAIT = 15000;

/**
 * Stops offering some conditions in the dialog that adds a restriction.
 *
 * @param {String[]} conditions Names of the conditions, such as grade and completion.
 */
export const init = (conditions) => {
    const started = Date.now();
    const apply = () => {
        const plugins = window.M?.core_availability?.form?.plugins;
        if (!plugins || !Object.keys(plugins).length) {
            if (Date.now() - started < WAIT) {
                window.setTimeout(apply, 100);
            }
            return;
        }
        conditions.forEach((name) => {
            if (plugins[name]) {
                plugins[name].allowAdd = false;
            }
        });
    };
    apply();
};
