<?php


$labels = [];
$messages = [];


/* TOOLBOX */
/* Author: Gianluca Giacometti */

$labels['toolbox'] = 'Strumenti';
$labels['toolbox-description'] = 'Insieme di strumenti utili per una migliore gestione della posta web';
$labels['toolbox-usersettings'] = 'Impostazioni utente';
$labels['toolbox-arialabeltoolform'] = 'Modulo di configurazione strumento';
$labels['toolbox-addresses'] = 'Indirizzi';
$labels['toolbox-deleteall'] = 'Cancella tutto';
$labels['toolbox-enabled'] = 'Abilitato';
$labels['toolbox-disabled'] = 'Disabilitato';

$messages['toolbox-driverconnecterror'] = 'Errore di connessione del driver';
$messages['toolbox-driverprocesserror'] = 'Errore di esecuzione del driver';
$messages['toolbox-driverdefaulterror'] = 'Errore sconosciuto di esecuzione del driver';
$messages['toolbox-unknowntool'] = 'Strumento sconosciuto';
$messages['toolbox-internalerror'] = 'Errore di esecuzione sconosciuto';
$messages['toolbox-datasuccessfullysaved'] = 'Dati salvati con successo';
$messages['toolbox-datasaveerror'] = 'Errore durante il salvataggio dei dati';


/* Aliases */
/* Author: Gianluca Giacometti */

$labels['aliases'] = 'Nomi alternativi';
$labels['aliases-manage'] = 'Configura i nomi alternativi';
$labels['aliases-aliasname'] = 'Nome alternativo';
$labels['aliases-addaddress'] = 'Aggiungi nome alternativo';

$messages['aliases-aliasexists'] = 'Nome alternativo già esistente';
$messages['aliases-aliasexistsindomain'] = 'Nome alternativo già esistente nel dominio';
$messages['aliases-aliasdeleteconfirm'] = 'Sei sicuro/a di di voler cancellare questo nome alternativo?';
$messages['aliases-aliasdeleted'] = 'Nome alternativo cancellato con successo';
$messages['aliases-aliasdeletederror'] = 'Impossibile cancellare il nome alternativo';
$messages['aliases-aliascreated'] = 'Nome alternativo creato con successo';
$messages['aliases-aliascreatederror'] = 'Impossibile creare il nome alternativo';
$messages['aliases-aliasupdated'] = 'Nome alternativo aggiornato con successo';
$messages['aliases-aliasupdatederror'] = 'Impossibile aggiornare il nome alternativo';
$messages['aliases-aliasnameerror'] = 'Il nome contiene caratteri non ammessi.';
$messages['aliases-noaliases'] = 'Nessun nome alternativo trovato';
$messages['aliases-novalidalias'] = 'Nome alternativo vuoto';


/* Forward */
/* Author: Gianluca Giacometti */

$labels['forward'] = 'Indirizzi di inoltro';
$labels['forward-manage'] = 'Configura gli indirizzi di inoltro';
$labels['forward-address'] = 'Indirizzo di inoltro';
$labels['forward-addaddress'] = 'Aggiungi indirizzo';
$labels['forward-keepcopies'] = 'Mantieni copia dei messaggi originali';

$messages['forward-invalidaddress'] = 'Indirizzo e-mail non valido';
$messages['forward-atleastoneaddress'] = 'La casella deve almeno contenere un indirizzo di inoltro se non si mantengono coipie dei messaggi';
$messages['forward-emptyaddress'] = 'L\'indirizzo di inoltro non può essere vuoto';
$messages['forward-deleteaddress'] = 'Vuoi eliminare l\'indirizzo di inoltro?';
$messages['forward-deletealladdresses'] = 'Cancella tutti gli indirizzi';
$messages['forward-addressexists'] = 'L\'indirizzo di inoltro è già esistente';
$messages['forward-noaddress'] = 'Nessun indirizzo trovato';


/* Vacation */
/* Author: Gianluica Giacometti */

$labels['vacation'] = 'Autorisponditore';
$labels['vacation-manage'] = 'Configura l\'autorisponditore';
$labels['vacation-subject'] = 'Oggetto:';
$labels['vacation-body'] = 'Messaggio:';
$labels['vacation-active'] = 'Attivo';
$labels['vacation-activefrom'] = 'Attivo da:';
$labels['vacation-activeuntil'] = 'Attivo fino a:';
$labels['vacation-intervaltime'] = 'Modalità di risposta:';
$labels['vacation-replyonce'] = 'Rispondi una volta sola per ciascun indirizzo';
$labels['vacation-replytoeverymail'] = 'Rispondi ad ogni messaggio';
$labels['vacation-replyonceperday'] = 'Rispondi una volta al giorno a ciascun indirizzo';
$labels['vacation-replyonceperweek'] = 'Rispondi una volta alla settimana a ciascun indirizzo';
$labels['vacation-htmleditor'] = 'Attiva la composizione HTML';

