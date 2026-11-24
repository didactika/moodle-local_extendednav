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
 * The management page: the list of the nodes of a navigation, the levels of the site administration, the scope of the changes,
 * the button that turns the navigation on or off and the menu of the exports. Every action changes the page in place through a
 * web service; nothing reloads it but the choice of another navigation or scope.
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
 * @param {string|number} name What goes in it.
 * @returns {string} The text.
 */
const named = (text, name) => text.replace('{$a}', name);

/**
 * Wires a menu of the page that opens under its button: it closes with a click outside it or with Escape.
 *
 * @param {HTMLElement} menu Element with data-menu.
 * @param {Function} [onOpen] Called when it opens.
 * @returns {Function} Opens or closes it.
 */
const popupMenu = (menu, onOpen) => {
    const popup = menu.querySelector('.dropdown-menu');
    const toggle = menu.querySelector('[data-menu-toggle]');
    const open = (show) => {
        popup.hidden = !show;
        toggle.setAttribute('aria-expanded', show ? 'true' : 'false');
        if (show && onOpen) {
            onOpen();
        }
    };
    toggle.addEventListener('click', () => open(popup.hidden));
    document.addEventListener('pointerdown', (event) => {
        if (!menu.contains(event.target)) {
            open(false);
        }
    });
    // Bootstrap 5 listens to the keys of every dropdown-menu in the capture phase, so Escape is handled before it.
    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !popup.hidden) {
            event.stopPropagation();
            open(false);
            toggle.focus();
        }
    }, true);
    return open;
};

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
    const search = menu.querySelector('[data-region="scopesearch"]');
    const list = menu.querySelector('[data-region="scopelist"]');
    const go = (contextid) => {
        const url = new URL(window.location.href);
        url.searchParams.delete('page');
        url.searchParams.delete('parent');
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
    const load = async() => {
        const {scopes} = await call('local_extendednav_get_scopes', {menu: config.menu, search: search.value.trim()});
        const items = [option(config.strings.wholesite, 0)];
        scopes.forEach((scope) => {
            const hint = scope.exceptions ? named(config.strings.exceptions, scope.exceptions) : '';
            items.push(option(scope.name, scope.contextid, hint));
        });
        if (scopes.length === 0 && search.value.trim()) {
            const empty = document.createElement('p');
            empty.className = 'text-muted small px-3 py-2 mb-0';
            empty.textContent = config.strings.noscopes;
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

    const keys = ['drag_node_named', 'navigation_on_help', 'navigation_off_help', 'edit_node_named', 'delete_node_named',
        'select_node_named', 'show_node_named', 'hide_node_named', 'delete_nodes_title', 'delete_nodes_text', 'delete',
        'reset_nodes_title', 'reset_nodes_text', 'reset', 'empty_nodes_title', 'empty_nodes_text', 'empty_level_title',
        'empty_level_text', 'nodes_changed', 'saved', 'applies_whole_site', 'exceptions_count', 'no_scopes',
        'open_level_named', 'open_level', 'tree_top'];
    const texts = await getStrings(keys.map((key) => ({key, component: key === 'delete' ? 'moodle' : 'local_extendednav'})));
    const t = Object.fromEntries(keys.map((key, index) => [key, texts[index]]));
    config.strings = {wholesite: t.applies_whole_site, exceptions: t.exceptions_count, noscopes: t.no_scopes};

    const editUrl = (row) => {
        const url = new URL(`${M.cfg.wwwroot}/local/extendednav/edit.php`);
        url.searchParams.set('menu', config.menu);
        if (config.contextid) {
            url.searchParams.set('contextid', config.contextid);
        }
        if (!config.contextid || row.exception) {
            url.searchParams.set('id', row.id);
        } else {
            url.searchParams.set('nodekey', row.nodekey);
        }
        return url.toString();
    };

    /**
     * Draws the breadcrumb of the level of the tree listed.
     *
     * @param {Array} path The categories from the top down.
     * @param {Object} instance The list.
     */
    const renderPath = (path, instance) => {
        const nav = root.querySelector('[data-region="path"]');
        if (!nav) {
            return;
        }
        nav.hidden = !config.tree || Boolean(instance.state.search);
        const crumbs = [{key: '', title: t.tree_top}, ...path];
        const items = crumbs.map((crumb, index) => {
            const item = document.createElement('li');
            item.className = 'breadcrumb-item';
            if (index === crumbs.length - 1) {
                item.classList.add('active');
                item.setAttribute('aria-current', 'page');
                item.textContent = crumb.title;
            } else {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'btn btn-link p-0 align-baseline';
                button.dataset.action = 'openlevel';
                button.dataset.key = crumb.key;
                button.textContent = crumb.title;
                item.append(button);
            }
            return item;
        });
        nav.querySelector('ol').replaceChildren(...items);
    };

    const list = new List(root, {
        method: 'local_extendednav_get_nodes',
        template: 'local_extendednav/list/nodes_table',
        defaults: {search: '', status: '', parent: '', page: 0, perpage: 12},
        args: (state) => ({
            menu: config.menu,
            contextid: config.contextid,
            search: state.search,
            visibility: state.status,
            parent: config.tree ? state.parent : '',
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
                draglabel: named(t.drag_node_named, row.title),
                editlabel: named(t.edit_node_named, row.title),
                deletelabel: named(t.delete_node_named, row.title),
                selectlabel: named(t.select_node_named, row.title),
                showlabel: named(t.show_node_named, row.title),
                hidelabel: named(t.hide_node_named, row.title),
                openlabel: named(t.open_level_named, row.title),
                childrenlabel: t.open_level,
            })),
        }),
        empty: (filtered, state) => (config.tree && state.parent
            ? {icon: 'i/folder', title: t.empty_level_title, text: t.empty_level_text}
            : {icon: 't/hide', title: t.empty_nodes_title, text: t.empty_nodes_text}),
        isFiltered: (state) => Boolean(state.search) || Boolean(state.status),
        cleared: {status: ''},
        onRender: (response, instance) => {
            const label = page.querySelector('[data-region="deletelabel"]');
            if (label) {
                label.textContent = config.contextid ? t.reset : t.delete;
            }
            renderPath(response.path || [], instance);
            enableDragging(instance.region('tablewrap'));
        },
    });

    /**
     * Runs a change, draws the list again and says how many nodes it changed.
     *
     * @param {Function} task Returns the promise of the change.
     * @param {number} count Nodes changed.
     * @returns {Promise<void>}
     */
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

    /**
     * Places a node before or after a sibling, and draws the list again.
     *
     * @param {string} nodekey The node that moves.
     * @param {string} beforekey The sibling it goes before, or empty.
     * @param {string} afterkey The sibling it goes after, or empty.
     * @returns {Promise<void>}
     */
    const place = (nodekey, beforekey, afterkey) => apply(() => call('local_extendednav_place_node', {
        menu: config.menu,
        contextid: config.contextid,
        nodekey,
        beforekey,
        afterkey,
    }));

    /**
     * Lets the rows of the table be dragged by their handle among their siblings. On the handle, the arrow keys move the row
     * one place, so the keyboard does the same as the mouse.
     *
     * @param {HTMLElement} wrap The box of the table.
     */
    const enableDragging = (wrap) => {
        const body = wrap?.querySelector('tbody');
        if (!body) {
            return;
        }
        let dragged = null;
        const mark = (row, where) => {
            if (where) {
                row.dataset.drop = where;
            } else {
                delete row.dataset.drop;
            }
        };
        const clear = () => body.querySelectorAll('[data-drop]').forEach((row) => mark(row, ''));
        const accepts = (row) => dragged && dragged !== row && dragged.dataset.parent === row.dataset.parent;
        Array.from(body.rows).forEach((row) => {
            const handle = row.querySelector('[data-region="handle"]');
            if (!handle) {
                return;
            }
            handle.addEventListener('pointerdown', () => {
                row.draggable = true;
            });
            handle.addEventListener('keydown', (event) => {
                if (event.key !== 'ArrowUp' && event.key !== 'ArrowDown') {
                    return;
                }
                event.preventDefault();
                apply(() => call('local_extendednav_move_node', {
                    menu: config.menu,
                    contextid: config.contextid,
                    nodekey: row.dataset.key,
                    direction: event.key === 'ArrowUp' ? -1 : 1,
                })).then(() => root.querySelector(`tr[data-key="${row.dataset.key}"] [data-region="handle"]`)?.focus());
            });
            row.addEventListener('dragstart', (event) => {
                dragged = row;
                event.dataTransfer.effectAllowed = 'move';
                event.dataTransfer.setData('text/plain', row.dataset.key);
                row.classList.add('opacity-50');
            });
            row.addEventListener('dragend', () => {
                row.draggable = false;
                row.classList.remove('opacity-50');
                dragged = null;
                clear();
            });
            row.addEventListener('dragover', (event) => {
                if (!accepts(row)) {
                    return;
                }
                event.preventDefault();
                const box = row.getBoundingClientRect();
                clear();
                mark(row, event.clientY > box.top + box.height / 2 ? 'after' : 'before');
            });
            row.addEventListener('drop', (event) => {
                if (!accepts(row)) {
                    return;
                }
                event.preventDefault();
                const after = row.dataset.drop === 'after';
                const key = dragged.dataset.key;
                clear();
                place(key, after ? '' : row.dataset.key, after ? row.dataset.key : '');
            });
        });
    };
    const chosenKeys = () => list.rows.filter((row) => list.selected.has(row.id)).map((row) => row.nodekey);

    root.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action]');
        const action = button?.dataset.action;
        if (action === 'openlevel') {
            list.clearSelection();
            list.update({parent: button.dataset.key, search: '', page: 0});
            const search = list.region('search');
            if (search) {
                search.value = '';
            }
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

    // The button next to the name turns the changes of this navigation on or off.
    const power = page.querySelector('[data-action="toggleenabled"]');
    const showPower = (on) => {
        power.setAttribute('aria-pressed', String(on));
        power.classList.toggle('btn-success', on);
        power.classList.toggle('btn-outline-secondary', !on);
        power.querySelector('[data-state="on"]').hidden = !on;
        power.querySelector('[data-state="off"]').hidden = on;
        power.title = on ? t.navigation_on_help : t.navigation_off_help;
    };
    if (power) {
        showPower(power.getAttribute('aria-pressed') === 'true');
        power.addEventListener('click', async() => {
            const on = power.getAttribute('aria-pressed') !== 'true';
            power.disabled = true;
            try {
                await call('local_extendednav_set_enabled', {menu: config.menu, value: on});
                showPower(on);
                toast(t.saved);
            } catch (error) {
                toast(describeError(error), 'danger');
            } finally {
                power.disabled = false;
            }
        });
    }

    const exportmenu = page.querySelector('[data-menu="export"]');
    if (exportmenu) {
        popupMenu(exportmenu);
    }
    initScope(page, config);

    await list.init();
    return list;
};
