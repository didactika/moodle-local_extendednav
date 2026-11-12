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
 * Portuguese strings for local_extendednav.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['action_created'] = 'Criado';
$string['action_deleted'] = 'Eliminado';
$string['action_updated'] = 'Atualizado';
$string['activity_log'] = 'Registo de atividade';
$string['add_node'] = 'Adicionar nó';
$string['apply'] = 'Aplicar';
$string['back_to_nodes'] = 'Voltar à navegação';
$string['badge_blocked'] = 'URL bloqueado';
$string['badge_unavailable'] = 'Atualmente indisponível';
$string['badge_unavailable_help'] = 'O Moodle não está a adicionar este item neste momento: pode estar desativado em Administração do site > Aspeto > Navegação, ter sido removido pelo tema ou provir de um plugin que já não está instalado. O plugin também não o adiciona. Pode eliminá-lo; voltará a aparecer se o Moodle o adicionar de novo.';
$string['blockaccess'] = 'Bloquear o acesso por URL';
$string['blockaccess_help'] = 'Quando assinalado, os utilizadores que não podem ver este item também são afastados do seu URL: se o abrirem diretamente, são enviados para o Painel, depois para a Página inicial do site, depois para Os meus cursos e depois para a página inicial predefinida, consoante a que lhes for permitida. Se nenhuma destas páginas for permitida, veem uma página que explica que a página não está disponível.

Só podem ser bloqueados URL deste site. Os idiomas nunca afetam o bloqueio e os administradores do site nunca são bloqueados.

