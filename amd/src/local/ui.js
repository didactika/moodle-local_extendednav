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
 * What the screens of the plugin share: a debounce, toasts, the confirmation of a destructive action and the Moodle forms
 * that create and edit delegations.
 *
 * @module     local_extendednav/local/ui
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalForm from 'core_form/modalform';
import ModalSaveCancel from 'core/modal_save_cancel';
import ModalEvents from 'core/modal_events';
import Templates from 'core/templates';
import {add as addToast} from 'core/toast';
import {getString} from 'core/str';
import {normalise} from 'local_extendednav/form_layout';
import {init as initSearchClear} from 'local_extendednav/local/search_clear';

// Every page of the plugin loads this module, so its search fields get the button that empties them.
initSearchClear();

/**
 * Calls a function once the calls to the returned one stop for a while.
 *
 * @param {Function} callback Function to call.
 * @param {number} wait Milliseconds without calls.
 * @returns {Function} The debounced function.
 */
export const debounce = (callback, wait) => {
    let timer = null;
    return (...args) => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => callback(...args), wait);
    };
};

/**
 * Shows the result of an action in a toast.
 *
 * @param {string} message What happened.
 * @param {string} type success, info, warning or danger.
 * @returns {Promise<void>}
 */
export const toast = async(message, type = 'success') => {
    await addToast(message, {type, closeButton: true});
};

/**
 * What a failed call says.
 *
 * @param {*} error The error of the call.
 * @returns {string} Its message.
 */
export const describeError = (error) => {
    if (error && typeof error === 'object') {
        return error.message || error.error || String(error);
    }
    return String(error);
};

/**
 * Asks before a destructive action, with the focus on Cancel and the action named in the danger button.
 *
 * @param {Object} options The question.
 * @param {string} options.title Question with the quantity.
 * @param {string} options.text What is lost and what is kept.
 * @param {string} options.button Name of the action.
 * @param {HTMLElement} options.trigger Element that gets the focus back.
 * @param {Function} options.action Does the action; a rejection keeps the dialog open with the error.
 * @returns {Promise<void>}
 */
export const confirmDanger = async({title, text, button, trigger, action}) => {
    const body = await Templates.render('local_extendednav/dialog/confirm', {text});
    const modal = await ModalSaveCancel.create({
        title,
        body,
        buttons: {save: button},
        removeOnClose: true,
        returnElement: trigger,
    });
    const root = modal.getRoot()[0];
    const save = root.querySelector('[data-action="save"]');
    save.classList.remove('btn-primary');
    save.classList.add('btn-danger');
    modal.getRoot().on(ModalEvents.save, async(event) => {
        event.preventDefault();
        save.disabled = true;
        root.querySelector('[data-region="confirm-error"]').hidden = true;
        try {
            await action();
            modal.destroy();
        } catch (error) {
            const alert = root.querySelector('[data-region="confirm-error"]');
            alert.textContent = describeError(error);
            alert.hidden = false;
            save.disabled = false;
        }
    });
    modal.getRoot().on(ModalEvents.shown, () => root.querySelector('[data-action="cancel"]')?.focus());
    modal.show();
};

/**
 * Opens one of the Moodle forms of the plugin in a dialog.
 *
 * @param {Object} options The form.
 * @param {string} options.formClass Class of the dynamic form.
 * @param {Object} options.args Arguments of the form.
 * @param {string} options.title Title of the dialog, the same words as the button that opens it.
 * @param {HTMLElement} options.trigger Element that gets the focus back.
 * @param {Function} options.onSubmitted Called with the result of the form.
 */
export const openForm = ({formClass, args, title, trigger, onSubmitted}) => {
    const modalForm = new ModalForm({
        modalConfig: {title, large: true},
        formClass,
        args,
        saveButtonText: getString('savechanges'),
        returnFocus: trigger,
    });
    modalForm.addEventListener(modalForm.events.LOADED, () => {
        const root = modalForm.modal.getRoot()[0];
        normalise(root);
    });
    modalForm.addEventListener(modalForm.events.FORM_SUBMITTED, (event) => onSubmitted(event.detail || {}));
    modalForm.show();
};

/** The sizes a page of a list may have, 0 being every row at once. */
export const PAGE_SIZES = [12, 24, 48, 96, 0];

/** The size a list starts with. */
export const DEFAULT_PAGE_SIZE = 12;

/**
 * Brings a size read from the address to one the pager offers.
 *
 * @param {number} perpage The size asked for.
 * @param {number} fallback The size to use when it is not one of them.
 * @returns {number}
 */
export const pageSize = (perpage, fallback = DEFAULT_PAGE_SIZE) => (PAGE_SIZES.includes(perpage) ? perpage : fallback);

/**
 * Which rows a page shows: the first and the last, counted from 1, and the last page there is.
 *
 * @param {number} page The page, from 0.
 * @param {number} perpage Rows per page, 0 for all of them.
 * @param {number} total How many rows there are.
 * @returns {{from: number, to: number, lastpage: number, start: number, end: number}}
 */
export const pageRange = (page, perpage, total) => {
    const size = perpage > 0 ? perpage : Math.max(total, 1);
    const lastpage = Math.max(0, Math.ceil(total / size) - 1);
    const start = Math.min(page, lastpage) * size;
    const end = Math.min(total, start + size);
    return {from: total ? start + 1 : 0, to: end, lastpage, start, end};
};

/**
 * Fits the sizes of a pager to a list: only the sizes smaller than the list are offered, besides all of it, and the select
 * always shows one of them, All when every row fits on the page.
 *
 * @param {HTMLSelectElement|null} select The select of the rows per page.
 * @param {number} perpage Rows per page, 0 for all of them.
 * @param {number} total How many rows there are.
 */
export const fitPageSizes = (select, perpage, total) => {
    if (!select) {
        return;
    }
    Array.from(select.options).forEach((option) => {
        const size = Number(option.value);
        option.hidden = size > 0 && size >= total;
    });
    select.value = perpage > 0 && perpage < total ? String(perpage) : '0';
};
