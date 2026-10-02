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
 * Manage page bulk actions.
 *
 * @module     local_extendednav/manage
 * @copyright  2026 Didactika
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = () => {
    const selectAll = document.getElementById('select-all-nodes');
    const checkboxes = document.querySelectorAll('.node-checkbox');
    const bulkBar = document.getElementById('bulk-actions-bar');
    const bulkCount = document.getElementById('bulk-count');

    const updateBulkBar = () => {
        let count = 0;
        let allChecked = true;

        for (let i = 0; i < checkboxes.length; i++) {
            if (checkboxes[i].checked) {
                count++;
            } else {
                allChecked = false;
            }
        }

        if (selectAll && checkboxes.length > 0) {
            selectAll.checked = allChecked;
        }

        if (bulkBar && bulkCount) {
            if (count > 0) {
                bulkCount.textContent = count;
                bulkBar.classList.remove('d-none');
            } else {
                bulkBar.classList.add('d-none');
            }
        }
    };

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const isChecked = this.checked;
            for (let i = 0; i < checkboxes.length; i++) {
                checkboxes[i].checked = isChecked;
            }
            updateBulkBar();
        });
    }

    for (let i = 0; i < checkboxes.length; i++) {
        checkboxes[i].addEventListener('change', updateBulkBar);
    }
};
