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
 * Management page: selection, bulk actions, delete confirmation and drag-and-drop ordering.
 *
 * @module     local_extendednav/manage
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import Ajax from 'core/ajax';
import Notification from 'core/notification';
import SortableList from 'core/sortable_list';
import {getString} from 'core/str';

/**
 * Shows the bulk actions bar while nodes are selected.
 *
 * @param {HTMLInputElement[]} checkboxes Row checkboxes.
 * @param {HTMLInputElement|null} selectAll The "select all" checkbox.
 */
const updateBulkBar = (checkboxes, selectAll) => {
    const bar = document.getElementById('bulk-actions-bar');
    const count = document.getElementById('bulk-count');
    const selected = checkboxes.filter((checkbox) => checkbox.checked).length;
    if (selectAll) {
        selectAll.checked = checkboxes.length > 0 && selected === checkboxes.length;
    }
    if (bar && count) {
        count.textContent = selected;
        bar.classList.toggle('d-none', selected === 0);
    }
};

/**
 * Asks for confirmation before deleting.
 *
 * @param {String} questionKey String identifier of the question.
 * @param {HTMLElement} trigger Element that opened the dialogue.
 * @returns {Promise}
 */
const confirmDelete = async(questionKey, trigger) => {
    const [title, question, label] = await Promise.all([
        getString('delete', 'core'),
        getString(questionKey, 'local_extendednav'),
        getString('delete', 'core'),
    ]);
    return Notification.deleteCancelPromise(title, question, label, {triggerElement: trigger});
};

/**
 * Returns the name of a row for the move dialogue: its title, or its node key.
 *
 * @param {HTMLElement} row Table row.
 * @returns {String}
 */
const getRowName = (row) => {
    const title = row.querySelector('.local-extendednav-col-title a');
    const key = row.querySelector('code');
    return (title && title.textContent.trim()) || (key && key.textContent.trim()) || '';
};

/**
 * Returns the node key and parent key of a row.
 *
 * @param {HTMLElement|null} row Table row.
 * @returns {Object} With key and parent (empty for a top-level node).
 */
const getRowInfo = (row) => {
    const checkbox = row ? row.querySelector('input.node-checkbox') : null;
    return {
        key: checkbox ? checkbox.dataset.nodekey : '',
        parent: checkbox ? checkbox.dataset.parentkey : '',
    };
};

/**
 * Whether a row is a child of another row of the table.
 *
 * @param {HTMLElement} row Table row.
 * @returns {Boolean}
 */
const isChild = (row) => getRowInfo(row).parent !== '';

/**
 * Returns the rows of the table.
 *
 * @param {HTMLElement} tbody Table body.
 * @returns {HTMLElement[]}
 */
const getRows = (tbody) => Array.from(tbody.children).filter((row) => row.querySelector('input.node-checkbox'));

/**
 * Returns the child rows of a node.
 *
 * @param {HTMLElement} tbody Table body.
 * @param {String} key Key of the parent node.
 * @returns {HTMLElement[]}
 */
const getChildren = (tbody, key) => getRows(tbody).filter((row) => getRowInfo(row).parent === key);

/**
 * Returns the first top-level row after a row, skipping child rows.
 *
 * @param {HTMLElement} row Table row.
 * @returns {HTMLElement|null} The row, or null at the end of the table.
 */
const getNextTopLevel = (row) => {
    let next = row.nextElementSibling;
    while (next && isChild(next)) {
        next = next.nextElementSibling;
    }
    return next;
};

/**
 * Works out where a dragged row may go when core proposes to put it before another row.
 *
 * A top-level row moves with its children, so it only goes between groups: inside a group it
 * moves to the end of that group. A child row stays among its siblings.
 *
 * @param {HTMLElement} tbody Table body.
 * @param {HTMLElement} dragged Dragged row.
 * @param {HTMLElement|null} before Row core proposes to put it before, or null for the end.
 * @returns {HTMLElement|null|undefined} The row to put it before, null for the end, or undefined when not allowed.
 */
