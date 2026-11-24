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
 * The button next to the name of a navigation, as the buttons of the protocols of the web services: a pill, green with a
 * checked circle while the changes of the navigation are applied, grey with an empty circle while Moodle draws it as it does
 * without the plugin. Pressing it saves at once, without reloading the page.
 *
 * @module     local_extendednav/manage/power
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import {describeError, toast} from 'local_extendednav/local/ui';

/**
 * Draws the button for a state.
 *
 * @param {HTMLButtonElement} button The button.
 * @param {boolean} on Whether the changes are applied.
 * @param {Object} strings Texts: onhelp, offhelp.
 */
const paint = (button, on, strings) => {
    button.setAttribute('aria-pressed', String(on));
    button.classList.toggle('btn-outline-success', on);
    button.classList.toggle('btn-outline-secondary', !on);
    button.querySelector('[data-state="on"]').hidden = !on;
    button.querySelector('[data-state="off"]').hidden = on;
    button.title = on ? strings.onhelp : strings.offhelp;
};

/**
 * Wires the button, if the page has one.
 *
 * @param {HTMLElement} page The page.
 * @param {string} menu Key of the navigation.
 * @param {Object} strings Texts: onhelp, offhelp, saved.
 */
export const init = (page, menu, strings) => {
    const button = page.querySelector('[data-action="toggleenabled"]');
    if (!button) {
        return;
    }
    paint(button, button.getAttribute('aria-pressed') === 'true', strings);
    button.addEventListener('click', async() => {
        const on = button.getAttribute('aria-pressed') !== 'true';
        button.disabled = true;
        try {
            await Ajax.call([{methodname: 'local_extendednav_set_enabled', args: {menu, value: on}}])[0];
            paint(button, on, strings);
            // The column of the navigations says which are off.
            const item = page.querySelector(`[data-region="origins"] .list-group-item.active`);
            item?.querySelector('.badge')?.toggleAttribute('hidden', on);
            toast(strings.saved);
        } catch (error) {
            toast(describeError(error), 'danger');
        } finally {
            button.disabled = false;
        }
    });
};
