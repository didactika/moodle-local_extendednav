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
