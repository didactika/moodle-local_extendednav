---
id: UC-008
name: discoverActivityItems
title: Find the items of the navigation of each kind of activity
status: draft
origin: code
sourceOfTruth: code
---

# UC-008 Find the items of the navigation of each kind of activity — implementation

| Overview | Requirements | Analysis | Design | Implementation | Tests |
| --- | --- | --- | --- | --- | --- |
| [Use cases](../../../requirements/use-case-overview.svg) | [Requirements](../../../requirements/use-cases/discoverActivityItems/README.md) | [Analysis](../../../analysis/use-cases/discoverActivityItems/README.md) | [Design](../../../design/use-cases/discoverActivityItems/README.md) | **Implementation** | **Tests** |

## Code

| File | Role |
| --- | --- |
| `classes/local/navigation/discovery.php` | Builds the navigation of the activities and records their items. |
| `classes/task/discover_nodes.php` | The scheduled task. |
| `db/tasks.php` | Its schedule. |
| `classes/external/discover_nodes.php` | The web service of **Find items**. |
| `classes/menu/activity.php` | The navigation of one kind of activity. |
| `classes/menu/secondary.php` | `key_of`, `children_of`, `describe`, `record_seen`. |

## Tests

| Test | What it checks |
| --- | --- |
| `tests/menu/activity_test.php::test_the_items_are_found_in_the_activities` | The items of a forum are found, and only the forums get them. |
| `tests/menu/activity_test.php::test_a_numbered_item_is_known_by_its_path` | An item Moodle numbers keeps one key. |
| `tests/behat/context_navigation.feature` (The site lists the navigation of each kind of activity) | **Find items** lists the subscriptions of a forum. |
