---
id: UC-012
name: manageByWebService
title: Gestionar las navegaciones desde fuera de Moodle
status: draft
origin: code
sourceOfTruth: code
---

# UC-012 Gestionar las navegaciones desde fuera de Moodle — implementación

| Vista general | Requisitos | Análisis | Diseño | Implementación | Pruebas |
| --- | --- | --- | --- | --- | --- |
| [Casos de uso](../../../requirements/use-case-overview.svg) | [Requisitos](../../../requirements/use-cases/manageByWebService/README.md) | [Análisis](../../../analysis/use-cases/manageByWebService/README.md) | [Diseño](../../../design/use-cases/manageByWebService/README.md) | **Implementación** | **Pruebas** |

## Código

| Archivo | Papel |
| --- | --- |
| `classes/external/manage_navigation.php` | Lista de navegaciones, guardar un nodo, exportar e importar. |
| `classes/external/search_export.php` | La búsqueda de lo que un archivo puede contener. |
| `classes/external/node_service.php` | La comprobación de la capacidad y del ámbito. |
| `db/services.php` | Las funciones y el servicio que las reúne. |

## Pruebas

| Prueba | Qué comprueba |
| --- | --- |
| `tests/external/manage_navigation_test.php::test_the_navigations_are_listed` | Se enumeran todas las navegaciones, sin perfil, y un usuario sin la capacidad es rechazado. |
| `tests/external/manage_navigation_test.php::test_a_node_is_saved` | Un nodo se añade y se cambia, y un campo omitido conserva su valor. |
| `tests/external/manage_navigation_test.php::test_a_user_saves_their_own_preferences` | Un usuario cambia solo lo que un usuario puede cambiar. |
| `tests/external/manage_navigation_test.php::test_a_file_goes_out_and_comes_back` | Un archivo se exporta y se importa, y un usuario que el sitio no tiene se omite con un aviso. |
| `tests/external/manage_navigation_test.php::test_the_export_finds_ten_at_a_time` | La búsqueda da diez resultados cada vez. |
| `tests/external/manage_navigation_test.php::test_the_service_holds_every_function` | El servicio contiene todas las funciones. |
