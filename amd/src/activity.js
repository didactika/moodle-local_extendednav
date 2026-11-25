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
 * The activity log of a navigation, newest first, narrowed by a search (a node key or the name of a person), by the menu of the
 * actions and by a menu of the date whose two days never go the wrong way round, as every list of the design system.
 *
 * @module     local_extendednav/activity
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_strings as getStrings} from 'core/str';
import Modal from 'core/modal';
import Templates from 'core/templates';
import List from 'local_extendednav/local/list';
import {init as fitTables} from 'local_extendednav/local/fit_tables';
import {init as filterGroup} from 'local_extendednav/local/filter_group';

/**
 * A day as a date field writes it.
 *
 * @param {Date} date The day.
 * @returns {string} The day, as year-month-day.
 */
const iso = (date) => [date.getFullYear(), String(date.getMonth() + 1).padStart(2, '0'), String(date.getDate()).padStart(2, '0')]
    .join('-');

/**
 * The usual periods, counted from today.
 *
 * @returns {Object<string, string[]>} The first and the last day of each period.
 */
const periods = () => {
    const today = new Date();
    const back = (days) => new Date(today.getFullYear(), today.getMonth(), today.getDate() - days);
    return {
        today: [iso(today), iso(today)],
        week: [iso(back(6)), iso(today)],
        month: [iso(new Date(today.getFullYear(), today.getMonth(), 1)), iso(today)],
        last30: [iso(back(29)), iso(today)],
    };
};

/**
 * Two days that bound a period, never the wrong way round: when the day just chosen passes the other one, the other one moves to
 * it.
 *
 * @param {string} from The first day, or empty.
 * @param {string} to The last day, or empty.
 * @param {string} changed Which of the two was just chosen: from or to.
 * @returns {string[]} The two days, in order.
 */
const orderedDays = (from, to, changed) => {
    if (from === '' || to === '' || from <= to) {
        return [from, to];
    }
    return changed === 'from' ? [from, from] : [to, to];
};

/**
 * Starts the activity log.
 *
 * @param {string} selector Selector of the element of the list.
 * @returns {Promise<List>}
 */
