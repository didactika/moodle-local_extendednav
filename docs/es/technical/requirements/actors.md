---
id: ACT
status: draft
origin: code
sourceOfTruth: code
---

# Actores

| Actor | Tipo | Objetivo | Casos de uso relacionados |
| --- | --- | --- | --- |
| Administrador | Primario | Dar forma a los menús y decidir quién ve y abre cada página. Tiene `local/extendednav:manage` o es administrador del sitio. | [UC-001](./use-cases/manageNodes/README.md), [UC-002](./use-cases/reorderNodes/README.md), [UC-003](./use-cases/importNodes/README.md), [UC-006](./use-cases/customiseSecondaryNavigation/README.md), [UC-007](./use-cases/setScopeException/README.md), [UC-009](./use-cases/arrangePreferences/README.md), [UC-010](./use-cases/cleanActivityLog/README.md) |
| Sistema | Secundario | Los ganchos de Moodle que construyen los menús y comprueban cada página, las tareas programadas, y la copia de seguridad y la restauración. | [UC-004](./use-cases/showMenus/README.md), [UC-005](./use-cases/blockUrl/README.md), [UC-008](./use-cases/discoverActivityItems/README.md), [UC-010](./use-cases/cleanActivityLog/README.md), [UC-011](./use-cases/backUpChanges/README.md) |
| Usuario | Secundario | Toda persona con sesión iniciada que ve los menús, puede ser redirigida y organiza su propia página de preferencias. | [UC-004](./use-cases/showMenus/README.md), [UC-005](./use-cases/blockUrl/README.md), [UC-009](./use-cases/arrangePreferences/README.md) |
| Integración | Primario | Un sistema fuera de Moodle que gestiona las navegaciones con el token de un usuario. | [UC-012](./use-cases/manageByWebService/README.md) |
| Moodle | Secundario | Aporta los ganchos, la navegación de Boost, los eventos y las capacidades en que se apoya el plugin. | todos |
