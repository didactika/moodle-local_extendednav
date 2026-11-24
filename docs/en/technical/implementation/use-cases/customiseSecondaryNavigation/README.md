---
id: UC-006
name: customiseSecondaryNavigation
title: Customise a secondary navigation
status: draft
origin: code
sourceOfTruth: code
---

# UC-006 Customise a secondary navigation — implementation

| Overview | Requirements | Analysis | Design | Implementation | Tests |
| --- | --- | --- | --- | --- | --- |
| [Use cases](../../../requirements/use-case-overview.svg) | [Requirements](../../../requirements/use-cases/customiseSecondaryNavigation/README.md) | [Analysis](../../../analysis/use-cases/customiseSecondaryNavigation/README.md) | [Design](../../../design/use-cases/customiseSecondaryNavigation/README.md) | **Implementation** | [Tests](../../../implementation/use-cases/customiseSecondaryNavigation/README.md#tests) |

Design: [README](../../../design/use-cases/customiseSecondaryNavigation/README.md)

## Code

| Element | Kind | Location | Verified |
| --- | --- | --- | --- |
| `get_nodes`, `change_nodes` | web service | `classes/external/get_nodes.php`, `classes/external/change_nodes.php` | yes |
| `node_list` | class | `classes/local/node_list.php` | yes |
| `secondary` | menu | `classes/menu/secondary.php` | yes |
| `secondary_navigation` | class | `classes/local/secondary_navigation.php` | yes |
| Management page | AMD and template | `amd/src/manage.js`, `templates/manage/page.mustache`, `templates/list/nodes_table.mustache` | yes |

## Tests

| Test | Covers | Location |
| --- | --- | --- |
| The list, its filters, counts and pages | main flow | `tests/local/node_list_test.php` |
| The services of the page | main flow | `tests/external/node_services_test.php` |
| Moving nodes | main flow | `tests/local/node_scope_test.php` |
| A node seen for the first time | RN-009 | `tests/local/node_list_test.php` |
| A hidden node and a renamed one in a course | main flow | `tests/behat/secondary_navigation.feature` |
| Behat journey of the management page | main flow | `tests/behat/manage_page.feature` |
