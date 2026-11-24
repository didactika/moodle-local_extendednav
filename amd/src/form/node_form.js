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
 * Live behaviour of the node form.
 *
 * - For a core node key: shows the core notice, uses the core title and URL as placeholders
 *   and makes title and URL optional.
 * - Warns when the key is already used by another node.
 * - Hides "Block URL access" for external URLs, which cannot be blocked.
 * - Explains that languages have no effect while the node is not visible.
 *
 * @module     local_extendednav/form/node_form
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Bootstrap hides the row; Moodle's own rules of the form use the hidden attribute, so the two never undo each other.
const HIDDEN_CLASS = 'd-none';

/**
 * Returns the label container of a form field.
 *
 * @param {HTMLElement} element Form field.
 * @returns {HTMLElement|null}
 */
const getLabel = (element) => {
    const item = element.closest('.fitem, .form-group');
    return item ? item.querySelector('.col-form-label') : null;
};

/**
 * Shows or hides the "required" icon of a label.
 *
 * @param {HTMLElement|null} label Label container.
 * @param {String} requiredHtml HTML of the icon.
 * @param {Boolean} required Whether the field is required.
 */
const setRequired = (label, requiredHtml, required) => {
    if (!label) {
        return;
    }
    const existing = label.querySelector('.req');
    if (!required && existing) {
        existing.remove();
    } else if (required && !existing) {
        const icon = document.createRange().createContextualFragment(requiredHtml);
        const help = label.querySelector('[data-region="help-link"], .btn-link, .text-info');
        if (help) {
            help.parentNode.insertBefore(icon, help);
        } else {
            label.appendChild(icon);
        }
    }
};

/**
 * Whether a URL typed in the form points outside this site.
 *
 * @param {String} value URL as typed.
 * @param {String} wwwroot Site root URL, with a trailing slash.
 * @returns {Boolean}
 */
const isExternal = (value, wwwroot) => {
    let url = value.trim();
    if (url === '') {
        return false;
    }
    if (url.startsWith('//')) {
        url = 'https:' + url;
    } else if (/^www\./i.test(url)) {
        url = 'https://' + url;
    } else if (!/^[a-z][a-z0-9+.-]*:/i.test(url)) {
        return false;
    }
    try {
        const parsed = new URL(url);
        const root = new URL(wwwroot);
        if (parsed.host.toLowerCase() !== root.host.toLowerCase()) {
            return true;
        }
        return !(parsed.pathname + '/').startsWith(root.pathname);
    } catch (error) {
        return true;
    }
};

/**
 * Hides each section of the form whose fields are all hidden, such as Where it goes for a tab, which has no parent: a heading
 * over nothing says nothing. The section comes back as soon as one of its fields does.
 *
 * @param {HTMLFormElement} form The form.
 */
const hideEmptySections = (form) => {
    const isHidden = (item) => item.hidden || item.classList.contains('d-none') || window.getComputedStyle(item).display === 'none';
    const update = () => {
        form.querySelectorAll('fieldset[id^="id_"]').forEach((section) => {
            const items = [...section.querySelectorAll('.fitem')];
            const empty = items.length > 0 && items.every(isHidden);
            if (section.hidden !== empty) {
                section.hidden = empty;
            }
        });
    };
    // Moodle shows and hides the fields itself when the values they depend on change.
    new MutationObserver(update).observe(form, {subtree: true, attributes: true, attributeFilter: ['class', 'hidden', 'style']});
    update();
};

/**
 * Sets up the form. The form writes in the element nodeformdata, as JSON: the titles and links of the nodes of Moodle by key
 * (placeholders), the keys of the other nodes of the navigation (customKeys), the icon of a required field (requiredHtml), the
 * root of the site (wwwroot) and the key of the node edited, when it cannot be changed (currentKey).
 */
export const init = () => {
    // The form writes what the script needs in an element of its own: the keys of a navigation can be hundreds.
    const data = document.querySelector('script[data-region="nodeformdata"]');
    const {placeholders, customKeys, requiredHtml, wwwroot, currentKey} = JSON.parse(data?.textContent || '{}');
    const title = document.querySelector('form.mform input[name="title"]');
    const form = title ? title.closest('form') : null;
    const url = form ? form.querySelector('input[name="url"]:not([type="hidden"])') : null;
    if (form) {
        hideEmptySections(form);
    }
    if (!form || !url) {
        return;
    }
    // The key of a core node cannot be changed, so it is not an input.
    const nodekey = form.querySelector('input[name="nodekey"]');

    const blockaccess = form.querySelector('input[type="checkbox"][name="blockaccess"]');
    const blockaccessItem = blockaccess ? blockaccess.closest('.fitem, .form-group') : null;
    const coreAlert = document.getElementById('core_node_alert');
    const duplicateAlert = document.getElementById('duplicate_node_alert');
    const titleLabel = getLabel(title);
    const urlLabel = getLabel(url);

    const updateKey = () => {
        const key = (nodekey ? nodekey.value : currentKey).trim();
        const core = Object.prototype.hasOwnProperty.call(placeholders, key) ? placeholders[key] : null;
        if (coreAlert) {
            coreAlert.hidden = core === null;
        }
        if (duplicateAlert) {
            duplicateAlert.hidden = !customKeys.includes(key);
        }
        title.placeholder = core ? core.title : '';
        url.placeholder = core ? core.url : '';
        setRequired(titleLabel, requiredHtml, core === null);
        setRequired(urlLabel, requiredHtml, core === null);
    };

    const updateBlockAccess = () => {
        if (blockaccessItem) {
            blockaccessItem.classList.toggle(HIDDEN_CLASS, isExternal(url.value, wwwroot));
        }
    };

    ['input', 'change'].forEach((type) => {
        nodekey?.addEventListener(type, updateKey);
        url.addEventListener(type, updateBlockAccess);
    });

    updateKey();
    updateBlockAccess();
};
