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
 * Italian strings for local_extendednav.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['action_created'] = 'Creato';
$string['action_deleted'] = 'Eliminato';
$string['action_updated'] = 'Aggiornato';
$string['activity_log'] = 'Registro delle attività';
$string['add_node'] = 'Aggiungi nodo';
$string['apply'] = 'Applica';
$string['back_to_nodes'] = 'Torna alla navigazione';
$string['badge_blocked'] = 'URL bloccato';
$string['badge_unavailable'] = 'Attualmente non disponibile';
$string['badge_unavailable_help'] = 'Moodle non aggiunge questo elemento in questo momento: potrebbe essere disattivato in Amministrazione del sito > Aspetto > Navigazione, rimosso dal tema o provenire da un plugin che non è più installato. Nemmeno il plugin lo aggiunge. Puoi eliminarlo; tornerà se Moodle lo aggiunge di nuovo.';
$string['blockaccess'] = 'Blocca l’accesso tramite URL';
$string['blockaccess_help'] = 'Quando è selezionato, gli utenti che non possono vedere questo elemento vengono tenuti lontani anche dal suo URL: se lo aprono direttamente, vengono indirizzati alla Dashboard, poi alla Home del sito, poi a I miei corsi, poi alla pagina iniziale predefinita, a seconda di quella che è loro consentita. Se nessuna di queste pagine è consentita, vedono una pagina che spiega che la pagina non è disponibile.

Si possono bloccare solo gli URL di questo sito. Le lingue non influiscono mai sul blocco e gli amministratori del sito non vengono mai bloccati.

