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
 * A list people work with, as the design system describes it: search, a menu of statuses, sortable headers, a pager, a selection
 * with its floating bar, and a designed state for loading, empty, error and results.
 *
 * The rows come from a web service and are drawn with a template, so a list never reloads the page. Its state (search,
 * statuses, sort, page and rows per page) is kept in the address, so a link or a reload comes back to the same list.
 *
 * @module     local_extendednav/local/list
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Templates from 'core/templates';
import Pending from 'core/pending';
import {getString} from 'core/str';
import {debounce, describeError, fitPageSizes, pageRange, pageSize} from 'local_extendednav/local/ui';
import {init as initSelectMenus} from 'local_extendednav/local/select_menu';

/** Milliseconds a load may take before the skeleton appears, so a fast answer does not flash. */
const SKELETON_DELAY = 300;

export default class List {
    /**
     * Creates a list.
     *
     * @param {HTMLElement} root Element with data-region="list".
     * @param {Object} options How the list loads and draws its rows.
     * @param {string} options.method Web service that returns {total, counts, rows}.
     * @param {Function} options.args Turns the state into the arguments of the web service.
     * @param {string} options.template Template that draws the rows.
     * @param {Function} [options.context] Turns a response into the context of the template.
     * @param {Object} options.defaults State the list starts from.
     * @param {string} [options.prefix] Prefix of its parameters in the address.
     * @param {Function} [options.empty] Returns the context of the empty state, given whether filters are applied.
     * @param {Function} [options.onRender] Called after the rows are drawn.
     * @param {Function} [options.isFiltered] Whether filters other than the search and the statuses are applied.
     * @param {Object} [options.cleared] The state that clears every filter.
     * @param {Function} [options.onClear] Called with that state, to empty the fields of the filters.
     * @param {Function} [options.filters] Turns the state into the filters of an action on every row.
     */
    constructor(root, options) {
        this.root = root;
        this.options = options;
        this.prefix = options.prefix || '';
        this.state = this.readState(options.defaults);
        this.selected = new Set();
        this.allMatching = false;
        this.total = 0;
        this.rows = [];
        this.request = 0;
        this.region = (name) => root.querySelector(`[data-region="${name}"]`);
    }

    /**
     * Reads the state from the address, falling back on the defaults.
     *
     * @param {Object} defaults State the list starts from.
     * @returns {Object} The state.
     */
    readState(defaults) {
        const params = new URL(window.location.href).searchParams;
        const state = {...defaults};
        Object.keys(defaults).forEach((key) => {
            const value = params.get(this.prefix + key);
            if (value !== null) {
                state[key] = typeof defaults[key] === 'number' ? Number(value) || 0 : value;
            }
        });
        if (typeof state.perpage === 'number') {
            state.perpage = pageSize(state.perpage, defaults.perpage);
        }
        return state;
    }

    /**
     * Writes the state into the address, leaving out what equals the defaults.
     */
    writeState() {
        const url = new URL(window.location.href);
        Object.keys(this.options.defaults).forEach((key) => {
            const value = this.state[key];
            if (value === this.options.defaults[key] || value === '') {
                url.searchParams.delete(this.prefix + key);
            } else {
                url.searchParams.set(this.prefix + key, value);
            }
        });
        window.history.replaceState(window.history.state, '', url);
    }

