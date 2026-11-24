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
$string['activity_empty_text'] = 'Les nœuds de cette navigation qui sont créés, modifiés ou supprimés apparaissent ici.';
$string['activity_empty_title'] = 'Aucune activité pour l’instant';
$string['activity_filter_action'] = 'Action';
$string['activity_filter_date'] = 'Date';
$string['activity_from'] = 'Du';
$string['activity_log'] = 'Journal d’activité';
$string['activity_node'] = 'Nœud';
$string['activity_nolog'] = 'Le journal standard est désactivé, les modifications ne sont donc pas enregistrées. Activez-le dans Administration du site > Plugins > Journalisation.';
$string['activity_report'] = 'Activité de la navigation étendue';
$string['activity_search'] = 'Rechercher par clé du nœud ou personne';
$string['activity_to'] = 'Au';
$string['add_node'] = 'Ajouter un nœud';
$string['admin_only'] = 'Vous seul';
$string['admin_only_help'] = 'Vous voyez ce nœud parce que vous êtes administrateur du site ; les personnes auxquelles il est restreint ne le voient pas.';
$string['applies_to'] = 'S’applique à';
$string['applies_to_help'] = 'Une catégorie ou un cours peut modifier ce que fixe le site, et tout ce qu’il contient le suit.';
$string['applies_whole_site'] = 'Tout le site';
$string['badge_blocked'] = 'URL bloquée';
$string['badge_unavailable'] = 'Actuellement indisponible';
$string['badge_unavailable_help'] = 'Moodle n’ajoute pas cet élément pour le moment : il peut être désactivé dans Administration du site > Apparence > Navigation, retiré par le thème ou provenir d’un plugin qui n’est plus installé. Le plugin ne l’ajoute pas non plus. Vous pouvez le supprimer ; il reviendra si Moodle l’ajoute de nouveau.';
$string['blockaccess'] = 'Bloquer l’accès par URL';
$string['blockaccess_help'] = 'Lorsque cette option est cochée, les utilisateurs qui ne peuvent pas voir cet élément sont aussi tenus à l’écart de son URL : s’ils l’ouvrent directement, ils sont redirigés vers le Tableau de bord, puis la Page d’accueil du site, puis Mes cours, puis la page d’accueil par défaut, selon ce qui leur est permis. Si aucune de ces pages n’est autorisée, ils voient une page expliquant que la page n’est pas disponible.

Seules les URL de ce site peuvent être bloquées. Les langues n’affectent jamais le blocage et les administrateurs du site ne sont jamais bloqués.

