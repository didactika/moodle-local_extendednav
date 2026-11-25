---
id: UC-001
name: manageNodes
title: Añadir, cambiar o eliminar un elemento
actor: Administrator
package: Navigation
status: draft
origin: code
sourceOfTruth: code
requirement: RF-001
includes: 
extends: 
---

# UC-001 Añadir, cambiar o eliminar un elemento

| Resumen | Requisitos | Análisis | Diseño | Implementación | Pruebas |
| --- | --- | --- | --- | --- | --- |
| [Casos de uso](../../use-case-overview.svg) | **Requisitos** | [Análisis](../../../analysis/use-cases/manageNodes/README.md) | [Diseño](../../../design/use-cases/manageNodes/README.md) | [Implementación](../../../implementation/use-cases/manageNodes/README.md) | [Pruebas](../../../implementation/use-cases/manageNodes/README.md#pruebas) |

## Información del caso de uso

| Campo | Valor |
| --- | --- |
| Actor | Administrador |
| Paquete | Navigation |
| Objetivo | Dar forma a la navegación principal y al menú de usuario elemento por elemento. |
| Tipo | Primario, esencial |
| Nivel | Objetivo de usuario |
| Requisito | RF-001 |
| Entidades del dominio | Node, Menu, Visibility, Role, Language, ActivityRecord |

## Contexto

Un administrador añade elementos a un menú, cambia los elementos de Moodle o quita elementos, y decide quién ve cada uno y si su página se bloquea.

## Precondiciones

- El administrador tiene `local/extendednav:manage`, o es administrador del sitio.

## Postcondiciones

- Éxito: el elemento queda guardado, el menú lo muestra al instante y el cambio queda registrado.
- Fallo: no se guarda nada y el formulario muestra qué corregir.

## Flujo principal

| Paso | Actor | Sistema |
| --- | --- | --- |
| 1 | Abre la página de gestión y elige **Añadir nodo**, o el icono de edición de un elemento. | Muestra el formulario, con los valores de Moodle como texto de ejemplo en sus elementos. |
| 2 | Rellena la clave, el título, la URL, el icono, la visibilidad, los roles, los idiomas y el padre, y guarda. | Valida el elemento frente a los demás de su menú. |
| 3 |  | Lo guarda en `local_extendednav` y lanza `node_created` o `node_updated`. |
| 4 | Elimina un elemento. | Lo elimina y lanza `node_deleted`. |

## Flujos alternativos

| ID | Empieza en el paso | Condición | Comportamiento | Termina |
| --- | --- | --- | --- | --- |
| A1 | 2 | La clave está repetida o no es válida, o falta la URL o el título. | Muestra el error bajo el campo; no se guarda nada. | Paso 1 |
| A2 | 2 | El cambio dejaría a todos los usuarios sin una página de destino. | Lo rechaza. | Paso 1 |
| A3 | 2 | El elemento es la Administración del sitio y se oculta o se anida. | Lo rechaza. | Paso 1 |
| A4 | 1 | El elemento es de la navegación principal o del menú de usuario. | **Tipo** ofrece Enlace o Submenú. Un submenú no tiene URL y contiene enlaces; solo un submenú se ofrece como padre de un enlace, y si no hay ninguno el padre no se muestra. | Paso 2 |
| A5 | 1 | El elemento es de la administración del sitio. | **Tipo** ofrece Pestaña, Categoría o Página, y el padre que se ofrece depende del tipo; una pestaña que solo contiene páginas las muestra en una sección propia, y mientras no contiene nada se enumera con una nota. | Paso 2 |
| A6 | 1 | El elemento es una sección o una opción de la página de preferencias. | **Tipo** ofrece Enlace o Sección; una opción va en cualquier sección. | Paso 2 |
| A7 | 1 | La navegación es una navegación secundaria. | El formulario no tiene icono: esas navegaciones no los dibujan. | Paso 2 |
| A8 | 4 | El elemento se oculta. | El diálogo ofrece también ocultarlo a los administradores y bloquear su URL, para quien pueda restringirlo. | Paso 4 |

## Reglas de negocio

| ID | Regla |
| --- | --- |
| RN-002 | Una clave de nodo es única dentro de su menú. |
| RN-003 | Se rechaza un cambio que dejaría a todos los usuarios sin una página de destino. |
| RN-008 | Un elemento de Moodle con campos vacíos conserva los valores de Moodle. |
| RN-034 | Un nodo sin URL agrupa otros: un submenú, una sección, una categoría o una pestaña; solo un grupo se ofrece como padre de otro nodo. |
| RN-035 | Una búsqueda o un filtro de la lista mira en todos los niveles de las carpetas. |

## Especificación

Fuente de verdad del comportamiento: [specification.puml](./specification.puml)

![Especificación](./specification.svg)

## Prototipos

![Prototipos](./wireframes.svg)
