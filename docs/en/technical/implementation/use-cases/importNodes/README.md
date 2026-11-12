---
id: UC-003
name: importNodes
title: Import and export items
status: draft
origin: code
sourceOfTruth: code
---

# UC-003 Import and export items — implementation

| Overview | Requirements | Analysis | Design | Implementation | Tests |
| --- | --- | --- | --- | --- | --- |
| [Use cases](../../../requirements/use-case-overview.svg) | [Requirements](../../../requirements/use-cases/importNodes/README.md) | [Analysis](../../../analysis/use-cases/importNodes/README.md) | [Design](../../../design/use-cases/importNodes/README.md) | **Implementation** | [Tests](../../../implementation/use-cases/importNodes/README.md#tests) |

Design: [README](../../../design/use-cases/importNodes/README.md)

## Code

| Element | Kind | Location | Verified |
| --- | --- | --- | --- |
| `node_importer` | service | `classes/local/node_importer.php` | yes |
| `yaml` | service | `classes/yaml.php` | yes |
| Import and export pages | pages | `import.php`, `export.php` | yes |
| `import_form` | form | `classes/form/import_form.php` | yes |

## Tests

| Test | Covers | Location |
| --- | --- | --- |
| Importing and exporting nodes | main flow | `tests/local/node_importer_test.php` |
| The YAML reader and writer | main flow | `tests/yaml_test.php` |
| Behat journey of import and export | main flow | `tests/behat/import_export.feature` |
