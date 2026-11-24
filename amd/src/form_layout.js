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
 * Normalises responsive Moodle form controls used in compact plugin surfaces.
 *
 * @module     local_extendednav/form_layout
 * @author     Hector Arrechea <hectorlazaroarrechea@gmail.com>
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const DATE_CONTROLS = '.fdate_selector, .fdate_time_selector';
const NARROW_VIEWPORT = '(max-width: 575.98px)';
const INLINE_MARGIN_CLASSES = [
    'mx-1',
    'mx-2',
    'ms-1',
    'ms-2',
    'me-1',
    'me-2',
    'ml-1',
    'ml-2',
    'mr-1',
    'mr-2',
];

/**
 * Applies Bootstrap utility classes without CSS specificity overrides.
 *
 * @param {HTMLElement} root Form container to normalise.
 */
export const normalise = root => {
    const shouldWrap = window.matchMedia(NARROW_VIEWPORT).matches;

    root.querySelectorAll(DATE_CONTROLS).forEach(controls => {
        controls.classList.toggle('flex-wrap', shouldWrap);
        controls.classList.toggle('flex-nowrap', !shouldWrap);
        Array.from(controls.children).forEach(control => control.classList.remove(...INLINE_MARGIN_CLASSES));
    });

    root.querySelectorAll('#fgroup_id_buttonar').forEach(buttonGroup => {
        buttonGroup.classList.remove('mb-3');
        buttonGroup.classList.add('mb-1', 'mt-2');
        buttonGroup.querySelectorAll('.fitem.mb-3').forEach(button => {
            button.classList.remove('mb-3');
        });
        Array.from(buttonGroup.children)
            .find(child => child.classList.contains('col-form-label'))
            ?.remove();
    });
};
