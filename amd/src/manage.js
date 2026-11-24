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
 * The management page: the list of the nodes of a navigation, the scope of the changes and the settings. Every action changes
 * the page in place through a web service; nothing reloads it but the choice of another navigation or scope.
 *
 * @module     local_extendednav/manage
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import {get_strings as getStrings} from 'core/str';
import List from 'local_extendednav/local/list';
import {confirmDanger, debounce, toast, describeError} from 'local_extendednav/local/ui';
import {init as fitTables} from 'local_extendednav/fit_tables';

const call = (methodname, args) => Ajax.call([{methodname, args}])[0];

/**
 * Fills the placeholder of a string with a name.
 *
 * @param {string} text String with {$a}.
 * @param {string} name What goes in it.
 * @returns {string} The text.
 */
const named = (text, name) => text.replace('{$a}', name);

/**
 * Wires the menu that chooses the scope of the changes.
 *
 * @param {HTMLElement} root The page.
 * @param {Object} config The page configuration.
 */
const initScope = (root, config) => {
    const menu = root.querySelector('[data-menu="scope"]');
    if (!menu) {
        return;
    }
    const popup = menu.querySelector('.dropdown-menu');
    const toggle = menu.querySelector('[data-menu-toggle]');
    const search = menu.querySelector('[data-region="scopesearch"]');
    const list = menu.querySelector('[data-region="scopelist"]');
    const [site, exceptions, nothing] = [config.strings.wholesite, config.strings.exceptions, config.strings.noscopes];
    const open = (show) => {
        popup.hidden = !show;
        toggle.setAttribute('aria-expanded', show ? 'true' : 'false');
        if (show) {
            search.focus();
            load();
        }
    };
    const go = (contextid) => {
        const url = new URL(window.location.href);
        url.searchParams.delete('page');
        if (contextid) {
            url.searchParams.set('contextid', contextid);
        } else {
            url.searchParams.delete('contextid');
        }
        window.location.assign(url);
    };
    const option = (label, contextid, hint = '') => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'dropdown-item d-flex align-items-center justify-content-between gap-3';
        button.setAttribute('role', 'option');
        button.setAttribute('aria-selected', String(contextid === config.contextid));
        const name = document.createElement('span');
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
    const load = async() => {
        const {scopes} = await call('local_extendednav_get_scopes', {menu: config.menu, search: search.value.trim()});
        const items = [option(site, 0)];
        scopes.forEach((scope) => {
            const hint = scope.exceptions ? named(exceptions, scope.exceptions) : '';
            items.push(option(scope.name, scope.contextid, hint));
        });
        if (scopes.length === 0 && search.value.trim()) {
            const empty = document.createElement('p');
            empty.className = 'text-muted small px-3 py-2 mb-0';
            empty.textContent = nothing;
            items.push(empty);
        }
        list.replaceChildren(...items);
    };
    toggle.addEventListener('click', () => open(popup.hidden));
    search.addEventListener('input', debounce(load, 300));
    document.addEventListener('pointerdown', (event) => {
        if (!menu.contains(event.target)) {
            open(false);
        }
    });
    menu.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !popup.hidden) {
            event.preventDefault();
            open(false);
            toggle.focus();
        }
    });
};

/**
 * Starts the page.
 *
 * @param {string} selector Selector of the element of the page.
 * @returns {Promise<List>}
 */
