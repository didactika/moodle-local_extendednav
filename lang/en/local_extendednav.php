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
 * File definition for local_extendednav.php.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Extended Navigation';
$string['enable_plugin'] = 'Enable Plugin';
$string['enable_plugin_desc'] = 'Master switch to completely enable or disable this plugin without uninstalling it.';
$string['manage_nodes'] = 'Manage Navigation Nodes';
$string['manage_nodes_desc'] = 'Add, edit, or remove custom navigation nodes, and override existing Moodle nodes. Rules are evaluated from top to bottom (Priority Hierarchy).';
$string['add_node'] = 'Add new node overriding';
$string['edit_node'] = 'Edit node';
$string['delete_node'] = 'Delete node';
$string['delete_node_confirm'] = 'Are you sure you want to delete this navigation rule?';

$string['nodekey'] = 'Node Key';
$string['nodekey_desc'] = 'Unique identifier. To override an existing node use its core key (e.g., myhome, mycourses, siteadminnode).';
$string['nodekey_help'] = 'The unique identifier for this menu item. To modify an existing Moodle menu, enter its exact internal key (for example: myhome, mycourses, siteadminnode). For a brand new menu, invent a new lowercase key without spaces (e.g., custom_tutorials).';

$string['title'] = 'Display Title';
$string['title_desc'] = 'The text that will be displayed. Leave blank if overriding an existing node to keep defaults.';
$string['title_help'] = 'The name displayed in the navigation bar. If you are modifying a native Moodle menu (like "mycourses") and want to keep its original translated name, leave this completely blank. Multilanguage filters (MLANG) are fully supported here.';

$string['url'] = 'URL';
$string['url_desc'] = 'The link destination. Leave blank if overriding an existing node to keep defaults.';
$string['url_help'] = 'The web address where the menu will redirect. For native nodes, leave it blank to conserve original routing. For new menus, use local paths like "/course/view.php?id=2" or absolute links.';

$string['newwindow'] = 'Open in new window (target="_blank")';
$string['newwindow_help'] = 'Check this box if you want the hyperlink to open in a new browser tab instead of navigating away from the current page.';

$string['icon'] = 'Icon Name';
$string['icon_desc'] = 'Moodle pix icon name, e.g. "i/dashboard" or "i/home". Leave blank for none or to keep default.';
$string['icon_help'] = 'The internal Moodle icon name (e.g., "i/home", "i/settings", "fa-book"). <br>• <b>Leave completely blank</b>: Retains the native icon (if it had one) or adds no icon.<br>• <b>Type "null" or "none"</b>: Explicitly removes and strips any icon from this menu, avoiding blank empty spaces in themes that force icons.';

$string['visibility'] = 'Visibility';
$string['visibility_desc'] = 'Control who can see this node.';
$string['vis_all'] = 'Visible to everyone';
$string['vis_roles'] = 'Restricted to specific roles';
$string['vis_hidden'] = 'Completely hidden';

$string['roles'] = 'Allowed Roles';
$string['roles_desc'] = 'Select the roles that are allowed to see this item if restricted visibility is selected.';
$string['roles_help'] = 'Choose the specific user roles permitted to see this menu. Only requested if "Restricted to specific roles" is selected.';

$string['parentkey'] = 'Parent Node Key (Dropdown)';
$string['parentkey_desc'] = 'If you want this node to be a sub-item in a dropdown, provide the key of the parent node here.';
$string['parentkey_help'] = 'Nested menus feature. Form a dropdown menu by typing the unique Core Key of a parent menu here. (e.g. create a main node called "resources", then create a second node and type "resources" in this field to nest it inside). Leave blank for normal top-level navigation.';
$string['err_parent_self'] = 'A node cannot be its own parent.';
$string['err_parent_thirdlevel'] = 'Moodle Top-Bar UI only supports 1 level of dropdowns. The parent you chose is already inside another menu.';
$string['err_parent_haschildren'] = 'This node already contains sub-menus. You cannot nest a dropdown inside another dropdown.';

$string['err_admin_hide'] = 'Anti-lockout: Site Administration menu cannot be hidden or restricted.';
$string['err_admin_child'] = 'Anti-lockout: Site Administration menu cannot be placed inside a dropdown.';
$string['err_admin_parent'] = 'Anti-lockout: Site Administration menu cannot be used as a dropdown container as it would break its link.';

$string['beforekey'] = 'Insert Before Key';
$string['beforekey_desc'] = 'If you want this custom item to appear before an existing menu item, type its key. Leave empty to append at the end. Not applicable when overriding.';
$string['beforekey_help'] = 'If you want to move or reorder this menu, write the Node Key of the menu that should appear immediately AFTER it. (e.g., Type "myhome" to place this menu before the Dashboard). Leave empty to put it at the very end.';

