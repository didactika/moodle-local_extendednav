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

<!-- rup-flow:use-cases:start -->

| ID | Caso de uso | Actor | Paquete | Requisito | Análisis | Diseño | Implementación |
| --- | --- | --- | --- | --- | --- | --- | --- |
| UC-001 | [Añadir, cambiar o eliminar un elemento](./use-cases/manageNodes/README.md) | Administrador | Navegación | RF-001 | [ficha](../analysis/use-cases/manageNodes/README.md) | [ficha](../design/use-cases/manageNodes/README.md) | [ficha](../implementation/use-cases/manageNodes/README.md) |
| UC-002 | [Reordenar los elementos](./use-cases/reorderNodes/README.md) | Administrador | Navegación | RF-002 | [ficha](../analysis/use-cases/reorderNodes/README.md) | [ficha](../design/use-cases/reorderNodes/README.md) | [ficha](../implementation/use-cases/reorderNodes/README.md) |
| UC-003 | [Importar y exportar elementos](./use-cases/importNodes/README.md) | Administrador | Navegación | RF-003 | [ficha](../analysis/use-cases/importNodes/README.md) | [ficha](../design/use-cases/importNodes/README.md) | [ficha](../implementation/use-cases/importNodes/README.md) |
| UC-004 | [Mostrar los menús de un usuario](./use-cases/showMenus/README.md) | Sistema | Presentación | RF-004 | [ficha](../analysis/use-cases/showMenus/README.md) | [ficha](../design/use-cases/showMenus/README.md) | [ficha](../implementation/use-cases/showMenus/README.md) |
| UC-005 | [Bloquear las páginas de los elementos ocultos](./use-cases/blockUrl/README.md) | Sistema | Presentación | RF-005 | [ficha](../analysis/use-cases/blockUrl/README.md) | [ficha](../design/use-cases/blockUrl/README.md) | [ficha](../implementation/use-cases/blockUrl/README.md) |
| UC-006 | [Personalizar una navegación secundaria](./use-cases/customiseSecondaryNavigation/README.md) | Administrador | Navegación | RF-007 | [ficha](../analysis/use-cases/customiseSecondaryNavigation/README.md) | [ficha](../design/use-cases/customiseSecondaryNavigation/README.md) | [ficha](../implementation/use-cases/customiseSecondaryNavigation/README.md) |
| UC-007 | [Cambiar una navegación para una categoría, un curso o una actividad](./use-cases/setScopeException/README.md) | Administrador | Navegación | RF-008 | [ficha](../analysis/use-cases/setScopeException/README.md) | [ficha](../design/use-cases/setScopeException/README.md) | [ficha](../implementation/use-cases/setScopeException/README.md) |
| UC-008 | [Buscar los elementos de la navegación de cada tipo de actividad](./use-cases/discoverActivityItems/README.md) | Sistema | Navegación | RF-012 | [ficha](../analysis/use-cases/discoverActivityItems/README.md) | [ficha](../design/use-cases/discoverActivityItems/README.md) | [ficha](../implementation/use-cases/discoverActivityItems/README.md) |
| UC-009 | [Organizar la página de preferencias](./use-cases/arrangePreferences/README.md) | User | Navegación | RF-015 | [ficha](../analysis/use-cases/arrangePreferences/README.md) | [ficha](../design/use-cases/arrangePreferences/README.md) | [ficha](../implementation/use-cases/arrangePreferences/README.md) |
| UC-010 | [Depurar el registro de actividad](./use-cases/cleanActivityLog/README.md) | Sistema | Maintenance | RF-016 | [ficha](../analysis/use-cases/cleanActivityLog/README.md) | [ficha](../design/use-cases/cleanActivityLog/README.md) | [ficha](../implementation/use-cases/cleanActivityLog/README.md) |
| UC-011 | [Respaldar y restaurar los cambios de un curso](./use-cases/backUpChanges/README.md) | Sistema | Maintenance | RF-017 | [ficha](../analysis/use-cases/backUpChanges/README.md) | [ficha](../design/use-cases/backUpChanges/README.md) | [ficha](../implementation/use-cases/backUpChanges/README.md) |
| UC-012 | [Gestionar las navegaciones desde fuera de Moodle](./use-cases/manageByWebService/README.md) | Integration | Navegación | RF-018 | [ficha](../analysis/use-cases/manageByWebService/README.md) | [ficha](../design/use-cases/manageByWebService/README.md) | [ficha](../implementation/use-cases/manageByWebService/README.md) |

### Diagrama de casos de uso

![Casos de uso](./use-case-overview.svg)

### Por actor

#### Administrador

![Administrador](./use-cases-administrator.svg)

#### Integration

![Integration](./use-cases-integration.svg)

#### Sistema

![Sistema](./use-cases-system.svg)

#### User

![User](./use-cases-user.svg)

<!-- rup-flow:use-cases:end -->