export const init = async(selector) => {
    // The box of the table takes the height the window leaves it.
    fitTables();
    const root = document.querySelector(selector);
    const config = JSON.parse(root.dataset.config);
    const menu = root.querySelector('[data-menu="date"]');
    const toggle = menu.querySelector('[data-menu-toggle]');
    const panel = menu.querySelector('.dropdown-menu');
    const from = menu.querySelector('input[name="datefrom"]');
    const to = menu.querySelector('input[name="dateto"]');
    const reset = root.querySelector('[data-region="filters"] [data-action="clearfilters"]');
    const [range, since, until, emptytitle, emptytext, nolog, changeslabel, changestitle] = await getStrings([
        {key: 'filter_date_range', component: 'local_extendednav', param: {from: '{from}', to: '{to}'}},
        {key: 'filter_date_since', component: 'local_extendednav', param: '{n}'},
        {key: 'filter_date_until', component: 'local_extendednav', param: '{n}'},
        {key: 'activity_empty_title', component: 'local_extendednav'},
        {key: 'activity_empty_text', component: 'local_extendednav'},
        {key: 'activity_nolog', component: 'local_extendednav'},
        {key: 'changes_of', component: 'local_extendednav', param: '{key}'},
        {key: 'changes_title', component: 'local_extendednav', param: '{key}'},
    ]);
    let haslog = true;
    // When the menus do not fit beside the search they are grouped in one menu, Filters.
    const group = filterGroup(root.querySelector('[data-region="filters"]'));

    /**
     * Opens or closes the menu of the date.
     *
     * @param {boolean} show Whether it opens.
     */
    const open = (show) => {
        panel.hidden = !show;
        toggle.setAttribute('aria-expanded', show ? 'true' : 'false');
    };

    let list = null;
    // What the menu of the date says: the period chosen, the two days or one of them, tinted with its cross while it narrows
    // the list; and the button that clears every filter, enabled while any filter is applied.
    const paint = () => {
        const state = list.state;
        const found = Object.entries(periods()).find(([, days]) => days[0] === state.datefrom && days[1] === state.dateto);
        const label = toggle.querySelector('[data-region="menu-label"]');
        let text = label.dataset.label;
        if (found) {
            text = menu.querySelector(`[data-preset="${found[0]}"]`).textContent.trim();
        } else if (state.datefrom && state.dateto) {
            text = range.replace('{from}', state.datefrom).replace('{to}', state.dateto);
        } else if (state.datefrom) {
            text = since.replace('{n}', state.datefrom);
        } else if (state.dateto) {
            text = until.replace('{n}', state.dateto);
        }
        const active = Boolean(state.datefrom || state.dateto);
        label.textContent = text;
        toggle.classList.toggle('border-primary', active);
        menu.querySelector('[data-menu-clear]').hidden = !active;
        from.value = state.datefrom || '';
        to.value = state.dateto || '';
        reset.disabled = !state.search && !state.status && !active;
        group.count((state.status ? 1 : 0) + (active ? 1 : 0));
    };

    list = new List(root, {
        method: 'local_extendednav_get_activity',
        template: 'local_extendednav/activity/table',
        defaults: {search: '', status: '', datefrom: '', dateto: '', sort: 'time', direction: 'desc', page: 0, perpage: 12},
        args: (state) => ({
            menu: config.menu,
            search: state.search,
            status: state.status,
            datefrom: state.datefrom,
            dateto: state.dateto,
            direction: state.direction === 'asc' ? 'asc' : 'desc',
            page: state.page,
            perpage: state.perpage,
        }),
        context: (response) => {
            haslog = response.haslog;
            return {rows: response.rows.map((row) => ({...row, changeslabel: changeslabel.replace('{key}', row.nodekey)}))};
        },
        isFiltered: (state) => Boolean(state.search || state.status || state.datefrom || state.dateto),
        cleared: {status: '', datefrom: '', dateto: ''},
        empty: () => ({icon: 'i/report', title: emptytitle, text: haslog ? emptytext : nolog}),
        onRender: () => paint(),
    });

    const change = (changes) => list.update({page: 0, ...changes});
    toggle.addEventListener('click', () => open(panel.hidden));
    menu.addEventListener('click', (event) => {
        const preset = event.target.closest('[data-preset]');
        if (preset) {
            const [first, last] = periods()[preset.dataset.preset];
            open(false);
            change({datefrom: first, dateto: last});
        }
    });
    from.addEventListener('change', () => {
        const [first, last] = orderedDays(from.value, list.state.dateto || '', 'from');
        change({datefrom: first, dateto: last});
    });
    to.addEventListener('change', () => {
        const [first, last] = orderedDays(list.state.datefrom || '', to.value, 'to');
        change({datefrom: first, dateto: last});
    });
    menu.querySelector('[data-menu-clear]').addEventListener('click', () => {
        open(false);
        change({datefrom: '', dateto: ''});
        toggle.focus();
    });
    // The menu closes with a press outside it, never with the one on its own button, and with Escape in the capture phase,
    // before Bootstrap 5 looks at the keys of every dropdown menu.
    document.addEventListener('pointerdown', (event) => {
        if (event.target instanceof Node && !menu.contains(event.target)) {
            open(false);
        }
    });
    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) {
            event.stopPropagation();
            open(false);
            toggle.focus();
        }
    }, true);
    root.closest('[data-region="activity"]')?.addEventListener('change', (event) => {
        const select = event.target.closest('[data-region="originselect"]');
        if (select?.value) {
            window.location.assign(select.value);
        }
    });

    // The fields of a change open the dialog that gives each one before and after.
    root.addEventListener('click', async(event) => {
        const button = event.target.closest('[data-action="changes"]');
        const row = button ? list.rows.find((item) => String(item.id) === button.dataset.id) : null;
        if (!row) {
            return;
        }
        const modal = await Modal.create({
            title: changestitle.replace('{key}', row.nodekey),
            body: Templates.render('local_extendednav/activity/changes', {changes: row.changes, time: row.time, user: row.user}),
            removeOnClose: true,
        });
        modal.show();
    });

    await list.init();
    return list;
};
