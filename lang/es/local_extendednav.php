<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Spanish strings for local_extendednav.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['action_created'] = 'Creado';
$string['action_deleted'] = 'Eliminado';
$string['action_updated'] = 'Actualizado';
$string['activity_log'] = 'Registro de actividad';
$string['add_node'] = 'Añadir nodo';
$string['apply'] = 'Aplicar';
$string['back_to_nodes'] = 'Volver a la navegación';
$string['badge_blocked'] = 'URL bloqueada';
$string['badge_unavailable'] = 'No disponible actualmente';
$string['badge_unavailable_help'] = 'Moodle no añade este elemento ahora mismo: puede estar desactivado en Administración del sitio > Apariencia > Navegación, haber sido retirado por el tema o proceder de un plugin que ya no está instalado. El plugin tampoco lo añade. Puede eliminarlo; volverá a aparecer si Moodle lo añade de nuevo.';
$string['blockaccess'] = 'Bloquear el acceso por URL';
$string['blockaccess_help'] = 'Cuando está marcado, los usuarios que no pueden ver este elemento tampoco pueden acceder a su URL: si la abren directamente, se les envía al Área personal, después a la Página principal del sitio, después a Mis cursos y después a la página de inicio predeterminada, a la primera que tengan permitida. Si ninguna de estas páginas está permitida, ven una página que explica que la página no está disponible.

Solo se pueden bloquear URL de este sitio. Los idiomas nunca afectan al bloqueo y los administradores del sitio nunca se bloquean.

