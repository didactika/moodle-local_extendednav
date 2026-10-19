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
 * English strings for local_extendednav.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['action_created'] = 'Created';
$string['action_deleted'] = 'Deleted';
$string['action_updated'] = 'Updated';
$string['activity_log'] = 'Activity log';
$string['add_node'] = 'Add node';
$string['apply'] = 'Apply';
$string['back_to_nodes'] = 'Back to navigation';
$string['badge_blocked'] = 'URL blocked';
$string['badge_unavailable'] = 'Currently unavailable';
$string['badge_unavailable_help'] = 'Moodle is not adding this item right now: it may be turned off in Site administration > Appearance > Navigation, removed by the theme, or come from a plugin that is no longer installed. The plugin does not add it either. You can delete it; it comes back if Moodle adds it again.';
$string['blockaccess'] = 'Block URL access';
$string['blockaccess_help'] = 'When checked, users who cannot see this item are also kept away from its URL: if they open it directly, they are sent to the Dashboard, then the Site home, then My courses, then the default home page, whichever they are allowed. If none of these pages is allowed, they see a page explaining that the page is not available.

Only URLs of this site can be blocked. Languages never affect blocking, and site administrators are never blocked.

Blocking only applies to pages that show the site header, so it shapes navigation; it does not replace permissions.';
$string['bulk_delete'] = 'Delete selected';
$string['bulk_delete_confirm'] = 'Delete the selected nodes? Core Moodle nodes are kept, unless they are currently unavailable. This cannot be undone.';
$string['cachedef_nodes'] = 'Navigation nodes of every menu';
$string['clear_filters'] = 'Clear';
$string['conflict_handling'] = 'Existing nodes';
$string['conflict_handling_help'] = 'What to do with nodes in the file whose menu and key already exist on this site: keep the existing node, or replace it with the one in the file.';
$string['conflict_overwrite'] = 'Replace them with the file';
$string['conflict_skip'] = 'Keep them';
$string['coreoverridealert'] = 'This is a core Moodle node. Title and URL are optional: leave them empty to keep the Moodle values, which stay translated.';
$string['delete_node_confirm'] = 'Delete this node? This cannot be undone.';
$string['display'] = 'Display';
$string['display_current'] = 'Current window';
$string['display_help'] = 'How the link opens: in the current window, in a new window, or in a pop-up window of the size below. Items with submenu items always open their submenu instead.';
$string['display_new'] = 'New window';
$string['display_popup'] = 'Pop-up';
$string['doc_example_format'] = 'Example';
$string['doc_example_tip'] = 'Every field except nodekey is optional. Empty fields of a core node keep the Moodle values.';
$string['doc_f_blockaccess'] = '1 to block the URL for users who cannot see the node, 0 otherwise. Only used when visibility is 0 or 2.';
$string['doc_f_display'] = 'How the link opens: 0 = current window, 1 = new window, 2 = pop-up.';
$string['doc_f_icon'] = 'FontAwesome class (for example "fa-book") or Moodle icon (for example "i/home"). "none" removes the icon.';
$string['doc_f_langs'] = 'Comma-separated language codes (for example "es,pt_br"). The node is only shown in the menu for these languages. Empty means every language.';
$string['doc_f_menu'] = 'Menu of the node: "primary" or "usermenu". Defaults to the menu being imported.';
$string['doc_f_nodekey'] = 'Key of the node, unique within its menu. Use a core key (for example "myhome") to change a core node.';
$string['doc_f_order'] = 'Position of the node in its menu, 1 being the first. Exported files list every position. On import, nodes without it keep their place, or go last when they are new.';
$string['doc_f_parentkey'] = 'Key of the parent node, to show this node in its submenu.';
$string['doc_f_popupheight'] = 'Height of the pop-up window in pixels, from 100 to 9999. Used when display is 2.';
$string['doc_f_popupwidth'] = 'Width of the pop-up window in pixels, from 100 to 9999. Used when display is 2.';
$string['doc_f_roles'] = 'Comma-separated role shortnames (for example "editingteacher,teacher"). Used when visibility is 2.';
$string['doc_f_title'] = 'Display text. Empty on a core node keeps the Moodle title.';
$string['doc_f_tooltip'] = 'Text shown when hovering over the link.';
$string['doc_f_url'] = 'Destination: a path of this site (for example "/course/view.php?id=2") or a full address.';
$string['doc_f_visibility'] = '1 = visible to everyone, 2 = restricted to the roles listed, 0 = not visible.';
$string['doc_field'] = 'Field';
$string['doc_fields_title'] = 'Fields';
$string['doc_intro'] = 'Import files are YAML files with a "nodes" list. Each entry describes one node with the fields below.';
$string['documentation'] = 'YAML format';
$string['download_example'] = 'Download an example file';
$string['edit_node'] = 'Edit node';
$string['enable_plugin'] = 'Enable';
$string['enable_plugin_desc'] = 'Apply the navigation nodes and URL blocking. When disabled, Moodle\'s navigation is shown unchanged and no page is blocked.';
$string['entries_count'] = '{$a} entries';
$string['entries_count_one'] = '1 entry';
$string['err_admin_child'] = 'Site administration cannot be placed in a submenu.';
$string['err_admin_hide'] = 'Site administration cannot be hidden, restricted, blocked or limited to some languages.';
$string['err_admin_parent'] = 'Site administration cannot have submenu items.';
$string['err_duplicate_key'] = 'Another node of this menu already uses this key.';
$string['err_duplicate_key_alert'] = 'Another node of this menu already uses this key.';
$string['err_fallbacks_blocked'] = 'This would block the Dashboard, the Site home, My courses and the default home page for every user, leaving users with no page to land on. Unblock at least one of them.';
$string['err_icon_invalid'] = 'Use a FontAwesome class (for example "fa-book"), a Moodle icon (for example "i/home") or "none".';
$string['err_import_duplicate'] = 'This node appears more than once in the file.';
$string['err_import_nodekey'] = 'The entry has no nodekey.';
$string['err_import_order'] = 'The order must be a whole number from 1.';
$string['err_invalid_display'] = 'Choose how the link opens: 0, 1 or 2.';
$string['err_invalid_list'] = 'Use only letters, numbers, underscores and hyphens, separated by commas.';
$string['err_invalid_menu'] = 'Unknown menu. Use "primary" or "usermenu".';
$string['err_invalid_nodekey'] = 'Use only letters, numbers, underscores and hyphens.';
$string['err_invalid_visibility'] = 'Unknown visibility. Use 0, 1 or 2.';
$string['err_invalid_yaml_file'] = 'The file could not be read. Upload a .yaml or .yml file.';
$string['err_invalid_yaml_format'] = 'The file is not valid YAML.';
$string['err_invalid_yaml_structure'] = 'The file must contain a "nodes" list.';
$string['err_parent_haschildren'] = 'This node has submenu items, so it cannot be placed in a submenu.';
$string['err_parent_missing'] = 'There is no node with the key "{$a}" in this menu.';
$string['err_parent_self'] = 'A node cannot be its own parent.';
$string['err_parent_thirdlevel'] = 'The chosen parent is already in a submenu. Menus only have one level of submenus.';
$string['err_popup_size'] = 'Enter a whole number from {$a->min} to {$a->max}.';
$string['err_restricted_page'] = 'The page you opened is not available for your account, and neither are the Dashboard, the Site home or My courses. Contact the site administrator if you think this is a mistake.';
$string['err_text_html'] = 'HTML is not allowed here.';
$string['err_title_usermenu_chars'] = 'User menu titles cannot contain "|" or line breaks.';
$string['err_url_forbidden'] = 'This kind of URL is not allowed.';
$string['err_url_invalid'] = 'Enter a valid URL, for example "/course/view.php?id=2" or "https://example.com".';
$string['eventnodecreated'] = 'Navigation node created';
$string['eventnodedeleted'] = 'Navigation node deleted';
$string['eventnodeupdated'] = 'Navigation node updated';
$string['export_all_btn'] = 'Export all';
$string['export_selected'] = 'Export selected';
$string['extendednav:manage'] = 'Manage the navigation nodes';
$string['filter_action'] = 'Filter by action';
$string['filter_action_any'] = 'All actions';
$string['filter_datefrom'] = 'From';
$string['filter_dateto'] = 'Until';
$string['filter_invalidperiod'] = 'The end date cannot be earlier than the start date.';
$string['filter_parent'] = 'Filter by parent key';
$string['filter_parent_any'] = 'Parent key';
$string['filter_visibility'] = 'Filter by visibility';
$string['icon'] = 'Icon';
$string['icon_help'] = 'A FontAwesome class (for example "fa-book" or "fa-solid fa-house") or a Moodle icon (for example "i/home"). Leave empty to keep the default, or type "none" to remove the icon. In the user menu only FontAwesome icons are supported.';
$string['import'] = 'Import';
$string['import_entry'] = 'Entry {$a}';
$string['import_failed'] = 'Nothing was imported. Fix these problems in the file and try again:';
$string['import_instructions_desc'] = 'The file must contain a "nodes" list. Every node is checked first; if any node is invalid, nothing is imported.';
$string['import_instructions_title'] = 'Before importing';
$string['import_nodes'] = 'Import nodes';
$string['import_success'] = 'Import finished: {$a->created} created, {$a->updated} replaced, {$a->skipped} kept.';
$string['langs'] = 'Languages';
$string['langs_all'] = 'All languages';
$string['langs_help'] = 'Show this item in the menu only to users whose current language is selected. Leave empty to show it in every language. A language also covers its regional variants: Spanish (es) includes Spanish - Mexico (es_mx).

