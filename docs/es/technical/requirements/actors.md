---
id: ACT
status: draft
origin: code
sourceOfTruth: code
---

# Actores

| Actor | Tipo | Objetivo | Casos de uso relacionados |
| --- | --- | --- | --- |
| Administrador | Primario | Dar forma a los menús y decidir quién ve y abre cada página. Tiene `local/extendednav:manage`, o es administrador del sitio. | [UC-001](./use-cases/manageNodes/README.md), [UC-002](./use-cases/reorderNodes/README.md), [UC-003](./use-cases/importNodes/README.md) |
| Sistema | Secundario | Los ganchos de Moodle que construyen los menús y comprueban cada página. | [UC-004](./use-cases/showMenus/README.md), [UC-005](./use-cases/blockUrl/README.md) |
| Usuario | Secundario | Toda persona con sesión iniciada que ve los menús y puede ser redirigida. | [UC-004](./use-cases/showMenus/README.md), [UC-005](./use-cases/blockUrl/README.md) |
| Moodle | Secundario | Aporta los ganchos, la navegación de Boost, los eventos y las capacidades en que se apoya el plugin. | todos |
