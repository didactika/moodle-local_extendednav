---
id: ACT
status: draft
origin: code
sourceOfTruth: code
---

# Actors

| Actor | Type | Goal | Related use cases |
| --- | --- | --- | --- |
| Administrator | Primary | Shape the menus and decide who sees and opens each page. Holds `local/extendednav:manage`, or is a site administrator. | [UC-001](./use-cases/manageNodes/README.md), [UC-002](./use-cases/reorderNodes/README.md), [UC-003](./use-cases/importNodes/README.md) |
| System | Secondary | The hooks of Moodle that build the menus and check each page. | [UC-004](./use-cases/showMenus/README.md), [UC-005](./use-cases/blockUrl/README.md) |
| User | Secondary | Any logged-in person who sees the menus and may be redirected. | [UC-004](./use-cases/showMenus/README.md), [UC-005](./use-cases/blockUrl/README.md) |
| Moodle | Secondary | Provides the hooks, the navigation of Boost, the events and the capabilities that the plugin relies on. | all |
