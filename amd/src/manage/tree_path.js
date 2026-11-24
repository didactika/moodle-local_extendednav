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
 * The breadcrumb above the list of the site administration: the levels from the top down to the one listed, each a button
 * that opens its level. It is hidden while a search looks in every level.
 *
 * @module     local_extendednav/manage/tree_path
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Draws the breadcrumb.
 *
 * @param {HTMLElement} nav The element of the breadcrumb.
 * @param {Object[]} path The levels from the top down, each with key and title.
 * @param {string} top Name of the top level.
 * @param {boolean} hidden Whether it is hidden.
 */
export const render = (nav, path, top, hidden) => {
    if (!nav) {
        return;
    }
    nav.hidden = hidden;
    const crumbs = [{key: '', title: top}, ...path];
    const items = crumbs.map((crumb, index) => {
        const item = document.createElement('li');
        item.className = 'breadcrumb-item';
        if (index === crumbs.length - 1) {
            item.classList.add('active');
            item.setAttribute('aria-current', 'page');
            item.textContent = crumb.title;
            return item;
        }
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-link p-0 align-baseline';
        button.dataset.action = 'openlevel';
        button.dataset.key = crumb.key;
        button.textContent = crumb.title;
        item.append(button);
        return item;
    });
    nav.querySelector('ol').replaceChildren(...items);
};
