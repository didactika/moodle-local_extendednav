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
 * A menu that opens under its button (a Bootstrap dropdown-menu drawn by the page): it closes with a press outside it, never
 * with the one on its own button, and with Escape in the capture phase of the window, before Bootstrap 5 looks at the keys of
 * every dropdown menu of the document.
 *
 * @module     local_extendednav/local/popup_menu
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Wires a menu.
 *
 * @param {HTMLElement} menu Element with the button (data-menu-toggle) and the dropdown-menu.
 * @param {Function} [onOpen] Called each time it opens.
 * @returns {Function} Opens or closes it, given whether it shows.
 */
export const init = (menu, onOpen) => {
    const popup = menu.querySelector(':scope > .dropdown-menu');
    const toggle = menu.querySelector(':scope > [data-menu-toggle]');
    const open = (show) => {
        popup.hidden = !show;
        toggle.setAttribute('aria-expanded', show ? 'true' : 'false');
        if (show && onOpen) {
            onOpen();
        }
    };
    toggle.addEventListener('click', () => open(popup.hidden));
    document.addEventListener('pointerdown', (event) => {
        if (event.target instanceof Node && !menu.contains(event.target)) {
            open(false);
        }
    });
    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !popup.hidden) {
            event.stopPropagation();
            open(false);
            toggle.focus();
        }
    }, true);
    return open;
};
