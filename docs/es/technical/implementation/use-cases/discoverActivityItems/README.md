---
id: UC-008
name: discoverActivityItems
title: Buscar los elementos de la navegación de cada tipo de actividad
status: draft
origin: code
sourceOfTruth: code
---

# UC-008 Buscar los elementos de la navegación de cada tipo de actividad — implementación

| Vista general | Requisitos | Análisis | Diseño | Implementación | Pruebas |
| --- | --- | --- | --- | --- | --- |
| [Casos de uso](../../../requirements/use-case-overview.svg) | [Requisitos](../../../requirements/use-cases/discoverActivityItems/README.md) | [Análisis](../../../analysis/use-cases/discoverActivityItems/README.md) | [Diseño](../../../design/use-cases/discoverActivityItems/README.md) | **Implementación** | **Pruebas** |

## Código

| Archivo | Papel |
| --- | --- |
| `classes/local/navigation/discovery.php` | Construye la navegación de las actividades y registra sus elementos. |
| `classes/task/discover_nodes.php` | La tarea programada. |
| `db/tasks.php` | Su programación. |
| `classes/external/discover_nodes.php` | El servicio web de **Buscar elementos**. |
| `classes/menu/activity.php` | La navegación de un tipo de actividad. |
| `classes/menu/secondary.php` | `key_of`, `children_of`, `describe`, `record_seen`. |

## Pruebas

| Prueba | Qué comprueba |
| --- | --- |
| `tests/menu/activity_test.php::test_the_items_are_found_in_the_activities` | Se encuentran los elementos de un foro, y solo los foros los reciben. |
| `tests/menu/activity_test.php::test_a_numbered_item_is_known_by_its_path` | Un elemento que Moodle numera conserva una clave. |
| `tests/behat/context_navigation.feature` (The site lists the navigation of each kind of activity) | **Buscar elementos** enumera las suscripciones de un foro. |
