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
 * The management page. It wires the parts, each in its own module: the list of the nodes (local/list), their order by dragging
 * (manage/dragging), the actions on them (manage/actions), the breadcrumb of the site administration (manage/tree_path), the
 * button that turns the navigation on or off (manage/power) and the menu of the exports. Every action
 * changes the page in place through a web service; nothing reloads it but the choice of another navigation or scope.
 *
 * @module     local_extendednav/manage
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import {get_strings as getStrings} from 'core/str';
import List from 'local_extendednav/local/list';
import {describeError, toast} from 'local_extendednav/local/ui';
import {init as fitTables} from 'local_extendednav/local/fit_tables';
import {init as filterGroup} from 'local_extendednav/local/filter_group';
import {init as popupMenu} from 'local_extendednav/local/popup_menu';
import {init as initActions} from 'local_extendednav/manage/actions';
import {init as enableDragging} from 'local_extendednav/manage/dragging';
import {init as initPower} from 'local_extendednav/manage/power';
import {render as renderPath} from 'local_extendednav/manage/tree_path';

/** Strings of the page, all of local_extendednav but the ones of Moodle. */
const STRINGS = ['drag_node_named', 'drag_nodes_named', 'navigation_on_help', 'navigation_off_help', 'edit_node_named',
    'delete_node_named', 'select_node_named', 'show_node_named', 'hide_node_named', 'delete_nodes_title', 'delete_nodes_text',
    'reset_nodes_title', 'reset_nodes_text', 'reset', 'empty_nodes_title', 'empty_nodes_text', 'empty_level_title',
    'empty_level_text', 'nodes_changed', 'saved', 'open_level_named',
    'tree_top', 'hide_nodes_title', 'hide_nodes_text', 'hide_nodes_admins', 'hide_nodes_admins_help',
    'hide_nodes_block', 'hide_nodes_block_help', 'show_nodes_title',
    'show_nodes_text', 'skipped_nodes', 'skipped_more', 'nodes_count_label', 'nodes_found', 'restrictions_title', 'edit_node',
    'discover_done', 'discover_none'];
const CORE_STRINGS = ['delete', 'show', 'hide'];

/**
 * Loads the strings of the page.
 *
 * @returns {Promise<Object>} Each string by its key.
 */
const loadStrings = async() => {
    const keys = [...STRINGS, ...CORE_STRINGS];
    const component = (key) => (CORE_STRINGS.includes(key) ? 'moodle' : 'local_extendednav');
    const texts = await getStrings(keys.map((key) => ({key, component: component(key)})));
    return Object.fromEntries(keys.map((key, index) => [key, texts[index]]));
};

/**
 * The address of the form of a node, for the scope of the page.
 *
 * @param {Object} config The configuration of the page.
 * @param {Object} row The row of the node.
 * @returns {string}
 */
