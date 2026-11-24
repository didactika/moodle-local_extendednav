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
 * The list a select opens, as the design system draws it. The browser draws its own and a page cannot style it, so a select
 * of the screens of the plugin opens a dropdown menu of the design system instead, with the chosen option marked and the
 * groups as headers, below the select or above it, wherever it fits. The select underneath keeps its value and fires
 * input and change, so the markup does not change.
 *
 * Keyboard: Enter, Space and the arrows open it; the arrows, Home and End move, Enter and Space choose, a letter jumps to the
 * next option that starts with it, Escape and Tab close it.
 *
 * A select with many options (data-searchable) opens with a search field at the top of its menu, which keeps the options that
 * hold every word typed, whatever their case and accents. Its label and the text shown when nothing matches come from the
 * select (data-search-label, data-noresults). The arrow down goes from the field to the options, Enter chooses the first one.
 *
 * @module     local_extendednav/local/select_menu
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** The selects that open the menu: single selects of the screens and dialogs of the plugin. */
const SELECTOR = ':is(.len, .modal) select:is(.form-select, .custom-select)';

/**
 * A text as the search of a menu compares it: in small letters and without accents.
 *
 * @param {string} value The text.
 * @returns {string}
 */
const fold = (value) => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

/** Room kept between the menu and the edge of the window. */
const MARGIN = 8;

let current = null;
let installed = false;

/**
 * Whether a select opens the menu.
 *
 * @param {HTMLSelectElement} select The select.
 * @returns {boolean}
 */
const eligible = (select) => !select.multiple && select.size <= 1 && !select.disabled;

/**
 * Closes the open menu.
 *
 * @param {boolean} restoreFocus Whether the select gets the focus back.
 */
const close = (restoreFocus = false) => {
    if (!current) {
        return;
    }
    const {select, menu} = current;
    menu.remove();
    select.removeAttribute('aria-expanded');
    current = null;
    if (restoreFocus) {
        select.focus();
    }
};

/**
 * Chooses an option and tells the page, as a native select would.
 *
 * @param {number} index Index of the entry.
 */
const choose = (index) => {
    const entry = current?.entries[index];
    if (!entry || entry.option.disabled) {
        return;
    }
    const {select} = current;
    const changed = select.selectedIndex !== entry.option.index;
    select.selectedIndex = entry.option.index;
    close(true);
    if (changed) {
        select.dispatchEvent(new Event('input', {bubbles: true}));
        select.dispatchEvent(new Event('change', {bubbles: true}));
    }
};

/**
 * Moves the focus to an entry, skipping the disabled ones.
 *
 * @param {number} index Index of the entry; -1 is the last.
 */
const activate = (index) => {
    const {entries, menu} = current;
    const count = entries.length;
    for (let step = 0; step < count; step++) {
        const at = (index + step * (index >= 0 ? 1 : -1) + count * 2) % count;
        const entry = entries[at];
        if (entry && !entry.option.disabled && !entry.button.hidden) {
            entries.forEach((other) => other.button.classList.toggle('active', other === entry));
            entry.button.focus();
            entry.button.scrollIntoView({block: 'nearest'});
            return;
        }
    }
    menu.focus();
};

/**
 * Places the menu under the select, or over it when there is more room above.
 *
 * @param {HTMLSelectElement} select The select.
 * @param {HTMLElement} menu The menu.
 */
const place = (select, menu) => {
    const box = select.getBoundingClientRect();
    menu.style.minWidth = `${box.width}px`;
    const below = window.innerHeight - box.bottom - MARGIN;
    const above = box.top - MARGIN;
    const height = menu.scrollHeight;
    const up = height > below && above > below;
    menu.style.maxHeight = `${Math.max(120, Math.min(17 * 16, up ? above : below))}px`;
    // A menu that opens above is held by its foot, next to the select, so it stays there when a search makes it shorter.
    if (up) {
        menu.style.top = 'auto';
        menu.style.bottom = `${window.innerHeight - box.top + 2}px`;
    } else {
        menu.style.top = `${box.bottom + 2}px`;
        menu.style.bottom = 'auto';
    }
    menu.style.left = `${Math.max(MARGIN, Math.min(box.left, window.innerWidth - menu.offsetWidth - MARGIN))}px`;
};

/**
 * Opens the menu of a select.
 *
 * @param {HTMLSelectElement} select The select.
 */
