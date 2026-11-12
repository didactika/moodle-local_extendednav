---
id: UC-001
name: manageNodes
title: Añadir, cambiar o eliminar un elemento
status: draft
origin: code
sourceOfTruth: code
---

# UC-001 Añadir, cambiar o eliminar un elemento — implementación

| Resumen | Requisitos | Análisis | Diseño | Implementación | Pruebas |
| --- | --- | --- | --- | --- | --- |
| [Casos de uso](../../../requirements/use-case-overview.svg) | [Requisitos](../../../requirements/use-cases/manageNodes/README.md) | [Análisis](../../../analysis/use-cases/manageNodes/README.md) | [Diseño](../../../design/use-cases/manageNodes/README.md) | **Implementación** | [Pruebas](../../../implementation/use-cases/manageNodes/README.md#pruebas) |

Diseño: [README](../../../design/use-cases/manageNodes/README.md)

## Código

| Elemento | Tipo | Ubicación | Verificado |
| --- | --- | --- | --- |
| `node_form` | formulario | `classes/form/node_form.php` | sí |
| `node_manager` | servicio | `classes/local/node_manager.php` | sí |
| Eventos de los nodos | eventos | `classes/event/` | sí |
| Pantalla del formulario | AMD | `amd/src/node_form.js` | sí |

## Pruebas

| Prueba | Cubre | Ubicación |
| --- | --- | --- |
| Crear, cambiar y eliminar nodos | flujo principal | `tests/local/node_manager_test.php` |
| Las reglas de validación | flujos alternativos, RN-002, RN-003 | `tests/local/node_validator_test.php` |
| Recorrido Behat del formulario | flujo principal | `tests/behat/node_form.feature` |
