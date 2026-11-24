---
id: UC-001
name: manageNodes
title: Añadir, cambiar o eliminar un elemento
status: draft
origin: code
sourceOfTruth: code
---

# UC-001 Añadir, cambiar o eliminar un elemento — realización del diseño

| Resumen | Requisitos | Análisis | Diseño | Implementación | Pruebas |
| --- | --- | --- | --- | --- | --- |
| [Casos de uso](../../../requirements/use-case-overview.svg) | [Requisitos](../../../requirements/use-cases/manageNodes/README.md) | [Análisis](../../../analysis/use-cases/manageNodes/README.md) | **Diseño** | [Implementación](../../../implementation/use-cases/manageNodes/README.md) | [Pruebas](../../../implementation/use-cases/manageNodes/README.md#pruebas) |

Análisis: [README](../../../analysis/use-cases/manageNodes/README.md)

## Elementos

| Elemento de diseño | Tipo | Código |
| --- | --- | --- |
| `validator::validate` | servicio | `classes/local/node/validator.php:46` |
| `manager::create` | servicio | `classes/local/node/manager.php:104` |
| `manager::update` | servicio | `classes/local/node/manager.php:134` |
| `manager::delete` | servicio | `classes/local/node/manager.php:159` |

## Secuencia

![Secuencia](./sequence.svg)

## Contratos

| Operación | Entrada | Salida | Errores |
| --- | --- | --- | --- |
| `manager::create(record, events)` | el elemento | el registro guardado con su id | elemento no válido |

## Datos que toca

| Tabla o entidad | Operación |
| --- | --- |
| `local_extendednav` | inserción, actualización, borrado |
| `logstore_standard_log` | inserción (un evento por cambio) |
