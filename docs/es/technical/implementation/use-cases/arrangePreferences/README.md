---
id: UC-009
name: arrangePreferences
title: Organizar la página de preferencias
status: draft
origin: code
sourceOfTruth: code
---

# UC-009 Organizar la página de preferencias — implementación

| Vista general | Requisitos | Análisis | Diseño | Implementación | Pruebas |
| --- | --- | --- | --- | --- | --- |
| [Casos de uso](../../../requirements/use-case-overview.svg) | [Requisitos](../../../requirements/use-cases/arrangePreferences/README.md) | [Análisis](../../../analysis/use-cases/arrangePreferences/README.md) | [Diseño](../../../design/use-cases/arrangePreferences/README.md) | **Implementación** | **Pruebas** |

## Código

| Archivo | Papel |
| --- | --- |
| `classes/local/navigation/preferences_injector.php` | Registra y aplica las secciones y opciones. |
| `classes/menu/preferences.php` | La navegación de las preferencias, con sus elementos de Moodle. |
| `classes/local/navigation/context_scope.php` | El ámbito de un usuario. |
| `classes/privacy/provider.php` | Exportación y eliminación de lo que un usuario organizó. |
| `db/access.php` | La capacidad `local/extendednav:personalise`. |
| `tours/personal.json`, `tours/preferences.json` | Los recorridos guiados de la página de un usuario. |

## Pruebas

| Prueba | Qué comprueba |
| --- | --- |
| `tests/menu/preferences_test.php::test_the_sections_are_folders` | Las secciones y opciones se registran y se enumeran como carpetas. |
| `tests/menu/preferences_test.php::test_the_site_arranges_the_page` | El sitio oculta, renombra, crea secciones y mueve opciones. |
| `tests/menu/preferences_test.php::test_a_user_arranges_their_own_page` | Un usuario oculta, ordena y mueve sus opciones, un administrador también, y Miscelánea se ordena. |
| `tests/menu/preferences_test.php::test_the_scope_of_a_user` | Qué pueden hacer un usuario, otro usuario y un administrador. |
| `tests/menu/preferences_test.php::test_personalising_takes_the_capability` | Sin la capacidad, la página sigue lo establecido por el sitio. |
| `tests/menu/preferences_test.php::test_the_privacy_of_a_user` | Los cambios se enumeran, se exportan y se eliminan, también al eliminar al usuario. |
| `tests/behat/preferences.feature` | Un usuario oculta una opción y la muestra de nuevo; otro usuario conserva la página del sitio. |
