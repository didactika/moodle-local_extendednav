---
id: REQ
status: draft
origin: requirements
sourceOfTruth: model
---

# Requisitos

## Índice

- [Actores](./actors.md)
- [Catálogo de requisitos](./requirements.md)

## Casos de uso

| ID | Caso de uso | Actor | Paquete | Requisito | Análisis | Diseño |
| --- | --- | --- | --- | --- | --- | --- |
| UC-001 | [Añadir, cambiar o eliminar un elemento](./use-cases/manageNodes/README.md) | Administrador | Navegación | RF-001 | [ficha](../analysis/use-cases/manageNodes/README.md) | [ficha](../design/use-cases/manageNodes/README.md) |
| UC-002 | [Reordenar los elementos](./use-cases/reorderNodes/README.md) | Administrador | Navegación | RF-002 | [ficha](../analysis/use-cases/reorderNodes/README.md) | [ficha](../design/use-cases/reorderNodes/README.md) |
| UC-003 | [Importar y exportar elementos](./use-cases/importNodes/README.md) | Administrador | Navegación | RF-003 | [ficha](../analysis/use-cases/importNodes/README.md) | [ficha](../design/use-cases/importNodes/README.md) |
| UC-004 | [Mostrar los menús de un usuario](./use-cases/showMenus/README.md) | Sistema | Presentación | RF-004 | [ficha](../analysis/use-cases/showMenus/README.md) | [ficha](../design/use-cases/showMenus/README.md) |
| UC-005 | [Bloquear las páginas de los elementos ocultos](./use-cases/blockUrl/README.md) | Sistema | Presentación | RF-005 | [ficha](../analysis/use-cases/blockUrl/README.md) | [ficha](../design/use-cases/blockUrl/README.md) |

### Diagrama de casos de uso

![Casos de uso](./use-case-overview.svg)

### Por actor

#### Administrador

![Administrador](./use-cases-administrator.svg)

#### Sistema

![Sistema](./use-cases-system.svg)

## Use cases

<!-- rup-flow:use-cases:start -->

| ID | Use case | Actor | Package | Requirement | Analysis | Design | Implementation |
| --- | --- | --- | --- | --- | --- | --- | --- |
| UC-001 | [Añadir, cambiar o eliminar un elemento](./use-cases/manageNodes/README.md) | Administrator | Navigation | RF-001 | [card](../analysis/use-cases/manageNodes/README.md) | [card](../design/use-cases/manageNodes/README.md) | [card](../implementation/use-cases/manageNodes/README.md) |
| UC-002 | [Reordenar los elementos](./use-cases/reorderNodes/README.md) | Administrator | Navigation | RF-002 | [card](../analysis/use-cases/reorderNodes/README.md) | [card](../design/use-cases/reorderNodes/README.md) | [card](../implementation/use-cases/reorderNodes/README.md) |
| UC-003 | [Importar y exportar elementos](./use-cases/importNodes/README.md) | Administrator | Navigation | RF-003 | [card](../analysis/use-cases/importNodes/README.md) | [card](../design/use-cases/importNodes/README.md) | [card](../implementation/use-cases/importNodes/README.md) |
| UC-004 | [Mostrar los menús de un usuario](./use-cases/showMenus/README.md) | System | Display | RF-004 | [card](../analysis/use-cases/showMenus/README.md) | [card](../design/use-cases/showMenus/README.md) | [card](../implementation/use-cases/showMenus/README.md) |
| UC-005 | [Bloquear las páginas de los elementos ocultos](./use-cases/blockUrl/README.md) | System | Display | RF-005 | [card](../analysis/use-cases/blockUrl/README.md) | [card](../design/use-cases/blockUrl/README.md) | [card](../implementation/use-cases/blockUrl/README.md) |
| UC-006 | [Personalizar una navegación secundaria](./use-cases/customiseSecondaryNavigation/README.md) | Administrator | Navigation | RF-007 | [card](../analysis/use-cases/customiseSecondaryNavigation/README.md) | [card](../design/use-cases/customiseSecondaryNavigation/README.md) | [card](../implementation/use-cases/customiseSecondaryNavigation/README.md) |
| UC-007 | [Cambiar una navegación para una categoría o un curso](./use-cases/setScopeException/README.md) | Administrator | Navigation | RF-008 | [card](../analysis/use-cases/setScopeException/README.md) | [card](../design/use-cases/setScopeException/README.md) | [card](../implementation/use-cases/setScopeException/README.md) |

### Use-case diagram

![Use cases](./use-case-overview.svg)

### By actor

#### Administrator

![Administrator](./use-cases-administrator.svg)

#### System

![System](./use-cases-system.svg)

<!-- rup-flow:use-cases:end -->
