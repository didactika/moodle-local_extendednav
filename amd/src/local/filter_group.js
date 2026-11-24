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
 * The menus of a row of filters, beside the search. When they do not fit, on a narrow screen or when the values chosen are long,
 * they are grouped in one menu, Filters, that says how many narrow the list and holds them; they come back to the row when there
 * is room again, so the row never breaks in two.
 *
 * @module     local_extendednav/local/filter_group
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** The width the search keeps beside the filters before they are grouped, in pixels (10rem). */
const SEARCH_ROOM = 160;

/**
 * Groups the menus of a row of filters when they do not fit.
 *
 * @param {HTMLElement} toolbar The row of the filters.
 * @returns {{count: Function}} Says how many filters narrow the list, for the button of the group.
 */
export const init = (toolbar) => {
    const box = toolbar.querySelector('[data-region="filter-menus"]');
    const group = box?.querySelector(':scope > [data-menu="filters"]');
    if (!box || !group) {
        return {count: () => null};
    }
    const panel = group.querySelector('[data-region="filter-panel"]');
    const head = group.querySelector('[data-region="filter-panel-head"]') || panel;
    const list = group.querySelector(':scope > .dropdown-menu');
    const toggle = group.querySelector(':scope > [data-menu-toggle]');
    const state = {grouped: false, needed: 0, count: 0};

    const open = (show) => {
        list.hidden = !show;
        toggle.setAttribute('aria-expanded', show ? 'true' : 'false');
    };
    toggle.addEventListener('click', () => open(list.hidden));
    // The list of a select inside the group floats outside it; a click there does not close the group.
    document.addEventListener('pointerdown', (event) => {
        if (event.target instanceof Element && !group.contains(event.target) && !event.target.closest('.dropdown-menu')) {
            open(false);
        }
    });
    // Escape closes it in the capture phase of the window, before Bootstrap 5 looks at the keys of every dropdown menu.
    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !list.hidden && !panel.querySelector('.dropdown-menu:not([hidden])')) {
            event.stopPropagation();
            open(false);
            toggle.focus();
        }
    }, true);

    const paint = () => {
        const label = toggle.querySelector('[data-region="menu-label"]');
        label.textContent = state.count > 0 ? `${label.dataset.label} (${state.count})` : label.dataset.label;
        toggle.classList.toggle('border-primary', state.count > 0);
    };

    // While the menus are grouped, the button that clears every filter goes to the head of the group, at its right.
    const reset = toolbar.querySelector('[data-action="clearfilters"]')?.parentElement;
    const resetHome = reset?.parentElement;

    const fit = () => {
        const row = box.parentElement;
        const gap = parseFloat(window.getComputedStyle(row).columnGap) || 0;
        const room = row.clientWidth - SEARCH_ROOM - gap;
        if (!state.grouped) {
            const filters = Array.from(box.children).filter((child) => child !== group);
            const width = filters.reduce((sum, child) => sum + child.offsetWidth, 0) + gap * Math.max(0, filters.length - 1);
            if (width > room) {
                // The row gains the room of the button when it goes into the group; it is counted back before ungrouping.
                state.needed = width + (reset ? reset.offsetWidth + gap : 0);
                state.grouped = true;
                filters.forEach((child) => panel.appendChild(child));
                if (reset) {
                    head.appendChild(reset);
                }
                group.hidden = false;
            }
        } else if (room >= state.needed) {
            state.grouped = false;
            open(false);
            Array.from(panel.children).forEach((child) => box.insertBefore(child, group));
            if (reset) {
                resetHome.appendChild(reset);
            }
            group.hidden = true;
        }
        paint();
    };

    new ResizeObserver(fit).observe(toolbar);
    fit();

    return {
        count: (count) => {
            state.count = count;
            paint();
        },
    };
};
