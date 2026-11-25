---
id: UC-009
name: arrangePreferences
title: Arrange the Preferences page
status: draft
origin: code
sourceOfTruth: code
---

# UC-009 Arrange the Preferences page — implementation

| Overview | Requirements | Analysis | Design | Implementation | Tests |
| --- | --- | --- | --- | --- | --- |
| [Use cases](../../../requirements/use-case-overview.svg) | [Requirements](../../../requirements/use-cases/arrangePreferences/README.md) | [Analysis](../../../analysis/use-cases/arrangePreferences/README.md) | [Design](../../../design/use-cases/arrangePreferences/README.md) | **Implementation** | **Tests** |

## Code

| File | Role |
| --- | --- |
| `classes/local/navigation/preferences_injector.php` | Records and applies the sections and options. |
| `classes/menu/preferences.php` | The navigation of the preferences, with its natives. |
| `classes/local/navigation/context_scope.php` | The scope of a user. |
| `classes/privacy/provider.php` | Export and deletion of what a user arranged. |
| `db/access.php` | The capability `local/extendednav:personalise`. |
| `tours/personal.json`, `tours/preferences.json` | The guided tours of the page of a user. |

## Tests

| Test | What it checks |
| --- | --- |
| `tests/menu/preferences_test.php::test_the_sections_are_folders` | The sections and options are recorded and listed as folders. |
| `tests/menu/preferences_test.php::test_the_site_arranges_the_page` | The site hides, renames, creates sections and moves options. |
| `tests/menu/preferences_test.php::test_a_user_arranges_their_own_page` | A user hides, orders and moves their options, an administrator too, and Miscellaneous is ordered. |
| `tests/menu/preferences_test.php::test_the_scope_of_a_user` | What a user, another user and an administrator may do. |
| `tests/menu/preferences_test.php::test_personalising_takes_the_capability` | Without the capability the page follows the site. |
| `tests/menu/preferences_test.php::test_the_privacy_of_a_user` | The changes are listed, exported and deleted, also when the user is deleted. |
| `tests/behat/preferences.feature` | A user hides an option and shows it again; another user keeps the page of the site. |
