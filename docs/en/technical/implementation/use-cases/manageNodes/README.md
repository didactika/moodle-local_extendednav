---
id: UC-001
name: manageNodes
title: Add, change or delete an item
status: draft
origin: code
sourceOfTruth: code
---

# UC-001 Add, change or delete an item — implementation

| Overview | Requirements | Analysis | Design | Implementation | Tests |
| --- | --- | --- | --- | --- | --- |
| [Use cases](../../../requirements/use-case-overview.svg) | [Requirements](../../../requirements/use-cases/manageNodes/README.md) | [Analysis](../../../analysis/use-cases/manageNodes/README.md) | [Design](../../../design/use-cases/manageNodes/README.md) | **Implementation** | [Tests](../../../implementation/use-cases/manageNodes/README.md#tests) |

Design: [README](../../../design/use-cases/manageNodes/README.md)

## Code

| Element | Kind | Location | Verified |
| --- | --- | --- | --- |
| `node_form` | form | `classes/form/node_form.php` | yes |
| `node_manager` | service | `classes/local/node_manager.php` | yes |
| Events of the nodes | events | `classes/event/` | yes |
| Item form screen | AMD | `amd/src/node_form.js` | yes |

## Tests

| Test | Covers | Location |
| --- | --- | --- |
| Creating, changing and deleting nodes | main flow | `tests/local/node_manager_test.php` |
| The rules of validation | alternative flows, RN-002, RN-003 | `tests/local/node_validator_test.php` |
| Behat journey of the form | main flow | `tests/behat/node_form.feature` |
