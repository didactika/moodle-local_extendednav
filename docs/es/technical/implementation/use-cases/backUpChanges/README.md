---
id: UC-011
name: backUpChanges
title: Respaldar y restaurar los cambios de un curso
status: draft
origin: code
sourceOfTruth: code
---

# UC-011 Respaldar y restaurar los cambios de un curso — implementación

| Vista general | Requisitos | Análisis | Diseño | Implementación | Pruebas |
| --- | --- | --- | --- | --- | --- |
| [Casos de uso](../../../requirements/use-case-overview.svg) | [Requisitos](../../../requirements/use-cases/backUpChanges/README.md) | [Análisis](../../../analysis/use-cases/backUpChanges/README.md) | [Diseño](../../../design/use-cases/backUpChanges/README.md) | **Implementación** | **Pruebas** |

## Código

| Archivo | Papel |
| --- | --- |
| `backup/moodle2/backup_local_extendednav_plugin.class.php` | Lo que se respalda. |
| `backup/moodle2/restore_local_extendednav_plugin.class.php` | Lo que se restaura y cómo se asignan sus condiciones. |
| `settings.php` | Los dos valores predeterminados. |

## Pruebas

| Prueba | Qué comprueba |
| --- | --- |
| `tests/backup_test.php::test_the_changes_travel_with_the_backup` | Los cambios de un curso vuelven con una restauración, y ningún valor predeterminado desactivado los deja pasar. |
| `tests/local/io/importer_scope_test.php::test_an_exception_goes_out_and_comes_back` | Las excepciones de un curso y de una actividad en un archivo. |
