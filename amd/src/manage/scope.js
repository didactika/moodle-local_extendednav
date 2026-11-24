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
 * The menu of the management page that chooses the scope of the changes: the whole site, or a category or a course, found by
 * a search. Choosing one opens the page for it.
 *
 * @module     local_extendednav/manage/scope
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import {debounce} from 'local_extendednav/local/ui';
import {init as popupMenu} from 'local_extendednav/local/popup_menu';

/**
 * Opens the page for a scope.
 *
 * @param {number} contextid Context of the scope, 0 for the whole site.
 */
const go = (contextid) => {
    const url = new URL(window.location.href);
    ['page', 'parent', 'contextid'].forEach((name) => url.searchParams.delete(name));
    if (contextid) {
        url.searchParams.set('contextid', contextid);
    }
    window.location.assign(url);
};

/**
 * An option of the menu.
 *
 * @param {string} label Name of the scope.
 * @param {number} contextid Its context.
 * @param {boolean} selected Whether it is the scope shown.
 * @param {string} hint How many exceptions it has, or empty.
 * @returns {HTMLButtonElement}
 */
const option = (label, contextid, selected, hint) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'dropdown-item d-flex align-items-center justify-content-between gap-3';
    button.setAttribute('role', 'option');
    button.setAttribute('aria-selected', String(selected));
    const name = document.createElement('span');
    name.className = 'text-truncate';
    name.textContent = label;
    button.append(name);
    if (hint) {
        const badge = document.createElement('span');
        badge.className = 'badge rounded-pill text-bg-secondary';
        badge.textContent = hint;
        button.append(badge);
    }
    button.addEventListener('click', () => go(contextid));
    return button;
};

/**
 * Wires the menu of the scope, if the page has one.
 *
 * @param {HTMLElement} page The page.
 * @param {Object} config The configuration of the page: menu and contextid.
 * @param {Object} strings Texts: wholesite, exceptions (with {$a}) and noscopes.
 */
export const init = (page, config, strings) => {
    const menu = page.querySelector('[data-menu="scope"]');
    if (!menu) {
        return;
    }
    const search = menu.querySelector('[data-region="scopesearch"]');
    const list = menu.querySelector('[data-region="scopelist"]');
    const load = async() => {
        const text = search.value.trim();
        const args = {menu: config.menu, search: text};
        const {scopes} = await Ajax.call([{methodname: 'local_extendednav_get_scopes', args}])[0];
        const items = [option(strings.wholesite, 0, config.contextid === 0, '')];
        scopes.forEach((scope) => {
            const hint = scope.exceptions ? strings.exceptions.replace('{$a}', scope.exceptions) : '';
            items.push(option(scope.name, scope.contextid, scope.contextid === config.contextid, hint));
        });
        if (scopes.length === 0 && text) {
            const empty = document.createElement('p');
            empty.className = 'text-muted small px-3 py-2 mb-0';
            empty.textContent = strings.noscopes;
            items.push(empty);
        }
        list.replaceChildren(...items);
    };
    popupMenu(menu, () => {
        search.focus();
        load();
    });
    search.addEventListener('input', debounce(load, 300));
};