const getAllowedBefore = (tbody, dragged, before) => {
    const info = getRowInfo(dragged);
    if (!info.parent) {
        if (!before || !isChild(before) || getRowInfo(before).parent === info.key) {
            // Its own children are hidden while it is dragged and follow it when it is dropped.
            return before;
        }
        return getNextTopLevel(before);
    }

    const parentRow = getRows(tbody).find((row) => getRowInfo(row).key === info.parent);
    if (!parentRow || before === parentRow) {
        return undefined;
    }
    if (before && getRowInfo(before).parent === info.parent) {
        return before;
    }
    return before === getNextTopLevel(parentRow) ? before : undefined;
};

/** @var {Number} Duration of the fade of child rows, as in styles.css. */
const FADE_MS = 150;

/**
 * Fades rows out, then hides them.
 *
 * @param {HTMLElement[]} rows Table rows.
 */
const fadeOut = (rows) => {
    rows.forEach((row) => row.classList.add('local-extendednav-fading'));
    setTimeout(() => {
        rows.filter((row) => row.classList.contains('local-extendednav-fading'))
            .forEach((row) => row.classList.add('d-none'));
    }, FADE_MS);
};

/**
 * Shows rows hidden by {@link fadeOut} and fades them in.
 *
 * @param {HTMLElement[]} rows Table rows.
 */
const fadeIn = (rows) => {
    rows.forEach((row) => row.classList.remove('d-none'));
    // Apply the hidden state before removing it, so the opacity change is animated.
    if (rows.length) {
        rows[0].getBoundingClientRect();
    }
    requestAnimationFrame(() => rows.forEach((row) => row.classList.remove('local-extendednav-fading')));
};

/**
 * Builds the dragged copy of rows as a small table.
 *
 * The copy is positioned "fixed" outside the table layout, so every cell gets the width of the
 * cell it copies.
 *
 * @param {HTMLElement} table The table of nodes.
 * @param {HTMLElement[]} rows Rows to copy.
 * @returns {HTMLElement}
 */
const buildProxy = (table, rows) => {
    const widths = Array.from(rows[0].children).map((cell) => cell.getBoundingClientRect().width);
    const proxy = document.createElement('table');
    proxy.className = table.className + ' local-extendednav-drag-proxy';
    proxy.style.width = rows[0].getBoundingClientRect().width + 'px';
    proxy.style.tableLayout = 'fixed';
    const body = document.createElement('tbody');
    rows.forEach((row) => {
        const copy = row.cloneNode(true);
        copy.classList.remove('d-none', 'sortable-list-current-position', 'sortable-list-is-dragged');
        copy.querySelectorAll('[id]').forEach((element) => element.removeAttribute('id'));
        copy.querySelectorAll('[name]').forEach((element) => element.removeAttribute('name'));
        Array.from(copy.children).forEach((cell, index) => {
            cell.style.width = widths[index] + 'px';
        });
        body.appendChild(copy);
    });
    proxy.appendChild(body);
    return proxy;
};

/**
 * Enables drag-and-drop ordering of the rows, keeping children with their parent.
 *
 * @param {String} menu Menu key.
 */
