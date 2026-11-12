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
 * French strings for local_extendednav.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['action_created'] = 'Créé';
$string['action_deleted'] = 'Supprimé';
$string['action_updated'] = 'Mis à jour';
$string['activity_log'] = 'Journal d’activité';
$string['add_node'] = 'Ajouter un nœud';
$string['apply'] = 'Appliquer';
$string['back_to_nodes'] = 'Retour à la navigation';
$string['badge_blocked'] = 'URL bloquée';
$string['badge_unavailable'] = 'Actuellement indisponible';
$string['badge_unavailable_help'] = 'Moodle n’ajoute pas cet élément pour le moment : il peut être désactivé dans Administration du site > Apparence > Navigation, retiré par le thème ou provenir d’un plugin qui n’est plus installé. Le plugin ne l’ajoute pas non plus. Vous pouvez le supprimer ; il reviendra si Moodle l’ajoute de nouveau.';
$string['blockaccess'] = 'Bloquer l’accès par URL';
$string['blockaccess_help'] = 'Lorsque cette option est cochée, les utilisateurs qui ne peuvent pas voir cet élément sont aussi tenus à l’écart de son URL : s’ils l’ouvrent directement, ils sont redirigés vers le Tableau de bord, puis la Page d’accueil du site, puis Mes cours, puis la page d’accueil par défaut, selon ce qui leur est permis. Si aucune de ces pages n’est autorisée, ils voient une page expliquant que la page n’est pas disponible.

Seules les URL de ce site peuvent être bloquées. Les langues n’affectent jamais le blocage et les administrateurs du site ne sont jamais bloqués.

