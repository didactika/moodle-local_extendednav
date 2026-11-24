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
 * The search of the selects of the page that chooses what to export: as the user types, it finds the navigations, kinds of
 * activity, users, courses or categories that match, a few at a time. The select says what it finds in its data-kind.
 *
 * @module     local_extendednav/form/export_search
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Finds the results of a query.
 *
 * @param {string} selector Selector of the original select.
 * @param {string} query What the user typed.
 * @param {Function} success Receives the results.
 * @param {Function} failure Receives the error.
 */
export const transport = (selector, query, success, failure) => {
    const kind = document.querySelector(selector)?.dataset.kind || '';
    Ajax.call([{methodname: 'local_extendednav_search_export', args: {kind, query}}])[0]
        .then((response) => success(response.results))
        .catch(failure);
};

/**
 * Turns the results into the options of the select.
 *
 * @param {string} selector Selector of the original select.
 * @param {Object[]} results The results, each with value and label.
 * @returns {Object[]}
 */
export const processResults = (selector, results) => results.map((result) => ({value: result.value, label: result.label}));
