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
| Cambiar nodos | métodos | `classes/local/node_manager.php:104`, `:134`, `:159`, `:248` | sí |
| Validación | método | `classes/local/node_validator.php:46` | sí |
| Importación y exportación | métodos | `classes/local/node_importer.php:66`, `:110` | sí |
| YAML | clase | `classes/yaml.php` | sí |
| Quién ve un nodo | métodos | `classes/local/node_access.php:112`, `:166` | sí |
| Bloquear una página | métodos | `classes/local/access_guard.php:51`, `:79` | sí |
| Ganchos | clase | `classes/hooks.php`, `db/hooks.php` | sí |
| Menús | clases | `classes/menu/primary.php`, `classes/menu/usermenu.php` | sí |
| Páginas de gestión | archivos | `manage.php`, `edit.php`, `import.php`, `export.php`, `activity.php` | sí |
| Servicios web | archivos | `classes/external/reorder_nodes.php`, `classes/external/get_icons.php` | sí |
| Eventos | clases | `classes/event/` | sí |
| Capacidades del plugin | archivo | `db/access.php` | sí |
| Privacidad | clase | `classes/privacy/provider.php` | sí |