O bloqueio só se aplica às páginas que mostram o cabeçalho do site, pelo que molda a navegação; não substitui as permissões.';
$string['bulk_delete'] = 'Eliminar os selecionados';
$string['bulk_delete_confirm'] = 'Eliminar os nós selecionados? Os nós do Moodle são mantidos, a menos que estejam atualmente indisponíveis. Esta ação não pode ser anulada.';
$string['cachedef_icons'] = 'Ícones oferecidos pelo seletor de ícones';
$string['cachedef_nodes'] = 'Nós de navegação de todos os menus';
$string['clear_filters'] = 'Limpar';
$string['conflict_handling'] = 'Nós existentes';
$string['conflict_handling_help'] = 'O que fazer com os nós do ficheiro cujo menu e chave já existem neste site: manter o nó existente ou substituí-lo pelo do ficheiro.';
$string['conflict_overwrite'] = 'Substituí-los pelos do ficheiro';
$string['conflict_skip'] = 'Mantê-los';
$string['coreoverridealert'] = 'Este é um nó do Moodle. O título e o URL são opcionais: deixe-os vazios para manter os valores do Moodle, que permanecem traduzidos.';
$string['delete_node_confirm'] = 'Eliminar este nó? Esta ação não pode ser anulada.';
$string['display'] = 'Apresentação';
$string['display_current'] = 'Janela atual';
$string['display_help'] = 'Como a ligação se abre: na janela atual, numa nova janela ou numa janela pop-up com a dimensão indicada abaixo. Os itens com subitens abrem sempre o seu submenu.';
$string['display_new'] = 'Nova janela';
$string['display_popup'] = 'Pop-up';
$string['doc_example_format'] = 'Exemplo';
$string['doc_example_tip'] = 'Todos os campos, exceto nodekey, são opcionais. Os campos vazios de um nó do Moodle mantêm os valores do Moodle.';
$string['doc_f_blockaccess'] = '1 para bloquear o URL aos utilizadores que não podem ver o nó, 0 caso contrário. Só é usado quando a visibilidade é 0 ou 2.';
$string['doc_f_display'] = 'Como a ligação se abre: 0 = janela atual, 1 = nova janela, 2 = pop-up.';
$string['doc_f_icon'] = 'Classe FontAwesome (por exemplo «fa-book») ou ícone do Moodle (por exemplo «i/home»). «none» remove o ícone.';
$string['doc_f_langs'] = 'Códigos de idioma separados por vírgulas (por exemplo «es,pt_br»). O nó só é mostrado no menu para esses idiomas. Vazio significa todos os idiomas.';
$string['doc_f_menu'] = 'Menu do nó: «primary» ou «usermenu». Por predefinição, o menu que está a ser importado.';
$string['doc_f_nodekey'] = 'Chave do nó, única dentro do seu menu. Use uma chave do Moodle (por exemplo «myhome») para alterar um nó do Moodle.';
$string['doc_f_order'] = 'Posição do nó no seu menu, sendo 1 a primeira. Os ficheiros exportados indicam todas as posições. Ao importar, os nós que não a têm mantêm o seu lugar, ou vão para o fim quando são novos.';
$string['doc_f_parentkey'] = 'Chave do nó pai, para mostrar este nó no seu submenu.';
$string['doc_f_popupheight'] = 'Altura da janela pop-up em píxeis, de 100 a 9999. Usada quando display é 2.';
$string['doc_f_popupwidth'] = 'Largura da janela pop-up em píxeis, de 100 a 9999. Usada quando display é 2.';
$string['doc_f_roles'] = 'Nomes abreviados de papéis separados por vírgulas (por exemplo «editingteacher,teacher»). Usado quando a visibilidade é 2.';
$string['doc_f_title'] = 'Texto apresentado. Vazio num nó do Moodle mantém o título do Moodle.';
$string['doc_f_tooltip'] = 'Texto mostrado ao passar o cursor sobre a ligação.';
$string['doc_f_url'] = 'Destino: um caminho deste site (por exemplo «/course/view.php?id=2») ou um endereço completo.';
$string['doc_f_visibility'] = '1 = visível para todos, 2 = restrito aos papéis indicados, 0 = não visível.';
$string['doc_field'] = 'Campo';
$string['doc_fields_title'] = 'Campos';
$string['doc_intro'] = 'Os ficheiros de importação são ficheiros YAML com uma lista «nodes». Cada entrada descreve um nó com os campos seguintes.';
$string['documentation'] = 'Formato YAML';
$string['download_example'] = 'Descarregar um ficheiro de exemplo';
$string['edit_node'] = 'Editar nó';
$string['enable_plugin'] = 'Ativar';
$string['enable_plugin_desc'] = 'Aplica os nós de navegação e o bloqueio de URL. Quando desativado, a navegação do Moodle é mostrada sem alterações e nenhuma página é bloqueada.';
$string['entries_count'] = '{$a} entradas';
$string['entries_count_one'] = '1 entrada';
$string['err_admin_child'] = 'A administração do site não pode ser colocada num submenu.';
$string['err_admin_hide'] = 'A administração do site não pode ser ocultada, restringida, bloqueada nem limitada a alguns idiomas.';
$string['err_admin_parent'] = 'A administração do site não pode ter itens de submenu.';
$string['err_duplicate_key'] = 'Outro nó deste menu já usa esta chave.';
$string['err_duplicate_key_alert'] = 'Outro nó deste menu já usa esta chave.';
$string['err_fallbacks_blocked'] = 'Isto bloquearia o Painel, a Página inicial do site, Os meus cursos e a página inicial predefinida para todos os utilizadores, deixando-os sem nenhuma página onde chegar. Desbloqueie pelo menos uma delas.';
$string['err_icon_invalid'] = 'Use uma classe FontAwesome (por exemplo «fa-book»), um ícone do Moodle (por exemplo «i/home») ou «none».';
$string['err_import_duplicate'] = 'Este nó aparece mais de uma vez no ficheiro.';
$string['err_import_nodekey'] = 'A entrada não tem nodekey.';
$string['err_import_order'] = 'A ordem deve ser um número inteiro a partir de 1.';
$string['err_invalid_display'] = 'Escolha como a ligação se abre: 0, 1 ou 2.';
$string['err_invalid_list'] = 'Use apenas letras, números, carateres de sublinhado e hífenes, separados por vírgulas.';
$string['err_invalid_menu'] = 'Menu desconhecido. Use «primary» ou «usermenu».';
$string['err_invalid_nodekey'] = 'Use apenas letras, números, carateres de sublinhado e hífenes.';
$string['err_invalid_visibility'] = 'Visibilidade desconhecida. Use 0, 1 ou 2.';
$string['err_invalid_yaml_file'] = 'Não foi possível ler o ficheiro. Carregue um ficheiro .yaml ou .yml.';
$string['err_invalid_yaml_format'] = 'O ficheiro não é um YAML válido.';
$string['err_invalid_yaml_structure'] = 'O ficheiro deve conter uma lista «nodes».';
$string['err_parent_haschildren'] = 'Este nó tem itens de submenu, pelo que não pode ser colocado num submenu.';
$string['err_parent_missing'] = 'Não existe nenhum nó com a chave «{$a}» neste menu.';
$string['err_parent_self'] = 'Um nó não pode ser o seu próprio pai.';
$string['err_parent_thirdlevel'] = 'O pai escolhido já está num submenu. Os menus só têm um nível de submenus.';
$string['err_popup_size'] = 'Introduza um número inteiro de {$a->min} a {$a->max}.';
$string['err_restricted_page'] = 'A página que abriu não está disponível para a sua conta, nem o Painel, a Página inicial do site ou Os meus cursos. Contacte o administrador do site se achar que se trata de um erro.';
$string['err_text_html'] = 'Não é permitido HTML aqui.';
$string['err_title_usermenu_chars'] = 'Os títulos do menu do utilizador não podem conter «|» nem quebras de linha.';
$string['err_url_forbidden'] = 'Este tipo de URL não é permitido.';
$string['err_url_invalid'] = 'Introduza um URL válido, por exemplo «/course/view.php?id=2» ou «https://example.com».';
$string['eventnodecreated'] = 'Nó de navegação criado';
$string['eventnodedeleted'] = 'Nó de navegação eliminado';
$string['eventnodeupdated'] = 'Nó de navegação atualizado';
$string['export_all_btn'] = 'Exportar tudo';
$string['export_selected'] = 'Exportar os selecionados';
$string['extendednav:manage'] = 'Gerir os nós de navegação';
$string['filter_action'] = 'Filtrar por ação';
$string['filter_action_any'] = 'Todas as ações';
$string['filter_datefrom'] = 'De';
$string['filter_dateto'] = 'Até';
$string['filter_invalidperiod'] = 'A data de fim não pode ser anterior à data de início.';
$string['filter_parent'] = 'Filtrar por chave do pai';
$string['filter_parent_any'] = 'Chave do pai';
$string['filter_visibility'] = 'Filtrar por visibilidade';
$string['icon'] = 'Ícone';
$string['icon_choose'] = 'Escolher ícone';
$string['icon_clear'] = 'Limpar';
$string['icon_help'] = 'Selecione o quadrado junto ao campo para escolher um ícone de uma lista com pesquisa, ou escreva uma classe FontAwesome (por exemplo «fa-book» ou «fa-brands fa-github») ou um ícone do Moodle (por exemplo «core:i/home», ou «i/home» para o núcleo). Deixe vazio para manter o predefinido ou escreva «none» para remover o ícone. No menu do utilizador, os ícones do Moodle são mostrados como o seu ícone FontAwesome.';
$string['icon_info_brand'] = 'Ícone de marca FontAwesome';
$string['icon_info_fontawesome'] = 'Ícone FontAwesome';
$string['icon_info_invalid'] = 'Não é um ícone que o plugin possa mostrar. Escolha um, ou escreva uma classe FontAwesome ou um ícone do Moodle.';
$string['icon_info_moodle'] = 'Ícone do Moodle de {$a}';
$string['icon_info_none'] = 'Sem ícone';
$string['icon_loading'] = 'A carregar os ícones...';
$string['icon_noresults'] = 'Nenhum ícone corresponde à sua pesquisa.';
$string['icon_picker'] = 'Escolher um ícone';
$string['icon_search'] = 'Pesquisar ícones';
$string['icon_tab_brands'] = 'Marcas';
$string['icon_tab_fontawesome'] = 'FontAwesome';
$string['icon_tab_moodle'] = 'Ícones do Moodle';
$string['icon_unavailable'] = 'Esta versão do Moodle guarda a sua lista FontAwesome onde o plugin não a consegue ler. Escreva antes a classe no campo do ícone, por exemplo «fa-book».';
$string['import'] = 'Importar';
$string['import_entry'] = 'Entrada {$a}';
$string['import_failed'] = 'Não foi importado nada. Corrija estes problemas no ficheiro e tente novamente:';
$string['import_instructions_desc'] = 'O ficheiro deve conter uma lista «nodes». Cada nó é verificado primeiro; se algum nó for inválido, não é importado nada.';
$string['import_instructions_title'] = 'Antes de importar';
$string['import_nodes'] = 'Importar nós';
$string['import_success'] = 'Importação concluída: {$a->created} criados, {$a->updated} substituídos, {$a->skipped} mantidos.';
$string['langs'] = 'Idiomas';
$string['langs_all'] = 'Todos os idiomas';
$string['langs_help'] = 'Mostra este item no menu apenas aos utilizadores cujo idioma atual esteja selecionado. Deixe vazio para o mostrar em todos os idiomas. Um idioma abrange também as suas variantes regionais: o espanhol (es) inclui o espanhol - México (es_mx).