$messages['vacation-invalidstartdate'] = 'Seleziona una data di inizio valida.';
$messages['vacation-invalidenddate'] = 'Seleziona una data di fine valida.';
$messages['vacation-invaliddateinterval'] = 'L\'intervallo di date non è valido.';
$messages['vacation-nosubject'] = 'Il campo oggetto non può essere vuoto.';
$messages['vacation-nomessage'] = 'Il campo messaggio non può essere vuoto.';
$messages['vacation-driverdefaulterror'] = 'Errore di esecuzione sconosciuto';
$messages['vacation-driverconnecterror'] = 'Errore durante la connessione';
$messages['vacation-driverprocesserror'] = 'Errore durante l’elaborazione';


/* Purge */
/* Author: Gianluca Giacometti */

$labels['purge'] = 'Pulizia cartelle';
$labels['purge-manage'] = 'Configura la pulizia periodica delle cartelle';
$labels['purge-domainsettings'] = 'Impostazioni del dominio';
$labels['purge-trash'] = 'Mantieni i messaggi nel Cestino per';
$labels['purge-junk'] = 'Mantieni i messaggi nella Posta indesiderata per';
$labels['purge-domainvalue'] = 'valore di base per il dominio';
$labels['purge-always'] = 'sempre';
$labels['purge-day'] = 'giorno';
$labels['purge-days'] = 'giorni';


/* Preview */
/* Author: Gianluca Giacometti */

$labels['preview'] = 'Anteprima messaggi';
$labels['preview-manage'] = 'Configura la visualizzazione dell\'anteprima dei messaggi';
$labels['preview-disable-message'] = 'Disabilita l\'anteprima dei messaggi';
$labels['preview-markasread-doubleclick'] = 'Imposta un messaggio come letto anche con il doppio click del mouse';


/* Customise */
/* Author: Gianluca Giacometti */

$labels['customise'] = 'Personalizza';
$labels['customise-manage'] = 'Personalizzazioni per il dominio';
$labels['customise-purge'] = 'Pulizia cartelle';
$labels['customise-purge-trash'] = 'Mantieni i messaggi nel Cestino per';
$labels['customise-purge-junk'] = 'Mantieni i messaggi nella Posta indesiderata per';
$labels['customise-blankpage'] = 'Pagina vuota personalizzata';
$labels['customise-blankpage-skin'] = 'Pagina vuota';
$labels['customise-blankpage-skin-default'] = 'Pagina vuota Roundcube originale';
$labels['customise-blankpage-skin-image'] = 'Pagina vuota con immagine';
$labels['customise-blankpage-skin-url'] = 'Collegamento a una pagina locale o esterna';
$labels['customise-blankpage-skin-custom'] = 'Pagina personalizzata';
$labels['customise-blankpage-image'] = 'Immagine';
$labels['customise-blankpage-url'] = 'Indirizzo';
$labels['customise-additional-css'] = 'CSS personalizzato';
$labels['customise-logo'] = 'Logo personalizzato';
$labels['customise-logo-template'] = 'Modello';
$labels['customise-logo-template-new'] = 'Nuovo logo personalizzato';
$labels['customise-logo-template-all'] = 'Tutti';
$labels['customise-logo-type'] = 'Tipo';
$labels['customise-logo-type-all'] = 'Tutti i tipi';
$labels['customise-logo-type-favicon'] = 'Favicon';
$labels['customise-logo-type-print'] = 'Print';
$labels['customise-logo-type-small'] = 'Small';
$labels['customise-logo-type-dark'] = 'Logo in modalità scrura';
$labels['customise-logo-type-small-dark'] = 'Small in modalità scura';
$labels['customise-logo-add-template'] = 'Aggiungi';
$labels['customise-logo-image'] = 'Immagine';

$messages['customise-logo-customisedlogoexists'] = 'Logo personalizzato già esistemte';
$messages['customise-logo-deletecustomisedlogo'] = 'Vuoi veramente cancellare questo logo personalizzato?';
$messages['customise-logo-deleteallcustomisedlogos'] = 'Vuoi veramente cancellare tutti i loghi personalizzati di questo tema?';
$messages['customise-logo-emptycustomisedlogotemplate'] = 'Il modello del logo non può essere vuoto';
$messages['customise-logo-emptycustomisedlogoimage'] = 'L\'immagine del logo non può essere vuota';
$messages['customise-logo-invalidcustomisedlogoimage'] = 'L\'immagine del logo non è valida';
$messages['customise-logo-nocustomisedlogo'] = 'Nessun logo personalizzato trovato';


/* Detach Attachments */
/* Author: Gianluca Giacometti */

$labels['attachment-expiry-date'] = 'Scadenza download';
$labels['attachment-maxuploadsize'] = 'NOTA: Se un singolo allegato supera $singlesize MB o l\'insieme degli allegati supera $totalsize MB verrà inviato al destinatario un collegamento ad un file .zip salvato localmente nel server valido per $lifespan giorno(i)';


/* Tags */
/* Author: Gianluca Giacometti */

$labels['tags'] = 'Etichette';
$labels['tags-label'] = 'Etichetta';
$labels['tags-labels'] = 'Etichette';

