---
id: UC-002
name: reorderNodes
title: Reorder the items
status: draft
origin: code
sourceOfTruth: code
---

# UC-002 Reorder the items — implementation

| Overview | Requirements | Analysis | Design | Implementation | Tests |
| --- | --- | --- | --- | --- | --- |
| [Use cases](../../../requirements/use-case-overview.svg) | [Requirements](../../../requirements/use-cases/reorderNodes/README.md) | [Analysis](../../../analysis/use-cases/reorderNodes/README.md) | [Design](../../../design/use-cases/reorderNodes/README.md) | **Implementation** | [Tests](../../../implementation/use-cases/reorderNodes/README.md#tests) |

Design: [README](../../../design/use-cases/reorderNodes/README.md)

## Code

| Element | Kind | Location | Verified |
| --- | --- | --- | --- |
| `reorder_nodes` | web service | `classes/external/reorder_nodes.php` | yes |
| Management screen | AMD | `amd/src/manage.js` | yes |
| `nodes_table` | table | `classes/table/nodes_table.php` | yes |

## Tests

| Test | Covers | Location |
| --- | --- | --- |
| Reordering through the service | main flow | `tests/external/reorder_nodes_test.php` |
| Grouping and positions | RN-007 | `tests/local/node_manager_test.php` |
| Behat journey of the management page | main flow | `tests/behat/manage_page.feature` |
