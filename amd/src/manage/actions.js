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
 * The actions on the nodes of the management page: show, hide and delete, on one row or on the rows selected.
 *
 * The bar of the selection offers an action only when one of the rows selected allows it (each row says whether it can be shown,
 * hidden or deleted). Hiding asks whether to hide from administrators too; an action on the selection says which rows it leaves
 * as they are. Each action is one call to a web service, whatever the number of rows, and the list is drawn again in place.
 *
 * @module     local_extendednav/manage/actions
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Modal from 'core/modal';
import Templates from 'core/templates';
import {confirmAction} from 'local_extendednav/local/action_dialog';
import {describeError, toast} from 'local_extendednav/local/ui';

/** What each action needs of a row, and how it looks. */
const ACTIONS = {
    // Moodle names its eyes by what they do: t/hide is the open eye, t/show the crossed one.
    show: {allowed: 'canshow', icon: 't/hide', tone: 'primary'},
    hide: {allowed: 'canhide', icon: 't/show', tone: 'secondary'},
    'delete': {allowed: 'candelete', icon: 't/delete', tone: 'danger'},
};

/**
 * Wires the actions.
 *
 * @param {Object} options What the actions work on.
 * @param {HTMLElement} options.root The list.
 * @param {List} options.list The list of nodes.
 * @param {Object} options.config The configuration of the page: menu, contextid and tree.
 * @param {Object} options.t The strings of the page.
 * @returns {{paintBar: Function}} Draws the bar of the selection for the rows selected.
 */
export const init = ({root, list, config, t}) => {
    const scope = {menu: config.menu, contextid: config.contextid};
    const call = (methodname, args) => Ajax.call([{methodname, args: {...scope, ...args}}])[0];
    const reset = Boolean(config.contextid);

    /**
     * Runs an action on nodes, draws the list again and says how many changed.
     *
     * @param {string} name show, hide or delete.
     * @param {string[]} nodekeys The nodes.
     * @param {boolean} hideadmins Whether hiding reaches administrators too.
     * @returns {Promise<void>}
     */
    const run = async(name, nodekeys, hideadmins) => {
        if (name === 'delete') {
            await call('local_extendednav_delete_nodes', {nodekeys});
        } else {
            await call('local_extendednav_set_visibility', {
                nodekeys,
                visibility: name === 'show' ? 1 : 0,
                hideadmins: name === 'hide' ? Number(hideadmins) : -1,
            });
        }
        await list.load();
        list.clearSelection();
        toast(t.nodes_changed.replace('{$a}', nodekeys.length));
    };

    /**
     * Asks before an action on some rows, saying which of them it leaves as they are.
     *
     * @param {string} name show, hide or delete.
     * @param {Object[]} rows The rows chosen.
     * @param {HTMLElement} trigger The button pressed.
     */
    const ask = (name, rows, trigger) => {
        const {allowed, icon, tone} = ACTIONS[name];
        const reached = rows.filter((row) => row[allowed]);
        const subject = reached.length === 1 ? reached[0].title : t.nodes_count_label.replace('{$a}', reached.length);
        const texts = {
            show: [t.show_nodes_title, t.show_nodes_text, t.show],
            hide: [t.hide_nodes_title, t.hide_nodes_text, t.hide],
            'delete': reset ? [t.reset_nodes_title, t.reset_nodes_text, t.reset]
                : [t.delete_nodes_title, t.delete_nodes_text, t.delete],
        }[name];
        confirmAction({
            title: texts[0].replace('{$a}', subject),
            text: texts[1],
            button: texts[2],
            icon,
            tone,
            trigger,
            // In the site administration a node is hidden from administrators too, unless they choose otherwise.
            option: name === 'hide'
                ? {label: t.hide_nodes_admins, help: t.hide_nodes_admins_help, checked: Boolean(config.tree)}
                : null,
            skipped: {
                text: t.skipped_nodes,
                more: t.skipped_more,
                names: rows.filter((row) => !row[allowed]).map((row) => row.title),
            },
            action: (hideadmins) => run(name, reached.map((row) => row.nodekey), hideadmins),
        });
    };

    /**
     * Opens the dialog that explains who sees a node: each badge of its visibility with its restriction in a sentence.
     *
     * @param {Object} row The row of the node.
     */
    const explain = async(row) => {
        const items = [{label: row.visibilitylabel, tone: row.tone, detail: row.visibilitydetail},
            ...row.flags.map((flag) => ({label: flag.label, tone: 'light', detail: flag.detail}))];
        const footer = Object.assign(document.createElement('a'), {className: 'btn btn-primary', href: row.editurl,
            textContent: t.edit_node});
        const modal = await Modal.create({
            title: t.restrictions_title.replace('{$a}', row.title),
            body: Templates.render('local_extendednav/dialog/restrictions', {items}),
            footer: footer.outerHTML,
            removeOnClose: true,
        });
        modal.show();
    };

    root.addEventListener('click', (event) => {
        const button = event.target.closest('[data-action]');
        const action = button?.dataset.action;
        if (action === 'restrictions') {
            const row = list.rows.find((item) => item.nodekey === button.dataset.key);
            if (row) {
                explain(row).catch((error) => toast(describeError(error), 'danger'));
            }
        } else if (action === 'show') {
            // Showing a node only takes it back: it asks nothing.
            run('show', [button.dataset.key]).catch((error) => toast(describeError(error), 'danger'));
        } else if (action === 'hide' || action === 'delete') {
            ask(action, list.rows.filter((row) => row.nodekey === button.dataset.key), button);
        } else if (['bulkshow', 'bulkhide', 'bulkdelete'].includes(action)) {
            ask(action.slice(4), list.selectedRows(), button);
        }
    });

    return {
        /**
         * Offers in the bar of the selection only the actions that some row selected allows.
         *
         * @param {Object[]} rows The rows selected.
         */
        paintBar: (rows) => {
            Object.entries(ACTIONS).forEach(([name, {allowed}]) => {
                const button = root.querySelector(`[data-region="floatbar"] [data-action="bulk${name}"]`);
                if (button) {
                    button.hidden = !rows.some((row) => row[allowed]);
                }
            });
            const label = root.querySelector('[data-region="deletelabel"]');
            if (label) {
                label.textContent = reset ? t.reset : t.delete;
            }
        },
    };
};