$labels['tags-title'] = 'Etichette personali';
$labels['tags-empty'] = 'Non ci sono etichette. Seleziona Nuova etichetta per crearne una.';
$labels['tags-newtag'] = 'Nuova etichetta';
$labels['tags-showinlist'] = 'Mostra le etichette nell’elenco dei messaggi';
$labels['tags-listhelp'] = 'I messaggi con etichette occupano una riga aggiuntiva nell’elenco.';
$labels['tags-emptyvalues'] = 'Nessun valore. Seleziona Nuovo valore per aggiungerne uno.';
$labels['tags-newvalue'] = 'Nuovo valore';
$labels['tags-editname'] = 'Modifica nome';
$labels['tags-deletename'] = 'Elimina nome etichetta';
$labels['tags-editvalue'] = 'Modifica valore';
$labels['tags-deletevalue'] = 'Elimina valore';
$labels['tags-name'] = 'Nome etichetta';
$labels['tags-value'] = 'Valore';
$labels['tags-color'] = 'Colore';
$labels['tags-choosecolor'] = 'Scegli il colore dell’etichetta';
$labels['tags-save'] = 'Salva';
$labels['tags-cancel'] = 'Annulla';
$labels['tags-newname'] = 'Nuovo nome etichetta';
$labels['tags-editnametitle'] = 'Modifica nome etichetta';
$labels['tags-parentprefix'] = 'Etichetta: ';
$labels['tags-confirmdeletegroup'] = 'Eliminare definitivamente «{name}» e tutti i suoi valori?';
$labels['tags-confirmdeletevalue'] = 'Eliminare definitivamente «{value}» da «{name}»?';
$labels['tags-deleteirreversible'] = 'Questa operazione non può essere annullata.';
$labels['tags-unavailable'] = 'Le etichette non sono disponibili.';
$labels['tags-noneassigned'] = 'Nessuna etichetta assegnata';
$labels['tags-listprefix'] = 'Etichette: ';
$labels['tags-edit'] = 'Modifica etichette';
$labels['tags-editorfailed'] = 'Impossibile caricare l’editor delle etichette. Controlla la sessione e ricarica l’editor.';
$labels['tags-defssaving'] = 'Salvataggio delle definizioni delle etichette…';
$labels['tags-definitions'] = 'Definizioni delle etichette';
$labels['tags-defsloading'] = 'Caricamento delle definizioni delle etichette…';
$labels['tags-opendefs'] = 'Apri Definizioni delle etichette per creare nomi e valori.';
$labels['tags-loading'] = 'Caricamento etichette…';
$labels['tags-saving'] = 'Salvataggio etichetta…';
$labels['tags-saveunconfirmed'] = 'Impossibile confermare la modifica. Ricarica le etichette prima di riprovare.';
$labels['tags-loadfailed'] = 'Impossibile caricare le etichette. Riprova.';
$labels['tags-updated'] = 'Etichette aggiornate.';
$labels['tags-updateunconfirmed'] = 'Impossibile confermare la modifica dell’etichetta.';
$labels['tags-reloadhint'] = 'Ricarica le etichette prima di riprovare.';
$labels['tags-selectone'] = 'Seleziona un solo messaggio per modificarne le etichette.';
$labels['tags-savepending'] = 'Una modifica alle etichette di questo messaggio è ancora in corso. Attendi.';
$labels['tags-options'] = 'Opzioni etichette';
$labels['tags-message'] = 'Etichette del messaggio';
$labels['tags-reloadeditor'] = 'Ricarica editor';
$labels['tags-reload'] = 'Ricarica etichette';
$labels['tags-defssavingclose'] = 'Salvataggio delle definizioni delle etichette… La finestra si chiuderà al termine.';

