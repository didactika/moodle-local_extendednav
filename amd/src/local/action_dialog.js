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
 * The question asked before an action on nodes, in a dialog of Moodle: what the action does, an option of it as a switch, and
 * the nodes it leaves as they are. However many those are, the dialog lists only the first of them and says how many more.
 *
 * @module     local_extendednav/local/action_dialog
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ModalSaveCancel from 'core/modal_save_cancel';
import ModalEvents from 'core/modal_events';
import Templates from 'core/templates';
import {describeError} from 'local_extendednav/local/ui';

/** Most names of the nodes left out that the dialog lists. */
const LISTED = 8;

/**
 * Asks before an action and runs it.
 *
 * @param {Object} options The question.
 * @param {string} options.title Title of the dialog.
 * @param {string} options.text What the action does.
 * @param {string} options.button Words of the button that runs it.
 * @param {string} [options.tone] Colour of the action: primary, secondary, success or danger.
 * @param {string} [options.icon] Icon of Moodle of the action.
 * @param {Object} [options.option] A switch: label, help and whether it starts on.
 * @param {Object} [options.skipped] The nodes left out: text (with {$a} for their number), more (with {$a}) and names.
 * @param {HTMLElement} options.trigger Element that gets the focus back.
 * @param {Function} options.action Runs the action, given whether the switch is on; a rejection is shown in the dialog.
 * @returns {Promise<void>}
 */
export const confirmAction = async({title, text, button, tone = 'primary', icon = 'i/info', option, skipped, trigger, action}) => {
    const names = skipped?.names ?? [];
    const context = {
        text,
        tone,
        icon,
        option: option ? {...option, id: `len-option-${Date.now()}`} : null,
        skipped: names.length ? {
            text: skipped.text.replace('{$a}', names.length),
            names: names.slice(0, LISTED),
            more: names.length > LISTED ? skipped.more.replace('{$a}', names.length - LISTED) : '',
        } : null,
    };
    const body = await Templates.render('local_extendednav/dialog/action', context);
    const modal = await ModalSaveCancel.create({title, body, buttons: {save: button}, removeOnClose: true, returnElement: trigger});
    const root = modal.getRoot()[0];
    const save = root.querySelector('[data-action="save"]');
    save.classList.remove('btn-primary');
    save.classList.add(`btn-${tone === 'secondary' ? 'primary' : tone}`);
    modal.getRoot().on(ModalEvents.save, async(event) => {
        event.preventDefault();
        const alert = root.querySelector('[data-region="confirm-error"]');
        save.disabled = true;
        alert.hidden = true;
        try {
            await action(Boolean(root.querySelector('[data-region="option"]')?.checked));
            modal.destroy();
        } catch (error) {
            alert.textContent = describeError(error);
            alert.hidden = false;
            save.disabled = false;
        }
    });
    modal.getRoot().on(ModalEvents.shown, () => root.querySelector('[data-action="cancel"]')?.focus());
    modal.show();
};
