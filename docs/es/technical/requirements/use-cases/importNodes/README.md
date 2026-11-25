---
id: UC-003
name: importNodes
title: Importar y exportar elementos
actor: Administrator
package: Navigation
status: draft
origin: code
sourceOfTruth: code
requirement: RF-003
includes: 
extends: 
---

# UC-003 Importar y exportar elementos

| Resumen | Requisitos | Análisis | Diseño | Implementación | Pruebas |
| --- | --- | --- | --- | --- | --- |
| [Casos de uso](../../use-case-overview.svg) | **Requisitos** | [Análisis](../../../analysis/use-cases/importNodes/README.md) | [Diseño](../../../design/use-cases/importNodes/README.md) | [Implementación](../../../implementation/use-cases/importNodes/README.md) | [Pruebas](../../../implementation/use-cases/importNodes/README.md#pruebas) |

## Información del caso de uso

| Campo | Valor |
| --- | --- |
| Actor | Administrador |
| Paquete | Navigation |
| Objetivo | Copiar la navegación de un sitio a otro. |
| Tipo | Primario, esencial |
| Nivel | Objetivo de usuario |
| Requisito | RF-003 |
| Entidades del dominio | ImportFile, Node |

## Contexto

Una navegación cuesta trabajo construirla. Un administrador exporta sus elementos a un archivo YAML y lo importa en otro sitio; todo el archivo se comprueba antes de guardar nada.

## Precondiciones

- El administrador tiene `local/extendednav:manage`.

## Postcondiciones

- Éxito: el archivo se escribe, o los elementos se crean, actualizan u omiten y se muestran los recuentos.
- Fallo: un archivo no válido no importa nada y se listan todos los errores.

## Flujo principal

| Paso | Actor | Sistema |
| --- | --- | --- |
| 1 | Abre **Exportar** y elige **Esta navegación** o **Todas las navegaciones**. | Escribe un único archivo YAML, con el mismo formato para ambos, con cada elemento, su navegación y su posición, y si cada navegación está activada. |
| 2 | Elige **Importar**, sube un archivo y elige qué hacer con los elementos existentes. | Lee el archivo y valida cada elemento. |
| 3 |  | Crea, actualiza u omite cada elemento, conservando su posición. |
| 4 |  | Muestra los recuentos y las advertencias, como un idioma que no está instalado. |

## Flujos alternativos

| ID | Empieza en el paso | Condición | Comportamiento | Termina |
| --- | --- | --- | --- | --- |
| A1 | 2 | Un elemento no es válido. | Lista cada elemento no válido con el motivo y no importa nada. | Paso 2 |
| A2 | 2 | El archivo no tiene una lista `nodes`. | Rechaza el archivo. | Paso 2 |
| A3 | 1 | El administrador elige **Elegir qué exportar**. | El formulario ofrece las navegaciones del sitio, los tipos de actividad, los usuarios, los cursos y las categorías en selectores que muestran diez resultados y buscan de diez en diez, y si se incluyen los cambios de todo el sitio. | Paso 1 |
| A4 | 1 | La exportación se hace desde la página de una categoría, un curso o una actividad. | El archivo contiene los cambios de ese contexto únicamente. | Paso 1 |
| A5 | 2 | El archivo nombra una categoría, un curso, una actividad o un usuario que no existe. | Ese nodo se omite con un aviso y el resto se importa. | Paso 4 |

## Reglas de negocio

| ID | Regla |
| --- | --- |
| RN-006 | Todo el archivo se comprueba antes de guardar nada. |
| RN-033 | Una importación omite, con un aviso, lo que nombra una categoría, un curso, una actividad o un usuario que el sitio no tiene. |

## Especificación

Fuente de verdad del comportamiento: [specification.puml](./specification.puml)

![Especificación](./specification.svg)

## Prototipos

![Prototipos](./wireframes.svg)