$messages['tags-error-invalidrequest'] = 'Richiesta relativa alle etichette non valida';
$messages['tags-error-missingmessage'] = 'Il messaggio non è disponibile in questa cartella';
$messages['tags-error-missingtag'] = 'Questa definizione di etichetta non esiste più';
$messages['tags-error-tagsnotallowed'] = 'Questa cartella non consente etichette personalizzate';
$messages['tags-error-updateunconfirmed'] = 'Il server di posta non ha confermato la modifica dell’etichetta';
$messages['tags-error-verifyfailed'] = 'Impossibile verificare l’etichetta aggiornata sul messaggio';
$messages['tags-error-invalidoperation'] = 'Operazione sulle etichette non valida';
$messages['tags-error-unknownoperation'] = 'Operazione sulle etichette sconosciuta';
$messages['tags-error-listsavefailed'] = 'Impossibile salvare l’opzione di visualizzazione nell’elenco dei messaggi';
$messages['tags-error-invalidlistoption'] = 'Opzione di visualizzazione nell’elenco dei messaggi non valida';
$messages['tags-error-invalidformvalue'] = 'Valore del modulo delle etichette non valido';
$messages['tags-error-toomanynames'] = 'È stato raggiunto il numero massimo di nomi delle etichette';
$messages['tags-error-unknownname'] = 'Nome etichetta sconosciuto';
$messages['tags-error-unknowntag'] = 'Definizione di etichetta personale sconosciuta';
$messages['tags-error-toomanyvalues'] = 'È stato raggiunto il numero massimo di definizioni delle etichette';
$messages['tags-error-restorefirst'] = 'Ripristina l’etichetta prima di modificarla';
$messages['tags-error-invalidstore'] = 'Archivio delle definizioni delle etichette non valido';
$messages['tags-error-corrupteddefinition'] = 'Definizione di etichetta personale danneggiata';
$messages['tags-error-corruptedtext'] = 'Testo di un’etichetta personale danneggiato';
$messages['tags-error-duplicategroupid'] = 'Identificatore di gruppo di etichette duplicato';
$messages['tags-error-corruptedgroups'] = 'Gruppi di etichette danneggiati';
$messages['tags-error-corruptedgroup'] = 'Gruppo di etichette danneggiato';
$messages['tags-error-corruptedgroupname'] = 'Nome di gruppo di etichette danneggiato';
$messages['tags-error-duplicategroupname'] = 'Nomi di gruppi di etichette duplicati';
$messages['tags-error-noparent'] = 'Un valore dell’etichetta non ha un gruppo padre valido';
$messages['tags-error-definitionssavefailed'] = 'Impossibile salvare le definizioni delle etichette personali';
$messages['tags-error-inactive'] = 'Il nome dell’etichetta non è attivo';
$messages['tags-error-nameexists'] = 'Il nome dell’etichetta esiste già';
$messages['tags-error-valueexists'] = 'Il valore dell’etichetta esiste già';
$messages['tags-error-invalidid'] = 'Identificatore dell’etichetta non valido';
$messages['tags-error-invalidname'] = 'Il nome dell’etichetta deve contenere testo valido e non vuoto';
$messages['tags-error-invalidvalue'] = 'Il valore dell’etichetta deve contenere testo valido e non vuoto';
$messages['tags-error-invalidcolor'] = 'Colore dell’etichetta non valido';
$messages['tags-error-unexpected'] = 'Impossibile completare l’operazione sulle etichette';


/* Cloud */
/* Author: Gianluca Giacometti */

$labels['cloud'] = 'Cloud';
$labels['cloud-googledrive'] = 'Google Drive';
$labels['cloud-dropbox'] = 'Dropbox';
$labels['cloud-onedrive'] = 'Microsoft OneDrive';
$labels['cloud-insertlink'] = 'Inserisci un collegamento al file';
$labels['cloud-insertfile'] = 'Allega un file';