const initSortable = (menu) => {
    const tbody = document.querySelector('#local-extendednav-manage tbody');
    if (!tbody || !tbody.querySelector('[data-drag-type="move"]')) {
        return;
    }
    const table = tbody.closest('table');
    const list = new SortableList(tbody, {moveHandlerSelector: '[data-drag-type="move"]'});
    // Children of the dragged parent, which follow it when it is dropped.
    let children = [];
    // The same children while they are hidden, during a drag with the mouse.
    let hiddenChildren = [];

    list.getElementName = (element) => $.Deferred().resolve(getRowName(element[0]));

    // Only move to positions allowed for the dragged row.
    const moveElement = list.moveElement.bind(list);
    list.moveElement = (parentElement, beforeElement) => {
        const before = getAllowedBefore(tbody, list.info.element[0], beforeElement[0] || null);
        if (before !== undefined) {
            moveElement(parentElement, before ? $(before) : $());
        }
    };

    // The move dialogue only offers the allowed positions, named after groups for a parent.
    const getDestinationsList = list.getDestinationsList.bind(list);
    list.getDestinationsList = () => getDestinationsList().then((destinations) => {
        const dragged = list.info.element[0];
        const draggedInfo = getRowInfo(dragged);
        const sameplace = draggedInfo.parent ? null : getNextTopLevel(dragged);
        destinations.find('[data-core_sortable_list-quickmove]').each((index, link) => {
            const before = $(link).data('before-element')[0] || null;
            let allowed = getAllowedBefore(tbody, dragged, before) === before;
            if (!draggedInfo.parent) {
                allowed = allowed && (!before || !isChild(before)) && !(sameplace && before === sameplace);
            }
            if (!allowed) {
                $(link).closest('li').remove();
            }
        });
        return destinations;
    });
    const getDestinationName = list.getDestinationName.bind(list);
    list.getDestinationName = (parentElement, afterElement) => {
        const after = afterElement[0];
        if (after && !isChild(list.info.element[0]) && isChild(after)) {
            const parentRow = getRows(tbody).find((row) => getRowInfo(row).key === getRowInfo(after).parent);
            if (parentRow) {
                return getDestinationName(parentElement, $(parentRow));
            }
        }
        return getDestinationName(parentElement, afterElement);
    };

    // The dragged copy shows the row only, sized like the table.
    const createProxy = list.createProxy.bind(list);
    list.createProxy = () => {
        createProxy();
        const proxy = buildProxy(table, [list.info.element[0]]);
        table.parentNode.appendChild(proxy);
        list.proxy.remove();
        list.proxy = $(proxy).addClass('sortable-list-is-dragged').css({position: 'fixed', left: 0, top: 0, margin: 0});
        list.proxy.offset({
            top: list.proxyDelta.y + list.lastEvent.pageY,
            left: list.proxyDelta.x + list.lastEvent.pageX,
        });
        // The drag really starts now (a click only opens the move dialogue): hide the children.
        hiddenChildren = children;
        fadeOut(hiddenChildren);
    };

    // A parent is moved with its children: they are hidden while it is dragged and put back after it.
    $(tbody).on(SortableList.EVENTS.DRAGSTART, 'tr', (event, info) => {
        const draggedInfo = getRowInfo(info.element[0]);
        children = draggedInfo.parent ? [] : getChildren(tbody, draggedInfo.key);
    });
    $(tbody).on(SortableList.EVENTS.DRAGEND, 'tr', () => {
        fadeIn(hiddenChildren);
        hiddenChildren = [];
        children = [];
    });

    $(tbody).on(SortableList.EVENTS.DROP, 'tr', (event, info) => {
        let previous = info.element[0];
        children.forEach((row) => {
            previous.after(row);
            previous = row;
        });
        if (!info.positionChanged) {
            return;
        }
        // The table already shows the new order; only save it.
        const ids = getRows(tbody).map((row) => parseInt(row.querySelector('input.node-checkbox').value, 10));
        Ajax.call([{methodname: 'local_extendednav_reorder_nodes', args: {menu, ids}}])[0]
            .catch(Notification.exception);
    });

    // Let the highlight of a dropped row play again on the next drop.
    tbody.addEventListener('animationend', (event) => {
        event.target.classList.remove('sortable-list-is-dropped');
    });
};

/**
 * Sets up the page.
 *
 * @param {Object} config Configuration.
 * @param {String} config.menu Menu key.
 * @param {Boolean} config.sortable Whether rows can be reordered.
 */
export const init = ({menu, sortable}) => {
    const form = document.getElementById('bulk-form');
    const selectAll = document.getElementById('select-all-nodes');
    const checkboxes = Array.from(document.querySelectorAll('.node-checkbox'));

    selectAll?.addEventListener('change', () => {
        checkboxes.forEach((checkbox) => {
            checkbox.checked = selectAll.checked;
        });
        updateBulkBar(checkboxes, selectAll);
    });
    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', () => updateBulkBar(checkboxes, selectAll)));

    const bulkDelete = document.querySelector('[data-action="bulk-delete"]');
    bulkDelete?.addEventListener('click', (event) => {
        event.preventDefault();
        confirmDelete('bulk_delete_confirm', bulkDelete)
            .then(() => {
                form.submit();
                return null;
            })
            .catch(() => null);
    });

    document.querySelectorAll('[data-action="delete-node"]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            confirmDelete('delete_node_confirm', link)
                .then(() => {
                    window.location.href = link.href;
                    return null;
                })
                .catch(() => null);
        });
    });

    if (sortable) {
        initSortable(menu);
    }
};
