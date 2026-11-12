---
id: RQ
status: draft
origin: code
sourceOfTruth: code
---

# Catálogo de requisitos

## Funcionales

| ID | Requisito | Prioridad | Origen | Casos de uso |
| --- | --- | --- | --- | --- |
| RF-001 | Un administrador puede añadir, cambiar y eliminar elementos de la navegación principal y del menú de usuario, incluidos los elementos de Moodle. | Debe | `classes/local/node_manager.php` | UC-001 |
| RF-002 | Un administrador puede reordenar los elementos, manteniendo juntos los submenús. | Debería | `classes/external/reorder_nodes.php` | UC-002 |
| RF-003 | Un administrador puede exportar elementos a un archivo YAML e importarlos desde uno, comprobando antes todo el archivo. | Debería | `classes/local/node_importer.php` | UC-003 |
| RF-004 | Cada usuario ve los elementos que sus roles e idioma permiten, en ambos menús. | Debe | `classes/hooks.php` | UC-004 |
| RF-005 | Una página puede bloquearse para los usuarios que no pueden ver su elemento. | Debe | `classes/local/access_guard.php` | UC-005 |
| RF-006 | Los cambios de los elementos quedan registrados en el registro de actividad. | Podría | `classes/event/` | UC-001 |

## No funcionales

| ID | Atributo de calidad | Requisito | Medida | Prioridad |
| --- | --- | --- | --- | --- |
| RNF-001 | Seguridad | Gestionar elementos necesita su propia capacidad, que ningún rol tiene de forma predeterminada. | `local/extendednav:manage` sin arquetipo | Debe |
| RNF-002 | Disponibilidad | El plugin nunca deja a todos los usuarios sin una página de destino. | `node_validator::validate` | Debe |
| RNF-003 | Privacidad | El plugin no almacena datos personales. | `classes/privacy/provider.php` (proveedor nulo) | Debe |
| RNF-004 | Integridad | Una importación no cambia nada si algún elemento no es válido. | `node_importer::import` | Debe |

## Reglas de negocio

Las reglas están en el [diccionario del modelo de dominio](../domain/README.md#reglas-de-negocio). Las que cambian el comportamiento se repiten aquí con su ID.
