---
id: RQ
status: draft
origin: code
sourceOfTruth: code
---

# Requirements catalogue

## Functional

| ID | Requirement | Priority | Origin | Use cases |
| --- | --- | --- | --- | --- |
| RF-001 | An administrator can add, change and delete items of the primary navigation and of the user menu, including the items of Moodle. | Must | `classes/local/node_manager.php` | UC-001 |
| RF-002 | An administrator can reorder the items, keeping submenus together. | Should | `classes/external/reorder_nodes.php` | UC-002 |
| RF-003 | An administrator can export items to a YAML file and import them from one, checking the whole file first. | Should | `classes/local/node_importer.php` | UC-003 |
| RF-004 | Each user sees the items that their roles and language allow, in both menus. | Must | `classes/hooks.php` | UC-004 |
| RF-005 | A page can be blocked for the users who cannot see its item. | Must | `classes/local/access_guard.php` | UC-005 |
| RF-006 | The changes to the items are recorded in the activity log. | Could | `classes/event/` | UC-001 |

## Non-functional

| ID | Quality attribute | Requirement | Measure | Priority |
| --- | --- | --- | --- | --- |
| RNF-001 | Security | Managing items needs its own capability, which no role has by default. | `local/extendednav:manage` with no archetype | Must |
| RNF-002 | Availability | The plugin never leaves every user without a landing page. | `node_validator::validate` | Must |
| RNF-003 | Privacy | The plugin stores no personal data. | `classes/privacy/provider.php` (null provider) | Must |
| RNF-004 | Integrity | An import changes nothing if any item is invalid. | `node_importer::import` | Must |

## Business rules

Rules live in the [domain model dictionary](../domain/README.md#business-rules). Rules that change behaviour are repeated here with their ID.