$labels['cloud-ui-generate-recovery'] = 'Genera una chiave di recupero';
$labels['cloud-ui-create-title'] = 'Crea la tua cassaforte Cloud personale';
$labels['cloud-ui-create-explanation'] = 'Scegli una password per la cassaforte (almeno 12 caratteri), una chiave di recupero generata o entrambe. La chiave di recupero viene mostrata una sola volta.';
$labels['cloud-ui-new-password-label'] = 'Password della cassaforte (facoltativa se si seleziona la chiave di recupero)';
$labels['cloud-ui-confirm-password-label'] = 'Conferma la password della cassaforte';
$labels['cloud-ui-create-vault'] = 'Crea cassaforte';
$labels['cloud-ui-locked'] = 'Bloccata';
$labels['cloud-ui-unlock-connections'] = 'Sblocca la cassaforte per gestire le connessioni.';
$labels['cloud-ui-reset-timer'] = 'Azzera il timer';
$labels['cloud-ui-lock-now'] = 'Blocca ora';
$labels['cloud-ui-vault-password'] = 'Password della cassaforte';
$labels['cloud-ui-recovery-key'] = 'Chiave di recupero';
$labels['cloud-ui-set-change-password'] = 'Imposta o modifica la password';
$labels['cloud-ui-generate-new-recovery'] = 'Genera una nuova chiave di recupero';
$labels['cloud-ui-vault-security'] = 'Sicurezza della cassaforte';
$labels['cloud-ui-change-security-help'] = 'Modifica password o chiave di recupero';
$labels['cloud-ui-new-vault-password'] = 'Nuova password della cassaforte';
$labels['cloud-ui-confirm-new-password'] = 'Conferma la nuova password della cassaforte';
$labels['cloud-ui-rotate-warning'] = 'Generare una nuova chiave di recupero invalida definitivamente quella precedente.';
$labels['cloud-ui-personal-vault'] = 'Cassaforte personale';
$labels['cloud-ui-unlock-info'] = 'Sblocca la cassaforte per gestire le connessioni Cloud. Il server la blocca nuovamente dopo 5 minuti di inattività Cloud o 15 minuti dall\'ultimo sblocco o azzeramento manuale del timer. Mentre è sbloccata, premi Azzera il timer per rinnovare entrambi i limiti, oppure Blocca ora per bloccarla subito. La chiave sbloccata viene conservata temporaneamente nella sessione autenticata sul server.';
$labels['cloud-ui-unlock-method'] = 'Metodo di sblocco';
$labels['cloud-ui-unlock-secret'] = 'Password della cassaforte / chiave di recupero';
$labels['cloud-ui-unlock-vault'] = 'Sblocca cassaforte';
$labels['cloud-ui-nextcloud-description'] = 'Connettiti con l\'URL WebDAV di Nextcloud e una password per app.';
$labels['cloud-ui-connect-account'] = 'Connetti account';
$labels['cloud-ui-owncloud-description'] = 'Connettiti con l\'URL WebDAV di ownCloud e le relative credenziali.';
$labels['cloud-ui-generic-webdav'] = 'WebDAV generico';
$labels['cloud-ui-webdav-description'] = 'Connetti qualsiasi endpoint WebDAV HTTPS pubblico supportato.';
$labels['cloud-ui-google-description'] = 'Autorizza con Google l\'accesso in sola lettura al tuo Drive.';
$labels['cloud-ui-connect-google'] = 'Connetti account Google';
$labels['cloud-ui-google-aria'] = 'Connetti account Google Drive';
$labels['cloud-ui-onedrive-business'] = 'OneDrive Business';
$labels['cloud-ui-onedrive-business-description'] = 'Autorizza l\'accesso in sola lettura a OneDrive aziendale o scolastico.';
$labels['cloud-ui-connect-business'] = 'Connetti account aziendale';
$labels['cloud-ui-onedrive-business-aria'] = 'Connetti account OneDrive Business';
$labels['cloud-ui-onedrive-personal'] = 'OneDrive Personal';
$labels['cloud-ui-onedrive-personal-description'] = 'Autorizza l\'accesso in sola lettura al tuo OneDrive personale Microsoft.';
$labels['cloud-ui-connect-personal'] = 'Connetti account personale';
$labels['cloud-ui-onedrive-personal-aria'] = 'Connetti account OneDrive Personal';
$labels['cloud-ui-dropbox-description'] = 'Autorizza l\'accesso in sola lettura al tuo account Dropbox.';
$labels['cloud-ui-connect-dropbox'] = 'Connetti account Dropbox';
$labels['cloud-ui-cloud-connections'] = 'Connessioni Cloud';
$labels['cloud-ui-choose-provider'] = 'Scegli un provider. WebDAV utilizza credenziali per app; i provider OAuth richiedono la configurazione dell\'amministratore.';
$labels['cloud-ui-connected-accounts'] = 'Account connessi';
$labels['cloud-ui-unlock-to-list'] = 'Sblocca la cassaforte per elencare gli account.';
$labels['cloud-ui-connect-cloud-account'] = 'Connetti account Cloud';
$labels['cloud-ui-close'] = 'Chiudi';
$labels['cloud-ui-close-connection'] = 'Chiudi la finestra di connessione';
$labels['cloud-ui-account-name'] = 'Nome account';
$labels['cloud-ui-my-cloud-account'] = 'Il mio account Cloud';
$labels['cloud-ui-public-webdav-url'] = 'URL WebDAV HTTPS pubblico completo';
$labels['cloud-ui-webdav-username'] = 'Nome utente WebDAV';
$labels['cloud-ui-webdav-password'] = 'Password per app WebDAV / password';
$labels['cloud-ui-connection-needs-unlock'] = 'La cassaforte Cloud deve essere sbloccata prima di connettere un account.';
$labels['cloud-ui-add-account'] = 'Aggiungi account';
$labels['cloud-ui-browse-files'] = 'Sfoglia i file Cloud';
$labels['cloud-ui-browse-explanation'] = 'Sfoglia le cartelle e consulta i file disponibili. Per allegare file a un messaggio, usa Allega da Cloud nella composizione.';
$labels['cloud-ui-up-folder'] = 'Cartella superiore';
$labels['cloud-ui-next-entries'] = 'Altre 100 voci';
$labels['cloud-ui-save-recovery-title'] = 'Salva ora la tua chiave di recupero';
$labels['cloud-ui-recovery-once-help'] = 'La chiave di recupero non può essere mostrata di nuovo. Copiala in un luogo sicuro fuori da questo account webmail.';
$labels['cloud-ui-recovery-saved'] = 'L\'ho salvata — nascondi la chiave';
$labels['cloud-ui-unlocked'] = 'Sbloccato';
$labels['cloud-ui-automatic-lock-in'] = 'Blocco automatico tra %s';
$labels['cloud-ui-unlock-expired'] = 'Sblocco scaduto.';
$labels['cloud-ui-request-in-progress'] = 'È già in corso un’operazione Cloud.';
$labels['cloud-ui-processing-request'] = 'Elaborazione richiesta Cloud…';
$labels['cloud-ui-request-send-failed'] = 'Impossibile inviare la richiesta Cloud.';
$labels['cloud-ui-unlock-secret-required'] = 'Inserisci la password del vault o la chiave di recupero per questa operazione.';
$labels['cloud-ui-paste-recovery'] = 'Incolla la chiave di recupero';
$labels['cloud-ui-enter-vault-password'] = 'Inserisci la password del vault';
$labels['cloud-ui-nextcloud-webdav-help'] = 'Usa l’indirizzo WebDAV dalle impostazioni File di Nextcloud.';
$labels['cloud-ui-owncloud-webdav-help'] = 'Usa l’indirizzo WebDAV indicato dalla tua installazione ownCloud.';
$labels['cloud-ui-webdav-url-help'] = 'Inserisci l’URL HTTPS pubblico completo della cartella WebDAV.';
$labels['cloud-ui-dialog-unsupported'] = 'Il browser non supporta la finestra di connessione Cloud.';
$labels['cloud-ui-unlock-cloud-first'] = 'Sblocca prima il vault Cloud.';
$labels['cloud-ui-connect-named'] = 'Connetti %s';
$labels['cloud-ui-my-named'] = 'Il mio %s';
$labels['cloud-ui-account-unavailable'] = 'L’account Cloud non è più disponibile. Aggiorna l’elenco.';
$labels['cloud-ui-edit-named'] = 'Modifica %s';
$labels['cloud-ui-cloud-account-default'] = 'account Cloud';
$labels['cloud-ui-cloud-account-title'] = 'Account Cloud';
$labels['cloud-ui-edit-webdav-help'] = 'Aggiorna la connessione WebDAV. Le credenziali salvate non vengono mai mostrate.';
$labels['cloud-ui-keep-username'] = 'Lascia vuoto per mantenere l’utente attuale';
$labels['cloud-ui-keep-app-password'] = 'Lascia vuoto per mantenere la password app attuale';
$labels['cloud-ui-keep-credentials-help'] = 'Lascia vuoti utente o password per mantenere il valore salvato.';
$labels['cloud-ui-save-changes'] = 'Salva modifiche';
$labels['cloud-ui-unlock-before-browse'] = 'Sblocca il vault prima di sfogliare i file.';
$labels['cloud-ui-my-drive'] = 'Il mio Drive';
$labels['cloud-ui-loading-folder'] = 'Caricamento cartella…';
$labels['cloud-ui-folder-list-failed-start'] = 'Impossibile avviare l’elenco della cartella.';
$labels['cloud-ui-select-valid-account'] = 'Sblocca il vault e seleziona un account WebDAV valido.';
$labels['cloud-ui-browse-files-named'] = 'Sfoglia file: %s';
$labels['cloud-ui-folder-empty'] = 'Questa cartella è vuota.';
$labels['cloud-ui-bytes'] = 'byte';
$labels['cloud-ui-no-cloud-accounts'] = 'Nessun account Cloud collegato.';
$labels['cloud-ui-browse-cloud-files'] = 'Sfoglia i file Cloud';
$labels['cloud-ui-edit-cloud-account'] = 'Modifica account Cloud';
$labels['cloud-ui-edit-account'] = 'Modifica account';
$labels['cloud-ui-delete-cloud-account'] = 'Elimina account Cloud';
$labels['cloud-ui-delete-account'] = 'Elimina account';
$labels['cloud-ui-folder-list-failed'] = 'Elenco cartella non riuscito. Controlla la notifica di errore di Roundcube.';
$labels['cloud-ui-operation-failed'] = 'Operazione Cloud non riuscita.';
$labels['cloud-ui-vault-unlocked'] = 'Vault Cloud sbloccato.';
$labels['cloud-ui-vault-locked'] = 'Vault Cloud bloccato.';
$labels['cloud-ui-timer-reset'] = 'Timer del vault azzerato.';
$labels['cloud-ui-vault-created'] = 'Vault creato. Salva la chiave di recupero, se generata.';
$labels['cloud-ui-google-open-failed'] = 'Impossibile aprire l’autorizzazione Google. Abilita i popup e riprova.';
$labels['cloud-ui-microsoft-open-failed'] = 'Impossibile aprire l’autorizzazione Microsoft. Abilita i popup e riprova.';
$labels['cloud-ui-personal-open-failed'] = 'Impossibile aprire l’autorizzazione OneDrive Personal. Abilita i popup e riprova.';
$labels['cloud-ui-dropbox-open-failed'] = 'Impossibile aprire l’autorizzazione Dropbox. Abilita i popup e riprova.';
$labels['cloud-ui-accounts-loaded'] = 'Account Cloud caricati.';
$labels['cloud-ui-account-added'] = 'Account Cloud aggiunto.';
$labels['cloud-ui-account-updated'] = 'Account Cloud aggiornato.';
$labels['cloud-ui-account-removed'] = 'Account Cloud rimosso.';
$labels['cloud-ui-vault-password-updated'] = 'Password del vault aggiornata.';
$labels['cloud-ui-recovery-changed'] = 'Chiave di recupero cambiata. La precedente non funziona più.';
$labels['cloud-ui-vault-password-mismatch'] = 'La password del vault e la conferma non corrispondono.';
$labels['cloud-ui-vault-password-length'] = 'La password del vault deve contenere almeno 12 caratteri.';
$labels['cloud-ui-vault-method-required'] = 'Scegli una password del vault, una chiave di recupero o entrambe.';
$labels['cloud-ui-google-popups'] = 'Consenti i popup di questa webmail per connettere Google Drive.';
$labels['cloud-ui-google-connected'] = 'Google Drive collegato.';
$labels['cloud-ui-google-auth-failed'] = 'Autorizzazione Google Drive non riuscita o vault scaduto.';
$labels['cloud-ui-onedrive-popups'] = 'Consenti i popup di questa webmail per connettere OneDrive.';
$labels['cloud-ui-onedrive-connected'] = 'Microsoft OneDrive collegato.';
$labels['cloud-ui-onedrive-auth-failed'] = 'Autorizzazione Microsoft OneDrive non riuscita o vault scaduto.';
$labels['cloud-ui-personal-popups'] = 'Consenti i popup di questa webmail per connettere OneDrive Personal.';
$labels['cloud-ui-personal-connected'] = 'OneDrive Personal collegato.';
$labels['cloud-ui-personal-auth-failed'] = 'Autorizzazione OneDrive Personal non riuscita o vault scaduto.';
$labels['cloud-ui-dropbox-popups'] = 'Consenti i popup di questa webmail per connettere Dropbox.';
$labels['cloud-ui-dropbox-connected'] = 'Dropbox collegato.';
$labels['cloud-ui-dropbox-auth-failed'] = 'Autorizzazione Dropbox non riuscita o vault scaduto.';
$labels['cloud-ui-unlock-first'] = 'Sblocca prima il vault.';
$labels['cloud-ui-choose-provider-first'] = 'Scegli prima un provider Cloud.';
$labels['cloud-ui-account-edit-required'] = 'Inserisci un nome account e un URL WebDAV HTTPS pubblico.';
$labels['cloud-ui-account-new-required'] = 'Inserisci nome account, URL WebDAV HTTPS pubblico, nome utente e password app.';
$labels['cloud-ui-remove-account-confirm'] = 'Rimuovere questo account Cloud?';
$labels['cloud-ui-new-password-mismatch'] = 'La nuova password del vault e la conferma non corrispondono.';
$labels['cloud-ui-new-password-length'] = 'La nuova password del vault deve contenere almeno 12 caratteri.';
$labels['cloud-ui-rotate-recovery-confirm'] = 'Generare una nuova chiave di recupero? La precedente smetterà di funzionare.';
$labels['cloud-ui-compose-send-failed'] = 'Impossibile inviare la richiesta Cloud.';
$labels['cloud-ui-compose-choose-account'] = 'Scegli un account Cloud';
$labels['cloud-ui-compose-select-account'] = 'Seleziona un account da esplorare.';
$labels['cloud-ui-compose-no-accounts'] = 'Nessun account collegato. Aggiungine uno nelle impostazioni Toolbox.';
$labels['cloud-ui-compose-attach'] = 'Allega';
$labels['cloud-ui-compose-attach-named'] = 'Allega %s';
$labels['cloud-ui-compose-invalid-id'] = 'Il file Cloud è stato salvato, ma l’ID dell’allegato non è valido. Aggiorna la bozza.';
$labels['cloud-ui-compose-attachment-default'] = 'Allegato Cloud';
$labels['cloud-ui-compose-remove-attachment'] = 'Rimuovi allegato';
$labels['cloud-ui-compose-delete'] = 'Elimina';
$labels['cloud-ui-compose-attached'] = 'Allegato %s da Cloud.';
$labels['cloud-ui-compose-request-failed'] = 'Richiesta Cloud non riuscita.';
$labels['cloud-ui-compose-create-vault'] = 'Crea prima il vault Cloud nelle impostazioni Toolbox.';
$labels['cloud-ui-compose-attach-from-cloud'] = 'Allega da Cloud';
$labels['cloud-ui-compose-unlock-help'] = 'Sblocca il vault Cloud per allegare i file.';
$labels['cloud-ui-compose-secret-label'] = 'Password o chiave di recupero';
$labels['cloud-ui-compose-account'] = 'Account Cloud';
$labels['cloud-ui-compose-picker-unsupported'] = 'Il browser non supporta il selettore Cloud.';
$labels['cloud-ui-compose-secret-required'] = 'Inserisci la password del vault o la chiave di recupero.';
$labels['cloud-ui-compose-importing'] = 'Importazione allegato Cloud…';

