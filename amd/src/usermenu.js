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
 * Adds icons, tooltips, how links open and submenus to the user menu.
 *
 * Moodle builds the user menu from "text|url" lines only, so the server prints
 * template elements describing the extras and this module applies them.
 *
 * @module     local_extendednav/usermenu
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import {init as initLinkDisplay} from 'local_extendednav/link_display';

let initialised = false;

/**
 * Builds a FontAwesome icon element.
 *
 * @param {String} iconClass FontAwesome classes.
 * @returns {HTMLElement}
 */
const createIcon = (iconClass) => {
    const icon = document.createElement('i');
    icon.className = `icon fa ${iconClass} fa-fw`;
    icon.setAttribute('aria-hidden', 'true');
    return icon;
};

/**
 * Normalises a URL so links can be compared: origin, path without "index.php", and query.
 *
 * @param {String} href URL.
 * @returns {String|null}
 */
const normaliseUrl = (href) => {
    if (!href) {
        return null;
    }
    try {
        const url = new URL(href, window.location.href);
        return url.origin + url.pathname.replace(/\/index\.php$/, '/') + url.search;
    } catch (error) {
        return null;
    }
};

/**
 * Finds the user menu link of an item, by URL first and then by text.
 *
 * @param {HTMLElement} container Main panel of the user menu.
 * @param {String} url Absolute URL of the item.
 * @param {String} title Text of the item.
 * @returns {HTMLAnchorElement|null}
 */
const findLink = (container, url, title) => {
    const links = Array.from(container.querySelectorAll('a'));
    const target = normaliseUrl(url);
    if (target) {
        const byUrl = links.find((link) => normaliseUrl(link.getAttribute('href')) === target);
        if (byUrl) {
            return byUrl;
        }
    }
    if (title) {
        return links.find((link) => link.textContent.trim() === title) || null;
    }
    return null;
};

/**
 * Applies the icon, tooltip and link display settings of a top-level item.
 *
 * @param {HTMLElement} main Main panel of the user menu.
 * @param {HTMLTemplateElement} fix Template element describing the item.
 */
const applyFix = (main, fix) => {
    const link = findLink(main, fix.dataset.url, fix.dataset.title);
    if (!link) {
        return;
    }
    ['extnavDisplay', 'extnavWidth', 'extnavHeight'].forEach((name) => {
        if (fix.dataset[name]) {
            link.dataset[name] = fix.dataset[name];
        }
    });
    if (fix.dataset.extnavDisplay === 'new') {
        link.setAttribute('target', '_blank');
        link.setAttribute('rel', 'noopener');
    }
    if (fix.dataset.tooltip) {
        link.setAttribute('title', fix.dataset.tooltip);
    }
    if (!fix.dataset.icon) {
        return;
    }
    const existing = link.querySelector('.icon');
    if (existing && existing.tagName === 'I') {
        existing.className = `icon fa ${fix.dataset.icon} fa-fw`;
        return;
    }
    const wrapper = existing ? existing.closest('.carousel-navigation-link-icon') : null;
    if (wrapper) {
        wrapper.replaceChildren(createIcon(fix.dataset.icon));
    } else if (existing) {
        existing.replaceWith(createIcon(fix.dataset.icon));
    } else {
        const span = document.createElement('span');
        span.className = 'carousel-navigation-link-icon';
        span.appendChild(createIcon(fix.dataset.icon));
        link.prepend(span);
    }
};

/**
 * Turns a top-level item into the trigger of a submenu panel.
 *
 * @param {HTMLElement} carousel Carousel of the user menu.
 * @param {HTMLElement} main Main panel of the user menu.
 * @param {HTMLTemplateElement} source Template element holding the panel.
 */
const addSubmenu = (carousel, main, source) => {
    const panelId = source.dataset.panelId;
    if (carousel.querySelector('#' + CSS.escape(panelId))) {
        return;
    }
    const trigger = findLink(main, source.dataset.triggerUrl, source.dataset.triggerTitle);
    if (!trigger) {
        return;
    }

    trigger.href = '#';
    trigger.classList.add('carousel-navigation-link');
    trigger.dataset.carouselTargetId = panelId;
    carousel.append(source.content.cloneNode(true));
    source.remove();

    const show = (panel) => {
        const index = Array.from(carousel.children).indexOf(panel);
        $(carousel.closest('#usermenu-carousel')).carousel(index);
    };
    const onKey = (handler) => (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            handler(event);
        }
    };

    const open = (event) => {
        event.preventDefault();
        event.stopPropagation();
        show(carousel.querySelector('#' + CSS.escape(panelId)));
    };
    trigger.addEventListener('click', open);
    trigger.addEventListener('keydown', onKey(open));

    // The back button is handled here to avoid the arrow Moodle adds to .carousel-navigation-link.
    const back = carousel.querySelector('#' + CSS.escape(panelId) + ' .local-extendednav-back-btn');
    if (back) {
        const close = (event) => {
            event.preventDefault();
            event.stopPropagation();
            show(main);
        };
        back.addEventListener('click', close);
        back.addEventListener('keydown', onKey(close));
    }
};

/**
 * Applies the user menu extras.
 */
export const init = () => {
    if (initialised) {
        return;
    }
    initialised = true;

    initLinkDisplay();

    const carousel = document.querySelector('.usermenu #usermenu-carousel .carousel-inner');
    const main = document.querySelector('.usermenu #carousel-item-main');
    if (!carousel || !main) {
        return;
    }

    document.querySelectorAll('template[data-region="local-extendednav-node-fix"]').forEach((fix) => applyFix(main, fix));
    document.querySelectorAll('template[data-region="local-extendednav-submenu"]').forEach(
        (source) => addSubmenu(carousel, main, source)
    );
};