Languages only control whether the item appears in the menu. They never affect access to its URL, because users can change their own language at any time. Access is controlled by Visibility, Allowed roles and "Block URL access".

To translate an item, create one node per language with the same URL, roles and visibility.';
$string['langs_hidden_notice'] = 'This item is hidden from everyone, so the selected languages have no effect on the menu. They are kept in case you make the item visible again. Access to the URL depends only on "Block URL access".';
$string['manage_nodes'] = 'Manage navigation';
$string['manage_nodes_desc'] = 'Add items to the primary navigation and the user menu, and change, reorder, nest or hide Moodle\'s own items.';
$string['menu_type'] = 'Menu';
$string['movenode'] = 'Move {$a}';
$string['native_cannot_delete'] = 'Core Moodle nodes cannot be deleted. Set their visibility to "Not visible" to hide them.';
$string['nativenodeinfo'] = 'Core Moodle node';
$string['no_nodes_export'] = 'There are no nodes to export.';
$string['node_saved'] = 'Node saved.';
$string['nodekey'] = 'Node key';
$string['nodekey_help'] = 'Key of the item, unique within its menu. To change a Moodle item, use its key: home, myhome, mycourses or siteadminnode in the primary navigation. For a new item, choose a new key using letters, numbers, underscores and hyphens, for example "help_es".';
$string['nodes_count'] = '{$a} items';
$string['nodes_count_one'] = '1 item';
$string['nodes_deleted'] = '{$a} node(s) deleted.';
$string['nodes_selected'] = 'node(s) selected';
$string['none_title'] = 'Untitled';
$string['opt_invalid_admin_submenu'] = 'Site administration is always a top-level item, so it cannot be placed in a submenu.';
$string['opt_invalid_has_submenus'] = 'This node has submenu items, so it cannot be placed in a submenu itself.';
$string['opt_native'] = '{$a->text} ({$a->key})';
$string['opt_none_root'] = '- None (top level) -';
$string['opt_plugin'] = '{$a->title} ({$a->key})';
$string['order'] = 'Order';
$string['parentkey'] = 'Parent';
$string['parentkey_help'] = 'Show this item in the submenu of another item. Only top-level items can be parents, and an item with submenu items cannot itself be placed in a submenu.';
$string['parenturlunused'] = 'This node has submenu items, so it opens its submenu instead of following its URL. The URL is still used for blocking.';
$string['pluginname'] = 'Extended navigation';
$string['popupheight'] = 'Pop-up height (in pixels)';
$string['popupwidth'] = 'Pop-up width (in pixels)';
$string['primary_nav'] = 'Primary navigation';
$string['privacy:metadata'] = 'The Extended navigation plugin does not store any personal data. Changes to navigation nodes are recorded in the standard log.';
$string['restricted_title'] = 'Page not available';
$string['roles'] = 'Allowed roles';
$string['roles_help'] = 'Users with at least one of these roles, in any context, see the item. Every logged-in user also holds the default role for authenticated users.';
$string['search_placeholder'] = 'Search by title, node key or URL...';
$string['settings_page'] = 'Settings';
$string['submenuof'] = 'Submenu of: {$a}';
$string['title'] = 'Title';
$string['title_help'] = 'Text shown in the menu. On a Moodle item, leave it empty to keep the Moodle title, which is translated. A language string can be used as "identifier,component", for example "profile,moodle".';
$string['tooltip'] = 'Tooltip';
$string['tooltip_help'] = 'Text shown when hovering over the link. Optional.';
$string['unknown_lang'] = 'Not installed: {$a}';
$string['unknown_role'] = 'Unknown role: {$a}';
$string['url'] = 'URL';
$string['url_help'] = 'Where the item leads: a path of this site, such as "/course/view.php?id=2", or a full address. On a Moodle item, leave it empty to keep the Moodle URL.';
$string['user_menu'] = 'User menu';
$string['view_documentation'] = 'See the YAML format';
$string['vis_all'] = 'Visible to everyone';
$string['vis_hidden'] = 'Not visible';
$string['vis_roles'] = 'Restricted';
$string['visibility'] = 'Visibility';
$string['visibility_help'] = 'Who sees the item in the menu: everyone, only users with the allowed roles, or nobody. With "Block URL access", users who cannot see the item cannot open its URL either.';
$string['warn_fallbacks_blocked_for_some'] = 'The Dashboard, the Site home, My courses and the default home page are all blocked for users without some roles. Those users will see an error page instead of being redirected.';
$string['warn_unknown_lang'] = 'The language "{$a}" is not installed.';
$string['warn_unknown_role'] = 'There is no role with the shortname "{$a}".';
$string['yamlconfigfile'] = 'YAML file';
$string['yamlconfigfile_help'] = 'A .yaml or .yml file with a "nodes" list, like the files created by "Export all".';
