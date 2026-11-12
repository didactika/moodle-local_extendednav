---
id: UC-005
name: blockUrl
title: Block the pages of hidden items
status: draft
origin: code
sourceOfTruth: code
---

# UC-005 Block the pages of hidden items — implementation

| Overview | Requirements | Analysis | Design | Implementation | Tests |
| --- | --- | --- | --- | --- | --- |
| [Use cases](../../../requirements/use-case-overview.svg) | [Requirements](../../../requirements/use-cases/blockUrl/README.md) | [Analysis](../../../analysis/use-cases/blockUrl/README.md) | [Design](../../../design/use-cases/blockUrl/README.md) | **Implementation** | [Tests](../../../implementation/use-cases/blockUrl/README.md#tests) |

Design: [README](../../../design/use-cases/blockUrl/README.md)

## Code

| Element | Kind | Location | Verified |
| --- | --- | --- | --- |
| `access_guard` | service | `classes/local/access_guard.php` | yes |
| `url_helper` | service | `classes/local/url_helper.php` | yes |
| Restricted page | page | `restricted.php` | yes |

## Tests

| Test | Covers | Location |
| --- | --- | --- |
| Blocking and fallbacks | main flow, RN-004, RN-005 | `tests/local/access_guard_test.php` |
| Matching of URLs | RN-004 | `tests/local/url_helper_test.php` |
| Behat journey of blocking | main flow | `tests/behat/block_access.feature` |
