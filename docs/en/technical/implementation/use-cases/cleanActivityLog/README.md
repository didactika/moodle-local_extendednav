---
id: UC-010
name: cleanActivityLog
title: Clean up the activity log
status: draft
origin: code
sourceOfTruth: code
---

# UC-010 Clean up the activity log — implementation

| Overview | Requirements | Analysis | Design | Implementation | Tests |
| --- | --- | --- | --- | --- | --- |
| [Use cases](../../../requirements/use-case-overview.svg) | [Requirements](../../../requirements/use-cases/cleanActivityLog/README.md) | [Analysis](../../../analysis/use-cases/cleanActivityLog/README.md) | [Design](../../../design/use-cases/cleanActivityLog/README.md) | **Implementation** | **Tests** |

## Code

| File | Role |
| --- | --- |
| `classes/task/clean_activity_log.php` | The task, its progress and its synchronisation with the settings. |
| `db/tasks.php` | The schedule, disabled by default. |
| `settings.php` | The settings of the clean-up. |
| `db/upgrade.php` | Turns the task off on the sites that already had the plugin. |

## Tests

| Test | What it checks |
| --- | --- |
| `tests/task/clean_activity_log_test.php::test_old_entries_of_the_plugin_are_deleted` | Only the old entries of the plugin go, a batch larger than one read is deleted whole, and a run goes on from where the last stopped. |
| `tests/task/clean_activity_log_test.php::test_the_task_follows_the_settings` | The task starts off, goes on with the settings and goes off with them. |
| `tests/task/clean_activity_log_test.php::test_the_log_is_read_through_its_index` | The plan of the query uses the index on the time (PostgreSQL). |
