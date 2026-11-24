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
| RF-001 | An administrator can add, change and delete items of the primary navigation and of the user menu, including the items of Moodle. | Must | `classes/local/node/manager.php` | UC-001 |
| RF-002 | An administrator can reorder the items, keeping submenus together. | Should | `classes/external/reorder_nodes.php` | UC-002 |
| RF-003 | An administrator can export items to a YAML file and import them from one, checking the whole file first. | Should | `classes/local/io/importer.php` | UC-003 |
| RF-004 | Each user sees the items that their roles and language allow, in both menus. | Must | `classes/hooks.php` | UC-004 |
| RF-005 | A page can be blocked for the users who cannot see its item. | Must | `classes/local/navigation/access_guard.php` | UC-005 |
| RF-006 | The changes to the items are recorded in the activity log. | Could | `classes/event/` | UC-001 |
| RF-007 | An administrator can order, rename, hide and extend the navigation of a course, of the site home, of an activity, of a category and of the site administration. | Must | `classes/local/navigation/injector.php` | UC-006 |
| RF-008 | A category or a course can change a navigation for itself, and everything inside it follows. | Should | `classes/local/node/repository.php` | UC-007 |
| RF-009 | A site administrator sees every node unless the plugin or the node hides it from administrators too. | Must | `classes/local/node/access.php` | UC-004 |
| RF-010 | The export and the import of nodes include the exceptions of categories and courses. | Should | `classes/local/io/importer.php` | UC-003 |
| RF-011 | The management page lists, filters, orders and changes nodes without reloading the page. | Must | `amd/src/manage.js` | UC-001, UC-002, UC-006 |

## Non-functional

| ID | Quality attribute | Requirement | Measure | Priority |
| --- | --- | --- | --- | --- |
| RNF-001 | Security | Managing items needs its own capability, which no role has by default. | `local/extendednav:manage` with no archetype | Must |
| RNF-002 | Availability | The plugin never leaves every user without a landing page. | `validator::validate` | Must |
| RNF-003 | Privacy | The plugin stores no personal data. | `classes/privacy/provider.php` (null provider) | Must |
| RNF-004 | Integrity | An import changes nothing if any item is invalid. | `importer::import` | Must |
| RNF-005 | Performance | A page that has nothing customised in its navigation reads nothing but the cache of the plugin, and the nodes of a navigation are found without building any page. | `injector::apply`, `secondary::record_seen` | Must |

## Business rules

Rules live in the [domain model dictionary](../domain/README.md#business-rules). Rules that change behaviour are repeated here with their ID.
