---
id: UC-004
name: showMenus
title: Mostrar los menús de un usuario
status: draft
origin: code
sourceOfTruth: code
---

# UC-004 Mostrar los menús de un usuario — implementación

| Resumen | Requisitos | Análisis | Diseño | Implementación | Pruebas |
| --- | --- | --- | --- | --- | --- |
| [Casos de uso](../../../requirements/use-case-overview.svg) | [Requisitos](../../../requirements/use-cases/showMenus/README.md) | [Análisis](../../../analysis/use-cases/showMenus/README.md) | [Diseño](../../../design/use-cases/showMenus/README.md) | **Implementación** | [Pruebas](../../../implementation/use-cases/showMenus/README.md#pruebas) |

Diseño: [README](../../../design/use-cases/showMenus/README.md)

## Código

| Elemento | Tipo | Ubicación | Verificado |
| --- | --- | --- | --- |
| `hooks` | funciones de gancho | `classes/hooks.php` | sí |
| `primary` | menú | `classes/menu/primary.php` | sí |
| `usermenu` | menú | `classes/menu/usermenu.php` | sí |
| Registro de ganchos | archivo | `db/hooks.php` | sí |

## Pruebas

| Prueba | Cubre | Ubicación |
| --- | --- | --- |
| La navegación principal | flujo principal | `tests/menu/primary_test.php` |
| El menú de usuario | flujo principal | `tests/menu/usermenu_test.php` |
| Quién ve un nodo | RN-001 | `tests/local/node_access_test.php` |
| Recorridos Behat de los menús | flujo principal | `tests/behat/primary_navigation.feature`, `tests/behat/user_menu.feature` |
