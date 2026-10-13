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
 * Usermenu enhancements for extended navigation.
 *
 * @module     local_extendednav/usermenu
 * @copyright  2026 Didactika
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';

/**
 * Helper to build an icon element without innerHTML string templates.
 *
 * @param {string} iconClass FontAwesome icon class name.
 * @returns {HTMLElement} The created icon element.
 */
const createIconElement = (iconClass) => {
    const iconEl = document.createElement('i');
    iconEl.className = `icon fa ${iconClass} fa-fw`;
    iconEl.setAttribute('aria-hidden', 'true');
    return iconEl;
};

export const init = () => {
    if (!window.customUsermenuTargetBlankInjected) {
        window.customUsermenuTargetBlankInjected = true;
        document.addEventListener('click', e => {
            const t = e.target.closest('a[target="_blank"]');
            if (t && t.closest('.usermenu')) {
                e.stopPropagation();
            }
        }, true);
    }

    const sources = document.querySelectorAll('template[data-region="local-extendednav-submenu"]');
    const carousel = document.querySelector('.usermenu #usermenu-carousel .carousel-inner');
    const main = document.querySelector('.usermenu #carousel-item-main');

    if (!carousel || !main) {
        return;
    }

    // Fix missing FontAwesome icons and new window targets for root items.
    const nodeFixes = document.querySelectorAll('template[data-region="local-extendednav-node-fix"]');
    nodeFixes.forEach(fix => {
        const url = fix.dataset.url;
        const title = fix.dataset.title;
        const icon = fix.dataset.icon;
        const newWindow = fix.dataset.newwindow;

        let link = null;
        const links = Array.from(main.querySelectorAll('a'));

        if (url) {
            try {
                link = main.querySelector(`a[href*="${url}"]`);
            } catch (e) {
                // Ignore invalid selectors.
            }
            if (!link) {
                link = links.find(l => l.href && l.href.includes(url));
            }
        }

        if (!link && title) {
            link = links.find(l => l.textContent.trim() === title);
        }

        if (link) {
            if (newWindow) {
                link.setAttribute('target', '_blank');
            }
            if (!icon) {
                return;
            }
            const existingIcon = link.querySelector('.icon');
            if (existingIcon) {
                if (existingIcon.tagName === 'IMG') {
                    const iconEl = createIconElement(icon);
                    const wrapper = existingIcon.closest('.carousel-navigation-link-icon');
                    if (wrapper) {
                        wrapper.replaceChildren(iconEl);
                    } else {
                        existingIcon.replaceWith(iconEl);
                    }
                } else if (existingIcon.tagName === 'I') {
                    existingIcon.className = `icon fa ${icon} fa-fw`;
                }
            } else {
                const spanWrapper = document.createElement('span');
                spanWrapper.className = 'carousel-navigation-link-icon';
                spanWrapper.appendChild(createIconElement(icon));
                link.prepend(spanWrapper);
            }
        }
    });

    sources.forEach(source => {
        const triggerUrl = source.dataset.triggerUrl;
        const panelId = source.dataset.panelId;

        if (carousel.querySelector('#' + panelId)) {
            return;
        }

        let trigger = null;
        const allLinks = Array.from(main.querySelectorAll('a'));

        if (triggerUrl) {
            try {
                trigger = main.querySelector(`a[href*="${triggerUrl}"]`);
            } catch (e) {
                // Ignore invalid selectors.
            }
            if (!trigger) {
                trigger = allLinks.find(l => l.href && l.href.includes(triggerUrl));
            }
        }

        if (!trigger) {
            const triggerTitle = source.dataset.triggerTitle;
            if (triggerTitle) {
                trigger = allLinks.find(l => l.textContent.trim() === triggerTitle);
            }
        }

        if (!trigger) {
            return;
        }

        trigger.href = '#';
        trigger.classList.add('carousel-navigation-link');
        trigger.dataset.carouselTargetId = panelId;

        carousel.append(source.content.cloneNode(true));
        source.remove();

        const openSubmenu = event => {
            event.preventDefault();
            event.stopPropagation();
            const panel = carousel.querySelector('#' + panelId);
            const index = Array.from(carousel.children).indexOf(panel);
            $(carousel.closest('#usermenu-carousel')).carousel(index);
        };
        trigger.addEventListener('click', openSubmenu);
        trigger.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                openSubmenu(event);
            }
        });

        // Add back button listener manually to avoid Moodle's right arrow CSS from .carousel-navigation-link.
        const backBtn = carousel.querySelector('#' + panelId + ' .local-extendednav-back-btn');
        if (backBtn) {
            const closeSubmenu = event => {
                event.preventDefault();
                event.stopPropagation();
                const index = Array.from(carousel.children).indexOf(main);
                $(carousel.closest('#usermenu-carousel')).carousel(index);
            };
            backBtn.addEventListener('click', closeSubmenu);
            backBtn.addEventListener('keydown', event => {
                if (event.key === 'Enter' || event.key === ' ') {
                    closeSubmenu(event);
                }
            });
        }
    });
};