    /**
     * Wires the controls and loads the first page.
     *
     * @returns {Promise<void>}
     */
    async init() {
        initSelectMenus();
        const search = this.region('search');
        if (search) {
            search.value = this.state.search || '';
            const run = debounce(() => this.update({search: search.value.trim(), page: 0}), 300);
            search.addEventListener('input', run);
            this.region('searchform')?.addEventListener('submit', (event) => {
                event.preventDefault();
                this.update({search: search.value.trim(), page: 0});
            });
        }
        this.root.addEventListener('click', (event) => this.click(event));
        this.root.addEventListener('change', (event) => this.change(event));
        // The menu of the statuses closes with a click outside it or with Escape.
        const menu = this.root.querySelector('[data-menu="status"]');
        if (menu) {
            document.addEventListener('pointerdown', (event) => {
                if (!menu.contains(event.target)) {
                    this.openStatusMenu(false);
                }
            });
            menu.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !menu.querySelector('.dropdown-menu').hidden) {
                    event.preventDefault();
                    this.openStatusMenu(false);
                    menu.querySelector('[data-menu-toggle]').focus();
                }
            });
        }
        const perpage = this.region('perpage');
        if (perpage) {
            perpage.value = String(this.state.perpage);
        }
        // The page may come with its first rows already drawn, for instance so a guided tour finds them.
        const initial = this.root.dataset.initial;
        const pristine = Object.keys(this.options.defaults).every((key) => this.state[key] === this.options.defaults[key]);
        if (initial && pristine) {
            await this.render(JSON.parse(initial));
        } else {
            await this.load();
        }
    }

    /**
     * Changes the state and reloads the list.
     *
     * @param {Object} changes What changes in the state.
     * @returns {Promise<void>}
     */
    async update(changes) {
        const filterchanged = ['search', 'status', 'scope'].some((key) => key in changes && changes[key] !== this.state[key]);
        this.state = {...this.state, ...changes};
        // Changing the filter, the search or the page clears the selection, unless all of them were chosen on purpose.
        if (filterchanged || ('page' in changes && !this.allMatching)) {
            this.clearSelection();
        }
        this.writeState();
        await this.load();
    }

    /**
     * Loads the current page and draws it.
     *
     * @returns {Promise<void>}
     */
    async load() {
        const request = ++this.request;
        const pending = new Pending('local_extendednav/list:load');
        const skeleton = window.setTimeout(() => this.showSkeleton(), SKELETON_DELAY);
        this.region('tablewrap')?.setAttribute('aria-busy', 'true');
        try {
            const response = await Ajax.call([{methodname: this.options.method, args: this.options.args(this.state)}])[0];
            window.clearTimeout(skeleton);
            if (request !== this.request) {
                return;
            }
            // A page past the end, after rows went away, goes back to the last page that has rows.
            const {lastpage} = pageRange(this.state.page, this.state.perpage, response.total);
            if (this.state.page > lastpage) {
                this.state.page = lastpage;
                this.writeState();
                window.clearTimeout(skeleton);
                await this.load();
                return;
            }
            await this.render(response);
        } catch (error) {
            if (request === this.request) {
                await this.showError(error);
            }
        } finally {
            window.clearTimeout(skeleton);
            this.region('tablewrap')?.removeAttribute('aria-busy');
            pending.resolve();
        }
    }

    /**
     * Draws a response: rows, counts, pager and the empty state.
     *
     * @param {Object} response What the web service returned.
     * @returns {Promise<void>}
     */
    /**
     * Keeps the columns of the table still: the first time the table has rows, the width the browser gives each column is measured,
     * and every table drawn after it takes the same widths, so a filter, a sort or another page never moves them. They are
     * measured again when the box of the table has changed width.
     */
    keepColumns() {
        const box = this.region('tablewrap');
        const table = box?.querySelector('table');
        const heads = Array.from(table?.tHead?.rows[0]?.cells ?? []);
        if (!table || heads.length === 0) {
            return;
        }
        const same = this.columns && this.columns.widths.length === heads.length
            && Math.abs(this.columns.box - box.clientWidth) <= 1;
        if (!same) {
            this.columns = {box: box.clientWidth, widths: heads.map((head) => head.getBoundingClientRect().width)};
        }
        heads.forEach((head, index) => {
            head.style.width = `${this.columns.widths[index]}px`;
        });
        table.style.tableLayout = 'fixed';
    }

    async render(response) {
        this.region('tablewrap')?.removeAttribute('aria-busy');
        this.total = response.total;
        this.rows = response.rows;
        const context = this.options.context ? this.options.context(response, this.state) : {rows: response.rows};
        context.rows = context.rows.map((row) => ({...row, isselected: this.allMatching || this.selected.has(row.id)}));
        // The template draws the whole table, headers included; the control that had the focus gets it back.
        const focus = document.activeElement?.closest('[data-focuskey]')?.dataset.focuskey;
        const {html, js} = await Templates.renderForPromise(this.options.template, context);
        Templates.replaceNodeContents(this.region('tablewrap'), html, js);
        if (focus) {
            this.root.querySelector(`[data-focuskey="${focus}"]`)?.focus();
        }

        this.region('error').hidden = true;
        const empty = response.total === 0;
        this.region('tablewrap').hidden = empty;
        if (!empty) {
            this.keepColumns();
        }
        this.region('foot').hidden = empty;
        await this.renderEmpty(empty);
        this.renderCounts(response.counts || {});
        await this.renderPager();
        this.renderSort();
        this.renderSelection();
        this.options.onRender?.(response, this);
    }

    /**
     * Shows rows of the right height while a page loads.
     */
    showSkeleton() {
        const wrap = this.region('tablewrap');
        const columns = wrap.querySelectorAll('thead th').length || 4;
        const rows = Array.from({length: Math.min(this.state.perpage || 6, 6)}, () => {
            const row = document.createElement('tr');
            row.setAttribute('aria-hidden', 'true');
            for (let column = 0; column < columns; column++) {
                const placeholder = document.createElement('span');
                placeholder.className = 'placeholder';
                row.insertCell().append(placeholder);
            }
            return row;
        });
        const table = wrap.querySelector('table');
        if (table && table.tBodies[0]) {
            table.tBodies[0].replaceChildren(...rows);
        } else {
            const skeleton = document.createElement('table');
            skeleton.className = 'table';
            skeleton.createTBody().append(...rows);
            wrap.replaceChildren(skeleton);
        }
        wrap.hidden = false;
        this.region('empty').hidden = true;
    }

    /**
     * Shows the empty state: the first time, or after a search or a filter.
     *
     * @param {boolean} empty Whether there are no rows.
     * @returns {Promise<void>}
     */
    async renderEmpty(empty) {
        const region = this.region('empty');
        region.hidden = !empty;
        if (!empty) {
            region.replaceChildren();
            return;
        }
        const filtered = this.options.isFiltered
            ? this.options.isFiltered(this.state)
            : Boolean(this.state.search) || Boolean(this.state.status);
        const context = this.options.empty ? this.options.empty(filtered, this.state) : {};
        const {html, js} = await Templates.renderForPromise('local_extendednav/list/empty', {...context, filtered});
        Templates.replaceNodeContents(region, html, js);
    }

    /**
     * Shows an alert where the rows were, with a way to try again.
     *
     * @param {*} error What failed.
     * @returns {Promise<void>}
     */
    async showError(error) {
        const message = await getString('error_loading', 'local_extendednav', describeError(error));
        const {html, js} = await Templates.renderForPromise('local_extendednav/list/error', {message});
        const region = this.region('error');
        Templates.replaceNodeContents(region, html, js);
        region.hidden = false;
        this.region('tablewrap').hidden = true;
        this.region('foot').hidden = true;
        this.region('empty').hidden = true;
    }

    /**
     * The statuses chosen in the menu.
     *
     * @returns {string[]} Their keys.
     */
    chosenStatuses() {
        return String(this.state.status || '').split(',').filter(Boolean);
    }

    /**
     * Opens or closes the menu of the statuses.
     *
     * @param {boolean} show Whether it opens.
     */
    openStatusMenu(show) {
        const menu = this.root.querySelector('[data-menu="status"]');
        if (!menu) {
            return;
        }
        menu.querySelector('.dropdown-menu').hidden = !show;
        menu.querySelector('[data-menu-toggle]').setAttribute('aria-expanded', show ? 'true' : 'false');
    }

    /**
     * Writes the counts of the menu of the statuses, what the menu says, and the number of results.
     *
     * @param {Object} counts Rows of each status.
     */
    renderCounts(counts) {
        const chosen = this.chosenStatuses();
        const menu = this.root.querySelector('[data-menu="status"]');
        if (menu) {
            menu.querySelectorAll('[data-status]').forEach((box) => {
                box.checked = chosen.includes(box.dataset.status);
            });
            menu.querySelectorAll('[data-status-count]').forEach((count) => {
                if (counts[count.dataset.statusCount] !== undefined) {
                    count.textContent = counts[count.dataset.statusCount];
                }
            });
            // The button says what it filters, the one status chosen, or how many are.
            const label = menu.querySelector('[data-region="menu-label"]');
            let text = label.dataset.label;
            if (chosen.length === 1) {
                text = menu.querySelector(`[data-status="${chosen[0]}"]`)?.nextElementSibling?.textContent.trim() || text;
            } else if (chosen.length > 1) {
                text = `${label.dataset.label}: ${chosen.length}`;
            }
            label.textContent = text;
            const toggle = menu.querySelector('[data-menu-toggle]');
            toggle.classList.toggle('border-primary', chosen.length > 0);
            menu.querySelector('[data-menu-clear]').hidden = chosen.length === 0;
        }
        const reset = this.root.querySelector('[data-region="filters"] [data-action="clearfilters"]');
        if (reset) {
            reset.disabled = !this.state.search && chosen.length === 0;
        }
        const count = this.region('count');
        if (count) {
            getString('results_count', 'local_extendednav', this.total).then((text) => {
                count.textContent = text;
                return text;
            }).catch(() => null);
        }
    }

    /**
     * Draws the range and the arrows of the pager.
     *
     * @returns {Promise<void>}
     */
    async renderPager() {
        const {from, to} = pageRange(this.state.page, this.state.perpage, this.total);
        fitPageSizes(this.region('perpage'), this.state.perpage, this.total);
        const range = this.region('range');
        if (range) {
            range.textContent = await getString('pager_range', 'local_extendednav', {from, to, total: this.total});
        }
        const prev = this.root.querySelector('[data-action="prev"]');
        const next = this.root.querySelector('[data-action="next"]');
        if (prev) {
            prev.disabled = this.state.page === 0;
        }
        if (next) {
            next.disabled = to >= this.total;
        }
    }

    /**
     * Marks the sorted column.
     */
    renderSort() {
        this.root.querySelectorAll('th[data-sort]').forEach((header) => {
            if (header.dataset.sort === this.state.sort) {
                header.setAttribute('aria-sort', this.state.direction === 'desc' ? 'descending' : 'ascending');
            } else {
                header.removeAttribute('aria-sort');
            }
        });
    }

    /**
     * Answers the clicks inside the list.
     *
     * @param {Event} event The click.
     */
    click(event) {
        const menu = event.target.closest('[data-menu="status"]');
        if (menu && event.target.closest('[data-menu-toggle]')) {
            this.openStatusMenu(menu.querySelector('.dropdown-menu').hidden);
            return;
        }
        if (menu && event.target.closest('[data-menu-clear]')) {
            this.openStatusMenu(false);
            this.update({status: '', page: 0});
            menu.querySelector('[data-menu-toggle]').focus();
            return;
        }
        const sort = event.target.closest('th[data-sort] > .btn');
        if (sort) {
            const key = sort.closest('th').dataset.sort;
            const direction = this.state.sort === key && this.state.direction === 'asc' ? 'desc' : 'asc';
            this.update({sort: key, direction, page: 0});
            return;
        }
        const action = event.target.closest('[data-action]')?.dataset.action;
        if (action === 'prev') {
            this.update({page: Math.max(0, this.state.page - 1)});
        } else if (action === 'next') {
            this.update({page: this.state.page + 1});
        } else if (action === 'clearsearch' || action === 'clearfilters') {
            const search = this.region('search');
            if (search) {
                search.value = '';
            }
            const cleared = action === 'clearfilters' ? (this.options.cleared || {status: ''}) : {};
            this.options.onClear?.(cleared);
            this.update({search: '', ...cleared, page: 0});
        } else if (action === 'retry') {
            this.load();
        } else if (action === 'selectall') {
            this.allMatching = true;
            this.renderSelection();
        } else if (action === 'clearselection') {
            this.clearSelection();
        }
    }

    /**
     * Answers the changes of the rows per page and of the checkboxes.
     *
     * @param {Event} event The change.
     */
    change(event) {
        if (event.target.matches('[data-status]')) {
            const chosen = Array.from(this.root.querySelectorAll('[data-status]'))
                .filter((box) => box.checked).map((box) => box.dataset.status);
            this.update({status: chosen.join(','), page: 0});
        } else if (event.target.matches('[data-region="perpage"]')) {
            this.update({perpage: Number(event.target.value), page: 0});
        } else if (event.target.matches('[data-region="selectpage"]')) {
            this.rows.filter((row) => row.selectable).forEach((row) => {
                if (event.target.checked) {
                    this.selected.add(row.id);
                } else {
                    this.selected.delete(row.id);
                }
            });
            this.allMatching = false;
            this.renderSelection();
        } else if (event.target.matches('[data-region="selectrow"]')) {
            const id = Number(event.target.value);
            if (event.target.checked) {
                this.selected.add(id);
            } else {
                this.selected.delete(id);
                this.allMatching = false;
            }
            this.renderSelection();
        }
    }

    /**
     * Forgets the selection.
     */
    clearSelection() {
        this.selected.clear();
        this.allMatching = false;
        this.renderSelection();
    }

    /**
     * How many rows are selected.
     *
     * @returns {number}
     */
    selectionCount() {
        return this.allMatching ? this.total : this.selected.size;
    }

    /**
     * What an action on the selection reaches, as the web services take it.
     *
     * @returns {{delegationids: number[], all: boolean, filters: Object}}
     */
    selection() {
        return {
            delegationids: this.allMatching ? [] : Array.from(this.selected),
            all: this.allMatching,
            filters: this.options.filters ? this.options.filters(this.state) : {},
        };
    }

    /**
     * Ticks the checkboxes, tints the rows and shows the floating bar with the count.
     */
    renderSelection() {
        const selectable = this.rows.filter((row) => row.selectable);
        this.root.querySelectorAll('[data-region="selectrow"]').forEach((checkbox) => {
            const checked = this.allMatching || this.selected.has(Number(checkbox.value));
            checkbox.checked = checked;
            checkbox.closest('tr')?.classList.toggle('table-active', checked);
        });
        const page = this.region('selectpage');
        if (page) {
            const chosen = selectable.filter((row) => this.allMatching || this.selected.has(row.id)).length;
            page.checked = selectable.length > 0 && chosen === selectable.length;
            page.indeterminate = chosen > 0 && chosen < selectable.length;
            page.disabled = selectable.length === 0;
        }
        const bar = this.region('floatbar');
        if (!bar) {
            return;
        }
        const count = this.selectionCount();
        bar.hidden = count === 0;
        // The page leaves room at its foot for the floating bar, so the bar never covers the pager.
        this.root.style.paddingBottom = count > 0 ? '5.5rem' : '';
        if (count === 0) {
            return;
        }
        getString('selected_count', 'local_extendednav', count).then((text) => {
            this.region('selectedcount').textContent = text;
            return text;
        }).catch(() => null);
        const all = bar.querySelector('[data-action="selectall"]');
        const pagefull = selectable.length > 0 && selectable.every((row) => this.selected.has(row.id));
        all.hidden = this.allMatching || !pagefull || this.total <= selectable.length;
        getString('select_all_matching', 'local_extendednav', this.total).then((text) => {
            all.textContent = text;
            return text;
        }).catch(() => null);
    }
}