Il blocco si applica solo alle pagine che mostrano l’intestazione del sito, quindi dà forma alla navigazione; non sostituisce i permessi.';
$string['bulk_delete'] = 'Elimina i selezionati';
$string['bulk_delete_confirm'] = 'Eliminare i nodi selezionati? I nodi di Moodle vengono conservati, a meno che non siano attualmente non disponibili. L’operazione non può essere annullata.';
$string['cachedef_icons'] = 'Icone proposte dal selettore di icone';
$string['cachedef_nodes'] = 'Nodi di navigazione di tutti i menu';
$string['clear_filters'] = 'Cancella';
$string['conflict_handling'] = 'Nodi esistenti';
$string['conflict_handling_help'] = 'Cosa fare con i nodi del file il cui menu e la cui chiave esistono già in questo sito: conservare il nodo esistente o sostituirlo con quello del file.';
$string['conflict_overwrite'] = 'Sostituiscili con quelli del file';
$string['conflict_skip'] = 'Conservali';
$string['coreoverridealert'] = 'Questo è un nodo di Moodle. Titolo e URL sono facoltativi: lasciali vuoti per mantenere i valori di Moodle, che restano tradotti.';
$string['delete_node_confirm'] = 'Eliminare questo nodo? L’operazione non può essere annullata.';
$string['display'] = 'Visualizzazione';
$string['display_current'] = 'Finestra corrente';
$string['display_help'] = 'Come si apre il collegamento: nella finestra corrente, in una nuova finestra o in una finestra popup delle dimensioni indicate sotto. Gli elementi con sottoelementi aprono sempre invece il loro sottomenu.';
$string['display_new'] = 'Nuova finestra';
$string['display_popup'] = 'Popup';
$string['doc_example_format'] = 'Esempio';
$string['doc_example_tip'] = 'Tutti i campi tranne nodekey sono facoltativi. I campi vuoti di un nodo di Moodle mantengono i valori di Moodle.';
$string['doc_f_blockaccess'] = '1 per bloccare l’URL agli utenti che non possono vedere il nodo, 0 altrimenti. Usato solo quando la visibilità è 0 o 2.';
$string['doc_f_display'] = 'Come si apre il collegamento: 0 = finestra corrente, 1 = nuova finestra, 2 = popup.';
$string['doc_f_icon'] = 'Classe FontAwesome (ad esempio «fa-book») o icona Moodle (ad esempio «i/home»). «none» rimuove l’icona.';
$string['doc_f_langs'] = 'Codici lingua separati da virgole (ad esempio «es,pt_br»). Il nodo viene mostrato nel menu solo per queste lingue. Vuoto significa tutte le lingue.';
$string['doc_f_menu'] = 'Menu del nodo: «primary» o «usermenu». Per impostazione predefinita, il menu che si sta importando.';
$string['doc_f_nodekey'] = 'Chiave del nodo, univoca all’interno del suo menu. Usa una chiave di Moodle (ad esempio «myhome») per modificare un nodo di Moodle.';
$string['doc_f_order'] = 'Posizione del nodo nel suo menu, dove 1 è la prima. I file esportati elencano tutte le posizioni. In importazione, i nodi che non la hanno mantengono il loro posto, oppure vanno per ultimi se sono nuovi.';
$string['doc_f_parentkey'] = 'Chiave del nodo genitore, per mostrare questo nodo nel suo sottomenu.';
$string['doc_f_popupheight'] = 'Altezza della finestra popup in pixel, da 100 a 9999. Usata quando display è 2.';
$string['doc_f_popupwidth'] = 'Larghezza della finestra popup in pixel, da 100 a 9999. Usata quando display è 2.';
$string['doc_f_roles'] = 'Nomi brevi dei ruoli separati da virgole (ad esempio «editingteacher,teacher»). Usato quando la visibilità è 2.';
$string['doc_f_title'] = 'Testo visualizzato. Vuoto su un nodo di Moodle mantiene il titolo di Moodle.';
$string['doc_f_tooltip'] = 'Testo mostrato al passaggio del mouse sul collegamento.';
$string['doc_f_url'] = 'Destinazione: un percorso di questo sito (ad esempio «/course/view.php?id=2») o un indirizzo completo.';
$string['doc_f_visibility'] = '1 = visibile a tutti, 2 = limitato ai ruoli indicati, 0 = non visibile.';
$string['doc_field'] = 'Campo';
$string['doc_fields_title'] = 'Campi';
$string['doc_intro'] = 'I file di importazione sono file YAML con un elenco «nodes». Ogni voce descrive un nodo con i campi seguenti.';
$string['documentation'] = 'Formato YAML';
$string['download_example'] = 'Scarica un file di esempio';
$string['edit_node'] = 'Modifica nodo';
$string['enable_plugin'] = 'Attiva';
$string['enable_plugin_desc'] = 'Applica i nodi di navigazione e il blocco degli URL. Quando è disattivato, la navigazione di Moodle viene mostrata invariata e nessuna pagina è bloccata.';
$string['entries_count'] = '{$a} voci';
$string['entries_count_one'] = '1 voce';
$string['err_admin_child'] = 'L’amministrazione del sito non può essere inserita in un sottomenu.';
$string['err_admin_hide'] = 'L’amministrazione del sito non può essere nascosta, limitata, bloccata né riservata ad alcune lingue.';
$string['err_admin_parent'] = 'L’amministrazione del sito non può avere elementi di sottomenu.';
$string['err_duplicate_key'] = 'Un altro nodo di questo menu usa già questa chiave.';
$string['err_duplicate_key_alert'] = 'Un altro nodo di questo menu usa già questa chiave.';
$string['err_fallbacks_blocked'] = 'Questo bloccherebbe la Dashboard, la Home del sito, I miei corsi e la pagina iniziale predefinita per tutti gli utenti, lasciandoli senza una pagina in cui arrivare. Sblocca almeno una di esse.';
$string['err_icon_invalid'] = 'Usa una classe FontAwesome (ad esempio «fa-book»), un’icona Moodle (ad esempio «i/home») o «none».';
$string['err_import_duplicate'] = 'Questo nodo compare più di una volta nel file.';
$string['err_import_nodekey'] = 'La voce non ha nodekey.';
$string['err_import_order'] = 'L’ordine deve essere un numero intero da 1.';
$string['err_invalid_display'] = 'Scegli come si apre il collegamento: 0, 1 o 2.';
$string['err_invalid_list'] = 'Usa solo lettere, numeri, trattini bassi e trattini, separati da virgole.';
$string['err_invalid_menu'] = 'Menu sconosciuto. Usa «primary» o «usermenu».';
$string['err_invalid_nodekey'] = 'Usa solo lettere, numeri, trattini bassi e trattini.';
$string['err_invalid_visibility'] = 'Visibilità sconosciuta. Usa 0, 1 o 2.';
$string['err_invalid_yaml_file'] = 'Impossibile leggere il file. Carica un file .yaml o .yml.';
$string['err_invalid_yaml_format'] = 'Il file non è un YAML valido.';
$string['err_invalid_yaml_structure'] = 'Il file deve contenere un elenco «nodes».';
$string['err_parent_haschildren'] = 'Questo nodo ha elementi di sottomenu, quindi non può essere inserito in un sottomenu.';
$string['err_parent_missing'] = 'Non esiste nessun nodo con la chiave «{$a}» in questo menu.';
$string['err_parent_self'] = 'Un nodo non può essere il genitore di se stesso.';
$string['err_parent_thirdlevel'] = 'Il genitore scelto è già in un sottomenu. I menu hanno un solo livello di sottomenu.';
$string['err_popup_size'] = 'Inserisci un numero intero da {$a->min} a {$a->max}.';
$string['err_restricted_page'] = 'La pagina che hai aperto non è disponibile per il tuo account, e nemmeno la Dashboard, la Home del sito o I miei corsi. Contatta l’amministratore del sito se pensi che sia un errore.';
$string['err_text_html'] = 'L’HTML non è consentito qui.';
$string['err_title_usermenu_chars'] = 'I titoli del menu utente non possono contenere «|» né interruzioni di riga.';
$string['err_url_forbidden'] = 'Questo tipo di URL non è consentito.';
$string['err_url_invalid'] = 'Inserisci un URL valido, ad esempio «/course/view.php?id=2» o «https://example.com».';
$string['eventnodecreated'] = 'Nodo di navigazione creato';
$string['eventnodedeleted'] = 'Nodo di navigazione eliminato';
$string['eventnodeupdated'] = 'Nodo di navigazione aggiornato';
$string['export_all_btn'] = 'Esporta tutto';
$string['export_selected'] = 'Esporta i selezionati';
$string['extendednav:manage'] = 'Gestire i nodi di navigazione';
$string['filter_action'] = 'Filtra per azione';
$string['filter_action_any'] = 'Tutte le azioni';
$string['filter_datefrom'] = 'Dal';
$string['filter_dateto'] = 'Fino al';
$string['filter_invalidperiod'] = 'La data di fine non può essere precedente alla data di inizio.';
$string['filter_parent'] = 'Filtra per chiave del genitore';
$string['filter_parent_any'] = 'Chiave del genitore';
$string['filter_visibility'] = 'Filtra per visibilità';
$string['icon'] = 'Icona';
$string['icon_choose'] = 'Scegli l’icona';
$string['icon_clear'] = 'Cancella';
$string['icon_help'] = 'Seleziona il quadrato accanto al campo per scegliere un’icona da un elenco con ricerca, oppure digita una classe FontAwesome (ad esempio «fa-book» o «fa-brands fa-github») o un’icona Moodle (ad esempio «core:i/home», o «i/home» per il core). Lascia vuoto per mantenere quella predefinita, o digita «none» per rimuovere l’icona. Nel menu utente, le icone Moodle vengono mostrate come la loro icona FontAwesome.';
$string['icon_info_brand'] = 'Icona di marchio FontAwesome';
$string['icon_info_fontawesome'] = 'Icona FontAwesome';
$string['icon_info_invalid'] = 'Non è un’icona che il plugin possa mostrare. Scegline una, oppure digita una classe FontAwesome o un’icona Moodle.';
$string['icon_info_moodle'] = 'Icona Moodle di {$a}';
$string['icon_info_none'] = 'Nessuna icona';
$string['icon_loading'] = 'Caricamento delle icone...';
$string['icon_noresults'] = 'Nessuna icona corrisponde alla tua ricerca.';
$string['icon_picker'] = 'Scegli un’icona';
$string['icon_search'] = 'Cerca icone';
$string['icon_tab_brands'] = 'Marchi';
$string['icon_tab_fontawesome'] = 'FontAwesome';
$string['icon_tab_moodle'] = 'Icone Moodle';
$string['icon_unavailable'] = 'Questa versione di Moodle conserva il suo elenco FontAwesome dove il plugin non può leggerlo. Digita invece la classe nel campo dell’icona, ad esempio «fa-book».';
$string['import'] = 'Importa';
$string['import_entry'] = 'Voce {$a}';
$string['import_failed'] = 'Non è stato importato nulla. Correggi questi problemi nel file e riprova:';
$string['import_instructions_desc'] = 'Il file deve contenere un elenco «nodes». Ogni nodo viene prima verificato; se un nodo non è valido, non viene importato nulla.';
$string['import_instructions_title'] = 'Prima di importare';
$string['import_nodes'] = 'Importa nodi';
$string['import_success'] = 'Importazione terminata: {$a->created} creati, {$a->updated} sostituiti, {$a->skipped} conservati.';
$string['langs'] = 'Lingue';
$string['langs_all'] = 'Tutte le lingue';
$string['langs_help'] = 'Mostra questo elemento nel menu solo agli utenti la cui lingua corrente è selezionata. Lascia vuoto per mostrarlo in tutte le lingue. Una lingua comprende anche le sue varianti regionali: lo spagnolo (es) include lo spagnolo - Messico (es_mx).

