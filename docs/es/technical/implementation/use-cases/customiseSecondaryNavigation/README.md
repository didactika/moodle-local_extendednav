---
id: UC-006
name: customiseSecondaryNavigation
title: Personalizar una navegación secundaria
status: draft
origin: code
sourceOfTruth: code
---

# UC-006 Personalizar una navegación secundaria — implementación

| Resumen | Requisitos | Análisis | Diseño | Implementación | Pruebas |
| --- | --- | --- | --- | --- | --- |
| [Casos de uso](../../../requirements/use-case-overview.svg) | [Requisitos](../../../requirements/use-cases/customiseSecondaryNavigation/README.md) | [Análisis](../../../analysis/use-cases/customiseSecondaryNavigation/README.md) | [Diseño](../../../design/use-cases/customiseSecondaryNavigation/README.md) | **Implementación** | [Pruebas](../../../implementation/use-cases/customiseSecondaryNavigation/README.md#tests) |

Diseño: [README](../../../design/use-cases/customiseSecondaryNavigation/README.md)

## Código

| Elemento | Tipo | Ubicación | Verificado |
| --- | --- | --- | --- |
| `get_nodes`, `change_nodes` | servicio web | `classes/external/get_nodes.php`, `classes/external/change_nodes.php` | sí |
| `nodes` | clase | `classes/local/listing/nodes.php` | sí |
| `secondary` | menú | `classes/menu/secondary.php` | sí |
| `injector` | clase | `classes/local/navigation/injector.php` | sí |
| Página de gestión | AMD y plantilla | `amd/src/manage.js`, `templates/manage/page.mustache`, `templates/list/nodes_table.mustache` | sí |

## Pruebas

| Prueba | Cubre | Ubicación |
| --- | --- | --- |
| La lista, sus filtros, cuentas y páginas | flujo principal | `tests/local/listing/nodes_test.php` |
| Los servicios de la página | flujo principal | `tests/external/node_services_test.php` |
| Mover nodos | flujo principal | `tests/local/node/scope_test.php` |
| Un nodo visto por primera vez | RN-009 | `tests/local/listing/nodes_test.php` |
| Un nodo oculto y otro renombrado en un curso | flujo principal | `tests/behat/injector.feature` |
| Recorrido Behat de la página de gestión | flujo principal | `tests/behat/manage_page.feature` |