Le blocage ne s’applique qu’aux pages qui affichent l’en-tête du site ; il façonne donc la navigation, mais ne remplace pas les permissions.';
$string['bulk_delete'] = 'Supprimer la sélection';
$string['bulk_delete_confirm'] = 'Supprimer les nœuds sélectionnés ? Les nœuds de Moodle sont conservés, sauf s’ils sont actuellement indisponibles. Cette action est irréversible.';
$string['cachedef_icons'] = 'Icônes proposées par le sélecteur d’icônes';
$string['cachedef_nodes'] = 'Nœuds de navigation de tous les menus';
$string['clear_filters'] = 'Effacer';
$string['conflict_handling'] = 'Nœuds existants';
$string['conflict_handling_help'] = 'Que faire des nœuds du fichier dont le menu et la clé existent déjà sur ce site : conserver le nœud existant ou le remplacer par celui du fichier.';
$string['conflict_overwrite'] = 'Les remplacer par ceux du fichier';
$string['conflict_skip'] = 'Les conserver';
$string['coreoverridealert'] = 'Ceci est un nœud de Moodle. Le titre et l’URL sont facultatifs : laissez-les vides pour conserver les valeurs de Moodle, qui restent traduites.';
$string['delete_node_confirm'] = 'Supprimer ce nœud ? Cette action est irréversible.';
$string['display'] = 'Affichage';
$string['display_current'] = 'Fenêtre actuelle';
$string['display_help'] = 'La façon dont le lien s’ouvre : dans la fenêtre actuelle, dans une nouvelle fenêtre ou dans une fenêtre contextuelle de la taille indiquée ci-dessous. Les éléments ayant des sous-éléments ouvrent toujours leur sous-menu à la place.';
$string['display_new'] = 'Nouvelle fenêtre';
$string['display_popup'] = 'Fenêtre contextuelle';
$string['doc_example_format'] = 'Exemple';
$string['doc_example_tip'] = 'Tous les champs sauf nodekey sont facultatifs. Les champs vides d’un nœud de Moodle conservent les valeurs de Moodle.';
$string['doc_f_blockaccess'] = '1 pour bloquer l’URL aux utilisateurs qui ne peuvent pas voir le nœud, 0 sinon. Utilisé uniquement lorsque la visibilité vaut 0 ou 2.';
$string['doc_f_display'] = 'La façon dont le lien s’ouvre : 0 = fenêtre actuelle, 1 = nouvelle fenêtre, 2 = fenêtre contextuelle.';
$string['doc_f_icon'] = 'Classe FontAwesome (par exemple « fa-book ») ou icône Moodle (par exemple « i/home »). « none » supprime l’icône.';
$string['doc_f_langs'] = 'Codes de langue séparés par des virgules (par exemple « es,pt_br »). Le nœud n’est affiché dans le menu que pour ces langues. Vide signifie toutes les langues.';
$string['doc_f_menu'] = 'Menu du nœud : « primary » ou « usermenu ». Par défaut, le menu en cours d’importation.';
$string['doc_f_nodekey'] = 'Clé du nœud, unique dans son menu. Utilisez une clé de Moodle (par exemple « myhome ») pour modifier un nœud de Moodle.';
$string['doc_f_order'] = 'Position du nœud dans son menu, 1 étant la première. Les fichiers exportés indiquent toutes les positions. À l’importation, les nœuds qui n’en ont pas conservent leur place, ou passent en dernier lorsqu’ils sont nouveaux.';
$string['doc_f_parentkey'] = 'Clé du nœud parent, pour afficher ce nœud dans son sous-menu.';
$string['doc_f_popupheight'] = 'Hauteur de la fenêtre contextuelle en pixels, de 100 à 9999. Utilisée lorsque display vaut 2.';
$string['doc_f_popupwidth'] = 'Largeur de la fenêtre contextuelle en pixels, de 100 à 9999. Utilisée lorsque display vaut 2.';
$string['doc_f_roles'] = 'Noms abrégés de rôles séparés par des virgules (par exemple « editingteacher,teacher »). Utilisé lorsque la visibilité vaut 2.';
$string['doc_f_title'] = 'Texte affiché. Vide sur un nœud de Moodle, il conserve le titre de Moodle.';
$string['doc_f_tooltip'] = 'Texte affiché au survol du lien.';
$string['doc_f_url'] = 'Destination : un chemin de ce site (par exemple « /course/view.php?id=2 ») ou une adresse complète.';
$string['doc_f_visibility'] = '1 = visible par tous, 2 = limité aux rôles indiqués, 0 = non visible.';
$string['doc_field'] = 'Champ';
$string['doc_fields_title'] = 'Champs';
$string['doc_intro'] = 'Les fichiers d’importation sont des fichiers YAML contenant une liste « nodes ». Chaque entrée décrit un nœud avec les champs ci-dessous.';
$string['documentation'] = 'Format YAML';
$string['download_example'] = 'Télécharger un fichier d’exemple';
$string['edit_node'] = 'Modifier le nœud';
$string['enable_plugin'] = 'Activer';
$string['enable_plugin_desc'] = 'Applique les nœuds de navigation et le blocage d’URL. Lorsque l’option est désactivée, la navigation de Moodle s’affiche sans modification et aucune page n’est bloquée.';
$string['entries_count'] = '{$a} entrées';
$string['entries_count_one'] = '1 entrée';
$string['err_admin_child'] = 'L’administration du site ne peut pas être placée dans un sous-menu.';
$string['err_admin_hide'] = 'L’administration du site ne peut pas être masquée, restreinte, bloquée ni limitée à certaines langues.';
$string['err_admin_parent'] = 'L’administration du site ne peut pas avoir d’éléments de sous-menu.';
$string['err_duplicate_key'] = 'Un autre nœud de ce menu utilise déjà cette clé.';
$string['err_duplicate_key_alert'] = 'Un autre nœud de ce menu utilise déjà cette clé.';
$string['err_fallbacks_blocked'] = 'Cela bloquerait le Tableau de bord, la Page d’accueil du site, Mes cours et la page d’accueil par défaut pour tous les utilisateurs, ne leur laissant aucune page d’arrivée. Débloquez-en au moins une.';
$string['err_icon_invalid'] = 'Utilisez une classe FontAwesome (par exemple « fa-book »), une icône Moodle (par exemple « i/home ») ou « none ».';
$string['err_import_duplicate'] = 'Ce nœud apparaît plus d’une fois dans le fichier.';
$string['err_import_nodekey'] = 'L’entrée n’a pas de nodekey.';
$string['err_import_order'] = 'L’ordre doit être un nombre entier à partir de 1.';
$string['err_invalid_display'] = 'Choisissez comment le lien s’ouvre : 0, 1 ou 2.';
$string['err_invalid_list'] = 'Utilisez uniquement des lettres, des chiffres, des tirets bas et des tirets, séparés par des virgules.';
$string['err_invalid_menu'] = 'Menu inconnu. Utilisez « primary » ou « usermenu ».';
$string['err_invalid_nodekey'] = 'Utilisez uniquement des lettres, des chiffres, des tirets bas et des tirets.';
$string['err_invalid_visibility'] = 'Visibilité inconnue. Utilisez 0, 1 ou 2.';
$string['err_invalid_yaml_file'] = 'Le fichier n’a pas pu être lu. Téléversez un fichier .yaml ou .yml.';
$string['err_invalid_yaml_format'] = 'Le fichier n’est pas un YAML valide.';
$string['err_invalid_yaml_structure'] = 'Le fichier doit contenir une liste « nodes ».';
$string['err_parent_haschildren'] = 'Ce nœud a des éléments de sous-menu ; il ne peut donc pas être placé dans un sous-menu.';
$string['err_parent_missing'] = 'Il n’y a aucun nœud avec la clé « {$a} » dans ce menu.';
$string['err_parent_self'] = 'Un nœud ne peut pas être son propre parent.';
$string['err_parent_thirdlevel'] = 'Le parent choisi est déjà dans un sous-menu. Les menus n’ont qu’un seul niveau de sous-menus.';
$string['err_popup_size'] = 'Saisissez un nombre entier de {$a->min} à {$a->max}.';
$string['err_restricted_page'] = 'La page que vous avez ouverte n’est pas disponible pour votre compte, pas plus que le Tableau de bord, la Page d’accueil du site ou Mes cours. Contactez l’administrateur du site si vous pensez qu’il s’agit d’une erreur.';
$string['err_text_html'] = 'Le HTML n’est pas autorisé ici.';
$string['err_title_usermenu_chars'] = 'Les titres du menu utilisateur ne peuvent pas contenir « | » ni de sauts de ligne.';
$string['err_url_forbidden'] = 'Ce type d’URL n’est pas autorisé.';
$string['err_url_invalid'] = 'Saisissez une URL valide, par exemple « /course/view.php?id=2 » ou « https://example.com ».';
$string['eventnodecreated'] = 'Nœud de navigation créé';
$string['eventnodedeleted'] = 'Nœud de navigation supprimé';
$string['eventnodeupdated'] = 'Nœud de navigation mis à jour';
$string['export_all_btn'] = 'Tout exporter';
$string['export_selected'] = 'Exporter la sélection';
$string['extendednav:manage'] = 'Gérer les nœuds de navigation';
$string['filter_action'] = 'Filtrer par action';
$string['filter_action_any'] = 'Toutes les actions';
$string['filter_datefrom'] = 'Du';
$string['filter_dateto'] = 'Jusqu’au';
$string['filter_invalidperiod'] = 'La date de fin ne peut pas être antérieure à la date de début.';
$string['filter_parent'] = 'Filtrer par clé du parent';
$string['filter_parent_any'] = 'Clé du parent';
$string['filter_visibility'] = 'Filtrer par visibilité';
$string['icon'] = 'Icône';
$string['icon_choose'] = 'Choisir l’icône';
$string['icon_clear'] = 'Effacer';
$string['icon_help'] = 'Sélectionnez le carré à côté du champ pour choisir une icône dans une liste avec recherche, ou saisissez une classe FontAwesome (par exemple « fa-book » ou « fa-brands fa-github ») ou une icône Moodle (par exemple « core:i/home », ou « i/home » pour le noyau). Laissez vide pour conserver celle par défaut, ou saisissez « none » pour supprimer l’icône. Dans le menu utilisateur, les icônes Moodle sont affichées sous la forme de leur icône FontAwesome.';
$string['icon_info_brand'] = 'Icône de marque FontAwesome';
$string['icon_info_fontawesome'] = 'Icône FontAwesome';
$string['icon_info_invalid'] = 'Ce n’est pas une icône que le plugin peut afficher. Choisissez-en une, ou saisissez une classe FontAwesome ou une icône Moodle.';
$string['icon_info_moodle'] = 'Icône Moodle de {$a}';
$string['icon_info_none'] = 'Aucune icône';
$string['icon_loading'] = 'Chargement des icônes...';
$string['icon_noresults'] = 'Aucune icône ne correspond à votre recherche.';
$string['icon_picker'] = 'Choisir une icône';
$string['icon_search'] = 'Rechercher des icônes';
$string['icon_tab_brands'] = 'Marques';
$string['icon_tab_fontawesome'] = 'FontAwesome';
$string['icon_tab_moodle'] = 'Icônes Moodle';
$string['icon_unavailable'] = 'Cette version de Moodle conserve sa liste FontAwesome là où le plugin ne peut pas la lire. Saisissez plutôt la classe dans le champ de l’icône, par exemple « fa-book ».';
$string['import'] = 'Importer';
$string['import_entry'] = 'Entrée {$a}';
$string['import_failed'] = 'Rien n’a été importé. Corrigez ces problèmes dans le fichier et réessayez :';
$string['import_instructions_desc'] = 'Le fichier doit contenir une liste « nodes ». Chaque nœud est d’abord vérifié ; si un nœud est invalide, rien n’est importé.';
$string['import_instructions_title'] = 'Avant d’importer';
$string['import_nodes'] = 'Importer des nœuds';
$string['import_success'] = 'Importation terminée : {$a->created} créés, {$a->updated} remplacés, {$a->skipped} conservés.';
$string['langs'] = 'Langues';
$string['langs_all'] = 'Toutes les langues';
$string['langs_help'] = 'Affiche cet élément dans le menu uniquement aux utilisateurs dont la langue actuelle est sélectionnée. Laissez vide pour l’afficher dans toutes les langues. Une langue couvre aussi ses variantes régionales : l’espagnol (es) inclut l’espagnol - Mexique (es_mx).

