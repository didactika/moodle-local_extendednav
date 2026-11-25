---
id: MAP
status: draft
origin: code
sourceOfTruth: code
---

# Code map

| Model element | Kind | Code location | Verified |
| --- | --- | --- | --- |
| Node | table | `db/install.xml` (`local_extendednav`) | yes |
| Changing nodes | methods | `classes/local/node/manager.php:104`, `:134`, `:159`, `:248` | yes |
| Validation | method | `classes/local/node/validator.php:46` | yes |
| Import and export | methods | `classes/local/io/importer.php:66`, `:110` | yes |
| YAML | class | `classes/yaml.php` | yes |
| Who sees a node | methods | `classes/local/node/access.php:112`, `:166` | yes |
| Block a page | methods | `classes/local/navigation/access_guard.php:51`, `:79` | yes |
| Hooks | class | `classes/hooks.php`, `db/hooks.php` | yes |
| Menus | classes | `classes/menu/primary.php`, `classes/menu/usermenu.php`, `classes/menu/secondary.php`, `classes/menu/activity.php`, `classes/menu/admintree.php` | yes |
| Navigation of each kind of activity | classes | `classes/menu/activity.php`, `classes/local/navigation/discovery.php`, `classes/task/discover_nodes.php` | yes |
| Pages of a category, a course or an activity | files | `context.php`, `classes/local/navigation/context_scope.php`, `lib.php` | yes |
| Backup and restore | classes | `backup/moodle2/backup_local_extendednav_plugin.class.php`, `backup/moodle2/restore_local_extendednav_plugin.class.php` | yes |
| Change from the single navigation of the activities | files | `db/upgradelib.php`, `classes/task/split_activity_navigation.php` | yes |
| Management pages | files | `manage.php`, `edit.php`, `import.php`, `export.php`, `activity.php` | yes |
| Web services | files | `classes/external/manage_navigation.php`, `classes/external/change_nodes.php`, `classes/external/get_nodes.php`, `classes/external/reorder_nodes.php`, `classes/external/search_export.php`, `db/services.php` | yes |
| Events | classes | `classes/event/` | yes |
| Capabilities of the plugin | file | `db/access.php` | yes |
| Privacy | class | `classes/privacy/provider.php` (the changes of each user to their Preferences page) | yes |
| Preferences page | classes | `classes/menu/preferences.php`, `classes/local/navigation/preferences_injector.php`, `lib.php` | yes |
| Activity log clean-up | files | `classes/task/clean_activity_log.php`, `db/tasks.php`, `settings.php` | yes |
| Export form and its search | files | `classes/form/export_form.php`, `classes/external/search_export.php`, `amd/src/form/export_search.js` | yes |
| Menu settings of Moodle | classes | `classes/local/support/menu_config.php`, `classes/task/sync_custom_menu.php`, `classes/observer.php` | yes |
| Guided tours | files | `tours/`, `lib.php` (`local_extendednav_import_tours`), `classes/task/import_tours.php` | yes |
