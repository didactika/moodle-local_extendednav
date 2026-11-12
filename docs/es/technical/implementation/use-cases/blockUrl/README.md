---
id: UC-005
name: blockUrl
title: Bloquear las páginas de los elementos ocultos
status: draft
origin: code
sourceOfTruth: code
---

# UC-005 Bloquear las páginas de los elementos ocultos — implementación

| Resumen | Requisitos | Análisis | Diseño | Implementación | Pruebas |
| --- | --- | --- | --- | --- | --- |
| [Casos de uso](../../../requirements/use-case-overview.svg) | [Requisitos](../../../requirements/use-cases/blockUrl/README.md) | [Análisis](../../../analysis/use-cases/blockUrl/README.md) | [Diseño](../../../design/use-cases/blockUrl/README.md) | **Implementación** | [Pruebas](../../../implementation/use-cases/blockUrl/README.md#pruebas) |

Diseño: [README](../../../design/use-cases/blockUrl/README.md)

## Código

| Elemento | Tipo | Ubicación | Verificado |
| --- | --- | --- | --- |
| `access_guard` | servicio | `classes/local/access_guard.php` | sí |
| `url_helper` | servicio | `classes/local/url_helper.php` | sí |
| Página restringida | página | `restricted.php` | sí |

## Pruebas

| Prueba | Cubre | Ubicación |
| --- | --- | --- |
| Bloqueo y páginas de reserva | flujo principal, RN-004, RN-005 | `tests/local/access_guard_test.php` |
| Coincidencia de URL | RN-004 | `tests/local/url_helper_test.php` |
| Recorrido Behat del bloqueo | flujo principal | `tests/behat/block_access.feature` |
