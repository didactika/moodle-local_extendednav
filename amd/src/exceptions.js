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
 * The overview of the categories and courses that change a navigation: a search, the menu of the kinds and the list, page
 * by page, as every list of the design system.
 *
 * @module     local_extendednav/exceptions
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {get_strings as getStrings} from 'core/str';
import List from 'local_extendednav/local/list';
import {init as fitTables} from 'local_extendednav/local/fit_tables';

/**
 * Starts the overview.
 *
 * @param {string} selector Selector of the element of the list.
 * @returns {Promise<List>}
 */
export const init = async(selector) => {
    fitTables();
    const root = document.querySelector(selector);
    const [title, text, open] = await getStrings([
        {key: 'exceptions_empty_title', component: 'local_extendednav'},
        {key: 'exceptions_empty_text', component: 'local_extendednav'},
        {key: 'exceptions_open', component: 'local_extendednav'},
    ]);
    const list = new List(root, {
        method: 'local_extendednav_get_exceptions',
        template: 'local_extendednav/exceptions/table',
        defaults: {search: '', kind: '', page: 0, perpage: 12},
        args: (state) => ({search: state.search, kind: state.kind, page: state.page, perpage: state.perpage || 12}),
        context: (response) => ({
            rows: response.rows.map((row) => ({
                ...row,
                iscourse: row.kind === 'course',
                openlabel: open.replace('{$a}', row.name),
            })),
        }),
        empty: () => ({icon: 'i/withsubcat', title, text}),
    });
    await list.init();
    return list;
};