export const init = async(selector) => {
    fitTables();
    const page = document.querySelector(selector);
    const config = JSON.parse(page.dataset.config);
    const root = page.querySelector('[data-region="list"]');

    const keys = ['move_up', 'move_down', 'edit_node_named', 'delete_node_named', 'select_node_named', 'show_node_named',
        'hide_node_named', 'delete_nodes_title', 'delete_nodes_text', 'delete', 'reset_nodes_title', 'reset_nodes_text', 'reset',
        'empty_nodes_title', 'empty_nodes_text', 'nodes_changed', 'saved', 'applies_whole_site', 'exceptions_count', 'no_scopes'];
    const texts = await getStrings(keys.map((key) => ({key, component: key === 'delete' ? 'moodle' : 'local_extendednav'})));
    const t = Object.fromEntries(keys.map((key, index) => [key, texts[index]]));
    config.strings = {wholesite: t.applies_whole_site, exceptions: t.exceptions_count, noscopes: t.no_scopes};

    const editUrl = (row) => {
        const url = new URL(`${M.cfg.wwwroot}/local/extendednav/edit.php`);
        url.searchParams.set('menu', config.menu);
        if (config.contextid) {
            url.searchParams.set('contextid', config.contextid);
        }
        if (row && (!config.contextid || row.exception)) {
            url.searchParams.set('id', row.id);
        } else if (row) {
            url.searchParams.set('nodekey', row.nodekey);
        }
        return url.toString();
    };

    const list = new List(root, {
        method: 'local_extendednav_get_nodes',
        template: 'local_extendednav/list/nodes_table',
        defaults: {search: '', status: '', page: 0, perpage: 12},
        args: (state) => ({
            menu: config.menu,
            contextid: config.contextid,
            search: state.search,
            visibility: state.status,
            page: state.page,
            perpage: state.perpage,
        }),
        context: (response) => ({
            rows: response.rows.map((row) => ({
                ...row,
                selectable: true,
                visible: row.visibility === 1,
                canmove: response.reorderable,
                editurl: editUrl(row),
                moveup: t.move_up,
                movedown: t.move_down,
                editlabel: named(t.edit_node_named, row.title),
                deletelabel: named(t.delete_node_named, row.title),
                selectlabel: named(t.select_node_named, row.title),
                showlabel: named(t.show_node_named, row.title),
                hidelabel: named(t.hide_node_named, row.title),
            })),
        }),
        empty: () => ({icon: 't/hide', title: t.empty_nodes_title, text: t.empty_nodes_text}),
        isFiltered: (state) => Boolean(state.search) || Boolean(state.status),
        cleared: {status: ''},
        onRender: (response, instance) => {
            const label = page.querySelector('[data-region="deletelabel"]');
            if (label) {
                label.textContent = config.contextid ? t.reset : t.delete;
            }
            instance.region('floatbar')?.classList.toggle('position-fixed', false);
        },
    });

    const apply = async(task, count = 1) => {
        try {
            await task();
            await list.load();
            list.clearSelection();
            toast(named(t.nodes_changed, count));
        } catch (error) {
            toast(describeError(error), 'danger');
        }
    };
    const chosenKeys = () => list.rows.filter((row) => list.selected.has(row.id)).map((row) => row.nodekey);

    root.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action]');
        const action = button?.dataset.action;
        if (action === 'move') {
            apply(() => call('local_extendednav_move_node', {
                menu: config.menu,
                contextid: config.contextid,
                nodekey: button.dataset.key,
                direction: Number(button.dataset.direction),
            }));
        } else if (action === 'toggle') {
            apply(() => call('local_extendednav_set_visibility', {
                menu: config.menu,
                contextid: config.contextid,
                nodekeys: [button.dataset.key],
                visibility: Number(button.dataset.visibility),
            }));
        } else if (action === 'bulkshow' || action === 'bulkhide') {
            const nodekeys = chosenKeys();
            apply(() => call('local_extendednav_set_visibility', {
                menu: config.menu, contextid: config.contextid, nodekeys, visibility: action === 'bulkshow' ? 1 : 0,
            }), nodekeys.length);
        } else if (action === 'delete' || action === 'bulkdelete') {
            const nodekeys = action === 'delete' ? [button.dataset.key] : chosenKeys();
            const reset = Boolean(config.contextid);
            const subject = action === 'delete' ? button.dataset.title : nodekeys.length;
            confirmDanger({
                title: named(reset ? t.reset_nodes_title : t.delete_nodes_title, subject),
                text: reset ? t.reset_nodes_text : t.delete_nodes_text,
                button: reset ? t.reset : t.delete,
                trigger: button,
                action: async() => {
                    await call('local_extendednav_delete_nodes', {menu: config.menu, contextid: config.contextid, nodekeys});
                    await list.load();
                    list.clearSelection();
                    toast(named(t.nodes_changed, nodekeys.length));
                },
            });
        }
    });

    page.querySelector('[data-region="originselect"]')?.addEventListener('change', (event) => {
        window.location.assign(event.target.value);
    });
    page.querySelectorAll('[data-setting]').forEach((input) => {
        input.addEventListener('change', async() => {
            try {
                await call('local_extendednav_set_setting', {name: input.dataset.setting, value: input.checked});
                toast(t.saved);
            } catch (error) {
                input.checked = !input.checked;
                toast(describeError(error), 'danger');
            }
        });
    });
    initScope(page, config);

    await list.init();
    return list;
};
