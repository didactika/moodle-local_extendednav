---
id: UC-004
name: showMenus
title: Show the menus of a user
status: draft
origin: code
sourceOfTruth: code
---

# UC-004 Show the menus of a user — implementation

| Overview | Requirements | Analysis | Design | Implementation | Tests |
| --- | --- | --- | --- | --- | --- |
| [Use cases](../../../requirements/use-case-overview.svg) | [Requirements](../../../requirements/use-cases/showMenus/README.md) | [Analysis](../../../analysis/use-cases/showMenus/README.md) | [Design](../../../design/use-cases/showMenus/README.md) | **Implementation** | [Tests](../../../implementation/use-cases/showMenus/README.md#tests) |

Design: [README](../../../design/use-cases/showMenus/README.md)

## Code

| Element | Kind | Location | Verified |
| --- | --- | --- | --- |
| `hooks` | hook callbacks | `classes/hooks.php` | yes |
| `primary` | menu | `classes/menu/primary.php` | yes |
| `usermenu` | menu | `classes/menu/usermenu.php` | yes |
| Hook registration | file | `db/hooks.php` | yes |

## Tests

| Test | Covers | Location |
| --- | --- | --- |
| The primary navigation | main flow | `tests/menu/primary_test.php` |
| The user menu | main flow | `tests/menu/usermenu_test.php` |
| Who sees a node | RN-001 | `tests/local/node_access_test.php` |
| Behat journeys of the menus | main flow | `tests/behat/primary_navigation.feature`, `tests/behat/user_menu.feature` |