$string['blockedurls'] = 'Blocked URLs (Intercept Access)';
$string['blockedurls_desc'] = 'If this node is Hidden for a user, you can type paths here (comma-separated, e.g. /my/courses.php) that will be actively blocked and redirected to the Dashboard if they try to access them directly.';
$string['blockedurls_help'] = 'Paths to block.';

$string['order'] = 'Priority / Order';

$string['actions'] = 'Actions';
$string['none'] = 'None';
$string['fallbackurl'] = 'Fallback Redirect URL';
$string['fallbackurl_desc'] = 'If a user hits a blocked URL, and the native fallbacks (Dashboard/Home) are also completely restricted by rules, they will be forcibly redirected here (e.g. /login/index.php). <br><br><b>Leave blank</b> to show Moodle\'s native fatal permission error screen instead (Recommended).';
$string['err_duplicate_key'] = 'This Node Key is already in use. Please edit the existing rule instead to prevent duplicates and conflicts.';
$string['coreoverridealert'] = '<strong>Notice:</strong> You are overriding a native Moodle core node. By doing so, the Title and URL fields become optional (if you leave them blank, Moodle will retain the original values for this node).';

$string['opt_none_root'] = '- None / Main Root -';
$string['opt_end_list'] = '- Default position / End of list -';
$string['opt_native'] = 'Native: {$a->text} ({$a->key})';
$string['opt_plugin'] = 'Plugin: {$a->title} ({$a->key})';
$string['opt_invalid_has_submenus'] = '- Invalid: This element already contains sub-menus -';
$string['opt_invalid_admin_submenu'] = '- Invalid: The admin panel cannot be a sub-menu -';
$string['none_title'] = 'Untitled';

$string['filters'] = 'Filters';
$string['search_free'] = 'Global Search (Key or Title)';
$string['search_parent'] = 'Hidden Parent Key (Hierarchy)';
$string['apply'] = 'Apply';
$string['reset'] = 'Reset';
$string['positioning'] = 'Positioning';
$string['filter_active'] = 'Active Filter';
$string['native_string'] = 'native string';
$string['native_route'] = 'native route';
$string['inside'] = 'inside:';
$string['before'] = 'before:';
$string['err_restricted_page'] = 'This page is restricted by the navigation hierarchy and role rules set for your account.';
$string['err_duplicate_key_alert'] = '<strong>Conflict:</strong> This Node Key is already in use by another custom rule. You cannot use duplicate keys.';

$string['yamlconfigfile'] = 'YAML Configuration File';
$string['import_overwrite'] = 'Overwrite existing nodes (Delete all current)';
$string['import_append'] = 'Append / Update (Merge with current)';
$string['importmode'] = 'Import Action';
$string['import_nodes'] = 'Import Nodes';
$string['export'] = 'Export YAML';
$string['import'] = 'Import YAML';
$string['err_invalid_yaml_file'] = 'Invalid file uploaded. Please upload a valid .yml or .yaml file.';
$string['err_invalid_yaml_format'] = 'The YAML file could not be parsed due to formatting errors.';
$string['err_invalid_yaml_structure'] = 'The YAML structure is invalid. It must contain a "nodes" array.';
$string['import_success'] = 'Nodes successfully imported from YAML.';

$string['yamlconfigfile_help'] = 'Upload a valid extended navigation YAML file to import nodes into the database.';
$string['importmode_help'] = 'Append will merge the YAML files with your existing entries. Overwrite will destroy all your existing links and replace them entirely with the YAML records.';

$string['export_all'] = 'Export All Configurations (Full Schema)';
$string['export_selected_mode'] = 'Export Selected Configurations Only';
$string['exportmode_label'] = 'Export Mode';
$string['select_nodes'] = 'Select Nodes';
$string['select_nodes_export'] = 'Nodes to Export';
$string['no_nodes_export'] = 'There are no nodes available to export.';
$string['export_nodes'] = 'Export YAML Configuration';

$string['nodes_selected'] = 'node(s) selected';

$string['bulk_delete'] = 'Delete Selected';
$string['bulk_delete_confirm'] = 'Are you sure you want to permanently delete these nodes? This action cannot be undone.';
$string['bulk_deleted'] = 'Nodes successfully deleted.';

$string['export_selected'] = 'Export Selected';
$string['export_all_btn'] = 'Export All YAML';

$string['import_instructions_title'] = 'YAML Structure Guidelines';
$string['import_instructions_desc'] = 'To import nodes, your YAML file must contain a root <strong>nodes</strong> key followed by a list of navigation objects. You can safely download and edit the example template below.';
$string['download_example'] = 'Download Example';
