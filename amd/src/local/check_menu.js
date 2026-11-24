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
 * A menu of a row of filters whose options are checkboxes with the rows of each, as the menu of the statuses of every list of
 * the design system. Its element names the key of the state it filters (data-filter); each checkbox holds a value
 * (data-option) and each count the key of the counts it shows (data-count). The base state, nothing chosen, is not an option:
 * once a value is chosen the menu is tinted and its cross takes it back.
 *
 *     <span class="dropdown" data-menu="origin" data-filter="origin">
 *         <button class="form-select" data-menu-toggle><span data-region="menu-label" data-label="Origin">Origin</span></button>
 *         <button class="btn btn-link p-0" data-menu-clear hidden>…</button>
 *         <span class="dropdown-menu show" hidden>
 *             <label class="dropdown-item">
 *                 <input type="checkbox" data-option="native"> Moodle <span data-count="native"></span>
 *             </label>
 *         </span>
 *     </span>
 *
 * @module     local_extendednav/local/check_menu
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * The values chosen, as the state keeps them: separated by commas.
 *
 * @param {string} value The value of the state.
 * @returns {string[]} The values.
 */
export const chosen = (value) => String(value || '').split(',').filter(Boolean);

/**
 * Opens or closes a menu.
 *
 * @param {HTMLElement} menu The element of the menu.
 * @param {boolean} show Whether it opens.
 */
export const open = (menu, show) => {
    menu.querySelector(':scope > .dropdown-menu').hidden = !show;
    menu.querySelector(':scope > [data-menu-toggle]').setAttribute('aria-expanded', show ? 'true' : 'false');
};

/**
 * Wires the menus of a list: each closes with a press outside it and with Escape, in the capture phase of the window, before
 * Bootstrap 5 looks at the keys of every dropdown menu of the document.
 *
 * @param {HTMLElement[]} menus The menus.
 */
export const wire = (menus) => {
    if (menus.length === 0) {
        return;
    }
    document.addEventListener('pointerdown', (event) => {
        menus.filter((menu) => event.target instanceof Node && !menu.contains(event.target)).forEach((menu) => open(menu, false));
    });
    window.addEventListener('keydown', (event) => {
        const shown = menus.find((menu) => !menu.querySelector(':scope > .dropdown-menu').hidden);
        if (event.key === 'Escape' && shown) {
            event.preventDefault();
            event.stopPropagation();
            open(shown, false);
            shown.querySelector(':scope > [data-menu-toggle]').focus();
        }
    }, true);
};

/**
 * Draws a menu for the state: the boxes ticked, the counts, what the button says (what it filters, the one value chosen, or how
 * many are) and its tint and cross while it narrows the list.
 *
 * @param {HTMLElement} menu The element of the menu.
 * @param {string} value The value of the state it filters.
 * @param {Object} counts Rows of each count key.
 */
export const paint = (menu, value, counts) => {
    const values = chosen(value);
    menu.querySelectorAll('[data-option]').forEach((box) => {
        box.checked = values.includes(box.dataset.option);
    });
    menu.querySelectorAll('[data-count]').forEach((count) => {
        if (counts[count.dataset.count] !== undefined) {
            count.textContent = counts[count.dataset.count];
        }
    });
    const label = menu.querySelector('[data-region="menu-label"]');
    let text = label.dataset.label;
    if (values.length === 1) {
        text = menu.querySelector(`[data-option="${values[0]}"]`)?.nextElementSibling?.textContent.trim() || text;
    } else if (values.length > 1) {
        text = `${label.dataset.label}: ${values.length}`;
    }
    label.textContent = text;
    menu.querySelector(':scope > [data-menu-toggle]').classList.toggle('border-primary', values.length > 0);
    menu.querySelector(':scope > [data-menu-clear]').hidden = values.length === 0;
};

/**
 * The value of the state that the boxes of a menu make.
 *
 * @param {HTMLElement} menu The element of the menu.
 * @returns {string} The values ticked, separated by commas.
 */
export const read = (menu) => Array.from(menu.querySelectorAll('[data-option]'))
    .filter((box) => box.checked).map((box) => box.dataset.option).join(',');
