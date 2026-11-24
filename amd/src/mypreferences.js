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
 * The page where a user orders and hides their own preferences: each switch and each arrow saves at once, without reloading
 * the page, and the button Restore takes the page back to what the site shows.
 *
 * @module     local_extendednav/mypreferences
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {getString} from 'core/str';
import {add as toast} from 'core/toast';

/**
 * Reads what the page shows: the keys hidden and the order of the groups and of the items of each one.
 *
 * @param {HTMLElement} page The page.
 * @returns {{hidden: string[], order: Array<{group: string, keys: string[]}>}}
 */
const state = (page) => {
    const hidden = [...page.querySelectorAll('[data-action="toggle"]:not(:checked)')]
        .map((input) => input.closest('[data-key]').dataset.key);
    const groups = [...page.querySelectorAll('[data-region="group"]')];
    const order = [{group: '', keys: groups.map((group) => group.dataset.key)}];
    groups.forEach((group) => order.push({
        group: group.dataset.key,
        keys: [...group.querySelectorAll('[data-region="item"]')].map((item) => item.dataset.key),
    }));
    return {hidden, order};
};

/**
 * Turns off the arrows that would move a row out of its list.
 *
 * @param {HTMLElement} page The page.
 */
const arrows = (page) => {
    page.querySelectorAll('[data-region="groups"], [data-region="items"]').forEach((list) => {
        const rows = [...list.children];
        rows.forEach((row, index) => {
            const own = row.matches('[data-region="group"]') ? row.querySelector('[data-region="row"]') : row;
            const up = own.querySelector('[data-action="up"]');
            const down = own.querySelector('[data-action="down"]');
            if (up && down) {
                up.disabled = index === 0;
                down.disabled = index === rows.length - 1;
            }
        });
    });
};

/**
 * Saves what the page shows.
 *
 * @param {HTMLElement} page The page.
 * @param {object} args What to save.
 * @returns {Promise}
 */
const save = (page, args) => Ajax.call([{methodname: 'local_extendednav_set_personal', args}])[0]
    .then(() => {
        page.querySelector('[data-action="reset"]').disabled = !args.hidden.length && !args.order.length;
        return getString('personal_preferences_saved', 'local_extendednav');
    })
    .then((message) => toast(message))
    .catch(Notification.exception);

/**
 * Sets up the page.
 */
export const init = () => {
    const page = document.querySelector('.len[data-region="mypreferences"]');
    if (!page) {
        return;
    }
    arrows(page);
    page.addEventListener('change', (event) => {
        if (event.target.matches('[data-action="toggle"]')) {
            save(page, state(page));
        }
    });
    page.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-action]');
        if (!button || button.disabled) {
            return;
        }
        if (button.dataset.action === 'reset') {
            // Back to what the site shows: every switch on and every row where the site puts it.
            page.querySelectorAll('[data-action="toggle"]').forEach((input) => {
                input.checked = true;
            });
            page.querySelectorAll('[data-region="groups"], [data-region="items"]').forEach((list) => {
                [...list.children]
                    .filter((row) => row.dataset.siteindex !== undefined)
                    .sort((one, two) => one.dataset.siteindex - two.dataset.siteindex)
                    .forEach((row) => list.append(row));
            });
            arrows(page);
            save(page, {hidden: [], order: []});
            return;
        }
        const row = button.closest('[data-region="item"]') || button.closest('[data-region="group"]');
        const sibling = button.dataset.action === 'up' ? row.previousElementSibling : row.nextElementSibling;
        if (!sibling) {
            return;
        }
        if (button.dataset.action === 'up') {
            sibling.before(row);
        } else {
            sibling.after(row);
        }
        button.focus();
        arrows(page);
        save(page, state(page));
    });
};
