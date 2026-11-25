---
id: UC-002
name: reorderNodes
title: Reordenar los elementos
actor: Administrator
package: Navigation
status: draft
origin: code
sourceOfTruth: code
requirement: RF-002
includes: 
extends: 
---

# UC-002 Reordenar los elementos

| Resumen | Requisitos | Análisis | Diseño | Implementación | Pruebas |
| --- | --- | --- | --- | --- | --- |
| [Casos de uso](../../use-case-overview.svg) | **Requisitos** | [Análisis](../../../analysis/use-cases/reorderNodes/README.md) | [Diseño](../../../design/use-cases/reorderNodes/README.md) | [Implementación](../../../implementation/use-cases/reorderNodes/README.md) | [Pruebas](../../../implementation/use-cases/reorderNodes/README.md#pruebas) |

## Información del caso de uso

| Campo | Valor |
| --- | --- |
| Actor | Administrador |
| Paquete | Navigation |
| Objetivo | Cambiar la posición de los elementos de un menú. |
| Tipo | Primario, esencial |
| Nivel | Objetivo de usuario |
| Requisito | RF-002 |
| Entidades del dominio | Node, Menu, Submenu |

## Contexto

El orden de la lista es el orden del menú. La lista muestra todos los elementos, sin páginas. Un administrador arrastra un elemento por su asa, solo o con los elementos seleccionados entre sus hermanos, o lo mueve con las teclas de flecha sobre su asa; los submenús se mantienen juntos.

## Precondiciones

- El administrador tiene `local/extendednav:manage`.
- No hay ningún filtro aplicado.

## Postcondiciones

- Éxito: el nuevo orden queda guardado y el menú lo muestra.
- Fallo: el orden no cambia.

## Flujo principal

| Paso | Actor | Sistema |
| --- | --- | --- |
| 1 | Arrastra un elemento por su asa y lo suelta encima o debajo de un hermano. | Lleva consigo los elementos seleccionados entre sus hermanos, en su orden, y calcula el nuevo orden. |
| 2 |  | Guarda el orden mediante la función de servicio web. |
| 3 |  | Mantiene a un padre con sus elementos de submenú y dibuja la lista de nuevo. |

## Flujos alternativos

| ID | Empieza en el paso | Condición | Comportamiento | Termina |
| --- | --- | --- | --- | --- |
| A1 | 1 | Hay una búsqueda o un filtro aplicado, o la pantalla mide menos de 768 px. | Las asas no se muestran. | Paso 1 |
| A2 | 2 | El servidor rechaza el orden. | Muestra el error y deja la lista como estaba. | Paso 1 |
| A3 | 1 | El administrador pulsa la tecla de flecha arriba o abajo sobre el asa. | Mueve el elemento un lugar entre sus hermanos. | Paso 2 |

## Reglas de negocio

| ID | Regla |
| --- | --- |
| RN-007 | Un padre se mueve con su submenú; un elemento de submenú solo se mueve entre sus hermanos. |

## Especificación

Fuente de verdad del comportamiento: [specification.puml](./specification.puml)

![Especificación](./specification.svg)

## Prototipos

![Prototipos](./wireframes.svg)
