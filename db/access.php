<?php
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
 * Capability definitions for local_extendednav.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Eduardo Estrada <eduardodavidestrada1@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    // Manage the navigation nodes. Whoever holds it controls every user's menu
    // and which pages are blocked, so it is not granted to any role by default.
    'local/extendednav:manage' => [
        'riskbitmask' => RISK_CONFIG | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [],
    ],
    // See the page Navigation of a category, a course or an activity, and what it changes.
    'local/extendednav:viewcontext' => [
        'riskbitmask' => 0,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW],
    ],
    // Change the navigations of one category, course or activity, and of everything inside it: the exceptions of that
    // context. Each kind of change also needs its own capability below.
    'local/extendednav:managecontext' => [
        'riskbitmask' => RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW],
    ],
    // Show and hide the nodes of a context.
    'local/extendednav:changevisibility' => [
        'riskbitmask' => 0,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW],
    ],
    // Order the nodes of a context, choose their parent and where they go.
    'local/extendednav:changeorder' => [
        'riskbitmask' => 0,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW],
    ],
    // Rename the nodes of a context and change their tooltips.
    'local/extendednav:changetitles' => [
        'riskbitmask' => RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW],
    ],
    // Change the icons of the nodes of a context and how they open.
    'local/extendednav:changeicons' => [
        'riskbitmask' => 0,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW],
    ],
    // Change the addresses the nodes of a context lead to.
    'local/extendednav:changelinks' => [
        'riskbitmask' => RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW],
    ],
    // Restrict the nodes of a context to roles, languages and access conditions.
    'local/extendednav:changeconditions' => [
        'riskbitmask' => 0,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW],
    ],
    // Add nodes of their own to a context.
    'local/extendednav:addnodes' => [
        'riskbitmask' => RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW],
    ],
    // Delete the nodes added to a context, or take a context back to what the site sets.
    'local/extendednav:deletenodes' => [
        'riskbitmask' => 0,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => ['editingteacher' => CAP_ALLOW, 'manager' => CAP_ALLOW],
    ],
    // Order and hide the items of their own page Preferences, within what the site shows.
    'local/extendednav:personalise' => [
        'riskbitmask' => 0,
        'captype' => 'write',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => ['user' => CAP_ALLOW],
    ],
];
