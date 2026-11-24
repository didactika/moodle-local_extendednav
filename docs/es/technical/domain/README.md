---
id: DOM
status: draft
origin: code
sourceOfTruth: code
---

# Modelo de dominio

## Propósito

Los menús de Moodle son iguales para todos y solo se pueden configurar en parte. Extended navigation permite que un administrador dé forma a los dos menús que ve todo usuario y decida, elemento por elemento, quién lo ve y quién puede abrir su página. Este modelo nombra los conceptos que hay detrás.

## Jerarquía de entidades

La posición vertical de una entidad expresa su importancia para el objetivo del proyecto. No es herencia ni composición UML.

| Nivel | Significado | Entidades | Por qué están aquí |
| --- | --- | --- | --- |
| 1 | Núcleo: el proyecto no puede existir sin ellas | Node | El plugin existe para describir un elemento de un menú y sus reglas. |
| 2 | Estructurales: dependen directamente del núcleo | Menu, Visibility | Un nodo pertenece a un menú y tiene una visibilidad. |
| 3 | Detalle: cualifican o especializan el nivel 2 | Role, Language, Submenu | Limitan quién ve un nodo y cómo se anidan los nodos. |
| 4 | Soporte: catálogos, auditoría, configuración | ActivityRecord, ImportFile | Registran lo que cambió y llevan navegaciones entre sitios. |

## Diagramas

![Modelo de dominio](./domain-model.svg)

![Modelo de dominio simplificado](./domain-model-simple.svg)

## Diccionario de entidades

| Entidad | Nivel | Definición | Atributos clave | Atributos omitidos y motivo |
| --- | --- | --- | --- | --- |
| Node | 1 | Un elemento de la navegación principal o del menú de usuario, añadido por el plugin o un elemento de Moodle que el plugin cambia. | `nodekey`, `menu`, `title`, `url`, `visibility`, `blockaccess` | El resto están en el [modelo de datos](../design/data-model/data-model.svg). |
| Menu | 2 | Uno de los dos menús: la navegación principal o el menú de usuario. | `primary`, `usermenu` | Es una clase del plugin, no una tabla. |
| Visibility | 2 | Quién ve un nodo: todos, los usuarios con algunos roles o nadie. | `visibility` | |
| Role | 3 | Un rol que puede ver un nodo restringido. | nombre corto del rol | Es el `role` de Moodle. |
| Condition | 3 | Una restricción de acceso de Moodle sobre un nodo: una fecha, un grupo, un agrupamiento, un campo del perfil o una condición de un plugin. | `availability` | Es el árbol de disponibilidad de Moodle, guardado como JSON en el nodo. |
| Language | 3 | Un idioma al que se limita un nodo. | código de idioma | Es una lista de códigos en el nodo. |
| Submenu | 3 | Los nodos que se muestran bajo un nodo padre, a un nivel de profundidad. | `parentkey` | |
| ActivityRecord | 4 | Un evento del registro de Moodle escrito cuando un nodo se crea, cambia o elimina. | evento, usuario, hora | Es el registro de Moodle. |
| ImportFile | 4 | Un archivo YAML que lleva nodos entre sitios. | la lista `nodes` | No se almacena. |

## Reglas de negocio

| ID | Regla | Entidades | Fuente |
| --- | --- | --- | --- |
| RN-001 | Un nodo se muestra a un usuario cuando su visibilidad se lo permite y sus idiomas incluyen el idioma actual del usuario; un padre oculto oculta su submenú. | Node, Role, Language | `access::is_shown` |
| RN-002 | Una clave de nodo es única dentro de su menú, y solo se permiten letras, números, guiones bajos y guiones. | Node | `validator::validate` |
| RN-003 | Se rechaza un cambio que dejaría a todos los usuarios sin una página de destino, y la Administración del sitio nunca puede ocultarse ni anidarse. | Node | `validator::validate` |
| RN-004 | Una URL bloqueada envía al usuario a la primera página de reserva que pueda abrir; si ninguna está permitida, a una página explicativa. | Node | `access_guard::evaluate` |
| RN-005 | Los administradores del sitio, las URL externas y las páginas de administración, de inicio de sesión y de plugins nunca se bloquean. | Node | `access_guard::is_immune` |
| RN-006 | Todo un archivo de importación se comprueba antes de guardar nada. | ImportFile | `importer::import` |
| RN-007 | Reordenar mantiene a un padre con su submenú, y un elemento de submenú solo se mueve entre sus hermanos. | Node, Submenu | `manager::reorder` |
| RN-008 | Un elemento de Moodle con campos vacíos conserva los valores de Moodle. | Node | `text` |
| RN-009 | Un nodo de una navegación secundaria se encuentra cuando una página lo muestra, una sola vez, y desde entonces tiene una fila que se puede cambiar. | Node, Menu | `secondary::record_seen` |
| RN-010 | En la administración del sitio cada categoría y cada página, a cualquier profundidad, se puede renombrar, ocultar y ordenar; un enlace del plugin va en una categoría; sus nodos también se ocultan a los administradores de forma predeterminada. | Node, Menu | `admintree::apply` |
| RN-011 | Un administrador del sitio ve todos los nodos, salvo que el plugin o el nodo se lo oculten también a los administradores. | Node, Role | `access::is_allowed` |
| RN-012 | Para cada nodo gana el ámbito más cercano a la página: el curso, luego las categorías que lo rodean y luego el sitio. | Node, Scope | `repository::get_scoped_nodes` |
| RN-013 | Cuando un ámbito cambia el orden de un nodo, fija el orden de toda su navegación. | Node, Scope | `repository::get_scoped_nodes` |
| RN-014 | Eliminar un nodo del sitio elimina sus excepciones. | Node, Scope | `manager::delete` |
| RN-015 | Un nodo se muestra solo cuando su visibilidad, sus roles y sus restricciones de acceso (la restricción de acceso de Moodle) permiten verlo al usuario; un nodo de un curso se comprueba en ese curso. | Node, Condition | `availability::allows` |
| RN-016 | Las fechas de las restricciones hacen que un nodo esté programado antes de su inicio y finalizado después de su fin; aparece y desaparece por sí solo. | Node, Condition | `availability::period` |
| RN-017 | Las excepciones de un curso viajan en la copia de seguridad del curso; el archivo del sitio contiene los nodos del sitio y las excepciones de sus categorías. | Node, Scope | `backup_local_extendednav_plugin`, `exporter::export_file` |
| RN-018 | Una categoría o un curso cambia sus navegaciones en su propia página, por quien gestiona la navegación del sitio o tiene local/extendednav:managecontext en ese contexto; la administración del sitio solo las enumera. | Node, Scope | `context_scope::require_manage`, `node_service::require_scope` |

## Ciclos de vida

### Nodo

![Ciclo de vida de un nodo](./entity-states/entity-states.svg)
