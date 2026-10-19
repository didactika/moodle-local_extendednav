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
 * Icon picker of the node form: a preview of the icon field and a searchable list of Moodle and FontAwesome icons.
 *
 * The list is loaded once and searched in the browser. Icons are added to the grid in batches as it scrolls, so
 * the thousands of FontAwesome icons cost little more than the first batch.
 *
 * @module     local_extendednav/icon_picker
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Modal from 'core/modal';
import ModalEvents from 'core/modal_events';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {getString} from 'core/str';

/** @var {Number} Icons added to the grid at a time. */
const BATCH = 150;

/** @var {String[]} Lists of the picker, in the order of their tabs. */
const TABS = ['moodle', 'fontawesome', 'brands'];

/** @var {Promise|null} The icons, loaded on first use. */
let icons = null;

/**
 * Loads the icons once.
 *
 * @returns {Promise<Object>} Lists keyed by tab.
 */
const loadIcons = () => {
    if (icons === null) {
        icons = Ajax.call([{methodname: 'local_extendednav_get_icons', args: {}}])[0];
        icons.catch(() => {
            icons = null;
        });
    }
    return icons;
};

/** @var {RegExp} A FontAwesome class, as the plugin validates it. */
const FONTAWESOME = /^(fa[a-z]?(-[a-z]+)?\s+)?fa-[a-z0-9-]+(\s+fa-[a-z0-9-]+)*$/;

/** @var {RegExp} A Moodle icon: "component:name" or a core icon path such as "i/home". */
const MOODLE = /^([a-z][a-z0-9_]*:[a-z0-9_-]+(\/[a-z0-9_-]+)*|[a-z0-9_-]+(\/[a-z0-9_-]+)+)$/;

/**
 * Whether the value is a FontAwesome class rather than a Moodle icon.
 *
 * @param {String} value Icon field value.
 * @returns {Boolean}
 */
const isFontAwesome = (value) => FONTAWESOME.test(value.trim());

/**
 * Builds an icon element.
 *
 * @param {String} classes Classes of the icon.
 * @returns {HTMLElement}
 */
const createIcon = (classes) => {
    const icon = document.createElement('i');
    icon.className = classes;
    icon.setAttribute('aria-hidden', 'true');
    return icon;
};

/**
 * Describes the value of the field: which string explains it, with its parameter.
 *
 * @param {String} value Icon field value.
 * @returns {Array} String identifier and parameter.
 */
const describe = (value) => {
    if (value === '' || ['none', 'null'].includes(value.toLowerCase())) {
        return ['icon_info_none', null];
    }
    if (isFontAwesome(value)) {
        return [/(^|\s)(fa-brands|fab)(\s|$)/.test(value) ? 'icon_info_brand' : 'icon_info_fontawesome', null];
    }
    if (MOODLE.test(value)) {
        return ['icon_info_moodle', value.includes(':') ? value.split(':', 2)[0] : 'core'];
    }
    return ['icon_info_invalid', null];
};

/**
 * Shows the icon of the field in its tile, and says what it is below the field.
 *
 * @param {HTMLElement} tile Tile that shows the icon.
 * @param {HTMLElement} info Line below the field.
 * @param {String} value Icon field value.
 */
const updatePreview = async(tile, info, value) => {
    value = value.trim();
    tile.dataset.value = value;
    const [identifier, param] = describe(value);
    const empty = identifier === 'icon_info_none' || identifier === 'icon_info_invalid';
    tile.classList.toggle('local-extendednav-icontile-empty', empty);
    tile.replaceChildren();
    getString(identifier, 'local_extendednav', param).then((text) => {
        info.textContent = text;
        return text;
    }).catch(Notification.exception);

    if (identifier === 'icon_info_none' || identifier === 'icon_info_invalid') {
        // An empty tile shows that it can be clicked to choose an icon; "none" shows that the icon is removed.
        tile.appendChild(createIcon(value === '' ? 'icon fa fa-plus fa-fw text-muted' : 'icon fa fa-ban fa-fw text-muted'));
        return;
    }
    if (isFontAwesome(value)) {
        tile.appendChild(createIcon(`icon fa ${value} fa-fw`));
        return;
    }
    const [component, name] = value.includes(':') ? value.split(':', 2) : ['core', value];
    try {
        const html = await Templates.renderPix(name, component === 'core' ? 'moodle' : component, '');
        // Ignore the answer if the field changed in the meantime.
        if (tile.dataset.value === value) {
            tile.innerHTML = html;
            // Moodle shows "b/" icons 64 pixels big; the menus show them at the size of the others.
            tile.querySelectorAll('.iconsize-big').forEach((icon) => icon.classList.remove('iconsize-big'));
        }
    } catch (error) {
        tile.replaceChildren();
    }
};

/**
 * Builds the button of one icon. Moodle icons show their name, with their component below it.
 *
 * @param {Object} icon Icon from the list.
 * @param {String} current Value of the icon field.
 * @returns {HTMLButtonElement}
 */
