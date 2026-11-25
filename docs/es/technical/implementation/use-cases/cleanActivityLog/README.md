---
id: UC-010
name: cleanActivityLog
title: Depurar el registro de actividad
status: draft
origin: code
sourceOfTruth: code
---

# UC-010 Depurar el registro de actividad — implementación

| Vista general | Requisitos | Análisis | Diseño | Implementación | Pruebas |
| --- | --- | --- | --- | --- | --- |
| [Casos de uso](../../../requirements/use-case-overview.svg) | [Requisitos](../../../requirements/use-cases/cleanActivityLog/README.md) | [Análisis](../../../analysis/use-cases/cleanActivityLog/README.md) | [Diseño](../../../design/use-cases/cleanActivityLog/README.md) | **Implementación** | **Pruebas** |

## Código

| Archivo | Papel |
| --- | --- |
| `classes/task/clean_activity_log.php` | La tarea, su avance y su sincronización con los ajustes. |
| `db/tasks.php` | El horario, desactivada de forma predeterminada. |
| `settings.php` | Los ajustes de la depuración. |
| `db/upgrade.php` | Desactiva la tarea en los sitios que ya tenían el plugin. |

## Pruebas

| Prueba | Qué comprueba |
| --- | --- |
| `tests/task/clean_activity_log_test.php::test_old_entries_of_the_plugin_are_deleted` | Solo se van las entradas antiguas del plugin, un lote mayor que una lectura se elimina entero y una ejecución continúa donde se detuvo la anterior. |
| `tests/task/clean_activity_log_test.php::test_the_task_follows_the_settings` | La tarea empieza desactivada, se activa con los ajustes y se desactiva con ellos. |
| `tests/task/clean_activity_log_test.php::test_the_log_is_read_through_its_index` | El plan de la consulta usa el índice del tiempo (PostgreSQL). |
