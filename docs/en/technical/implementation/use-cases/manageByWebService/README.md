---
id: UC-012
name: manageByWebService
title: Manage the navigations from outside Moodle
status: draft
origin: code
sourceOfTruth: code
---

# UC-012 Manage the navigations from outside Moodle — implementation

| Overview | Requirements | Analysis | Design | Implementation | Tests |
| --- | --- | --- | --- | --- | --- |
| [Use cases](../../../requirements/use-case-overview.svg) | [Requirements](../../../requirements/use-cases/manageByWebService/README.md) | [Analysis](../../../analysis/use-cases/manageByWebService/README.md) | [Design](../../../design/use-cases/manageByWebService/README.md) | **Implementation** | **Tests** |

## Code

| File | Role |
| --- | --- |
| `classes/external/manage_navigation.php` | List of navigations, save a node, export and import. |
| `classes/external/search_export.php` | The search of what a file can hold. |
| `classes/external/node_service.php` | The check of the capability and of the scope. |
| `db/services.php` | The functions and the service that gathers them. |

## Tests

| Test | What it checks |
| --- | --- |
| `tests/external/manage_navigation_test.php::test_the_navigations_are_listed` | Every navigation is listed, with no profile, and a user without the capability is refused. |
| `tests/external/manage_navigation_test.php::test_a_node_is_saved` | A node is added and changed, and a field left out keeps its value. |
| `tests/external/manage_navigation_test.php::test_a_user_saves_their_own_preferences` | A user changes only what a user may change. |
| `tests/external/manage_navigation_test.php::test_a_file_goes_out_and_comes_back` | A file is exported and imported, and a user the site lacks is left out with a warning. |
| `tests/external/manage_navigation_test.php::test_the_export_finds_ten_at_a_time` | The search gives ten results at a time. |
| `tests/external/manage_navigation_test.php::test_the_service_holds_every_function` | The service holds every function. |