Os idiomas só controlam se o item aparece no menu. Nunca afetam o acesso ao seu URL, porque os utilizadores podem alterar o seu próprio idioma a qualquer momento. O acesso é controlado por Visibilidade, Papéis permitidos e «Bloquear o acesso por URL».

Para traduzir um item, crie um nó por idioma com o mesmo URL, os mesmos papéis e a mesma visibilidade.';
$string['langs_hidden_notice'] = 'Este item está oculto para todos, pelo que os idiomas selecionados não têm efeito no menu. São mantidos caso volte a tornar o item visível. O acesso ao URL depende apenas de «Bloquear o acesso por URL».';
$string['manage_nodes'] = 'Gerir a navegação';
$string['manage_nodes_desc'] = 'Adicione itens à navegação primária e ao menu do utilizador, e altere, reordene, aninhe ou oculte os itens do próprio Moodle.';
$string['menu_type'] = 'Menu';
$string['movenode'] = 'Mover {$a}';
$string['native_cannot_delete'] = 'Os nós do Moodle não podem ser eliminados. Defina a sua visibilidade como «Não visível» para os ocultar.';
$string['nativenodeinfo'] = 'Nó do Moodle';
$string['no_nodes_export'] = 'Não há nós para exportar.';
$string['node_saved'] = 'Nó guardado.';
$string['nodekey'] = 'Chave do nó';
$string['nodekey_help'] = 'Chave do item, única dentro do seu menu. Para alterar um item do Moodle, use a sua chave: home, myhome, mycourses ou siteadminnode na navegação primária. Para um item novo, escolha uma chave nova com letras, números, carateres de sublinhado e hífenes, por exemplo «help_es».';
$string['nodes_count'] = '{$a} itens';
$string['nodes_count_one'] = '1 item';
$string['nodes_deleted'] = '{$a} nó(s) eliminado(s).';
$string['nodes_selected'] = 'nó(s) selecionado(s)';
$string['none_title'] = 'Sem título';
$string['opt_invalid_admin_submenu'] = 'A administração do site é sempre um item de nível superior, pelo que não pode ser colocada num submenu.';
$string['opt_invalid_has_submenus'] = 'Este nó tem itens de submenu, pelo que não pode ser colocado, por sua vez, num submenu.';
$string['opt_native'] = '{$a->text} ({$a->key})';
$string['opt_none_root'] = '- Nenhum (nível superior) -';
$string['opt_plugin'] = '{$a->title} ({$a->key})';
$string['order'] = 'Ordem';
$string['parentkey'] = 'Pai';
$string['parentkey_help'] = 'Mostra este item no submenu de outro item. Só os itens de nível superior podem ser pais, e um item com subitens não pode ser colocado, por sua vez, num submenu.';
$string['parenturlunused'] = 'Este nó tem itens de submenu, pelo que abre o seu submenu em vez de seguir o seu URL. O URL continua a ser usado para o bloqueio.';
$string['pluginname'] = 'Navegação alargada';
$string['popupheight'] = 'Altura do pop-up (em píxeis)';
$string['popupwidth'] = 'Largura do pop-up (em píxeis)';
$string['primary_nav'] = 'Navegação primária';
$string['privacy:metadata'] = 'O plugin Navegação alargada não armazena nenhum dado pessoal. As alterações aos nós de navegação ficam registadas no registo padrão.';
$string['restricted_title'] = 'Página não disponível';
$string['roles'] = 'Papéis permitidos';
$string['roles_help'] = 'Os utilizadores com pelo menos um destes papéis, em qualquer contexto, veem o item. Todo o utilizador com sessão iniciada tem também o papel predefinido dos utilizadores autenticados.';
$string['search_placeholder'] = 'Pesquisar por título, chave do nó ou URL...';
$string['settings_page'] = 'Definições';
$string['submenuof'] = 'Submenu de: {$a}';
$string['title'] = 'Título';
$string['title_help'] = 'Texto mostrado no menu. Num item do Moodle, deixe vazio para manter o título do Moodle, que está traduzido. Uma cadeia de idioma pode ser usada como «identificador,componente», por exemplo «profile,moodle».';
$string['tooltip'] = 'Dica';
$string['tooltip_help'] = 'Texto mostrado ao passar o cursor sobre a ligação. Opcional.';
$string['unknown_lang'] = 'Não instalado: {$a}';
$string['unknown_role'] = 'Papel desconhecido: {$a}';
$string['url'] = 'URL';
$string['url_help'] = 'Para onde leva o item: um caminho deste site, como «/course/view.php?id=2», ou um endereço completo. Num item do Moodle, deixe vazio para manter o URL do Moodle.';
$string['user_menu'] = 'Menu do utilizador';
$string['view_documentation'] = 'Ver o formato YAML';
$string['vis_all'] = 'Visível para todos';
$string['vis_hidden'] = 'Não visível';
$string['vis_roles'] = 'Restrito';
$string['visibility'] = 'Visibilidade';
$string['visibility_help'] = 'Quem vê o item no menu: todos, apenas os utilizadores com os papéis permitidos ou ninguém. Com «Bloquear o acesso por URL», os utilizadores que não podem ver o item também não podem abrir o seu URL.';
$string['warn_fallbacks_blocked_for_some'] = 'O Painel, a Página inicial do site, Os meus cursos e a página inicial predefinida estão todos bloqueados para os utilizadores sem alguns papéis. Esses utilizadores verão uma página de erro em vez de serem redirecionados.';
$string['warn_unknown_lang'] = 'O idioma «{$a}» não está instalado.';
$string['warn_unknown_role'] = 'Não existe nenhum papel com o nome abreviado «{$a}».';
$string['yamlconfigfile'] = 'Ficheiro YAML';
$string['yamlconfigfile_help'] = 'Um ficheiro .yaml ou .yml com uma lista «nodes», como os ficheiros criados por «Exportar tudo».';
