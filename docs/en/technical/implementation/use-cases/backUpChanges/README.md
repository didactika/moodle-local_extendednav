---
id: UC-011
name: backUpChanges
title: Back up and restore the changes of a course
status: draft
origin: code
sourceOfTruth: code
---

# UC-011 Back up and restore the changes of a course — implementation

| Overview | Requirements | Analysis | Design | Implementation | Tests |
| --- | --- | --- | --- | --- | --- |
| [Use cases](../../../requirements/use-case-overview.svg) | [Requirements](../../../requirements/use-cases/backUpChanges/README.md) | [Analysis](../../../analysis/use-cases/backUpChanges/README.md) | [Design](../../../design/use-cases/backUpChanges/README.md) | **Implementation** | **Tests** |

## Code

| File | Role |
| --- | --- |
| `backup/moodle2/backup_local_extendednav_plugin.class.php` | What is backed up. |
| `backup/moodle2/restore_local_extendednav_plugin.class.php` | What is restored and how its conditions are mapped. |
| `settings.php` | The two defaults. |

## Tests

| Test | What it checks |
| --- | --- |
| `tests/backup_test.php::test_the_changes_travel_with_the_backup` | The changes of a course come back with a restore, and neither default left off lets them through. |
| `tests/local/io/importer_scope_test.php::test_an_exception_goes_out_and_comes_back` | The exceptions of a course and of an activity in a file. |