Les langues contrôlent seulement si l’élément apparaît dans le menu. Elles n’affectent jamais l’accès à son URL, car les utilisateurs peuvent changer leur propre langue à tout moment. L’accès est contrôlé par la Visibilité, les Rôles autorisés et « Bloquer l’accès par URL ».

Pour traduire un élément, créez un nœud par langue avec la même URL, les mêmes rôles et la même visibilité.';
$string['langs_hidden_notice'] = 'Cet élément est masqué pour tout le monde ; les langues sélectionnées n’ont donc aucun effet sur le menu. Elles sont conservées au cas où vous rendriez de nouveau l’élément visible. L’accès à l’URL dépend uniquement de « Bloquer l’accès par URL ».';
$string['manage_nodes'] = 'Gérer la navigation';
$string['manage_nodes_desc'] = 'Ajoutez des éléments à la navigation principale et au menu utilisateur, et modifiez, réordonnez, imbriquez ou masquez les éléments propres à Moodle.';
$string['menu_type'] = 'Menu';
$string['movenode'] = 'Déplacer {$a}';
$string['native_cannot_delete'] = 'Les nœuds de Moodle ne peuvent pas être supprimés. Définissez leur visibilité sur « Non visible » pour les masquer.';
$string['nativenodeinfo'] = 'Nœud de Moodle';
$string['no_nodes_export'] = 'Il n’y a aucun nœud à exporter.';
$string['node_saved'] = 'Nœud enregistré.';
$string['nodekey'] = 'Clé du nœud';
$string['nodekey_help'] = 'Clé de l’élément, unique dans son menu. Pour modifier un élément de Moodle, utilisez sa clé : home, myhome, mycourses ou siteadminnode dans la navigation principale. Pour un nouvel élément, choisissez une nouvelle clé composée de lettres, de chiffres, de tirets bas et de tirets, par exemple « help_es ».';
$string['nodes_count'] = '{$a} éléments';
$string['nodes_count_one'] = '1 élément';
$string['nodes_deleted'] = '{$a} nœud(s) supprimé(s).';
$string['nodes_selected'] = 'nœud(s) sélectionné(s)';
$string['none_title'] = 'Sans titre';
$string['opt_invalid_admin_submenu'] = 'L’administration du site est toujours un élément de premier niveau ; elle ne peut donc pas être placée dans un sous-menu.';
$string['opt_invalid_has_submenus'] = 'Ce nœud a des éléments de sous-menu ; il ne peut donc pas être placé lui-même dans un sous-menu.';
$string['opt_native'] = '{$a->text} ({$a->key})';
$string['opt_none_root'] = '- Aucun (premier niveau) -';
$string['opt_plugin'] = '{$a->title} ({$a->key})';
$string['order'] = 'Ordre';
$string['parentkey'] = 'Parent';
$string['parentkey_help'] = 'Affiche cet élément dans le sous-menu d’un autre élément. Seuls les éléments de premier niveau peuvent être parents, et un élément ayant des sous-éléments ne peut pas être placé lui-même dans un sous-menu.';
$string['parenturlunused'] = 'Ce nœud a des éléments de sous-menu ; il ouvre donc son sous-menu au lieu de suivre son URL. L’URL reste utilisée pour le blocage.';
$string['pluginname'] = 'Navigation étendue';
$string['popupheight'] = 'Hauteur de la fenêtre contextuelle (en pixels)';
$string['popupwidth'] = 'Largeur de la fenêtre contextuelle (en pixels)';
$string['primary_nav'] = 'Navigation principale';
$string['privacy:metadata'] = 'Le plugin Navigation étendue ne stocke aucune donnée personnelle. Les modifications des nœuds de navigation sont consignées dans le journal standard.';
$string['restricted_title'] = 'Page non disponible';
$string['roles'] = 'Rôles autorisés';
$string['roles_help'] = 'Les utilisateurs ayant au moins l’un de ces rôles, dans n’importe quel contexte, voient l’élément. Tout utilisateur connecté possède aussi le rôle par défaut des utilisateurs authentifiés.';
$string['search_placeholder'] = 'Rechercher par titre, clé du nœud ou URL...';
$string['settings_page'] = 'Paramètres';
$string['submenuof'] = 'Sous-menu de : {$a}';
$string['title'] = 'Titre';
$string['title_help'] = 'Texte affiché dans le menu. Sur un élément de Moodle, laissez vide pour conserver le titre de Moodle, qui est traduit. Une chaîne de langue peut être utilisée sous la forme « identifiant,composant », par exemple « profile,moodle ».';
$string['tooltip'] = 'Infobulle';
$string['tooltip_help'] = 'Texte affiché au survol du lien. Facultatif.';
$string['unknown_lang'] = 'Non installée : {$a}';
$string['unknown_role'] = 'Rôle inconnu : {$a}';
$string['url'] = 'URL';
$string['url_help'] = 'Où mène l’élément : un chemin de ce site, comme « /course/view.php?id=2 », ou une adresse complète. Sur un élément de Moodle, laissez vide pour conserver l’URL de Moodle.';
$string['user_menu'] = 'Menu utilisateur';
$string['view_documentation'] = 'Voir le format YAML';
$string['vis_all'] = 'Visible par tous';
$string['vis_hidden'] = 'Non visible';
$string['vis_roles'] = 'Restreint';
$string['visibility'] = 'Visibilité';
$string['visibility_help'] = 'Qui voit l’élément dans le menu : tout le monde, uniquement les utilisateurs ayant les rôles autorisés, ou personne. Avec « Bloquer l’accès par URL », les utilisateurs qui ne peuvent pas voir l’élément ne peuvent pas non plus ouvrir son URL.';
$string['warn_fallbacks_blocked_for_some'] = 'Le Tableau de bord, la Page d’accueil du site, Mes cours et la page d’accueil par défaut sont tous bloqués pour les utilisateurs sans certains rôles. Ces utilisateurs verront une page d’erreur au lieu d’être redirigés.';
$string['warn_unknown_lang'] = 'La langue « {$a} » n’est pas installée.';
$string['warn_unknown_role'] = 'Il n’existe aucun rôle avec le nom abrégé « {$a} ».';
$string['yamlconfigfile'] = 'Fichier YAML';
$string['yamlconfigfile_help'] = 'Un fichier .yaml ou .yml contenant une liste « nodes », comme les fichiers créés par « Tout exporter ».';
