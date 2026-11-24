---
id: UC-007
name: setScopeException
title: Change a navigation for a category or a course
status: draft
origin: code
sourceOfTruth: code
---

# UC-007 Change a navigation for a category or a course — implementation

| Overview | Requirements | Analysis | Design | Implementation | Tests |
| --- | --- | --- | --- | --- | --- |
| [Use cases](../../../requirements/use-case-overview.svg) | [Requirements](../../../requirements/use-cases/setScopeException/README.md) | [Analysis](../../../analysis/use-cases/setScopeException/README.md) | [Design](../../../design/use-cases/setScopeException/README.md) | **Implementation** | [Tests](../../../implementation/use-cases/setScopeException/README.md#tests) |

Design: [README](../../../design/use-cases/setScopeException/README.md)

## Code

| Element | Kind | Location | Verified |
| --- | --- | --- | --- |
| `get_scopes` | web service | `classes/external/get_scopes.php` | yes |
| `repository` | class | `classes/local/node/repository.php` | yes |
| `manager` | class | `classes/local/node/manager.php` | yes |
| Scope menu | AMD | `amd/src/manage.js` | yes |

## Tests

| Test | Covers | Location |
| --- | --- | --- |
| The nearest scope wins | RN-012 | `tests/local/node/scope_test.php` |
| An exception leaves the site alone | main flow | `tests/local/node/scope_test.php` |
| Moving in a scope | RN-013 | `tests/local/node/scope_test.php` |
| Deleting a node | RN-014 | `tests/local/node/scope_test.php` |
| The scopes of a navigation | main flow | `tests/external/node_services_test.php` |
| Export and import of an exception | main flow | `tests/local/io/importer_scope_test.php` |
