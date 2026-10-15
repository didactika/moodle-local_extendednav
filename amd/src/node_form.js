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
 * @module     local_extendednav/node_form
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const HIDDEN_CLASS = 'local-extendednav-hidden';

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
 * Sets up the form.
 *
 * @param {Object} config Configuration.
 * @param {Object} config.placeholders Core titles and URLs keyed by node key.
 * @param {Array} config.customKeys Keys used by the other nodes of the menu.
 * @param {String} config.requiredHtml HTML of the "required" icon.
 * @param {String} config.wwwroot Site root URL.
 * @param {String} config.currentKey Key of the node being edited, used when the key cannot be changed.
 */
export const init = ({placeholders, customKeys, requiredHtml, wwwroot, currentKey}) => {
    const title = document.querySelector('form.mform input[name="title"]');
    const form = title ? title.closest('form') : null;
    const url = form ? form.querySelector('input[name="url"]') : null;
    if (!form || !url) {
        return;
    }
    // The key of a core node cannot be changed, so it is not an input.
    const nodekey = form.querySelector('input[name="nodekey"]');

    const visibility = form.querySelector('select[name="visibility"]');
    const langs = form.querySelector('select[name="langs[]"]');
    const blockaccess = form.querySelector('input[type="checkbox"][name="blockaccess"]');
    const blockaccessItem = blockaccess ? blockaccess.closest('.fitem, .form-group') : null;
    const coreAlert = document.getElementById('core_node_alert');
    const duplicateAlert = document.getElementById('duplicate_node_alert');
    const langsNotice = document.getElementById('local_extendednav_langs_notice');
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

    const updateLangsNotice = () => {
        if (!langsNotice || !visibility || !langs) {
            return;
        }
        langsNotice.hidden = !(visibility.value === '0' && langs.selectedOptions.length > 0);
    };

    ['input', 'change'].forEach((type) => {
        nodekey?.addEventListener(type, updateKey);
        url.addEventListener(type, updateBlockAccess);
    });
    visibility?.addEventListener('change', updateLangsNotice);
    langs?.addEventListener('change', updateLangsNotice);

    updateKey();
    updateBlockAccess();
    updateLangsNotice();
};