Le blocage ne s’applique qu’aux pages qui affichent l’en-tête du site ; il façonne donc la navigation, mais ne remplace pas les permissions.';
$string['blocked'] = 'Adresse bloquée';
$string['cachedef_icons'] = 'Icônes proposées par le sélecteur d’icônes';
$string['cachedef_nodes'] = 'Nœuds de navigation de tous les menus';
$string['category_of_page'] = 'Catégorie';
$string['category_of_page_help'] = 'La catégorie de l’administration du site dans laquelle va le lien. Il est placé à la fin de la catégorie et peut être déplacé depuis la liste.';
$string['clear_filters'] = 'Effacer les filtres';
$string['conditions'] = 'Restrictions d’accès';
$string['conditions_help'] = 'Les mêmes conditions que la restriction d’accès d’une activité : dates, groupes et groupements (pour un nœud d’un cours), champs du profil de l’utilisateur et conditions des plugins du site. Toutes doivent être remplies pour que le nœud soit affiché ; une date de fin fait disparaître le nœud de lui-même.';
$string['conditions_invalid'] = 'Les conditions de ce nœud ne peuvent pas être lues.';
$string['conflict_handling'] = 'Nœuds existants';
$string['conflict_handling_help'] = 'Que faire des nœuds du fichier dont le menu et la clé existent déjà sur ce site : conserver le nœud existant ou le remplacer par celui du fichier.';
$string['conflict_overwrite'] = 'Les remplacer par ceux du fichier';
$string['conflict_skip'] = 'Les conserver';
$string['coreoverridealert'] = 'Ceci est un nœud de Moodle. Le titre et l’URL sont facultatifs : laissez-les vides pour conserver les valeurs de Moodle, qui restent traduites.';
$string['delete_node_confirm'] = 'Supprimer ce nœud ? Cette action est irréversible.';
$string['delete_node_named'] = 'Supprimer {$a}';
$string['delete_nodes_text'] = 'Les nœuds sont retirés de la navigation et ne peuvent pas être récupérés. Les nœuds propres à Moodle ne peuvent pas être supprimés ; masquez-les plutôt.';
$string['delete_nodes_title'] = 'Supprimer {$a} ?';
$string['display'] = 'Affichage';
$string['display_current'] = 'Fenêtre actuelle';
$string['display_help'] = 'La façon dont le lien s’ouvre : dans la fenêtre actuelle, dans une nouvelle fenêtre ou dans une fenêtre contextuelle de la taille indiquée ci-dessous. Les éléments ayant des sous-éléments ouvrent toujours leur sous-menu à la place.';
$string['display_new'] = 'Nouvelle fenêtre';
$string['display_popup'] = 'Fenêtre contextuelle';
$string['doc_example_format'] = 'Exemple';
$string['doc_example_tip'] = 'Tous les champs sauf nodekey sont facultatifs. Les champs vides d’un nœud de Moodle conservent les valeurs de Moodle.';
$string['doc_f_availability'] = 'Restrictions d’accès de Moodle, en JSON (le format qu’enregistre la restriction d’accès d’une activité). Les groupes et groupements sont ceux du cours du nœud.';
$string['doc_f_blockaccess'] = '1 pour bloquer l’URL aux utilisateurs qui ne peuvent pas voir le nœud, 0 sinon. Utilisé uniquement lorsque la visibilité vaut 0 ou 2.';
$string['doc_f_display'] = 'La façon dont le lien s’ouvre : 0 = fenêtre actuelle, 1 = nouvelle fenêtre, 2 = fenêtre contextuelle.';
$string['doc_f_hideadmins'] = '1 pour masquer le nœud aussi aux administrateurs du site, qui voient tous les nœuds par défaut.';
$string['doc_f_icon'] = 'Classe FontAwesome (par exemple « fa-book ») ou icône Moodle (par exemple « i/home »). « none » supprime l’icône.';
$string['doc_f_langs'] = 'Codes de langue séparés par des virgules (par exemple « es,pt_br »). Le nœud n’est affiché dans le menu que pour ces langues. Vide signifie toutes les langues.';
$string['doc_f_menu'] = 'Navigation du nœud : primary, usermenu, course, frontpage, activity, category ou admin. Par défaut, la navigation importée.';
$string['doc_f_native'] = '1 quand le nœud est un nœud de Moodle que le plugin modifie ; 0 quand le plugin l’ajoute.';
$string['doc_f_nodekey'] = 'Clé du nœud, unique dans son menu. Utilisez une clé de Moodle (par exemple « myhome ») pour modifier un nœud de Moodle.';
$string['doc_f_order'] = 'Position du nœud dans son menu, 1 étant la première. Les fichiers exportés indiquent toutes les positions. À l’importation, les nœuds qui n’en ont pas conservent leur place, ou passent en dernier lorsqu’ils sont nouveaux.';
$string['doc_f_parentkey'] = 'Clé du nœud parent, pour afficher ce nœud dans son sous-menu.';
$string['doc_f_placement'] = 'Où va un nœud d’une navigation secondaire : 0 là où Moodle le place, 1 toujours dans le menu Plus, 2 jamais dans le menu Plus.';
$string['doc_f_popupheight'] = 'Hauteur de la fenêtre contextuelle en pixels, de 100 à 9999. Utilisée lorsque display vaut 2.';
$string['doc_f_popupwidth'] = 'Largeur de la fenêtre contextuelle en pixels, de 100 à 9999. Utilisée lorsque display vaut 2.';
$string['doc_f_roles'] = 'Noms abrégés de rôles séparés par des virgules (par exemple « editingteacher,teacher »). Utilisé lorsque la visibilité vaut 2.';
$string['doc_f_scope'] = 'Catégorie ou cours pour lequel le nœud est une exception : "course:nomabrégé" ou "category:Parent / Nom". Vide signifie tout le site.';
$string['doc_f_title'] = 'Texte affiché. Vide sur un nœud de Moodle, il conserve le titre de Moodle.';
$string['doc_f_tooltip'] = 'Texte affiché au survol du lien.';
$string['doc_f_url'] = 'Destination : un chemin de ce site (par exemple « /course/view.php?id=2 ») ou une adresse complète.';
$string['doc_f_visibility'] = '1 = visible par tous, 2 = limité aux rôles indiqués, 0 = non visible.';
$string['doc_field'] = 'Champ';
$string['doc_fields_title'] = 'Champs';
$string['doc_intro'] = 'Les fichiers d’importation sont des fichiers YAML contenant une liste « nodes ». Chaque entrée décrit un nœud avec les champs ci-dessous.';
$string['documentation'] = 'Format YAML';
$string['download_example'] = 'Télécharger un fichier d’exemple';
$string['drag_node_named'] = 'Faites glisser {$a} pour le déplacer';
$string['drag_nodes_named'] = 'Faites glisser les {$a} nœuds sélectionnés pour les déplacer';
$string['edit_node'] = 'Modifier le nœud';
$string['edit_node_named'] = 'Modifier {$a}';
$string['empty_level_text'] = 'Ajoutez un lien personnel à cette catégorie, ou revenez à un autre niveau.';
$string['empty_level_title'] = 'Cette catégorie est vide';
$string['empty_nodes_text'] = 'Les nœuds de Moodle apparaissent ici dès qu’une page les affiche. Trouvez-les maintenant depuis une page du site, ou ajoutez un nœud.';
$string['empty_nodes_title'] = 'Aucun nœud pour l’instant';
$string['empty_search_text'] = 'Rien ne correspond à la recherche et aux filtres.';
$string['empty_search_title'] = 'Aucun résultat';
$string['enable_plugin'] = 'Activer';
$string['err_admin_child'] = 'L’administration du site ne peut pas être placée dans un sous-menu.';
$string['err_admin_hide'] = 'L’administration du site ne peut pas être masquée, restreinte, bloquée ni limitée à certaines langues.';
$string['err_admin_parent'] = 'L’administration du site ne peut pas avoir d’éléments de sous-menu.';
$string['err_conditions'] = 'Ce ne sont pas des restrictions d’accès valides.';
$string['err_duplicate_key'] = 'Un autre nœud de ce menu utilise déjà cette clé.';
$string['err_duplicate_key_alert'] = 'Un autre nœud de ce menu utilise déjà cette clé.';
$string['err_fallbacks_blocked'] = 'Cela bloquerait le Tableau de bord, la Page d’accueil du site, Mes cours et la page d’accueil par défaut pour tous les utilisateurs, ne leur laissant aucune page d’arrivée. Débloquez-en au moins une.';
$string['err_icon_invalid'] = 'Utilisez une classe FontAwesome (par exemple « fa-book »), une icône Moodle (par exemple « i/home ») ou « none ».';
$string['err_import_duplicate'] = 'Ce nœud apparaît plus d’une fois dans le fichier.';
$string['err_import_nodekey'] = 'L’entrée n’a pas de nodekey.';
$string['err_import_order'] = 'L’ordre doit être un nombre entier à partir de 1.';
$string['err_import_scope'] = 'La catégorie ou le cours "{$a}" n’existe pas, ou cette navigation n’a pas d’exceptions.';
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
$string['err_tree_category'] = 'Choisissez la catégorie de l’administration du site dans laquelle va le lien.';
$string['err_url_forbidden'] = 'Ce type d’URL n’est pas autorisé.';
$string['err_url_invalid'] = 'Saisissez une URL valide, par exemple « /course/view.php?id=2 » ou « https://example.com ».';
$string['error_loading_title'] = 'La liste ne s’est pas chargée';
$string['eventnodecreated'] = 'Nœud de navigation créé';
$string['eventnodedeleted'] = 'Nœud de navigation supprimé';
$string['eventnodeupdated'] = 'Nœud de navigation mis à jour';
$string['exception'] = 'Exception';
$string['exceptions_count'] = '{$a} modifications';
$string['export'] = 'Exporter';
$string['export_everything'] = 'Toutes les navigations';
$string['export_everything_help'] = 'Un fichier avec toutes les navigations et l’état de chacune';
$string['export_this'] = 'Cette navigation';
$string['extendednav:manage'] = 'Gérer les nœuds de navigation';
$string['filter_date_last30'] = '30 derniers jours';
$string['filter_date_month'] = 'Ce mois-ci';
$string['filter_date_range'] = '{$a->from} – {$a->to}';
$string['filter_date_since'] = 'À partir du {$a}';
$string['filter_date_today'] = 'Aujourd’hui';
$string['filter_date_until'] = 'Jusqu’au {$a}';
$string['filter_date_week'] = '7 derniers jours';
$string['filter_parent'] = 'Filtrer par clé du parent';
$string['filter_visibility'] = 'Filtrer par visibilité';
$string['find_nodes'] = 'Trouver les nœuds';
$string['flag_conditions'] = 'Conditions';
$string['flag_ended'] = 'Terminé';
$string['flag_hideadmins'] = 'Administrateurs aussi';
$string['flag_langs'] = 'Langues';
$string['flag_protected'] = 'Toujours affiché';
$string['flag_protected_help'] = 'Ce nœud mène à l’administration du site ou de ce plugin ; il ne peut donc pas être masqué.';
$string['flag_roles'] = 'Rôles';
$string['flag_scheduled'] = 'Programmé';
$string['flag_unavailable'] = 'Indisponible';
$string['form_section_access'] = 'Qui le voit';
$string['form_section_look'] = 'Apparence et ouverture';
$string['form_section_node'] = 'Nœud';
$string['form_section_place'] = 'Où il se place';
$string['hidden_by_moodle'] = 'Masqué par Moodle';
$string['hidden_from_admins'] = 'Masqué aux administrateurs';
$string['hide_node_named'] = 'Masquer {$a}';
$string['hide_nodes_admins'] = 'Masquer aussi aux administrateurs';
$string['hide_nodes_admins_help'] = 'Sinon, les administrateurs du site continuent de voir ces nœuds, signalés pour indiquer que les autres personnes ne les voient pas.';
$string['hide_nodes_text'] = 'Les nœuds sont masqués pour toutes les personnes qui les voyaient. Vous pouvez les afficher de nouveau à tout moment.';
$string['hide_nodes_title'] = 'Masquer {$a} ?';
$string['hide_selected'] = 'Masquer';
$string['hideadmins'] = 'Masquer aussi aux administrateurs';
$string['hideadmins_help'] = 'Un administrateur du site voit tous les nœuds par défaut, même ceux qui sont masqués ou limités à des rôles qu’il n’a pas. Activez cette option pour appliquer les règles de ce nœud aussi aux administrateurs.';
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
$string['import_instructions_desc'] = 'Importez un fichier exporté depuis cette page, d’une navigation ou de toutes : les deux ont le même format, et chaque nœud indique sa navigation. Chaque nœud est d’abord vérifié ; si l’un d’eux n’est pas valide, rien n’est importé.';
$string['import_instructions_title'] = 'Avant d’importer';
$string['import_nodes'] = 'Importer des nœuds';
$string['import_success'] = 'Importation terminée : {$a->created} créés, {$a->updated} remplacés, {$a->skipped} conservés.';
$string['inherited'] = 'Hérité';
$string['langs'] = 'Langues';
$string['langs_all'] = 'Toutes les langues';
$string['langs_help'] = 'Affiche cet élément dans le menu uniquement aux utilisateurs dont la langue actuelle est sélectionnée. Laissez vide pour l’afficher dans toutes les langues. Une langue couvre aussi ses variantes régionales : l’espagnol (es) inclut l’espagnol - Mexique (es_mx).

