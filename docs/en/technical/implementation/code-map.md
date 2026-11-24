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
| Menus | classes | `classes/menu/primary.php`, `classes/menu/usermenu.php` | yes |
| Management pages | files | `manage.php`, `edit.php`, `import.php`, `export.php`, `activity.php` | yes |
| Web services | files | `classes/external/reorder_nodes.php`, `classes/external/get_icons.php` | yes |
| Events | classes | `classes/event/` | yes |
| Capabilities of the plugin | file | `db/access.php` | yes |
| Privacy | class | `classes/privacy/provider.php` | yes |