El bloqueo solo se aplica a las páginas que muestran la cabecera del sitio, de modo que da forma a la navegación; no sustituye a los permisos.';
$string['bulk_delete'] = 'Eliminar los seleccionados';
$string['bulk_delete_confirm'] = '¿Eliminar los nodos seleccionados? Los nodos de Moodle se conservan, salvo que no estén disponibles actualmente. Esta acción no se puede deshacer.';
$string['cachedef_icons'] = 'Iconos que ofrece el selector de iconos';
$string['cachedef_nodes'] = 'Nodos de navegación de todos los menús';
$string['clear_filters'] = 'Limpiar';
$string['conflict_handling'] = 'Nodos existentes';
$string['conflict_handling_help'] = 'Qué hacer con los nodos del archivo cuyo menú y clave ya existen en este sitio: conservar el nodo existente o sustituirlo por el del archivo.';
$string['conflict_overwrite'] = 'Sustituirlos por los del archivo';
$string['conflict_skip'] = 'Conservarlos';
$string['coreoverridealert'] = 'Este es un nodo de Moodle. El título y la URL son opcionales: déjelos vacíos para conservar los valores de Moodle, que siguen traducidos.';
$string['delete_node_confirm'] = '¿Eliminar este nodo? Esta acción no se puede deshacer.';
$string['display'] = 'Visualización';
$string['display_current'] = 'Ventana actual';
$string['display_help'] = 'Cómo se abre el enlace: en la ventana actual, en una ventana nueva o en una ventana emergente del tamaño indicado abajo. Los elementos con subelementos abren siempre su submenú.';
$string['display_new'] = 'Ventana nueva';
$string['display_popup'] = 'Ventana emergente';
$string['doc_example_format'] = 'Ejemplo';
$string['doc_example_tip'] = 'Todos los campos salvo nodekey son opcionales. Los campos vacíos de un nodo de Moodle conservan los valores de Moodle.';
$string['doc_f_blockaccess'] = '1 para bloquear la URL a los usuarios que no pueden ver el nodo, 0 en caso contrario. Solo se usa cuando la visibilidad es 0 o 2.';
$string['doc_f_display'] = 'Cómo se abre el enlace: 0 = ventana actual, 1 = ventana nueva, 2 = ventana emergente.';
$string['doc_f_icon'] = 'Clase de FontAwesome (por ejemplo «fa-book») o icono de Moodle (por ejemplo «i/home»). «none» elimina el icono.';
$string['doc_f_langs'] = 'Códigos de idioma separados por comas (por ejemplo «es,pt_br»). El nodo solo se muestra en el menú para esos idiomas. Vacío significa todos los idiomas.';
$string['doc_f_menu'] = 'Menú del nodo: «primary» o «usermenu». Por defecto, el menú que se está importando.';
$string['doc_f_nodekey'] = 'Clave del nodo, única dentro de su menú. Use una clave de Moodle (por ejemplo «myhome») para cambiar un nodo de Moodle.';
$string['doc_f_order'] = 'Posición del nodo en su menú, siendo 1 la primera. Los archivos exportados enumeran todas las posiciones. Al importar, los nodos que no la tienen conservan su lugar, o van al final cuando son nuevos.';
$string['doc_f_parentkey'] = 'Clave del nodo padre, para mostrar este nodo en su submenú.';
$string['doc_f_popupheight'] = 'Altura de la ventana emergente en píxeles, de 100 a 9999. Se usa cuando display es 2.';
$string['doc_f_popupwidth'] = 'Anchura de la ventana emergente en píxeles, de 100 a 9999. Se usa cuando display es 2.';
$string['doc_f_roles'] = 'Nombres cortos de roles separados por comas (por ejemplo «editingteacher,teacher»). Se usa cuando la visibilidad es 2.';
$string['doc_f_title'] = 'Texto que se muestra. Vacío en un nodo de Moodle conserva el título de Moodle.';
$string['doc_f_tooltip'] = 'Texto que se muestra al pasar el cursor sobre el enlace.';
$string['doc_f_url'] = 'Destino: una ruta de este sitio (por ejemplo «/course/view.php?id=2») o una dirección completa.';
$string['doc_f_visibility'] = '1 = visible para todos, 2 = restringido a los roles indicados, 0 = no visible.';
$string['doc_field'] = 'Campo';
$string['doc_fields_title'] = 'Campos';
$string['doc_intro'] = 'Los archivos de importación son archivos YAML con una lista «nodes». Cada entrada describe un nodo con los campos siguientes.';
$string['documentation'] = 'Formato YAML';
$string['download_example'] = 'Descargar un archivo de ejemplo';
$string['edit_node'] = 'Editar nodo';
$string['enable_plugin'] = 'Activar';
$string['enable_plugin_desc'] = 'Aplica los nodos de navegación y el bloqueo de URL. Cuando está desactivado, se muestra la navegación de Moodle sin cambios y no se bloquea ninguna página.';
$string['entries_count'] = '{$a} entradas';
$string['entries_count_one'] = '1 entrada';
$string['err_admin_child'] = 'La administración del sitio no puede colocarse en un submenú.';
$string['err_admin_hide'] = 'La administración del sitio no puede ocultarse, restringirse, bloquearse ni limitarse a algunos idiomas.';
$string['err_admin_parent'] = 'La administración del sitio no puede tener elementos de submenú.';
$string['err_duplicate_key'] = 'Otro nodo de este menú ya usa esta clave.';
$string['err_duplicate_key_alert'] = 'Otro nodo de este menú ya usa esta clave.';
$string['err_fallbacks_blocked'] = 'Esto bloquearía el Área personal, la Página principal del sitio, Mis cursos y la página de inicio predeterminada para todos los usuarios, y los dejaría sin ninguna página a la que llegar. Desbloquee al menos una de ellas.';
$string['err_icon_invalid'] = 'Use una clase de FontAwesome (por ejemplo «fa-book»), un icono de Moodle (por ejemplo «i/home») o «none».';
$string['err_import_duplicate'] = 'Este nodo aparece más de una vez en el archivo.';
$string['err_import_nodekey'] = 'La entrada no tiene nodekey.';
$string['err_import_order'] = 'El orden debe ser un número entero a partir de 1.';
$string['err_invalid_display'] = 'Elija cómo se abre el enlace: 0, 1 o 2.';
$string['err_invalid_list'] = 'Use solo letras, números, guiones bajos y guiones, separados por comas.';
$string['err_invalid_menu'] = 'Menú desconocido. Use «primary» o «usermenu».';
$string['err_invalid_nodekey'] = 'Use solo letras, números, guiones bajos y guiones.';
$string['err_invalid_visibility'] = 'Visibilidad desconocida. Use 0, 1 o 2.';
$string['err_invalid_yaml_file'] = 'No se pudo leer el archivo. Suba un archivo .yaml o .yml.';
$string['err_invalid_yaml_format'] = 'El archivo no es un YAML válido.';
$string['err_invalid_yaml_structure'] = 'El archivo debe contener una lista «nodes».';
$string['err_parent_haschildren'] = 'Este nodo tiene elementos de submenú, de modo que no puede colocarse en un submenú.';
$string['err_parent_missing'] = 'No hay ningún nodo con la clave «{$a}» en este menú.';
$string['err_parent_self'] = 'Un nodo no puede ser su propio padre.';
$string['err_parent_thirdlevel'] = 'El padre elegido ya está en un submenú. Los menús solo tienen un nivel de submenús.';
$string['err_popup_size'] = 'Introduzca un número entero de {$a->min} a {$a->max}.';
$string['err_restricted_page'] = 'La página que abrió no está disponible para su cuenta, ni tampoco el Área personal, la Página principal del sitio ni Mis cursos. Póngase en contacto con el administrador del sitio si cree que se trata de un error.';
$string['err_text_html'] = 'No se permite HTML aquí.';
$string['err_title_usermenu_chars'] = 'Los títulos del menú de usuario no pueden contener «|» ni saltos de línea.';
$string['err_url_forbidden'] = 'Este tipo de URL no está permitido.';
$string['err_url_invalid'] = 'Introduzca una URL válida, por ejemplo «/course/view.php?id=2» o «https://example.com».';
$string['eventnodecreated'] = 'Nodo de navegación creado';
$string['eventnodedeleted'] = 'Nodo de navegación eliminado';
$string['eventnodeupdated'] = 'Nodo de navegación actualizado';
$string['export_all_btn'] = 'Exportar todo';
$string['export_selected'] = 'Exportar los seleccionados';
$string['extendednav:manage'] = 'Gestionar los nodos de navegación';
$string['filter_action'] = 'Filtrar por acción';
$string['filter_action_any'] = 'Todas las acciones';
$string['filter_datefrom'] = 'Desde';
$string['filter_dateto'] = 'Hasta';
$string['filter_invalidperiod'] = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
$string['filter_parent'] = 'Filtrar por clave del padre';
$string['filter_parent_any'] = 'Clave del padre';
$string['filter_visibility'] = 'Filtrar por visibilidad';
$string['icon'] = 'Icono';
$string['icon_choose'] = 'Elegir icono';
$string['icon_clear'] = 'Limpiar';
$string['icon_help'] = 'Seleccione el cuadrado situado junto al campo para elegir un icono de una lista con búsqueda, o escriba una clase de FontAwesome (por ejemplo «fa-book» o «fa-brands fa-github») o un icono de Moodle (por ejemplo «core:i/home», o «i/home» para el núcleo). Déjelo vacío para conservar el predeterminado o escriba «none» para eliminar el icono. En el menú de usuario, los iconos de Moodle se muestran como su icono de FontAwesome.';
$string['icon_info_brand'] = 'Icono de marca de FontAwesome';
$string['icon_info_fontawesome'] = 'Icono de FontAwesome';
$string['icon_info_invalid'] = 'No es un icono que el plugin pueda mostrar. Elija uno o escriba una clase de FontAwesome o un icono de Moodle.';
$string['icon_info_moodle'] = 'Icono de Moodle de {$a}';
$string['icon_info_none'] = 'Sin icono';
$string['icon_loading'] = 'Cargando iconos...';
$string['icon_noresults'] = 'Ningún icono coincide con su búsqueda.';
$string['icon_picker'] = 'Elegir un icono';
$string['icon_search'] = 'Buscar iconos';
$string['icon_tab_brands'] = 'Marcas';
$string['icon_tab_fontawesome'] = 'FontAwesome';
$string['icon_tab_moodle'] = 'Iconos de Moodle';
$string['icon_unavailable'] = 'Esta versión de Moodle guarda su lista de FontAwesome donde el plugin no puede leerla. Escriba en su lugar la clase en el campo del icono, por ejemplo «fa-book».';
$string['import'] = 'Importar';
$string['import_entry'] = 'Entrada {$a}';
$string['import_failed'] = 'No se importó nada. Corrija estos problemas en el archivo e inténtelo de nuevo:';
$string['import_instructions_desc'] = 'El archivo debe contener una lista «nodes». Primero se comprueba cada nodo; si alguno no es válido, no se importa nada.';
$string['import_instructions_title'] = 'Antes de importar';
$string['import_nodes'] = 'Importar nodos';
$string['import_success'] = 'Importación terminada: {$a->created} creados, {$a->updated} sustituidos, {$a->skipped} conservados.';
$string['langs'] = 'Idiomas';
$string['langs_all'] = 'Todos los idiomas';
$string['langs_help'] = 'Muestra este elemento en el menú solo a los usuarios cuyo idioma actual esté seleccionado. Déjelo vacío para mostrarlo en todos los idiomas. Un idioma también abarca sus variantes regionales: español (es) incluye español - México (es_mx).