Les langues contrôlent seulement si l’élément apparaît dans le menu. Elles n’affectent jamais l’accès à son URL, car les utilisateurs peuvent changer leur propre langue à tout moment. L’accès est contrôlé par la Visibilité, les Rôles autorisés et « Bloquer l’accès par URL ».

Pour traduire un élément, créez un nœud par langue avec la même URL, les mêmes rôles et la même visibilité.';
$string['langs_hidden_notice'] = 'Cet élément est masqué pour tout le monde ; les langues sélectionnées n’ont donc aucun effet sur le menu. Elles sont conservées au cas où vous rendriez de nouveau l’élément visible. L’accès à l’URL dépend uniquement de « Bloquer l’accès par URL ».';
$string['list_caption_activity'] = 'Modifications des nœuds de la navigation, de la plus récente à la plus ancienne';
$string['list_caption_nodes'] = 'Nœuds de la navigation, avec leur visibilité et leur lien';
$string['manage_nodes'] = 'Gérer la navigation';
$string['manage_nodes_desc'] = 'Ordonnez, renommez, masquez et complétez les navigations de Moodle : la navigation principale, le menu utilisateur et les navigations d’un cours, d’une activité, d’une catégorie et de l’administration du site.';
$string['menu_type'] = 'Menu';
$string['navigation_off'] = 'Désactivée';
$string['navigation_off_help'] = 'Cette navigation est affichée comme Moodle la dessine. Appuyez pour appliquer les modifications.';
$string['navigation_on'] = 'Activée';
$string['navigation_on_help'] = 'Les modifications de cette navigation sont appliquées. Appuyez pour les désactiver.';
$string['navigations'] = 'Navigations';
$string['next_page'] = 'Page suivante';
$string['no_nodes_export'] = 'Il n’y a aucun nœud à exporter.';
$string['no_scopes'] = 'Aucune catégorie ni aucun cours ne correspond.';
$string['node_saved'] = 'Nœud enregistré.';
$string['nodekey'] = 'Clé du nœud';
$string['nodekey_help'] = 'Clé de l’élément, unique dans son menu. Pour modifier un élément de Moodle, utilisez sa clé : home, myhome, mycourses ou siteadminnode dans la navigation principale. Pour un nouvel élément, choisissez une nouvelle clé composée de lettres, de chiffres, de tirets bas et de tirets, par exemple « help_es ».';
$string['nodes_changed'] = '{$a} modifiés';
$string['nodes_count'] = '{$a} éléments';
$string['nodes_count_label'] = '{$a} nœuds';
$string['nodes_deleted'] = '{$a} nœud(s) supprimé(s).';
$string['nodes_found'] = '{$a} nœuds trouvés';
$string['nodes_selected'] = 'nœud(s) sélectionné(s)';
$string['none_title'] = 'Sans titre';
$string['open_level'] = 'Ouvrir';
$string['open_level_named'] = 'Ouvrir {$a}';
$string['open_sample_page'] = 'Ouvrir une page qui l’affiche';
$string['opt_invalid_admin_submenu'] = 'L’administration du site est toujours un élément de premier niveau ; elle ne peut donc pas être placée dans un sous-menu.';
$string['opt_invalid_has_submenus'] = 'Ce nœud a des éléments de sous-menu ; il ne peut donc pas être placé lui-même dans un sous-menu.';
$string['opt_native'] = '{$a->text} ({$a->key})';
$string['opt_none_root'] = '- Aucun (premier niveau) -';
$string['opt_plugin'] = '{$a->title} ({$a->key})';
$string['order'] = 'Ordre';
$string['origin'] = 'Origine';
$string['origin_custom'] = 'Ajouté';
$string['origin_custom_help'] = 'Ce nœud a été ajouté sur cette page.';
$string['origin_group_admin'] = 'Administration';
$string['origin_group_course'] = 'Cours';
$string['origin_group_site'] = 'Site';
$string['origin_native'] = 'Moodle';
$string['origin_native_help'] = 'Moodle ou un autre plugin ajoute ce nœud ; le plugin modifie son affichage.';
$string['pager_range'] = '{$a->from}–{$a->to} sur {$a->total}';
$string['parentkey'] = 'Parent';
$string['parentkey_help'] = 'Affiche cet élément dans le sous-menu d’un autre élément. Seuls les éléments de premier niveau peuvent être parents, et un élément ayant des sous-éléments ne peut pas être placé lui-même dans un sous-menu.';
$string['parenturlunused'] = 'Ce nœud a des éléments de sous-menu ; il ouvre donc son sous-menu au lieu de suivre son URL. L’URL reste utilisée pour le blocage.';
$string['period_ended'] = 'N’est plus affiché depuis le {$a}';
$string['period_scheduled'] = 'Affiché à partir du {$a}';
$string['placement'] = 'Où il se place';
$string['placement_default'] = 'Là où Moodle le place';
$string['placement_help'] = 'Moodle affiche les premiers nœuds d’une navigation dans sa barre et les autres dans le menu Plus. Choisissez Plus pour garder toujours ce nœud dans le menu, ou Dans la barre pour l’en sortir.';
$string['placement_main'] = 'Dans la barre';
$string['placement_more'] = 'Dans Plus';
$string['pluginname'] = 'Navigation étendue';
$string['popupheight'] = 'Hauteur de la fenêtre contextuelle (en pixels)';
$string['popupwidth'] = 'Largeur de la fenêtre contextuelle (en pixels)';
$string['previous_page'] = 'Page précédente';
$string['primary_nav'] = 'Navigation principale';
$string['privacy:metadata'] = 'Le plugin Navigation étendue ne stocke aucune donnée personnelle. Les modifications des nœuds de navigation sont consignées dans le journal standard.';
$string['reset'] = 'Revenir';
$string['reset_nodes_text'] = 'Les modifications de cette catégorie ou de ce cours sont supprimées et sa navigation suit de nouveau le site.';
$string['reset_nodes_title'] = 'Revenir à ce que fixe le site pour {$a} ?';
$string['restricted_title'] = 'Page non disponible';
$string['results_count'] = 'Résultats : {$a}';
$string['roles'] = 'Rôles autorisés';
$string['roles_help'] = 'Les utilisateurs ayant au moins l’un de ces rôles, dans n’importe quel contexte, voient l’élément. Tout utilisateur connecté possède aussi le rôle par défaut des utilisateurs authentifiés.';
$string['rows_per_page'] = 'Lignes par page';
$string['saved'] = 'Enregistré';
$string['scope_search'] = 'Rechercher une catégorie ou un cours';
$string['search_nodes'] = 'Rechercher des nœuds';
$string['search_placeholder'] = 'Rechercher par titre, clé du nœud ou URL...';
$string['secondary_activity'] = 'Navigation de l’activité';
$string['secondary_admin'] = 'Administration du site';
$string['secondary_category'] = 'Navigation de la catégorie';
$string['secondary_course'] = 'Navigation du cours';
$string['secondary_frontpage'] = 'Navigation de la page d’accueil du site';
$string['select_all_matching'] = 'Tout sélectionner ({$a})';
$string['select_node_named'] = 'Sélectionner {$a}';
$string['selected_count'] = '{$a} sélectionnées';
$string['selection'] = 'Actions pour les nœuds sélectionnés';
$string['show_node_named'] = 'Afficher {$a}';
$string['show_nodes_text'] = 'Les nœuds sont de nouveau affichés pour tout le monde.';
$string['show_nodes_title'] = 'Afficher {$a} ?';
$string['show_selected'] = 'Afficher';
$string['skipped_more'] = 'et {$a} de plus';
$string['skipped_nodes'] = '{$a} des nœuds sélectionnés restent tels quels :';
$string['title'] = 'Titre';
$string['title_help'] = 'Texte affiché dans le menu. Sur un élément de Moodle, laissez vide pour conserver le titre de Moodle, qui est traduit. Une chaîne de langue peut être utilisée sous la forme « identifiant,composant », par exemple « profile,moodle ».';
$string['tooltip'] = 'Infobulle';
$string['tooltip_help'] = 'Texte affiché au survol du lien. Facultatif.';
$string['tree_path'] = 'Niveau de l’administration du site';
$string['tree_top'] = 'Administration du site';
$string['try_again'] = 'Réessayer';
$string['unknown_lang'] = 'Non installée : {$a}';
$string['unknown_role'] = 'Rôle inconnu : {$a}';
$string['url'] = 'URL';
$string['url_help'] = 'Où mène l’élément : un chemin de ce site, comme « /course/view.php?id=2 », ou une adresse complète. Sur un élément de Moodle, laissez vide pour conserver l’URL de Moodle.';
$string['user_menu'] = 'Menu utilisateur';
$string['view_documentation'] = 'Voir le format YAML';
$string['vis_all'] = 'Visible par tous';
$string['vis_conditional'] = 'Avec conditions';
$string['vis_hidden'] = 'Non visible';
$string['vis_roles'] = 'Restreint';
$string['visibility'] = 'Visibilité';
$string['visibility_help'] = 'Qui voit l’élément dans le menu : tout le monde, uniquement les utilisateurs ayant les rôles autorisés, ou personne. Avec « Bloquer l’accès par URL », les utilisateurs qui ne peuvent pas voir l’élément ne peuvent pas non plus ouvrir son URL.';
$string['visibility_langs_line'] = 'Langues : {$a}';
$string['visibility_roles_line'] = 'Rôles : {$a}';
$string['warn_fallbacks_blocked_for_some'] = 'Le Tableau de bord, la Page d’accueil du site, Mes cours et la page d’accueil par défaut sont tous bloqués pour les utilisateurs sans certains rôles. Ces utilisateurs verront une page d’erreur au lieu d’être redirigés.';
$string['warn_import_absent'] = 'Ce site n’a pas ce nœud de Moodle ; il a été ignoré.';
$string['warn_unknown_lang'] = 'La langue « {$a} » n’est pas installée.';
$string['warn_unknown_role'] = 'Il n’existe aucun rôle avec le nom abrégé « {$a} ».';
$string['yamlconfigfile'] = 'Fichier YAML';
$string['yamlconfigfile_help'] = 'Un fichier .yaml ou .yml avec une liste "nodes", comme ceux créés par Exporter.';
