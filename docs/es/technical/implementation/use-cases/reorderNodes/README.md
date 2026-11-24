---
id: UC-002
name: reorderNodes
title: Reordenar los elementos
status: draft
origin: code
sourceOfTruth: code
---

# UC-002 Reordenar los elementos — implementación

| Resumen | Requisitos | Análisis | Diseño | Implementación | Pruebas |
| --- | --- | --- | --- | --- | --- |
| [Casos de uso](../../../requirements/use-case-overview.svg) | [Requisitos](../../../requirements/use-cases/reorderNodes/README.md) | [Análisis](../../../analysis/use-cases/reorderNodes/README.md) | [Diseño](../../../design/use-cases/reorderNodes/README.md) | **Implementación** | [Pruebas](../../../implementation/use-cases/reorderNodes/README.md#pruebas) |

Diseño: [README](../../../design/use-cases/reorderNodes/README.md)

## Código

| Elemento | Tipo | Ubicación | Verificado |
| --- | --- | --- | --- |
| `reorder_nodes` | servicio web | `classes/external/reorder_nodes.php` | sí |
| Pantalla de gestión | AMD | `amd/src/manage.js` | sí |
| `change_nodes::move` | servicio web | `classes/external/change_nodes.php` | sí |
| `manager::move` | método | `classes/local/node/manager.php` | sí |

## Pruebas

| Prueba | Cubre | Ubicación |
| --- | --- | --- |
| Reordenar mediante el servicio | flujo principal | `tests/external/reorder_nodes_test.php` |
| Agrupación y posiciones | RN-007 | `tests/local/node/manager_test.php` |
| Recorrido Behat de la página de gestión | flujo principal | `tests/behat/manage_page.feature` |
