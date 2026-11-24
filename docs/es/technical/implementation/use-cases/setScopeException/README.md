---
id: UC-007
name: setScopeException
title: Cambiar una navegación para una categoría o un curso
status: draft
origin: code
sourceOfTruth: code
---

# UC-007 Cambiar una navegación para una categoría o un curso — implementación

| Resumen | Requisitos | Análisis | Diseño | Implementación | Pruebas |
| --- | --- | --- | --- | --- | --- |
| [Casos de uso](../../../requirements/use-case-overview.svg) | [Requisitos](../../../requirements/use-cases/setScopeException/README.md) | [Análisis](../../../analysis/use-cases/setScopeException/README.md) | [Diseño](../../../design/use-cases/setScopeException/README.md) | **Implementación** | [Pruebas](../../../implementation/use-cases/setScopeException/README.md#tests) |

Diseño: [README](../../../design/use-cases/setScopeException/README.md)

## Código

| Elemento | Tipo | Ubicación | Verificado |
| --- | --- | --- | --- |
| `get_scopes` | servicio web | `classes/external/get_scopes.php` | sí |
| `node_repository` | clase | `classes/local/node_repository.php` | sí |
| `node_manager` | clase | `classes/local/node_manager.php` | sí |
| Menú del ámbito | AMD | `amd/src/manage.js` | sí |

## Pruebas

| Prueba | Cubre | Ubicación |
| --- | --- | --- |
| Gana el ámbito más cercano | RN-012 | `tests/local/node_scope_test.php` |
| Una excepción deja el sitio como está | flujo principal | `tests/local/node_scope_test.php` |
| Mover en un ámbito | RN-013 | `tests/local/node_scope_test.php` |
| Eliminar un nodo | RN-014 | `tests/local/node_scope_test.php` |
| Los ámbitos de una navegación | flujo principal | `tests/external/node_services_test.php` |
| Exportar e importar una excepción | flujo principal | `tests/local/node_importer_scope_test.php` |
