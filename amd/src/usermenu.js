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
 * Real-time DOM interception for User Menu "immortal" items.
 *
 * @module     local_extendednav/usermenu
 * @copyright  2026 Didactika
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = (hiddenItemsStr) => {
    if (!hiddenItemsStr) {
        return;
    }
    const hiddenItems = hiddenItemsStr.split(',');

    const hideMenuItems = () => {
        const menu = document.getElementById('user-action-menu');
        if (!menu) {
            return;
        }

        const links = menu.querySelectorAll('a.dropdown-item');
        links.forEach(link => {
            const href = link.getAttribute('href') || '';
            hiddenItems.forEach(pattern => {
                if (href.includes(pattern)) {
                    link.style.display = 'none';
                    const pref = link.previousElementSibling;
                    if (pref && pref.classList.contains('dropdown-divider')) {
                        pref.style.display = 'none';
                    }
                }
            });
        });
    };

    hideMenuItems();

    const trigger = document.getElementById('user-menu-toggle') || document.querySelector('.usermenu .dropdown-toggle');
    if (trigger) {
        trigger.addEventListener('click', hideMenuItems);
    }
};