const editUrl = (config, row) => {
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
    const t = await loadStrings();
    const named = (text, name) => text.replace('{$a}', name);
    const scope = {menu: config.menu, contextid: config.contextid};
    const call = (methodname, args) => Ajax.call([{methodname, args: {...scope, ...args}}])[0];
    let actions = null;
    let group = null;

    const list = new List(root, {
        method: 'local_extendednav_get_nodes',
        template: 'local_extendednav/list/nodes_table',
        // The whole list is drawn, so it can be ordered: a navigation has tens of nodes, a level of the tree a few more.
        defaults: {search: '', status: '', origin: '', restriction: '', parent: '', page: 0, perpage: 0},
        args: (state) => ({
            menu: config.menu,
            contextid: config.contextid,
            search: state.search,
            visibility: state.status,
            origin: state.origin,
            restriction: state.restriction,
            parent: config.tree ? state.parent : '',
            page: 0,
            perpage: 0,
        }),
        context: (response) => ({
            rows: response.rows.map((row) => ({
                ...row,
                selectable: !row.virtual,
                visible: row.visibility === 1,
                canmove: response.reorderable && !row.virtual,
                hasdetails: row.details.length > 0,
                editurl: editUrl(config, row),
                draglabel: named(t.drag_node_named, row.title),
                editlabel: named(t.edit_node_named, row.title),
                deletelabel: named(t.delete_node_named, row.title),
                selectlabel: named(t.select_node_named, row.title),
                showlabel: named(t.show_node_named, row.title),
                hidelabel: named(t.hide_node_named, row.title),
                openlabel: named(t.open_level_named, row.title),
            })),
        }),
        empty: (filtered, state) => (config.tree && state.parent
            ? {icon: 'i/folder', title: t.empty_level_title, text: t.empty_level_text}
            : {icon: 't/hide', title: t.empty_nodes_title, text: t.empty_nodes_text, sampleurl: config.sampleurl}),
        onRender: (response, instance) => {
            group?.count(['status', 'origin', 'restriction'].filter((key) => instance.state[key]).length);
            // In the site administration, Add node starts in the category open.
            const add = page.querySelector('[data-action="addnode"]');
            if (add && config.tree) {
                const url = new URL(add.href);
                // General is a group of the list, not a category: what is added there goes to the top.
                url.searchParams.set('parent', instance.state.parent === 'admingeneral' ? '' : instance.state.parent || '');
                add.href = url.toString();
            }
            // The top of the folders is named as the navigation shown.
            renderPath(root.querySelector('[data-region="path"]'), response.path || [], config.treetop || t.tree_top,
                !config.tree || Boolean(instance.state.search || instance.state.status || instance.state.origin
                    || instance.state.restriction));
            enableDragging(instance.region('tablewrap'), {
                selectedKeys: () => instance.selectedRows().map((row) => row.nodekey),
                place: (nodekeys, beforekey, afterkey) => change(() => call('local_extendednav_place_node',
                    {nodekeys, beforekey, afterkey}), nodekeys.length),
                move: async(nodekey, direction) => {
                    await change(() => call('local_extendednav_move_node', {nodekey, direction}));
                    root.querySelector(`tr[data-key="${nodekey}"] [data-region="handle"]`)?.focus();
                },
            });
        },
        onSelection: (rows) => actions?.paintBar(rows),
    });

    /**
     * Runs a change of the order, draws the list again and says how many nodes moved.
     *
     * @param {Function} task Returns the promise of the change.
     * @param {number} count Nodes changed.
     * @returns {Promise<void>}
     */
    const change = async(task, count = 1) => {
        try {
            await task();
            await list.load();
            toast(named(t.nodes_changed, count));
        } catch (error) {
            toast(describeError(error), 'danger');
        }
    };

    /**
     * Finds the nodes of a navigation from a page that shows it: the page is loaded in the background, which records the
     * nodes it shows, and the list is drawn again.
     *
     * @param {HTMLButtonElement} button The button pressed, with the address of the page.
     * @returns {Promise<void>}
     */
    const findNodes = async(button) => {
        button.disabled = true;
        try {
            await fetch(button.dataset.url, {credentials: 'same-origin'});
            await list.load();
            toast(named(t.nodes_found, list.total));
        } catch (error) {
            toast(describeError(error), 'danger');
            button.disabled = false;
        }
    };

    /**
     * Looks at the activities of the kind of the navigation for the items of their navigation, and draws the list again.
     *
     * @param {HTMLButtonElement} button The button pressed.
     * @returns {Promise<void>}
     */
    const discover = async(button) => {
        button.disabled = true;
        try {
            const result = await Ajax.call([{methodname: 'local_extendednav_discover_nodes', args: {menu: config.menu}}])[0];
            await list.load();
            toast(result.activities ? t.discover_done.replace('{$a->found}', result.found)
                .replace('{$a->activities}', result.activities) : t.discover_none);
        } catch (error) {
            toast(describeError(error), 'danger');
        } finally {
            button.disabled = false;
        }
    };

    actions = initActions({root, list, config, t});
    // The button that looks for the items of a kind of activity is in the head of the page, above the list.
    page.querySelector('[data-action="discover"]')?.addEventListener('click', (event) => discover(event.currentTarget));
    root.addEventListener('click', (event) => {
        const sample = event.target.closest('[data-action="loadsample"]');
        if (sample) {
            findNodes(sample);
            return;
        }
        const button = event.target.closest('[data-action="openlevel"]');
        if (button) {
            list.clearSelection();
            const search = list.region('search');
            if (search) {
                search.value = '';
            }
            list.update({parent: button.dataset.key, search: '', page: 0});
        }
    });
    // Each select of the navigations (the one of a narrow screen, the one of the kinds of activity) opens the one chosen.
    page.addEventListener('change', (event) => {
        const select = event.target.closest('[data-region="originselect"]');
        if (select?.value) {
            window.location.assign(select.value);
        }
    });

    initPower(page, config.menu, {onhelp: t.navigation_on_help, offhelp: t.navigation_off_help, saved: t.saved});
    const exportmenu = page.querySelector('[data-menu="export"]');
    if (exportmenu) {
        popupMenu(exportmenu);
    }
    // The menus of the filters are grouped in one menu, Filters, when they do not fit beside the search.
    group = filterGroup(root.querySelector('[data-region="filters"]'));

    await list.init();
    return list;
};
