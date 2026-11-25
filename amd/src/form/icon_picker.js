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
 * @module     local_extendednav/form/icon_picker
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
 * @param {Boolean} native Whether the node is one of Moodle, which has an icon of its own.
 * @returns {Array} String identifier and parameter.
 */
const describe = (value, native) => {
    if (value === '') {
        return [native ? 'icon_info_default' : 'icon_info_none', null];
    }
    if (['none', 'null'].includes(value.toLowerCase())) {
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
 * @param {Boolean} native Whether the node is one of Moodle.
 */
const updatePreview = async(tile, info, value, native) => {
    value = value.trim();
    tile.dataset.value = value;
    const [identifier, param] = describe(value, native);
    const empty = ['icon_info_none', 'icon_info_default', 'icon_info_invalid'].includes(identifier);
    tile.dataset.empty = empty ? '1' : '';
    tile.replaceChildren();
    getString(identifier, 'local_extendednav', param).then((text) => {
        info.textContent = text;
        return text;
    }).catch(Notification.exception);

    if (empty) {
        // An empty tile shows that an icon can be chosen; "none" shows that the icon is removed.
        tile.appendChild(createIcon(value === '' ? 'icon fa fa-image fa-fw text-muted' : 'icon fa fa-ban fa-fw text-muted'));
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
            await Templates.replaceNodeContents(tile, html, '');
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
    button.className = 'btn btn-light';
    button.dataset.region = 'iconcell';
    button.dataset.value = icon.value;
    button.title = icon.value;
    button.setAttribute('aria-label', icon.value);
    if (icon.value === current) {
        button.classList.add('active');
        button.setAttribute('aria-current', 'true');
    }
    const [component, name] = icon.value.includes(':') ? icon.value.split(':', 2) : ['', icon.label];

    const glyph = document.createElement('span');
    glyph.dataset.region = 'iconglyph';
    const element = document.createElement('i');
    element.className = icon.classes;
    element.setAttribute('aria-hidden', 'true');
    glyph.appendChild(element);
    const label = document.createElement('span');
    label.className = 'small';
    label.dataset.region = 'iconlabel';
    label.textContent = name;
    button.append(glyph, label);
    if (component !== '') {
        const small = document.createElement('span');
        small.className = 'small text-muted text-truncate mw-100';
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
    sentinel.dataset.region = 'iconsentinel';

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
        // An icon the lists do not hold, written as a FontAwesome class or a Moodle icon, is offered as it is.
        const typed = search.value.trim();
        if ((isFontAwesome(typed) || MOODLE.test(typed)) && !matches.some((icon) => icon.value === typed)) {
            matches = [{value: typed, label: typed, aliases: [], classes: isFontAwesome(typed) ? `icon fa ${typed} fa-fw`
                : 'icon fa fa-image fa-fw'}, ...matches];
        }
        shown = 0;
        grid.replaceChildren(sentinel);
        grid.scrollTop = 0;
        showMore();
        unavailable.hidden = list.length > 0;
        empty.hidden = matches.length > 0 || (list.length === 0 && query === '');
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
 * Builds the control of the icon in its place of the form: a button that shows the icon chosen and its name and opens the
 * picker, and the buttons that go back to the icon of Moodle or remove the icon. The value lives in a hidden field.
 *
 * @param {String} fieldName Name of the hidden icon field.
 */
export const init = async(fieldName) => {
    const field = document.querySelector(`form.mform input[name="${fieldName}"]`);
    const place = document.querySelector('form.mform [data-region="iconfield"]');
    if (!field || !place) {
        return;
    }
    const [choose, clear, remove, removeCustom] = await Promise.all([
        getString('icon_choose', 'local_extendednav'),
        getString('icon_clear', 'local_extendednav'),
        getString('icon_remove', 'local_extendednav'),
        getString('icon_clear_custom', 'local_extendednav'),
    ]);

    const row = document.createElement('div');
    row.className = 'd-flex flex-wrap align-items-center gap-2';
    const open = document.createElement('button');
    open.type = 'button';
    open.className = 'btn btn-outline-secondary d-inline-flex align-items-center gap-2';
    open.dataset.region = 'iconopen';
    const tile = document.createElement('span');
    tile.dataset.region = 'icontile';
    tile.setAttribute('aria-hidden', 'true');
    const name = document.createElement('span');
    open.append(tile, name);
    const clearButton = document.createElement('button');
    clearButton.type = 'button';
    clearButton.className = 'btn btn-link';
    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'btn btn-link';
    removeButton.textContent = remove;
    const info = document.createElement('div');
    info.className = 'form-text text-muted';
    info.setAttribute('aria-live', 'polite');
    row.append(open, clearButton, removeButton);
    place.replaceChildren(row, info);

    // A node of Moodle has an icon of its own: going back to it and removing it are two choices. A node added has none.
    const isNative = () => place.dataset.native === '1' || document.getElementById('core_node_alert')?.hidden === false;
    const refresh = () => {
        const value = field.value.trim();
        const native = isNative();
        name.textContent = value === '' || value === 'none' ? choose : value;
        clearButton.textContent = native ? clear : removeCustom;
        clearButton.hidden = value === '';
        removeButton.hidden = !native || value === 'none';
        updatePreview(tile, info, value, native);
    };
    const setValue = (value) => {
        field.value = value;
        field.dispatchEvent(new Event('change', {bubbles: true}));
        refresh();
        open.focus();
    };
    open.addEventListener('click', () => openPicker(field, setValue).catch(Notification.exception));
    clearButton.addEventListener('click', () => setValue(''));
    removeButton.addEventListener('click', () => setValue('none'));
    document.querySelector('form.mform input[name="nodekey"]')?.addEventListener('input', () => setTimeout(refresh));
    refresh();
};