Los idiomas solo controlan si el elemento aparece en el menú. Nunca afectan al acceso a su URL, porque los usuarios pueden cambiar su propio idioma en cualquier momento. El acceso se controla con Visibilidad, Roles permitidos y «Bloquear el acceso por URL».

Para traducir un elemento, cree un nodo por idioma con la misma URL, los mismos roles y la misma visibilidad.';
$string['langs_hidden_notice'] = 'Este elemento está oculto para todos, de modo que los idiomas seleccionados no tienen efecto en el menú. Se conservan por si vuelve a hacer visible el elemento. El acceso a la URL depende únicamente de «Bloquear el acceso por URL».';
$string['manage_nodes'] = 'Gestionar la navegación';
$string['manage_nodes_desc'] = 'Añada elementos a la navegación primaria y al menú de usuario, y cambie, reordene, anide u oculte los elementos propios de Moodle.';
$string['menu_type'] = 'Menú';
$string['movenode'] = 'Mover {$a}';
$string['native_cannot_delete'] = 'Los nodos de Moodle no pueden eliminarse. Establezca su visibilidad en «No visible» para ocultarlos.';
$string['nativenodeinfo'] = 'Nodo de Moodle';
$string['no_nodes_export'] = 'No hay nodos que exportar.';
$string['node_saved'] = 'Nodo guardado.';
$string['nodekey'] = 'Clave del nodo';
$string['nodekey_help'] = 'Clave del elemento, única dentro de su menú. Para cambiar un elemento de Moodle, use su clave: home, myhome, mycourses o siteadminnode en la navegación primaria. Para un elemento nuevo, elija una clave nueva con letras, números, guiones bajos y guiones, por ejemplo «help_es».';
$string['nodes_count'] = '{$a} elementos';
$string['nodes_count_one'] = '1 elemento';
$string['nodes_deleted'] = '{$a} nodo(s) eliminado(s).';
$string['nodes_selected'] = 'nodo(s) seleccionado(s)';
$string['none_title'] = 'Sin título';
$string['opt_invalid_admin_submenu'] = 'La administración del sitio es siempre un elemento de nivel superior, de modo que no puede colocarse en un submenú.';
$string['opt_invalid_has_submenus'] = 'Este nodo tiene elementos de submenú, de modo que no puede colocarse a su vez en un submenú.';
$string['opt_native'] = '{$a->text} ({$a->key})';
$string['opt_none_root'] = '- Ninguno (nivel superior) -';
$string['opt_plugin'] = '{$a->title} ({$a->key})';
$string['order'] = 'Orden';
$string['parentkey'] = 'Padre';
$string['parentkey_help'] = 'Muestra este elemento en el submenú de otro elemento. Solo los elementos de nivel superior pueden ser padres, y un elemento con subelementos no puede colocarse a su vez en un submenú.';
$string['parenturlunused'] = 'Este nodo tiene elementos de submenú, de modo que abre su submenú en lugar de seguir su URL. La URL se sigue usando para el bloqueo.';
$string['pluginname'] = 'Navegación ampliada';
$string['popupheight'] = 'Altura de la ventana emergente (en píxeles)';
$string['popupwidth'] = 'Anchura de la ventana emergente (en píxeles)';
$string['primary_nav'] = 'Navegación primaria';
$string['privacy:metadata'] = 'El plugin Navegación ampliada no almacena ningún dato personal. Los cambios en los nodos de navegación se registran en el registro estándar.';
$string['restricted_title'] = 'Página no disponible';
$string['roles'] = 'Roles permitidos';
$string['roles_help'] = 'Los usuarios con al menos uno de estos roles, en cualquier contexto, ven el elemento. Todo usuario con sesión iniciada tiene además el rol predeterminado de los usuarios autenticados.';
$string['search_placeholder'] = 'Buscar por título, clave del nodo o URL...';
$string['settings_page'] = 'Ajustes';
$string['submenuof'] = 'Submenú de: {$a}';
$string['title'] = 'Título';
$string['title_help'] = 'Texto que se muestra en el menú. En un elemento de Moodle, déjelo vacío para conservar el título de Moodle, que está traducido. Una cadena de idioma puede usarse como «identificador,componente», por ejemplo «profile,moodle».';
$string['tooltip'] = 'Información emergente';
$string['tooltip_help'] = 'Texto que se muestra al pasar el cursor sobre el enlace. Opcional.';
$string['unknown_lang'] = 'No instalado: {$a}';
$string['unknown_role'] = 'Rol desconocido: {$a}';
$string['url'] = 'URL';
$string['url_help'] = 'A dónde lleva el elemento: una ruta de este sitio, como «/course/view.php?id=2», o una dirección completa. En un elemento de Moodle, déjelo vacío para conservar la URL de Moodle.';
$string['user_menu'] = 'Menú de usuario';
$string['view_documentation'] = 'Ver el formato YAML';
$string['vis_all'] = 'Visible para todos';
$string['vis_hidden'] = 'No visible';
$string['vis_roles'] = 'Restringido';
$string['visibility'] = 'Visibilidad';
$string['visibility_help'] = 'Quién ve el elemento en el menú: todos, solo los usuarios con los roles permitidos o nadie. Con «Bloquear el acceso por URL», los usuarios que no pueden ver el elemento tampoco pueden abrir su URL.';
$string['warn_fallbacks_blocked_for_some'] = 'El Área personal, la Página principal del sitio, Mis cursos y la página de inicio predeterminada están todos bloqueados para los usuarios sin algunos roles. Esos usuarios verán una página de error en lugar de ser redirigidos.';
$string['warn_unknown_lang'] = 'El idioma «{$a}» no está instalado.';
$string['warn_unknown_role'] = 'No hay ningún rol con el nombre corto «{$a}».';
$string['yamlconfigfile'] = 'Archivo YAML';
$string['yamlconfigfile_help'] = 'Un archivo .yaml o .yml con una lista «nodes», como los archivos que crea «Exportar todo».';
