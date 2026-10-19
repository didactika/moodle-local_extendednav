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
 * Opens menu links in a new window or a pop-up, as Moodle's URL resource does.
 *
 * A link carries data-extnav-display ("new" or "popup", with data-extnav-width and data-extnav-height),
 * either itself or on a hidden marker inside its text: the primary navigation template prints no link
 * attributes. Clicks are handled on the document, so the copies the "More" menu and the mobile drawer
 * make of the links work too.
 *
 * @module     local_extendednav/link_display
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTOR = '[data-extnav-display]';

/** @var {String} Window features of Moodle's URL resource pop-ups, after the size. */
const POPUP_FEATURES = 'toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes';

let initialised = false;

/**
 * Returns the element holding the display settings of a link.
 *
 * @param {HTMLAnchorElement} link The link.
 * @returns {HTMLElement|null}
 */
const getSource = (link) => (link.matches(SELECTOR) ? link : link.querySelector(SELECTOR));

/**
 * Makes a link open in a new window.
 *
 * @param {HTMLAnchorElement} link The link.
 */
const openInNewWindow = (link) => {
    link.setAttribute('target', '_blank');
    link.setAttribute('rel', 'noopener');
};

/**
 * Opens a link in a pop-up window, unless the user asked for a tab or a window with a modifier key.
 *
 * @param {MouseEvent} event The click.
 * @param {HTMLAnchorElement} link The link.
 * @param {HTMLElement} source Element holding the size.
 */
const openPopup = (event, link, source) => {
    if (event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) {
        return;
    }
    event.preventDefault();
    const width = parseInt(source.dataset.extnavWidth, 10);
    const height = parseInt(source.dataset.extnavHeight, 10);
    window.open(link.href, '', `width=${width},height=${height},${POPUP_FEATURES}`);
};

/**
 * Sets up the links.
 */
export const init = () => {
    if (initialised) {
        return;
    }
    initialised = true;

    document.querySelectorAll(SELECTOR).forEach((source) => {
        const link = source.closest('a');
        if (link && source.dataset.extnavDisplay === 'new') {
            openInNewWindow(link);
        }
    });

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a[href]');
        const source = link ? getSource(link) : null;
        if (!source) {
            return;
        }
        if (source.dataset.extnavDisplay === 'new') {
            openInNewWindow(link);
        } else if (source.dataset.extnavDisplay === 'popup') {
            openPopup(event, link, source);
        }
        // Keep the user menu's own click handling from swallowing the click.
        if (link.closest('.usermenu')) {
            event.stopPropagation();
        }
    }, true);
};
