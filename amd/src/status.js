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
 * The page of the state of the navigations: the switch of the whole plugin and one switch for each navigation, each saved at
 * once without reloading the page. Turning the plugin off asks first, since the pages the nodes block are open again; while it
 * is off the switches of the navigations are grey and cannot be pressed, but keep the state each navigation goes back to.
 *
 * @module     local_extendednav/status
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import {get_strings as getStrings} from 'core/str';
import {describeError, toast} from 'local_extendednav/local/ui';
import {confirmAction} from 'local_extendednav/local/action_dialog';

/** Strings of the page, all of local_extendednav. */
const STRINGS = ['saved', 'status_confirm_title', 'status_confirm', 'status_confirm_yes'];

/**
 * Loads the strings of the page.
 *
 * @returns {Promise<Object>} Each string by its key.
 */
const loadStrings = async() => {
    const texts = await getStrings(STRINGS.map((key) => ({key, component: 'local_extendednav'})));
    return Object.fromEntries(STRINGS.map((key, index) => [key, texts[index]]));
};

/**
 * Says in the header of each group how many of its navigations are on.
 *
 * @param {HTMLElement} page The page.
 */
const countGroups = async(page) => {
    const groups = [...page.querySelectorAll('[data-group]')];
    const counts = groups.map((group) => {
        const inputs = [...group.querySelectorAll('[data-action="togglenavigation"]')];
        return {on: inputs.filter((input) => input.checked).length, count: inputs.length};
    });
    const labels = await getStrings(counts.map((a) => ({key: 'status_group_count', component: 'local_extendednav', param: a})));
    groups.forEach((group, index) => {
        group.querySelector('[data-region="groupcount"]').textContent = labels[index];
    });
};

/**
 * Greys the navigations and locks their switches while the plugin is off.
 *
 * @param {HTMLElement} page The page.
 * @param {boolean} on Whether the plugin is on.
 */
const paintPlugin = (page, on) => {
    page.dataset.pluginenabled = on ? '1' : '0';
    page.querySelector('[data-region="pluginoff"]').hidden = on;
    const navigations = page.querySelector('[data-region="navigations"]');
    if (on) {
        navigations.removeAttribute('aria-disabled');
    } else {
        navigations.setAttribute('aria-disabled', 'true');
    }
    navigations.querySelectorAll('[data-action="togglenavigation"]').forEach((input) => {
        input.disabled = !on;
    });
};

/**
 * Wires the switches of the page.
 *
 * @param {string} selector Selector of the page.
 */
export const init = async(selector) => {
    const page = document.querySelector(selector);
    if (!page) {
        return;
    }
    const t = await loadStrings();
    const plugin = page.querySelector('[data-action="toggleplugin"]');

    /**
     * Saves the switch of the whole plugin, or puts it back as it was when the change fails.
     *
     * @param {boolean} on Whether the plugin is turned on.
     */
    const savePlugin = async(on) => {
        plugin.disabled = true;
        try {
            await Ajax.call([{methodname: 'local_extendednav_set_plugin_enabled', args: {value: on}}])[0];
            plugin.checked = on;
            paintPlugin(page, on);
            toast(t.saved);
        } catch (error) {
            plugin.checked = !on;
            throw error;
        } finally {
            plugin.disabled = false;
        }
    };

    plugin.addEventListener('change', () => {
        const on = plugin.checked;
        if (on) {
            savePlugin(true).catch((error) => toast(describeError(error), 'danger'));
            return;
        }
        // Off, the pages the nodes block are open to everyone: the switch stays on until that is confirmed.
        plugin.checked = true;
        confirmAction({
            title: t.status_confirm_title,
            text: t.status_confirm,
            button: t.status_confirm_yes,
            tone: 'danger',
            icon: 'i/warning',
            trigger: plugin,
            action: () => savePlugin(false),
        });
    });

    page.querySelectorAll('[data-action="togglenavigation"]').forEach((input) => {
        input.addEventListener('change', async() => {
            const on = input.checked;
            input.disabled = true;
            try {
                await Ajax.call([{methodname: 'local_extendednav_set_enabled', args: {menu: input.dataset.key, value: on}}])[0];
                toast(t.saved);
                countGroups(page);
            } catch (error) {
                input.checked = !on;
                toast(describeError(error), 'danger');
            } finally {
                input.disabled = page.dataset.pluginenabled !== '1';
            }
        });
    });
};
