// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Brings the open tab of each row of tabs to the middle of its row, which on a narrow screen scrolls sideways, so the person
 * sees where they are.
 *
 * @module     local_extendednav/tab_scroll
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Centres the open tab of every row of tabs of the plugin.
 */
export const init = () => {
    document.querySelectorAll('.len .nav-tabs').forEach((row) => {
        const tab = row.querySelector('.nav-link.active');
        if (tab) {
            const outer = row.getBoundingClientRect();
            const inner = tab.getBoundingClientRect();
            row.scrollLeft += inner.left - outer.left - (outer.width - inner.width) / 2;
        }
    });
};
