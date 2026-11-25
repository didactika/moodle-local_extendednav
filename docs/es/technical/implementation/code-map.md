---
id: MAP
status: draft
origin: code
sourceOfTruth: code
---

# Mapa del código

| Elemento del modelo | Tipo | Ubicación en el código | Verificado |
| --- | --- | --- | --- |
| Node | tabla | `db/install.xml` (`local_extendednav`) | sí |
| Cambiar nodos | métodos | `classes/local/node/manager.php:104`, `:134`, `:159`, `:248` | sí |
| Validación | método | `classes/local/node/validator.php:46` | sí |
| Importación y exportación | métodos | `classes/local/io/importer.php:66`, `:110` | sí |
| YAML | clase | `classes/yaml.php` | sí |
| Quién ve un nodo | métodos | `classes/local/node/access.php:112`, `:166` | sí |
| Bloquear una página | métodos | `classes/local/navigation/access_guard.php:51`, `:79` | sí |
| Ganchos | clase | `classes/hooks.php`, `db/hooks.php` | sí |
| Menús | clases | `classes/menu/primary.php`, `classes/menu/usermenu.php`, `classes/menu/secondary.php`, `classes/menu/activity.php`, `classes/menu/admintree.php` | sí |
| Navegación de cada tipo de actividad | clases | `classes/menu/activity.php`, `classes/local/navigation/discovery.php`, `classes/task/discover_nodes.php` | sí |
| Páginas de una categoría, un curso o una actividad | archivos | `context.php`, `classes/local/navigation/context_scope.php`, `lib.php` | sí |
| Copia de seguridad y restauración | clases | `backup/moodle2/backup_local_extendednav_plugin.class.php`, `backup/moodle2/restore_local_extendednav_plugin.class.php` | sí |
| Paso desde la navegación única de las actividades | archivos | `db/upgradelib.php`, `classes/task/split_activity_navigation.php` | sí |
| Páginas de gestión | archivos | `manage.php`, `edit.php`, `import.php`, `export.php`, `activity.php` | sí |
| Servicios web | archivos | `classes/external/manage_navigation.php`, `classes/external/change_nodes.php`, `classes/external/get_nodes.php`, `classes/external/reorder_nodes.php`, `classes/external/search_export.php`, `db/services.php` | sí |
| Eventos | clases | `classes/event/` | sí |
| Capacidades del plugin | archivo | `db/access.php` | sí |
| Privacidad | clase | `classes/privacy/provider.php` (los cambios de cada usuario en su página de preferencias) | sí |
| Página de preferencias | clases | `classes/menu/preferences.php`, `classes/local/navigation/preferences_injector.php`, `lib.php` | sí |
| Depuración del registro de actividad | archivos | `classes/task/clean_activity_log.php`, `db/tasks.php`, `settings.php` | sí |
| Formulario de exportación y su búsqueda | archivos | `classes/form/export_form.php`, `classes/external/search_export.php`, `amd/src/form/export_search.js` | sí |
| Ajustes de menú de Moodle | clases | `classes/local/support/menu_config.php`, `classes/task/sync_custom_menu.php`, `classes/observer.php` | sí |
| Recorridos guiados | archivos | `tours/`, `lib.php` (`local_extendednav_import_tours`), `classes/task/import_tours.php` | sí |