const buildCell = (icon, current) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'local-extendednav-iconcell';
    button.dataset.value = icon.value;
    button.title = icon.value;
    button.setAttribute('aria-label', icon.value);
    if (icon.value === current) {
        button.classList.add('local-extendednav-iconcell-current');
        button.setAttribute('aria-current', 'true');
    }
    const [component, name] = icon.value.includes(':') ? icon.value.split(':', 2) : ['', icon.label];

    const glyph = document.createElement('span');
    glyph.className = 'local-extendednav-iconglyph';
    const element = document.createElement('i');
    element.className = icon.classes;
    element.setAttribute('aria-hidden', 'true');
    glyph.appendChild(element);
    const label = document.createElement('span');
    label.className = 'local-extendednav-iconlabel';
    label.textContent = name;
    button.append(glyph, label);
    if (component !== '') {
        const small = document.createElement('span');
        small.className = 'local-extendednav-iconcomponent';
        small.textContent = component;
        button.appendChild(small);
    }
    return button;
};

/**
 * Opens the picker.
 *
 * @param {HTMLInputElement} field Icon field.
 * @param {Function} onPick Called with the chosen value.
 */
const openPicker = async(field, onPick) => {
    const [title, tabs] = await Promise.all([
        getString('icon_picker', 'local_extendednav'),
        Promise.all(TABS.map((key) => getString('icon_tab_' + key, 'local_extendednav'))),
    ]);
    const body = await Templates.render('local_extendednav/icon_picker', {
        tabs: TABS.map((key, index) => ({key, label: tabs[index], active: index === 0})),
    });
    const modal = await Modal.create({title, body, large: true, show: true, removeOnClose: true});
    const root = modal.getRoot()[0];
    const search = root.querySelector('[data-region="search"]');
    const grid = root.querySelector('[data-region="grid"]');
    const empty = root.querySelector('[data-region="empty"]');
    const unavailable = root.querySelector('[data-region="unavailable"]');
    const loading = root.querySelector('[data-region="loading"]');

    let lists;
    try {
        lists = await loadIcons();
    } catch (error) {
        modal.destroy();
        Notification.exception(error);
        return;
    }
    loading.hidden = true;

    // Open the list the current icon comes from.
    let tab = 'moodle';
    if (isFontAwesome(field.value)) {
        tab = field.value.includes('fa-brands') ? 'brands' : 'fontawesome';
    }
    let matches = [];
    let shown = 0;
    const sentinel = document.createElement('div');
    sentinel.className = 'local-extendednav-iconsentinel';

    const showMore = () => {
        const fragment = document.createDocumentFragment();
        matches.slice(shown, shown + BATCH).forEach((icon) => fragment.appendChild(buildCell(icon, field.value.trim())));
        shown = Math.min(shown + BATCH, matches.length);
        grid.insertBefore(fragment, sentinel);
    };
    const observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting) && shown < matches.length) {
            showMore();
        }
    }, {root: grid, rootMargin: '200px'});

    const refresh = () => {
        const query = search.value.trim().toLowerCase();
        const list = lists[tab] || [];
        matches = query === '' ? list : list.filter((icon) => icon.value.toLowerCase().includes(query) ||
            icon.aliases.some((alias) => alias.includes(query)));
        shown = 0;
        grid.replaceChildren(sentinel);
        grid.scrollTop = 0;
        showMore();
        unavailable.hidden = list.length > 0;
        empty.hidden = list.length === 0 || matches.length > 0;
        root.querySelectorAll('[data-tab]').forEach((button) => {
            const active = button.dataset.tab === tab;
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    };

    root.querySelectorAll('[data-tab]').forEach((button) => button.addEventListener('click', () => {
        tab = button.dataset.tab;
        refresh();
        search.focus();
    }));
    let timer = null;
    search.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(refresh, 150);
    });
    grid.addEventListener('click', (event) => {
        const cell = event.target.closest('[data-value]');
        if (cell) {
            onPick(cell.dataset.value);
            modal.destroy();
        }
    });
    modal.getRoot().on(ModalEvents.hidden, () => observer.disconnect());

    refresh();
    observer.observe(sentinel);
    search.focus();
};

/**
 * Puts the icon field in a row with a tile that shows its icon and opens the picker, and a line saying what it is.
 *
 * @param {String} fieldName Name of the icon field.
 */
export const init = async(fieldName) => {
    const field = document.querySelector(`form.mform input[name="${fieldName}"]`);
    if (!field) {
        return;
    }
    const [choose, clear] = await Promise.all([
        getString('icon_choose', 'local_extendednav'),
        getString('icon_clear', 'local_extendednav'),
    ]);

    const row = document.createElement('div');
    row.className = 'local-extendednav-iconfield';
    const tile = document.createElement('button');
    tile.type = 'button';
    tile.className = 'local-extendednav-icontile';
    tile.title = choose;
    tile.setAttribute('aria-label', choose);
    const clearButton = document.createElement('button');
    clearButton.type = 'button';
    clearButton.className = 'btn btn-link';
    clearButton.textContent = clear;
    const info = document.createElement('div');
    info.className = 'local-extendednav-iconinfo form-text text-muted';
    info.setAttribute('aria-live', 'polite');
    field.before(row);
    row.append(tile, field, clearButton);
    row.after(info);

    const refreshPreview = () => {
        // There is only something to clear when the field has a value.
        clearButton.hidden = field.value.trim() === '';
        updatePreview(tile, info, field.value);
    };
    const setValue = (value) => {
        field.value = value;
        field.dispatchEvent(new Event('change', {bubbles: true}));
        refreshPreview();
        field.focus();
    };
    const open = () => openPicker(field, setValue).catch(Notification.exception);
    field.addEventListener('input', refreshPreview);
    tile.addEventListener('click', open);
    clearButton.addEventListener('click', () => setValue(''));
    refreshPreview();
};
