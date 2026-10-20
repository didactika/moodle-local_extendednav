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
 * Shows the icon and the link display marker of primary navigation items that Moodle prints as text.
 *
 * From Moodle 5.3 the primary navigation is drawn by React, which prints the text of top-level items (and of
 * parents moved into the "More" menu) as plain text, so the icon and marker the plugin puts in the text would
 * show as code. This module finds those text nodes and shows the plugin's elements instead. Only the plugin's
 * own elements are kept, with their known attributes; anything else stays text.
 *
 * React keeps owning its text node: it is emptied, not removed, and the elements are added before it. When
 * React writes the text again, for example when items move into the "More" menu, it is shown again.
 *
 * @module     local_extendednav/primary_labels
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Config from 'core/config';

/** @var {String} Marks the elements this module adds. */
const ADDED = 'data-extnav-label';

/** @var {RegExp} Text that holds the plugin's markup: an icon or the link display marker. */
const MARKUP = /<(i|img)\s[^>]*class="[^"]*\bicon\b|<span\s[^>]*class="local-extendednav-display"/;

/** @var {Object} Attributes kept for each element the plugin puts in item text. */
const ALLOWED = {
    I: ['class', 'aria-hidden', 'title'],
    IMG: ['class', 'src', 'alt', 'title', 'aria-hidden', 'role'],
    SPAN: ['class', 'hidden', 'data-extnav-display', 'data-extnav-width', 'data-extnav-height'],
};

let initialised = false;

/**
 * Whether an element is one the plugin puts in item text.
 *
 * @param {Element} element Parsed element.
 * @returns {Boolean}
 */
const isPluginElement = (element) => {
    if (element.tagName === 'SPAN') {
        return element.classList.contains('local-extendednav-display');
    }
    if (element.tagName === 'IMG') {
        // Image icons of Moodle are served by this site.
        const src = element.getAttribute('src') || '';
        return element.classList.contains('icon') && src.startsWith(Config.wwwroot + '/');
    }
    return element.tagName === 'I' && element.classList.contains('icon');
};

/**
 * Builds a clean copy of a plugin element, with its known attributes only.
 *
 * @param {Element} element Parsed element.
 * @returns {Element}
 */
const copyElement = (element) => {
    const copy = document.createElement(element.tagName.toLowerCase());
    ALLOWED[element.tagName].forEach((name) => {
        if (element.hasAttribute(name)) {
            copy.setAttribute(name, element.getAttribute(name));
        }
    });
    copy.setAttribute(ADDED, '');
    return copy;
};

/**
 * Turns the text into nodes: the plugin's elements, and everything else as text.
 *
 * The text is parsed in a template, where nothing runs or loads.
 *
 * @param {String} text Text that holds the plugin's markup.
 * @returns {Node[]}
 */
const buildNodes = (text) => {
    const template = document.createElement('template');
    template.innerHTML = text;
    return Array.from(template.content.childNodes).map((node) => {
        if (node.nodeType === Node.ELEMENT_NODE && ALLOWED[node.tagName] && isPluginElement(node)) {
            return copyElement(node);
        }
        const textnode = document.createElement('span');
        textnode.setAttribute(ADDED, '');
        textnode.textContent = node.textContent;
        return textnode;
    });
};

/**
 * Shows the plugin's elements of one text node.
 *
 * @param {Text} textnode Text node printed by React.
 */
const repair = (textnode) => {
    const parent = textnode.parentNode;
    if (!parent) {
        return;
    }
    // Remove what was added for an earlier text of this node.
    let previous = textnode.previousSibling;
    while (previous && previous.nodeType === Node.ELEMENT_NODE && previous.hasAttribute(ADDED)) {
        const node = previous;
        previous = previous.previousSibling;
        node.remove();
    }
    buildNodes(textnode.data).forEach((node) => parent.insertBefore(node, textnode));
    textnode.data = '';
};

/**
 * Repairs every text node of the navigation that holds the plugin's markup.
 *
 * @param {Element} root Primary navigation.
 */
const repairAll = (root) => {
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
        acceptNode: (node) => (MARKUP.test(node.data) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_SKIP),
    });
    const textnodes = [];
    while (walker.nextNode()) {
        textnodes.push(walker.currentNode);
    }
    textnodes.forEach(repair);
};

/**
 * Sets up the primary navigation.
 */
export const init = () => {
    if (initialised) {
        return;
    }
    initialised = true;

    document.querySelectorAll('.primary-navigation').forEach((root) => {
        repairAll(root);
        let pending = false;
        new MutationObserver(() => {
            // Repair once per frame, however many changes React makes.
            if (!pending) {
                pending = true;
                requestAnimationFrame(() => {
                    pending = false;
                    repairAll(root);
                });
            }
        }).observe(root, {childList: true, subtree: true, characterData: true});
    });
};