$labels['cloud-backend-unlock-failed'] = 'Impossibile sbloccare il vault. Controlla la password o la chiave di recupero.';
$labels['cloud-backend-operation-failed'] = 'Operazione Cloud non riuscita. Controlla i dettagli e i log del server.';
$labels['cloud-backend-google-success'] = 'Account Google Drive collegato. Puoi chiudere questa finestra.';
$labels['cloud-backend-google-failed'] = 'Connessione a Google Drive non riuscita o sblocco del vault scaduto. Puoi chiudere questa finestra.';
$labels['cloud-backend-business-success'] = 'Account OneDrive Business collegato. Puoi chiudere questa finestra.';
$labels['cloud-backend-business-failed'] = 'Autorizzazione OneDrive Business non riuscita o sblocco del vault scaduto. Puoi chiudere questa finestra.';
$labels['cloud-backend-personal-success'] = 'Account OneDrive Personal collegato. Puoi chiudere questa finestra.';
$labels['cloud-backend-personal-failed'] = 'Autorizzazione OneDrive Personal non riuscita o sblocco del vault scaduto. Puoi chiudere questa finestra.';
$labels['cloud-backend-dropbox-success'] = 'Account Dropbox collegato. Puoi chiudere questa finestra.';
$labels['cloud-backend-dropbox-failed'] = 'Autorizzazione Dropbox non riuscita o sblocco del vault scaduto. Puoi chiudere questa finestra.';