Le lingue controllano solo se l’elemento compare nel menu. Non influiscono mai sull’accesso al suo URL, perché gli utenti possono cambiare la propria lingua in qualsiasi momento. L’accesso è controllato da Visibilità, Ruoli consentiti e «Blocca l’accesso tramite URL».

Per tradurre un elemento, crea un nodo per ogni lingua con lo stesso URL, gli stessi ruoli e la stessa visibilità.';
$string['langs_hidden_notice'] = 'Questo elemento è nascosto a tutti, quindi le lingue selezionate non hanno alcun effetto sul menu. Vengono conservate nel caso in cui tu renda di nuovo visibile l’elemento. L’accesso all’URL dipende solo da «Blocca l’accesso tramite URL».';
$string['manage_nodes'] = 'Gestisci la navigazione';
$string['manage_nodes_desc'] = 'Aggiungi elementi alla navigazione principale e al menu utente, e modifica, riordina, annida o nascondi gli elementi propri di Moodle.';
$string['menu_type'] = 'Menu';
$string['movenode'] = 'Sposta {$a}';
$string['native_cannot_delete'] = 'I nodi di Moodle non possono essere eliminati. Imposta la loro visibilità su «Non visibile» per nasconderli.';
$string['nativenodeinfo'] = 'Nodo di Moodle';
$string['no_nodes_export'] = 'Non ci sono nodi da esportare.';
$string['node_saved'] = 'Nodo salvato.';
$string['nodekey'] = 'Chiave del nodo';
$string['nodekey_help'] = 'Chiave dell’elemento, univoca all’interno del suo menu. Per modificare un elemento di Moodle, usa la sua chiave: home, myhome, mycourses o siteadminnode nella navigazione principale. Per un nuovo elemento, scegli una nuova chiave con lettere, numeri, trattini bassi e trattini, ad esempio «help_es».';
$string['nodes_count'] = '{$a} elementi';
$string['nodes_count_one'] = '1 elemento';
$string['nodes_deleted'] = '{$a} nodo/i eliminato/i.';
$string['nodes_selected'] = 'nodo/i selezionato/i';
$string['none_title'] = 'Senza titolo';
$string['opt_invalid_admin_submenu'] = 'L’amministrazione del sito è sempre un elemento di primo livello, quindi non può essere inserita in un sottomenu.';
$string['opt_invalid_has_submenus'] = 'Questo nodo ha elementi di sottomenu, quindi non può essere a sua volta inserito in un sottomenu.';
$string['opt_native'] = '{$a->text} ({$a->key})';
$string['opt_none_root'] = '- Nessuno (primo livello) -';
$string['opt_plugin'] = '{$a->title} ({$a->key})';
$string['order'] = 'Ordine';
$string['parentkey'] = 'Genitore';
$string['parentkey_help'] = 'Mostra questo elemento nel sottomenu di un altro elemento. Solo gli elementi di primo livello possono essere genitori, e un elemento con sottoelementi non può essere a sua volta inserito in un sottomenu.';
$string['parenturlunused'] = 'Questo nodo ha elementi di sottomenu, quindi apre il suo sottomenu invece di seguire il suo URL. L’URL è comunque usato per il blocco.';
$string['pluginname'] = 'Navigazione estesa';
$string['popupheight'] = 'Altezza del popup (in pixel)';
$string['popupwidth'] = 'Larghezza del popup (in pixel)';
$string['primary_nav'] = 'Navigazione principale';
$string['privacy:metadata'] = 'Il plugin Navigazione estesa non memorizza alcun dato personale. Le modifiche ai nodi di navigazione vengono registrate nel log standard.';
$string['restricted_title'] = 'Pagina non disponibile';
$string['roles'] = 'Ruoli consentiti';
$string['roles_help'] = 'Gli utenti con almeno uno di questi ruoli, in qualsiasi contesto, vedono l’elemento. Ogni utente autenticato possiede anche il ruolo predefinito degli utenti autenticati.';
$string['search_placeholder'] = 'Cerca per titolo, chiave del nodo o URL...';
$string['settings_page'] = 'Impostazioni';
$string['submenuof'] = 'Sottomenu di: {$a}';
$string['title'] = 'Titolo';
$string['title_help'] = 'Testo mostrato nel menu. Su un elemento di Moodle, lascia vuoto per mantenere il titolo di Moodle, che è tradotto. Una stringa di lingua può essere usata come «identificatore,componente», ad esempio «profile,moodle».';
$string['tooltip'] = 'Suggerimento';
$string['tooltip_help'] = 'Testo mostrato al passaggio del mouse sul collegamento. Facoltativo.';
$string['unknown_lang'] = 'Non installata: {$a}';
$string['unknown_role'] = 'Ruolo sconosciuto: {$a}';
$string['url'] = 'URL';
$string['url_help'] = 'Dove porta l’elemento: un percorso di questo sito, come «/course/view.php?id=2», o un indirizzo completo. Su un elemento di Moodle, lascia vuoto per mantenere l’URL di Moodle.';
$string['user_menu'] = 'Menu utente';
$string['view_documentation'] = 'Vedi il formato YAML';
$string['vis_all'] = 'Visibile a tutti';
$string['vis_hidden'] = 'Non visibile';
$string['vis_roles'] = 'Limitato';
$string['visibility'] = 'Visibilità';
$string['visibility_help'] = 'Chi vede l’elemento nel menu: tutti, solo gli utenti con i ruoli consentiti o nessuno. Con «Blocca l’accesso tramite URL», gli utenti che non possono vedere l’elemento non possono nemmeno aprire il suo URL.';
$string['warn_fallbacks_blocked_for_some'] = 'La Dashboard, la Home del sito, I miei corsi e la pagina iniziale predefinita sono tutte bloccate per gli utenti senza alcuni ruoli. Questi utenti vedranno una pagina di errore invece di essere reindirizzati.';
$string['warn_unknown_lang'] = 'La lingua «{$a}» non è installata.';
$string['warn_unknown_role'] = 'Non esiste alcun ruolo con il nome breve «{$a}».';
$string['yamlconfigfile'] = 'File YAML';
$string['yamlconfigfile_help'] = 'Un file .yaml o .yml con un elenco «nodes», come i file creati da «Esporta tutto».';