const open = (select) => {
    close();
    const scope = select.closest('.len, .modal');
    const menu = document.createElement('div');
    menu.className = 'dropdown-menu show';
    menu.setAttribute('role', 'listbox');
    menu.tabIndex = -1;
    const entries = [];
    const add = (option, parent) => {
        if (option.hidden) {
            return;
        }
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'dropdown-item';
        button.setAttribute('role', 'option');
        button.setAttribute('aria-selected', String(option.selected));
        if (option.disabled) {
            button.classList.add('disabled');
            button.setAttribute('aria-disabled', 'true');
            button.tabIndex = -1;
        }
        button.textContent = option.textContent;
        parent.append(button);
        entries.push({option, button});
    };
    Array.from(select.children).forEach((child) => {
        if (child instanceof HTMLOptGroupElement) {
            const header = document.createElement('h6');
            header.className = 'dropdown-header';
            header.textContent = child.label;
            menu.append(header);
            Array.from(child.children).forEach((option) => add(option, menu));
        } else if (child instanceof HTMLOptionElement) {
            add(child, menu);
        }
    });
    let field = null;
    if (select.dataset.searchable !== undefined) {
        const box = document.createElement('div');
        box.className = 'position-relative d-flex align-items-center p-1';
        box.setAttribute('role', 'search');
        // The magnifier of the search fields of the page, drawn by the theme, so the field looks like them.
        const icon = scope.querySelector('[role="search"] .icon');
        if (icon) {
            box.append(icon.cloneNode(true));
        }
        field = document.createElement('input');
        field.type = 'search';
        field.className = 'form-control form-control-sm';
        field.autocomplete = 'off';
        field.placeholder = select.dataset.searchLabel || '';
        field.setAttribute('aria-label', field.placeholder);
        box.append(field);
        menu.prepend(box);
        const none = document.createElement('p');
        none.className = 'text-muted small px-3 py-2 mb-0';
        none.setAttribute('role', 'status');
        none.textContent = select.dataset.noresults || '';
        none.hidden = true;
        menu.append(none);
        field.addEventListener('input', () => {
            const words = fold(field.value).split(/\s+/).filter(Boolean);
            let shown = 0;
            entries.forEach((entry) => {
                entry.button.hidden = !words.every((word) => fold(entry.button.textContent).includes(word));
                shown += entry.button.hidden ? 0 : 1;
            });
            none.hidden = shown > 0;
        });
    }
    scope.append(menu);
    place(select, menu);
    select.setAttribute('aria-expanded', 'true');
    current = {select, menu, entries};

    menu.addEventListener('click', (event) => {
        const button = event.target.closest('.dropdown-item');
        const at = entries.findIndex((entry) => entry.button === button);
        if (at >= 0) {
            choose(at);
        }
    });
    menu.addEventListener('keydown', (event) => {
        const at = entries.findIndex((entry) => entry.button === document.activeElement);
        // In the search field the keys write; the arrow down goes to the options and Enter chooses the first one shown.
        if (field && event.target === field) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                activate(0);
            } else if (event.key === 'Enter') {
                event.preventDefault();
                choose(entries.findIndex((entry) => !entry.button.hidden && !entry.option.disabled));
            } else if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                close(true);
            } else if (event.key === 'Tab') {
                close();
            }
            return;
        }
        if (field && event.key === 'ArrowUp' && at === entries.findIndex((entry) => !entry.button.hidden)) {
            event.preventDefault();
            field.focus();
            return;
        }
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            activate(at + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            activate(at <= 0 ? -1 : at - 1);
        } else if (event.key === 'Home') {
            event.preventDefault();
            activate(0);
        } else if (event.key === 'End') {
            event.preventDefault();
            activate(-1);
        } else if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
            close(true);
        } else if (event.key === 'Tab') {
            close();
        } else if (event.key.length === 1 && /\S/.test(event.key)) {
            const letter = event.key.toLowerCase();
            const from = at + 1;
            const found = [...entries.slice(from), ...entries.slice(0, from)].find((entry) => !entry.option.disabled &&
                entry.option.textContent.trim().toLowerCase().startsWith(letter));
            if (found) {
                activate(entries.indexOf(found));
            }
        }
    });
    const selected = entries.findIndex((entry) => entry.option.selected);
    activate(selected >= 0 ? selected : 0);
    field?.focus({preventScroll: true});
};

/**
 * Makes the selects of the screens of the plugin open the menu of the design system. It is installed once per page.
 */
export const init = () => {
    if (installed) {
        return;
    }
    installed = true;
    // On mousedown, before the browser opens its own list.
    document.addEventListener('mousedown', (event) => {
        const target = event.target instanceof Element ? event.target : null;
        if (!target) {
            return;
        }
        if (current && !current.menu.contains(target)) {
            const same = target.closest('select') === current.select;
            close();
            if (same) {
                event.preventDefault();
                return;
            }
        }
        const select = target.closest(SELECTOR);
        if (!select || !eligible(select)) {
            return;
        }
        event.preventDefault();
        select.focus();
        open(select);
    });
    document.addEventListener('keydown', (event) => {
        const select = event.target instanceof Element && !current ? event.target.closest(SELECTOR) : null;
        if (select && eligible(select) && ['Enter', ' ', 'ArrowDown', 'ArrowUp'].includes(event.key)) {
            event.preventDefault();
            open(select);
        }
    });
    window.addEventListener('resize', () => close());
    window.addEventListener('scroll', (event) => {
        if (current && !(event.target instanceof Node && current.menu.contains(event.target))) {
            close();
        }
    }, {capture: true, passive: true});
};
