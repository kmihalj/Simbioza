# Accesso e utenti

<a id="login-and-users"></a>


> Le schermate mostrano l’interfaccia in inglese.

Questa guida accompagna l'amministratore attraverso tutte le impostazioni di accesso, gli attributi utente, i gruppi, gli account, la registrazione locale, l'accesso temporaneo come altro utente, le chiavi API, i servizi SSO e la schermata personale Sicurezza e accesso.

<div class="alert alert-info">

**Gli esempi sono stati creati su un'installazione pulita SQLite.** Gli account, i gruppi e le chiavi mostrati qui esistono solo a scopo di documentazione. I segreti e i valori degli attributi personali sono stati rimossi dagli screenshot. Nella produzione, utilizza i tuoi nomi, domini, durate e regole di privilegio minimo.

</div>

<a id="contents"></a>

## Contenuto

1.  [Impostazioni di accesso](#login-settings)
2.  [Attributi utente](#attributes)
3.  [Gruppi](#groups)
4.  [Utenti e account locali](#users)
5.  [Accesso locale e registrazione](#local-login)
6.  [Accesso temporaneo come altro utente](#impersonation)
7.  [API chiavi](#api-keys)
8.  [Flusso di lavoro comune SSO](#sso)
9.  [SAML](#saml)
10. [SAMLproxy](#saml-proxy)
11. [CAS](#cas)
12. [OIDC](#oidc)
13. [OAuth2](#oauth2)
14. [Profilo personale: sicurezza e accesso](#personal-profile)
15. [Elenco di controllo per la verifica e la sicurezza](#verification)

<a id="login-settings"></a>

<a id="1-login-settings"></a>

## 1. Impostazioni di accesso

Apri **Impostazioni → Utenti → Impostazioni di accesso**. Questa pagina abilita i metodi di autenticazione per l'applicazione. L'abilitazione di un provider rende disponibile solo la sua configurazione e il suo menu; ogni singolo profilo deve comunque essere salvato, attivato, testato e mappato correttamente.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/fb8366ae-1f95-45c3-82a7-b4b374ba51a2.png" class="figure-img img-fluid rounded mb-1" alt="Impostazioni di accesso e configurazione dei metodi di accesso disponibili." /><span class="figure-caption d-block">Panoramica delle impostazioni di accesso</span></span>

<figure class="my-4">

<figcaption>Impostazioni di accesso nell'interfaccia inglese.</figcaption>
</figure>

<a id="main-switches"></a>

### Interruttori principali

- **Accesso locale** abilita l'autenticazione del nome utente e della password memorizzata da Simbioza.
- **SimpleSAMLphp, CAS, OIDC e OAuth2** abilitano i gruppi di profili SSO corrispondenti.
- **L'accesso locale break-glass** mantiene disponibile un percorso amministrativo diretto separato quando l'accesso locale normale è disabilitato. Proteggi quell'account con una password univoca complessa e utilizzala solo per il ripristino.
- **Durata dell'accesso** è un limite di inattività espresso in giorni (1–365), rinnovato mentre l'utente che ha effettuato l'accesso rimane attivo. Utilizza un periodo più breve sui dispositivi amministrativi e condivisi.

<div class="alert alert-warning">

**"Profilo attivo" non equivale al segno di spunta verde.** Attivo significa che il profilo può essere utilizzato. Il segno di spunta verde e lo stato *Testato e operativo* vengono visualizzati solo dopo che il test del fornitore ha avuto esito positivo e le mappature richieste sono valide. Un profilo attivo potrebbe essere ancora non operativo.

</div>

<a id="attributes"></a>

<a id="2-user-attributes"></a>

## 2. Attributi dell'utente

Apri **Attributi utente**. Il modello normalmente include e-mail, nome e cognome. Aggiungi un attributo quando l'applicazione deve memorizzare un valore esterno al profilo di base, ad esempio un'istituzione, un dipartimento, una matricola del dipendente o un ruolo di directory esterna.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/f1ea79f6-6612-4b72-bb14-29ff71f2a9d8.png" class="figure-img img-fluid rounded mb-1" alt="Impostazioni per gli attributi utente predefiniti e personalizzati." /><span class="figure-caption d-block">Attributi dell'utente</span></span>

<figure class="my-4">

<figcaption>Attributi predefiniti e personalizzati nell'interfaccia inglese.</figcaption>
</figure>

<a id="adding-an-attribute"></a>

### Aggiunta di un attributo

1.  Immettere un nome macchina stabile in lettere minuscole senza spazi, ad esempio `affiliation` .
2.  Fornire etichette croate e inglesi separate.
3.  Scegli il tipo di dati e configura **Obbligatorio** , **Registrazione** , **Profilo** e **Attivo** in modo indipendente.
4.  Salvalo. Il nuovo campo diventa disponibile nei profili utente e in ogni tabella di mappatura dei profili SSO.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/6e88b4f0-77b9-4da0-8e7a-e84f74f86e7e.png" class="img-fluid rounded border" alt="Esempio di attributo personalizzato bilingue" /> <span class="figure-caption d-block">Esempio di attributo personalizzato bilingue</span> </span>
<figcaption>Il campo del modello di affiliazione è etichettato Ustanova / Istituzione, rimane disponibile per i profili e le mappature SSO ed è escluso dall'autoregistrazione.</figcaption>
</figure>

La **Mappatura** collega un campo del modello Simbioza al nome esatto dell'attributo restituito dal provider. I nomi degli attributi sono esatti: `mail` , `givenName` e `given_name` sono campi diversi. Prima prova, controlla i nomi restituiti e solo dopo inserisci le mappature. Una mappatura SSO non aggiunge automaticamente il campo al modulo di registrazione pubblico.

<div class="alert alert-info">

**Quali campi sono inclusi nell'autoregistrazione?** L'amministratore lo sceglie per attributo. La **Registrazione** controlla se un campo appare nel modulo pubblico, mentre **Obbligatorio** rifiuta un valore vuoto nei contesti in cui quel campo partecipa. Quando entrambe le impostazioni sono abilitate, il campo è obbligatorio durante l'autoregistrazione. Un attributo obbligatorio deve avere anche una mappatura SSO valida affinché un test del profilo del provider diventi operativo. In questo esempio, `affiliation` memorizza l'istituzione fornita da un fornitore e appare nel profilo, quindi è intenzionalmente esclusa dalla registrazione.

</div>

<a id="groups"></a>

<a id="3-groups"></a>

## 3. Gruppi

I gruppi raccolgono gli utenti e portano le autorizzazioni. Il gruppo Amministratore è un gruppo di sistema protetto e non deve essere utilizzato come un normale gruppo aziendale. Crea gruppi separati con privilegi minimi per dipartimenti, progetti o ruoli.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="assets/group-list_en.jpg" class="figure-img img-fluid rounded mb-1" alt="Elenco dei gruppi di sistema e di esempio con azioni di gestione." /><span class="figure-caption d-block">Gruppi</span></span>

Gruppi di sistema e di esempio nell'interfaccia inglese.\

1.  Scegli **Aggiungi gruppo** (il pulsante più), inserisci un **Nome gruppo** riconoscibile e seleziona **Attivo**.
2.  Scegli **Salva gruppi**. Questa schermata non contiene una descrizione o autorizzazioni per i contenuti.
3.  Aggiungi o rimuovi l'appartenenza al gruppo dai dettagli di ciascun utente.
4.  Concedi al gruppo le autorizzazioni richieste nelle impostazioni di un'area di lavoro, pagina, calendario o sondaggio. L'appartenenza a SSO può anche seguire regole basate su un attributo mappato.

**Risultato:** il gruppo esiste e può essere selezionato durante l'assegnazione delle autorizzazioni. Crearlo da solo non apre il contenuto ai suoi membri.

Per disattivare temporaneamente un gruppo non di sistema, deseleziona **Attivo** e salva. Per eliminarlo definitivamente, scegli **Elimina gruppo**. Nella schermata di conferma controlla il nome e le conseguenze indicate per le appartenenze e le regole di mappatura, quindi conferma l’eliminazione oppure annulla. Gli account utente rimangono. Un nome vuoto non elimina più un gruppo. Prima di confermare, verifica dove il gruppo consente l’accesso ai contenuti. Un nuovo gruppo con lo stesso nome ha un’altra identità e non ripristina le appartenenze o le autorizzazioni precedenti. I gruppi di sistema non possono essere eliminati.

<figure class="my-4">
<img src="assets/group-deletion_en.jpg" class="img-fluid rounded border" alt="Controlla il nome e le conseguenze prima di confermare. Annulla torna all’elenco senza eliminare nulla." />
<figcaption>Controlla il nome e le conseguenze prima di confermare. Annulla torna all’elenco senza eliminare nulla.</figcaption>
</figure>

<a id="users"></a>

<a id="4-users-and-local-accounts"></a>

## 4. Utenti e account locali

Apri **Impostazioni → Utenti → Utenti** per trovare gli account, controllarne lo stato e gestire l'iscrizione e l'accesso.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/a00666a6-9521-4f7d-bd23-8c116dfc53a8.png" class="figure-img img-fluid rounded mb-1" alt="Elenco reattivo di account utente con azioni di gestione." /><span class="figure-caption d-block">Tabella utenti reattivi</span></span>

<figure class="my-4">
<span>Colonne bilanciate della tabella utente nell'interfaccia inglese.</span>
</figure>

<a id="creating-a-local-user"></a>

### Creazione di un utente locale

1.  Seleziona **Nuovo utente** .
2.  Inserisci un identificatore di accesso univoco, un nome visualizzato e un indirizzo e-mail.
3.  Scegli la fonte di autenticazione locale e assegna una password temporanea complessa.
4.  Richiedi la modifica della password al primo accesso.
5.  Assegnare solo i gruppi richiesti; l'accesso come amministratore non deve essere l'impostazione predefinita.

<span class="figure editor-html-media-figure editor-html-media-image d-inline-block align-top me-2 mb-2 w-100"><img src="images/8eb23938-b2cd-4e3f-b7f2-2f19aa91a301.png" class="figure-img img-fluid rounded mb-1" alt="Modulo per la creazione di un account utente locale." /><span class="figure-caption d-block">Crea modulo utente locale</span></span>

<figure class="my-4">
<span>Esempio di modulo di account locale senza password visualizzata.</span>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/80bea9cb-0777-45c4-96ce-3ee97e33ceab.png" class="img-fluid rounded border" alt="Nuovo utente nell'elenco" /> <span class="figure-caption d-block">Nuovo utente nell'elenco</span> </span>
<figcaption>L'utente della documentazione dopo la creazione.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/8caff857-27dd-4bcd-9492-82641074e399.png" class="img-fluid rounded border" alt="Dettagli dell'utente" /> <span class="figure-caption d-block">Dettagli dell'utente</span> </span>
<figcaption>Stato dell'account, fornitori collegati e appartenenze ai gruppi.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/46024a32-147e-49bb-9097-4a925d195440.png" class="img-fluid rounded border" alt="Modifica obbligatoria della password di primo accesso" /> <span class="figure-caption d-block">Modifica obbligatoria della password di primo accesso</span> </span>
<figcaption>Una password temporanea obbliga l'utente a sceglierne una nuova prima di continuare.</figcaption>
</figure>

Nella pagina dei dettagli, verifica l'attività dell'account, l'origine di autenticazione, le identità esterne collegate e le appartenenze ai gruppi. La disattivazione è solitamente più sicura dell'eliminazione perché preserva la traccia di controllo. Non sostituire mai manualmente l'identificatore di soggetto stabile di un provider esterno.

<a id="local-login"></a>

<a id="5-local-login-and-registration"></a>

## 5. Accesso locale e registrazione

**Accesso locale** controlla l'autoregistrazione. Mantienilo disabilitato per i sistemi chiusi e crea account in modo amministrativo o tramite SSO attendibile. Se abilitata, la registrazione crea un account ordinario attivo e fa accedere l'utente. Non invia un'e-mail di verifica né dimostra la proprietà dell'indirizzo inserito. Per un sistema chiuso, mantieni la registrazione disabilitata e utilizza gli account creati dall'amministratore o un provider SSO attendibile. Pubblicare un'apposita informativa sulla privacy prima di accettare le registrazioni. Il modulo pubblico include solo gli attributi utente la cui opzione **Registrazione** è abilitata; pertanto il campo *Istituzione* SSO-only non viene visualizzato qui.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/d88655d6-11ff-40c9-aef9-82a7e31d919b.png" class="img-fluid rounded border" alt="Registrazione disabilitata" /> <span class="figure-caption d-block">Registrazione disabilitata</span> </span>
<figcaption>Stato iniziale consigliato per un'applicazione chiusa.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/71149ada-dd68-4f4f-8db6-7705ba261edd.png" class="img-fluid rounded border" alt="Registrazione abilitata" /> <span class="figure-caption d-block">Registrazione abilitata</span> </span>
<figcaption>Registrazione abilitata solo per un esemplare controllato.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/d18a4b43-a1b2-4078-a810-b18a02cc0793.png" class="img-fluid rounded border" alt="Collegamento di registrazione" /> <span class="figure-caption d-block">Collegamento di registrazione</span> </span>
<figcaption>La schermata di accesso mostra la registrazione solo dopo che è stata abilitata.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/4f5652ca-62a4-4b3a-857c-6fccf29b2e76.png" class="img-fluid rounded border" alt="Modulo di registrazione" /> <span class="figure-caption d-block">Modulo di registrazione</span> </span>
<figcaption>Modulo pubblico con i campi di registrazione selezionati; l'attributo dell'istituto utilizzato per la mappatura SSO è intenzionalmente nascosto.</figcaption>
</figure>

Dopo il test, ripristinare lo stato di produzione previsto. Le password devono essere univoche, sufficientemente lunghe e archiviate in un gestore di password. Gli amministratori non devono mai chiedere agli utenti la password attuale.

<a id="impersonation"></a>

<a id="6-impersonation"></a>

## 6. Accesso temporaneo come altro utente

**Accedi come utente** apre temporaneamente l'applicazione nel contesto dell'utente selezionato. Usalo per verificare autorizzazioni e visualizzazione, non per svolgere operazioni di lavoro per conto dell'utente.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/08b8a426-17a9-4fcd-b69c-1d9f877eb742.png" class="img-fluid rounded border" alt="Schermata per l'accesso temporaneo come altro utente" /> <span class="figure-caption d-block">Schermata per l'accesso temporaneo come altro utente</span> </span>
<figcaption>Selezione di un account per la diagnostica di supporto controllata. <span>Seleziona solo l'account necessario per il caso di supporto.</span></figcaption>
</figure>

<figure class="my-4">
<span>L'interfaccia indica chiaramente la sessione rappresentata attiva.</span>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/a0e76efd-bc62-4268-b508-d7de6e12fce8.png" class="img-fluid rounded border" alt="Ritorna all'amministratore" /> <span class="figure-caption d-block">Ritorna all'amministratore</span> </span>
<figcaption>Termina l'attività tornando al tuo account amministratore.</figcaption>
</figure>

<div class="alert alert-warning">

L'accesso temporaneo come altro utente non verifica l'autenticazione SSO effettiva, MFA o i criteri del servizio di accesso. A questo scopo usa la verifica del profilo oppure un account di prova dedicato.

</div>

<a id="api-keys"></a>

<a id="7-api-keys"></a>

## 7. Chiavi API

Queste schermate sono disponibili quando il modulo API è installato e abilitato. Una chiave API funge da proprietario. Gli ambiti possono solo ridurre le autorizzazioni esistenti del proprietario; non possono concedere autorizzazioni di cui il proprietario non dispone. Utilizzare una chiave separata per integrazione, un'etichetta descrittiva, la durata pratica più breve e il set di ambiti più piccolo.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/33c88c2c-3956-4eaf-840d-5f236fb052a1.png" class="img-fluid rounded border" alt="API amministrazione chiavi" /> <span class="figure-caption d-block">API amministrazione chiavi</span> </span>
<figcaption>API amministrazione delle chiavi nell'interfaccia inglese.</figcaption>
</figure>

<a id="direct-key-issuance"></a>

### Emissione diretta delle chiavi

1.  Seleziona il proprietario e solo gli ambiti richiesti.
2.  Imposta una data di scadenza e, se necessario, un indirizzo IP consentito o una restrizione di rete.
3.  Crea la chiave e copia subito il segreto in un luogo di conservazione sicuro.
4.  Il segreto viene mostrato solo una volta. In caso di smarrimento revocare la chiave ed emetterne una nuova.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/89146be8-0fe9-4bdd-a406-d92bc09e00bc.png" class="img-fluid rounded border" alt="Chiave API con privilegi minimi" /> <span class="figure-caption d-block">Chiave API con privilegi minimi</span> </span>
<figcaption>Una chiave intenzionalmente limitata prima della creazione.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/4f972d0e-66b0-4085-8234-5e3b53fdb175.png" class="img-fluid rounded border" alt="Segreto API una tantum" /> <span class="figure-caption d-block">Segreto API una tantum</span> </span>
<figcaption>Il segreto è stato rimosso dallo screenshot.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/39bcd07d-03f4-4f31-8085-0b34a0604698.png" class="img-fluid rounded border" alt="Chiavi API attive" /> <span class="figure-caption d-block">Chiavi API attive</span> </span>
<figcaption>Rivedi e revoca le chiavi attive da questo elenco.</figcaption>
</figure>

<a id="user-request-and-administrator-approval"></a>

### Richiesta dell'utente e approvazione dell'amministratore

Un utente può richiedere una chiave dal profilo personale e spiegarne lo scopo. L'amministratore esamina il proprietario, gli ambiti, la durata e la giustificazione, quindi approva o rifiuta la richiesta. Dopo l'approvazione l'utente potrà rivelare il segreto una sola volta nel profilo personale. Quando Notifiche è abilitato, anche una notifica in-app punta a quella schermata. Né la notifica né l'e-mail devono contenere il segreto.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/fcdeaf36-1804-4d2f-8639-78774d1f3580.png" class="img-fluid rounded border" alt="API modulo di richiesta chiave" /> <span class="figure-caption d-block">API modulo di richiesta chiave</span> </span>
<figcaption>L'utente richiede una chiave limitata e ne dichiara lo scopo.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/e20ced95-af39-44d6-ad45-775b198caabb.png" class="img-fluid rounded border" alt="Richiesta inviata" /> <span class="figure-caption d-block">Richiesta inviata</span> </span>
<figcaption>La richiesta attende la revisione dell'amministratore.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/7f412246-9d37-4f65-a008-49c42fd15f68.png" class="img-fluid rounded border" alt="Richiesta API in sospeso" /> <span class="figure-caption d-block">Richiesta API in sospeso</span> </span>
<figcaption>L'amministratore controlla la richiesta prima dell'approvazione.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/1b731910-dbde-43d7-8e72-416157977d5a.png" class="img-fluid rounded border" alt="Richiesta API approvata" /> <span class="figure-caption d-block">Richiesta API approvata</span> </span>
<figcaption>L'approvazione crea la chiave senza rivelare il segreto all'amministratore.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/36020798-5696-4756-a707-3a463d582f49.png" class="img-fluid rounded border" alt="Notifica chiave API" /> <span class="figure-caption d-block">Notifica chiave API</span> </span>
<figcaption>L'utente viene informato della decisione.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/be701281-d85e-40a8-ada2-2668a2c212c0.png" class="img-fluid rounded border" alt="Notifica di richiesta approvata" /> <span class="figure-caption d-block">Notifica di richiesta approvata</span> </span>
<figcaption>La notifica comporta il recupero del segreto una tantum.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/140963e5-a521-4a24-83df-d8a549fb49da.png" class="img-fluid rounded border" alt="Recupero segreto API una tantum" /> <span class="figure-caption d-block">Recupero segreto API una tantum</span> </span>
<figcaption>Il segreto viene rimosso e non può essere visualizzato nuovamente dopo la chiusura.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/0c3b8928-1587-4071-b847-2d66589c6c03.png" class="img-fluid rounded border" alt="Richiesta API completata" /> <span class="figure-caption d-block">Richiesta API completata</span> </span>
<figcaption>La richiesta completata rimane controllabile senza esporre il segreto.</figcaption>
</figure>

<a id="sso"></a>

<a id="8-common-sso-workflow"></a>

## 8. Flusso di lavoro comune SSO

1.  Registra l'applicazione presso il provider e inserisci solo gli endpoint, l'ID client e il segreto necessari.
2.  Salva il profilo attivo. I segreti esistenti non vengono mai visualizzati durante la modifica; lasciando vuoto il campo segreto si mantiene il valore lato server.
3.  Seleziona **Prova profilo** prima di indovinare le mappature. Completa un login reale per raccogliere gli attributi effettivi del provider.
4.  Associa soggetto, identificatore di accesso, nome visualizzato e campi utente ai nomi restituiti.
5.  Prova di nuovo. Solo **Testato e operativo** insieme a un segno di spunta verde conferma un profilo completo.

<a id="allowed-domains"></a>

### Domini consentiti

L'elenco limita l'accesso ai domini dell'organizzazione. Inserisci un dominio per riga o un elenco separato da virgole. `example.org` consente quel dominio e i suoi sottodomini; un suffisso come `hr` consente identificatori il cui dominio termina con `.hr` . Un elenco vuoto non impone alcuna restrizione di dominio.

**Attributo origine dominio** deve essere l'attributo reale del provider contenente un valore come `user@example.org` o un dominio. È obbligatorio solo quando l'elenco dei domini consentiti è popolato. Non utilizzare un nome visualizzato o un altro valore modificabile.

<a id="public-callback-addresses"></a>

### Indirizzi di richiamata pubblici

Il provider deve restituire il browser all'URL pubblico della stessa installazione. Includere una sottodirectory quando ne viene utilizzata una: per un'applicazione in `https://simbioza.example.org/simbioza`, immettere il valore completo come URL di base pubblico. Non è l'indirizzo interno PHP-FPM, il contenitore o il database. Registra gli URL di richiamata esatti mostrati dal profilo.

<a id="saml"></a>

## 9. SAML

Un profilo SAML dipende da un **SimpleSAMLphp** installato separatamente e correttamente configurato. Il caricatore automatico, l'origine di autenticazione SP, i certificati, i metadati e gli URL di ritorno devono già funzionare. Il percorso di `vendor/autoload.php` e il nome dell'origine di autenticazione devono corrispondere all'installazione.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/d33dc6ed-ec34-4a5f-b54b-94354364bd31.png" class="img-fluid rounded border" alt="Configurazione iniziale SAML" /> <span class="figure-caption d-block">Configurazione iniziale SAML</span> </span>
<figcaption>Il profilo è salvato, ma le mappature non sono ancora confermate.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 editor-html-media-image w-100"> <img src="images/fc09510d-0f1e-44ed-88b5-22d64f4d151f.png" class="img-fluid rounded border" alt="Primo test SAML" /> <span class="figure-caption d-block">Primo test SAML</span> </span>
<figcaption>Il collegamento funziona e gli attributi sono stati raccolti; i valori sono oscurati.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/21676ed7-d1a6-4008-8ab6-8885b087e737.png" class="img-fluid rounded border" alt="Avvio dell'accesso AAI" /> <span class="figure-caption d-block">Avvio dell'accesso AAI</span> </span>
<figcaption>La schermata di accesso in inglese Simbioza avvia il flusso AAI@EduHr SSO configurato senza esporre le credenziali.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/a92bb0e8-0687-4f28-88c7-e02fa8f36b42.png" class="img-fluid rounded border" alt="SAML mappature" /> <span class="figure-caption d-block">SAML mappature</span> </span>
<figcaption>Mappature richieste immesse dagli attributi effettivamente restituiti.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/31d0a1e9-cc0a-479b-afbc-9aadcd604748.png" class="img-fluid rounded border" alt="Profilo operativo SAML" /> <span class="figure-caption d-block">Profilo operativo SAML</span> </span>
<figcaption>Il test ripetuto conferma un profilo SAML operativo.</figcaption>
</figure>

<a id="saml-proxy"></a>

<a id="10-saml-proxy-and-child-profiles"></a>

## 10. SAML proxy e profili secondari

Un'istanza proxy rappresenta una sorgente di autenticazione SimpleSAMLphp che può esporre più servizi a monte. La scheda **Default** definisce il percorso autoload condiviso, la sorgente di autenticazione e la mappatura di base. Le schede secondarie ereditano le impostazioni di collegamento condivise, mantenendo le proprie mappature degli attributi.

**Un proxy non ha domini consentiti o campi di attributi di origine del dominio.** Questo è intenzionale: i provider upstream possono restituire identificatori e domini diversi. Applica le restrizioni al proxy o al provider upstream, non tramite una regola di dominio condivisa Simbioza.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/4f8e5ae8-57d5-4e8a-878c-ac5e78821030.png" class="img-fluid rounded border" alt="Selezione del fornitore proxy" /> <span class="figure-caption d-block">Selezione del fornitore proxy</span> </span>
<figcaption>Il vero accesso proxy offre AAI@EduHR, Facebook e Google, spiegando i tre profili figlio.</figcaption>
</figure>

<a id="default--aaieduhr"></a>

### Predefinito / AAI@EduHR

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/faa2088e-6d03-4385-852f-0bab107c5ae1.png" class="img-fluid rounded border" alt="Primo test proxy predefinito" /> <span class="figure-caption d-block">Primo test proxy predefinito</span> </span>
<figcaption>Attributi raccolti prima della mappatura, con valori rimossi.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/3cf0d969-0b78-4ba6-ae53-89391e530d7d.png" class="img-fluid rounded border" alt="Mappature proxy predefinite" /> <span class="figure-caption d-block">Mappature proxy predefinite</span> </span>
<figcaption>Mappature per l'origine AAI predefinita.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/d731b742-31b9-447a-9bf7-b481b3417240.png" class="img-fluid rounded border" alt="Profilo proxy operativo predefinito" /> <span class="figure-caption d-block">Profilo proxy operativo predefinito</span> </span>
<figcaption>Scheda proxy predefinita dopo il test ripetuto con esito positivo.</figcaption>
</figure>

### Facebook

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/a44f01cb-48ba-4b0d-bdb4-f7257ede99c5.png" class="img-fluid rounded border" alt="Primo test proxy di Facebook" /> <span class="figure-caption d-block">Primo test proxy di Facebook</span> </span>
<figcaption>Attributi di Facebook raccolti prima della mappatura, con valori rimossi.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/b8477a91-99c7-474b-bdd0-62f141c956e0.png" class="img-fluid rounded border" alt="Mappature del sottoprofilo Facebook" /> <span class="figure-caption d-block">Mappature del sottoprofilo Facebook</span> </span>
<figcaption>ID Facebook stabile, login e campi utente mappati in modo indipendente.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/cd8a17cd-616a-4826-8caa-85ace9446401.png" class="img-fluid rounded border" alt="Sottoprofilo Facebook operativo" /> <span class="figure-caption d-block">Sottoprofilo Facebook operativo</span> </span>
<figcaption>Scheda secondaria di Facebook dopo la mappatura e il test riusciti.</figcaption>
</figure>

### Google

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/c0338f0d-59e3-4a6b-ab0b-fdd3a1ee8f0c.png" class="img-fluid rounded border" alt="Primo test proxy di Google" /> <span class="figure-caption d-block">Primo test proxy di Google</span> </span>
<figcaption>Attributi Google raccolti prima della mappatura, con valori rimossi.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/da95a7fc-0699-4fb3-b8f3-874cdfd0f370.png" class="img-fluid rounded border" alt="Mappature del sottoprofilo Google" /> <span class="figure-caption d-block">Mappature del sottoprofilo Google</span> </span>
<figcaption>L'UID Google è il soggetto, l'email Google è l'identificatore di accesso e i campi del nome sono associati al modello.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/4290218a-cbf8-4cd3-b989-0c4c2700a349.png" class="img-fluid rounded border" alt="Sottoprofilo Google operativo" /> <span class="figure-caption d-block">Sottoprofilo Google operativo</span> </span>
<figcaption>Scheda secondaria di Google dopo il test ripetuto con successo.</figcaption>
</figure>

<a id="cas"></a>

## 11. CAS

Un profilo CAS necessita dell'URL di base del server e dei relativi endpoint di accesso, convalida e disconnessione. Se viene utilizzata una catena di CA privata, fornire un percorso del certificato leggibile. Non disabilitare mai la convalida TLS per aggirare una configurazione del certificato errata.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/aa4de493-9953-4a64-a76c-548b7aa92c9d.png" class="img-fluid rounded border" alt="Configurazione iniziale CAS" /> <span class="figure-caption d-block">Configurazione iniziale CAS</span> </span>
<figcaption>La connessione CAS è preparata ma le mappature non sono complete.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/417fd550-238d-4b9b-b008-280738935d04.png" class="img-fluid rounded border" alt="Primo test CAS" /> <span class="figure-caption d-block">Primo test CAS</span> </span>
<figcaption>L'autenticazione CAS restituisce gli attributi; i valori vengono redatti.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/1e2d14d5-7702-4326-8bca-73f266673fa9.png" class="img-fluid rounded border" alt="CAS mappature" /> <span class="figure-caption d-block">CAS mappature</span> </span>
<figcaption>Soggetto stabile, identificatore di accesso, nome visualizzato e campi del modello associati in base alla verifica.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/c4341d65-56fa-4c42-82cf-28db9b05e563.png" class="img-fluid rounded border" alt="Profilo operativo CAS" /> <span class="figure-caption d-block">Profilo operativo CAS</span> </span>
<figcaption>Il test ripetuto conferma il profilo CAS.</figcaption>
</figure>

<a id="oidc"></a>

## 12. OIDC

OIDC utilizza un ID client e un segreto, un'autorizzazione, un token, informazioni utente e, facoltativamente, endpoint di fine sessione. L'ambito deve includere `openid` , seguito solo dai dati obbligatori come `profile` e `email` . **URL di base di reindirizzamento pubblico** è l'indirizzo di base pubblico completo di questa installazione, inclusa una sottodirectory ove presente.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/a8ba39ed-751e-4586-9199-a5cdf7b80c8c.png" class="img-fluid rounded border" alt="Configurazione iniziale OIDC" /> <span class="figure-caption d-block">Configurazione iniziale OIDC</span> </span>
<figcaption>Endpoint OIDC e URL di base pubblica prima della mappatura.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/7dd1a0ab-0d45-48f9-a529-7fb5016ba951.png" class="img-fluid rounded border" alt="Primo test OIDC" /> <span class="figure-caption d-block">Primo test OIDC</span> </span>
<figcaption>OIDC attestazioni raccolte prima della mappatura, con valori rimossi.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/ff56adb1-ea0d-4d0a-bf0e-02b358a71c05.png" class="img-fluid rounded border" alt="OIDC mappatura e dominio" /> <span class="figure-caption d-block">OIDC mappatura e dominio</span> </span>
<figcaption>Soggetto, identificatore di accesso, nome visualizzato, campi utente e origine del dominio associati ai valori effettivamente restituiti.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/0603f7ff-a5e7-4f13-a6e6-b7b144b992a5.png" class="img-fluid rounded border" alt="Profilo operativo OIDC" /> <span class="figure-caption d-block">Profilo operativo OIDC</span> </span>
<figcaption>Il test ripetuto conferma OIDC e visualizza il segno di spunta verde.</figcaption>
</figure>

Utilizza un parametro di accesso forzato come `max_age=0` solo quando il provider lo supporta e **Forza accesso** è abilitato. Altrimenti lascialo vuoto per preservare una sessione SSO esistente valida.

<a id="oauth2"></a>

## 13. OAuth2

OAuth2 utilizza endpoint di autorizzazione, token e informazioni utente oltre agli ambiti richiesti per i dati utente. Se il provider supporta OpenID Connect, preferisci OIDC perché standardizza la convalida dell'identità e del token. OAuth2 Il logout può richiedere un endpoint dedicato, un nome di parametro di reindirizzamento o un suggerimento; inserire solo i valori richiesti dalla documentazione del provider.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/c8024292-d7ab-4926-82fc-3ae3135df40e.png" class="img-fluid rounded border" alt="Configurazione iniziale OAuth2" /> <span class="figure-caption d-block">Configurazione iniziale OAuth2</span> </span>
<figcaption>Profilo OAuth2 prima della mappatura degli attributi.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/5a358208-3572-4641-970a-278bf2b22082.png" class="img-fluid rounded border" alt="Primo test OAuth2" /> <span class="figure-caption d-block">Primo test OAuth2</span> </span>
<figcaption>Attributi Userinfo raccolti tramite un accesso reale, con valori rimossi.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/0df4572c-6616-4522-939e-f825f70504d2.png" class="img-fluid rounded border" alt="OAuth2 mappatura e dominio" /> <span class="figure-caption d-block">OAuth2 mappatura e dominio</span> </span>
<figcaption>Mappature e restrizioni di dominio immesse dal risultato del test.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/d3faabdb-f174-4a6b-9f00-45f56e11eb3b.png" class="img-fluid rounded border" alt="Profilo operativo OAuth2" /> <span class="figure-caption d-block">Profilo operativo OAuth2</span> </span>
<figcaption>Il test ripetuto conferma il profilo OAuth2.</figcaption>
</figure>

<a id="personal-profile"></a>

<a id="14-personal-profile-security-and-access"></a>

## 14. Profilo personale: sicurezza e accesso

Apri **Profilo personale** dal menu utente. La sezione principale mostra l'identità e gli attributi personalizzati. **Sicurezza e accesso** visualizza l'appartenenza al gruppo, lo stato della password locale, gli accessi esterni collegati e le richieste di chiave API.

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/52960239-b877-4349-bd2a-016f5837e8f7.png" class="img-fluid rounded border" alt="Profilo personale" /> <span class="figure-caption d-block">Profilo personale</span> </span>
<figcaption>Dati personali e attributi personalizzati nell'interfaccia inglese.</figcaption>
</figure>

<figure class="my-4">
<span class="figure editor-html-media-figure d-inline-block align-top me-2 mb-2 w-25 editor-html-media-image"> <img src="images/12c871d9-5263-4a65-93f0-43ed0980d549.png" class="img-fluid rounded border" alt="Sicurezza e accesso ampliati" /> <span class="figure-caption d-block">Sicurezza e accesso ampliati</span> </span>
<figcaption>Gruppi, accesso locale, provider collegati e richieste API in un unico posto.</figcaption>
</figure>

Collega un account esterno solo mentre controlli entrambe le identità. Rimuovi i collegamenti sconosciuti o non utilizzati, ma verifica prima che sia disponibile un altro metodo di accesso. Modifica le password locali tramite questo modulo sicuro, mai tramite e-mail o un messaggio dell'amministratore.

<a id="verification"></a>

<a id="15-verification-and-security-checklist"></a>

## 15. Verifica e checklist di sicurezza

- Mantieni disponibile almeno un accesso amministrativo controllato; conserva l'account di emergenza separato dal normale uso quotidiano.
- Per ogni profilo SSO, verifica gli URL di richiamata esatti, i certificati TLS, gli ambiti minimi e la rotazione dei segreti.
- Non accettare una casella di controllo attiva come prova. Richiedere lo stato operativo verde dopo il test ripetuto.
- Il soggetto deve essere un identificatore immutabile del servizio di accesso. Identificatore di accesso ed email possono cambiare e non sono sempre adatti come identificatori principali.
- Testare le restrizioni del dominio sia con un account di prova consentito che con uno negato.
- Esamina regolarmente gli amministratori, le appartenenze ai gruppi, gli accessi esterni collegati, le chiavi API attive e le richieste in sospeso.
- Non inserire mai password, segreti client, token, segreti API o attributi personali non elaborati in screenshot, documentazione, ticket o controllo del codice sorgente.
- Dopo il test, rimuovi o disattiva gli account e le chiavi della documentazione che non sono più necessari.

<div class="alert alert-success">

Dopo aver salvato, prova un accesso normale e un account negato. Conferma che ogni utente veda solo il contenuto e le azioni assegnate ai propri gruppi.

</div>