/* Folders */
/* Author: Gianluca Giacometti */

$labels['folders'] = 'Riepilogo cartelle';
$labels['folders-numberoffiles'] = 'Numero file';
$labels['folders-properties'] = 'Proprietà cartella';
$labels['folders-folder'] = 'Cartella';
$labels['folders-total'] = 'Messaggi';
$labels['folders-unread'] = 'Non letti';
$labels['folders-read'] = 'Letti';
$labels['folders-size'] = 'Dimensione cartella';
$labels['folders-calculate'] = 'Calcola dimensione';
$labels['folders-notcalculated'] = 'Non calcolata';
$labels['folders-calculating'] = 'Calcolo in corso…';
$labels['folders-sizewarning'] = 'Su alcuni server il calcolo della dimensione può richiedere tempo per cartelle molto grandi.';
$labels['folders-namespace'] = 'Spazio dei nomi';
$labels['folders-special'] = 'Cartella speciale';
$labels['folders-yes'] = 'Sì';
$labels['folders-no'] = 'No';
$labels['folders-loading'] = 'Caricamento informazioni cartella…';
$labels['folders-loadfailed'] = 'Impossibile caricare le proprietà della cartella.';
$labels['folders-sizefailed'] = 'Impossibile calcolare la dimensione della cartella.';
$labels['folders-namespace-personal'] = 'Personale';
$labels['folders-namespace-other'] = 'Altri utenti';
$labels['folders-namespace-shared'] = 'Condiviso';
$labels['folders-namespace-unknown'] = 'Sconosciuto';


