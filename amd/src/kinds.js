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
 * The list of the kinds of activity: the search keeps the kinds whose name holds every word typed, whatever their case and
 * accents, and says so when none does. The list is short, so it is searched in the page.
 *
 * @module     local_extendednav/kinds
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * A text as the search compares it: in small letters and without accents.
 *
 * @param {string} value The text.
 * @returns {string}
 */
const fold = (value) => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

/**
 * Sets up the search of the list.
 *
 * @param {string} selector Selector of the page.
 */
export const init = (selector) => {
    const page = document.querySelector(selector);
    const search = page?.querySelector('[data-region="kindsearch"]');
    if (!search) {
        return;
    }
    const rows = Array.from(page.querySelectorAll('[data-region="kind"]'));
    const empty = page.querySelector('[data-region="kindsempty"]');
    const table = page.querySelector('[data-region="tablewrap"] table');
    search.closest('form')?.addEventListener('submit', (event) => event.preventDefault());
    search.addEventListener('input', () => {
        const words = fold(search.value).split(/\s+/).filter(Boolean);
        let shown = 0;
        rows.forEach((row) => {
            row.hidden = !words.every((word) => fold(row.dataset.name).includes(word));
            shown += row.hidden ? 0 : 1;
        });
        empty.hidden = shown > 0;
        table.hidden = shown === 0;
    });
};
