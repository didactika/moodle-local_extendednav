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
 * The order of the rows of the table of nodes, by dragging. A row is dragged by its handle among its siblings; when it is
 * selected, every selected sibling goes with it, in the order they have. A line over or under a row shows where they land. On
 * the handle, the arrow keys move the row one place, so the keyboard does what the mouse does.
 *
 * @module     local_extendednav/manage/dragging
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Marks where the dragged rows would land.
 *
 * @param {HTMLTableRowElement} row The row under the pointer.
 * @param {string} where before, after, or empty to clear it.
 */
const mark = (row, where) => {
    if (where) {
        row.dataset.drop = where;
    } else {
        delete row.dataset.drop;
    }
};

/**
 * Lets the rows of a table be dragged.
 *
 * @param {HTMLElement} wrap The box of the table, drawn again after each change.
 * @param {Object} actions What a move does.
 * @param {Function} actions.selectedKeys Returns the keys of the rows selected.
 * @param {Function} actions.place Places nodes: given their keys, the sibling they go before and the one they go after.
 * @param {Function} actions.move Moves one node one place: given its key and -1 or 1.
 */
export const init = (wrap, {selectedKeys, place, move}) => {
    const body = wrap?.querySelector('tbody');
    if (!body) {
        return;
    }
    let moving = [];
    const clear = () => body.querySelectorAll('[data-drop]').forEach((row) => mark(row, ''));
    const accepts = (row) => moving.length > 0 && !moving.includes(row.dataset.key)
        && row.dataset.parent === body.querySelector(`tr[data-key="${moving[0]}"]`)?.dataset.parent && !row.dataset.virtual;

    // The rows that move with one: the selected siblings in the order of the table, when it is one of them; else only it.
    const together = (row) => {
        const chosen = selectedKeys();
        if (!chosen.includes(row.dataset.key)) {
            return [row.dataset.key];
        }
        return Array.from(body.rows)
            .filter((other) => chosen.includes(other.dataset.key) && other.dataset.parent === row.dataset.parent)
            .map((other) => other.dataset.key);
    };

    Array.from(body.rows).forEach((row) => {
        const handle = row.querySelector('[data-region="handle"]');
        if (!handle) {
            return;
        }
        handle.addEventListener('pointerdown', () => {
            row.draggable = true;
        });
        handle.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
                event.preventDefault();
                move(row.dataset.key, event.key === 'ArrowUp' ? -1 : 1);
            }
        });
        row.addEventListener('dragstart', (event) => {
            moving = together(row);
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', moving.join(','));
            moving.forEach((key) => body.querySelector(`tr[data-key="${key}"]`)?.classList.add('opacity-50'));
        });
        row.addEventListener('dragend', () => {
            row.draggable = false;
            body.querySelectorAll('tr.opacity-50').forEach((other) => other.classList.remove('opacity-50'));
            moving = [];
            clear();
        });
        row.addEventListener('dragover', (event) => {
            if (!accepts(row)) {
                return;
            }
            event.preventDefault();
            const box = row.getBoundingClientRect();
            clear();
            mark(row, event.clientY > box.top + box.height / 2 ? 'after' : 'before');
        });
        row.addEventListener('drop', (event) => {
            if (!accepts(row)) {
                return;
            }
            event.preventDefault();
            const after = row.dataset.drop === 'after';
            const keys = moving;
            clear();
            place(keys, after ? '' : row.dataset.key, after ? row.dataset.key : '');
        });
    });
};
