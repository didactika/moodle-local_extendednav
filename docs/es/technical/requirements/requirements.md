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
| RF-001 | Un administrador puede añadir, cambiar y eliminar elementos de la navegación principal y del menú de usuario, incluidos los elementos de Moodle. | Debe | `classes/local/node/manager.php` | UC-001 |
| RF-002 | Un administrador puede reordenar los elementos, manteniendo juntos los submenús. | Debería | `classes/external/reorder_nodes.php` | UC-002 |
| RF-003 | Un administrador puede exportar elementos a un archivo YAML e importarlos desde uno, comprobando antes todo el archivo. | Debería | `classes/local/io/importer.php` | UC-003 |
| RF-004 | Cada usuario ve los elementos que sus roles e idioma permiten, en ambos menús. | Debe | `classes/hooks.php` | UC-004 |
| RF-005 | Una página puede bloquearse para los usuarios que no pueden ver su elemento. | Debe | `classes/local/navigation/access_guard.php` | UC-005 |
| RF-006 | Los cambios de los elementos quedan registrados en el registro de actividad, cada uno con los campos que tocó y sus valores antes y después. | Podría | `classes/event/` | UC-001 |
| RF-007 | Un administrador puede ordenar, renombrar, ocultar y ampliar la navegación de un curso, de la página principal del sitio, de cada tipo de actividad, de una categoría y de la administración del sitio. | Debe | `classes/local/navigation/injector.php` | UC-006 |
| RF-008 | Una categoría o un curso pueden cambiar una navegación para sí mismos, y todo lo que hay dentro los sigue. | Debería | `classes/local/node/repository.php` | UC-007 |
| RF-009 | Un administrador del sitio ve todos los nodos, salvo que el plugin o el nodo se lo oculten también a los administradores. | Debe | `classes/local/node/access.php` | UC-004 |
| RF-010 | La exportación y la importación de nodos incluyen las excepciones de categorías y cursos. | Debería | `classes/local/io/importer.php` | UC-003 |
| RF-011 | La página de gestión lista, filtra (por visibilidad, origen y tipo de restricción), ordena y cambia nodos sin recargar la página. | Debe | `amd/src/manage.js` | UC-001, UC-002, UC-006 |
| RF-012 | Cada tipo de actividad tiene su propia navegación, cuyos elementos se encuentran en las actividades del sitio, también los que un plugin añade solo a algunas actividades. | Debe | `classes/menu/activity.php`, `classes/local/navigation/discovery.php` | UC-006, UC-008 |
| RF-013 | Una actividad cambia su propia navegación desde su página, y los cambios de un curso y de sus actividades viajan en sus copias de seguridad. | Debería | `classes/local/navigation/context_scope.php`, `backup/moodle2/` | UC-007 |
| RF-014 | Un nodo de la administración del sitio sin enlace es una categoría del plugin, que contiene enlaces y otras categorías; en el nivel superior es una pestaña cuando contiene una categoría. | Debería | `classes/menu/admintree.php` | UC-006 |
| RF-015 | Un usuario organiza su propia página de preferencias, y el sitio la organiza para todos, como carpetas de secciones y opciones. | Should | `classes/local/navigation/preferences_injector.php` | UC-009 |
| RF-016 | Las entradas del registro de actividad se pueden conservar durante un tiempo que el sitio establece, y una tarea que sigue ese ajuste elimina las más antiguas. | Could | `classes/task/clean_activity_log.php` | UC-010 |
| RF-017 | Los valores predeterminados de las copias de seguridad y de las restauraciones deciden si se incluyen los cambios de un curso y de sus actividades. | Should | `settings.php`, `backup/moodle2/` | UC-011 |
| RF-018 | Todo lo que hace la página de gestión se puede hacer mediante servicios web, reunidos en un solo servicio. | Should | `classes/external/`, `db/services.php` | UC-012 |
| RF-019 | En una categoría, un curso o una actividad, ver la navegación y cada tipo de cambio tienen su propia capacidad. | Should | `db/access.php`, `classes/local/navigation/context_scope.php` | UC-007 |
| RF-020 | Lo que contiene un archivo se elige entre navegaciones, tipos de actividad, usuarios, cursos y categorías, que se buscan de diez en diez; una importación omite, con un aviso, lo que el sitio no tiene. | Should | `classes/form/export_form.php`, `classes/external/search_export.php`, `classes/local/io/importer.php` | UC-003, UC-012 |
| RF-021 | Un nodo sin URL agrupa otros, como un submenú, una sección, una categoría o una pestaña, y las listas lo muestran como una carpeta. | Should | `classes/local/listing/nodes.php`, `classes/form/node_form.php` | UC-001, UC-006 |
| RF-022 | Recorridos guiados explican la página Navegación de un curso o una actividad, la página donde un usuario organiza sus preferencias y el formulario de un nodo; se importan al instalar o actualizar el plugin y se gestionan en la página de recorridos de Moodle. | Could | `tours/`, `lib.php` | UC-001, UC-009 |
| RF-023 | Ocultar elementos puede bloquear también sus URL, para quien pueda restringirlos. | Should | `classes/local/node/manager.php` | UC-005 |

## No funcionales

| ID | Atributo de calidad | Requisito | Medida | Prioridad |
| --- | --- | --- | --- | --- |
| RNF-001 | Seguridad | Gestionar la navegación del sitio necesita su propia capacidad, que ningún rol tiene de forma predeterminada; en una categoría, un curso o una actividad, los docentes con permiso de edición y los gestores tienen las capacidades del contexto, y todos los usuarios tienen la que organiza su propia página de preferencias. | `local/extendednav:manage` sin arquetipo; `db/access.php` | Must |
| RNF-002 | Disponibilidad | El plugin nunca deja a todos los usuarios sin una página de destino. | `validator::validate` | Debe |
| RNF-003 | Privacidad | El único dato personal que el plugin conserva es cómo un usuario organizó su propia página de preferencias, que se exporta y se elimina con la API de privacidad y con el usuario. | `classes/privacy/provider.php`, `classes/observer.php` | Must |
| RNF-004 | Integridad | Una importación no cambia nada si algún elemento no es válido. | `importer::import` | Debe |
| RNF-005 | Rendimiento | Una página que no tiene nada personalizado en su navegación solo lee la caché del plugin. Las páginas de los usuarios nunca construyen otra página: registran lo que muestran, y los elementos de los tipos de actividad los busca una tarea por la noche o a petición. | `injector::apply`, `secondary::record_seen`, `discovery::kind` | Debe |
| RNF-006 | Rendimiento | Ninguna consulta se ejecuta dentro de un bucle en un camino que crece con los datos: restaurar, exportar, importar, actualizar y las listas leen en bloque, y el registro de actividad se depura por tiempo, por lotes, a través de su índice. | `tests/performance_test.php` (miles de nodos), `classes/task/clean_activity_log.php` | Must |
| RNF-007 | Seguridad | La página para un usuario sin ninguna página adonde ir es de ese usuario y no necesita capacidad; un invitado es enviado a iniciar sesión. | `restricted.php` | Should |

## Reglas de negocio

Las reglas están en el [diccionario del modelo de dominio](../domain/README.md#reglas-de-negocio). Las que cambian el comportamiento se repiten aquí con su ID.
