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
 * Opens marked primary navigation links in a new window.
 *
 * The primary navigation template has no target attribute, so nodes that open in a new
 * window carry a hidden marker inside their text and this module sets the target.
 *
 * @module     local_extendednav/primary
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const MARKER = '.local-extendednav-newwindow';

let initialised = false;

/**
 * Makes a link open in a new window.
 *
 * @param {HTMLAnchorElement} link The link.
 */
const openInNewWindow = (link) => {
    link.setAttribute('target', '_blank');
    link.setAttribute('rel', 'noopener');
};

/**
 * Sets up the links.
 */
export const init = () => {
    if (initialised) {
        return;
    }
    initialised = true;

    document.querySelectorAll(MARKER).forEach((marker) => {
        const link = marker.closest('a');
        if (link) {
            openInNewWindow(link);
        }
    });

    // The "More" menu and the mobile drawer copy items after the page has loaded.
    document.addEventListener('click', (event) => {
        const link = event.target.closest('a');
        if (link && link.querySelector(MARKER)) {
            openInNewWindow(link);
        }
    }, true);
};