/* Login Monitor */
/* Author: Gianluca Giacometti */

$labels['login_monitor'] = 'Attività di accesso';
$labels['login-monitor-manage'] = 'Preferenze attività di accesso';
$labels['login-monitor-intro'] = 'Scegli quali dati registrare per i prossimi accessi e quali informazioni salvate mostrare nella cronologia. Data e ora di accesso vengono sempre registrate.';
$labels['login-monitor-collect'] = 'Registra';
$labels['login-monitor-visible'] = 'Mostra nella cronologia';
$labels['login-monitor-field-browser_name'] = 'Browser';
$labels['login-monitor-field-browser_version'] = 'Versione del browser';
$labels['login-monitor-field-os_name'] = 'Sistema operativo';
$labels['login-monitor-field-os_version'] = 'Versione del sistema operativo';
$labels['login-monitor-field-device_type'] = 'Tipo di dispositivo';
$labels['login-monitor-field-device_brand'] = 'Marca del dispositivo';
$labels['login-monitor-field-device_model'] = 'Modello del dispositivo';
$labels['login-monitor-field-city'] = 'Città (approssimativa)';
$labels['login-monitor-field-country'] = 'Paese (approssimativo)';
$labels['login-monitor-field-ip_address'] = 'Indirizzo IP';
$labels['login-monitor-field-user_agent'] = 'User-Agent originale';
$labels['login-monitor-field-browser_engine'] = 'Motore del browser';
$labels['login-monitor-field-bot_detection'] = 'Rilevamento dei bot';
$labels['login-monitor-tab-history'] = 'Cronologia accessi';
$labels['login-monitor-tab-preferences'] = 'Preferenze';
$labels['login-monitor-column-information'] = 'Informazioni';
$labels['login-monitor-unavailable'] = 'Non disponibile';
$labels['login-monitor-history-pending'] = 'La cronologia degli accessi non è ancora disponibile in questa versione.';
$labels['login-monitor-history-intro'] = 'Gli accessi riusciti sono elencati qui sotto. Seleziona Dettagli per consultare le informazioni salvate per ogni accesso.';
$labels['login-monitor-previous-title'] = 'Ultimo accesso precedente';
$labels['login-monitor-previous-unavailable'] = 'L’accesso precedente sarà visibile dopo il prossimo accesso.';
$labels['login-monitor-previous-none'] = 'Non risultano accessi riusciti precedenti.';
$labels['login-monitor-history-error'] = 'Impossibile caricare la cronologia degli accessi. Riprova più tardi.';
$labels['login-monitor-history-title'] = 'Cronologia degli accessi riusciti';
$labels['login-monitor-history-empty'] = 'Non sono stati registrati accessi riusciti.';
$labels['login-monitor-column-date'] = 'Data e ora';
$labels['login-monitor-column-summary'] = 'Informazioni di accesso';
$labels['login-monitor-column-details'] = 'Dettagli';
$labels['login-monitor-current'] = 'Sessione corrente';
$labels['login-monitor-details-open'] = 'Dettagli';
$labels['login-monitor-no-details'] = 'Non registrato';
$labels['login-monitor-page-previous'] = 'Precedente';
$labels['login-monitor-page-next'] = 'Successiva';
$labels['login-monitor-page-number'] = 'Pagina %s';
$labels['login-monitor-pagination'] = 'Pagine della cronologia accessi';
$labels['login-monitor-details-title'] = 'Dettagli dell’accesso';
$labels['login-monitor-details-close'] = 'Chiudi';

$labels['login-monitor-toast-hello'] = 'Ciao';
$labels['login-monitor-toast-last'] = 'Ultimo accesso';
$labels['login-monitor-toast-from'] = 'Da';
$labels['login-monitor-toast-none'] = 'Nessun accesso precedente registrato';

// END OF FILE
