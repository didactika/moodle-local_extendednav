---
id: ACT
status: draft
origin: code
sourceOfTruth: code
---

# Actors

| Actor | Type | Goal | Related use cases |
| --- | --- | --- | --- |
| Administrator | Primary | Shape the menus and decide who sees and opens each page. Holds `local/extendednav:manage`, or is a site administrator. | [UC-001](./use-cases/manageNodes/README.md), [UC-002](./use-cases/reorderNodes/README.md), [UC-003](./use-cases/importNodes/README.md), [UC-006](./use-cases/customiseSecondaryNavigation/README.md), [UC-007](./use-cases/setScopeException/README.md), [UC-009](./use-cases/arrangePreferences/README.md), [UC-010](./use-cases/cleanActivityLog/README.md) |
| System | Secondary | The hooks of Moodle that build the menus and check each page, the scheduled tasks, and the backup and restore. | [UC-004](./use-cases/showMenus/README.md), [UC-005](./use-cases/blockUrl/README.md), [UC-008](./use-cases/discoverActivityItems/README.md), [UC-010](./use-cases/cleanActivityLog/README.md), [UC-011](./use-cases/backUpChanges/README.md) |
| User | Secondary | Any logged-in person who sees the menus, may be redirected and arranges their own Preferences page. | [UC-004](./use-cases/showMenus/README.md), [UC-005](./use-cases/blockUrl/README.md), [UC-009](./use-cases/arrangePreferences/README.md) |
| Integration | Primary | A system outside Moodle that manages the navigations with the token of a user. | [UC-012](./use-cases/manageByWebService/README.md) |
| Moodle | Secondary | Provides the hooks, the navigation of Boost, the events and the capabilities that the plugin relies on. | all |
