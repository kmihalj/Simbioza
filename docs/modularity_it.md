# Scelta delle funzionalità Simbioza

<a id="choosing-simbioza-features"></a>


> Le schermate mostrano l’interfaccia in inglese.

[Versione croata](modularity_hr.md)

Simbioza riunisce pagine, un Wiki, calendari, sondaggi e collaborazione in un'unica applicazione completa. Se hai bisogno solo di alcune di queste funzionalità, il framework modulare HeartPhrame consente anche un'installazione mirata. Le seguenti preimpostazioni descrivono questa opzione aggiuntiva.

**Simbioza** in croato significa **simbiosi**: diversi organismi che vivono insieme. L'applicazione prende il nome da quell'idea perché i suoi moduli hanno ruoli distinti ma possono lavorare insieme. Una pagina spiega un evento del calendario, un Wiki collega le conoscenze del team e un sondaggio raccoglie feedback. La versione completa di Simbioza combina questi strumenti, attività, commenti, notifiche e integrazioni in un unico ambiente.

<a id="choose-a-purpose"></a>

## Scegli uno scopo

| Di cosa hai bisogno | Preimpostazione di installazione | Moduli di funzionalità |
| --- | --- | --- |
| Tutte le funzionalità disponibili insieme | Simbioza completo | Tutti i moduli opzionali |
| Un sito semplice con pagine HTML | Sito web HTML | HTML Editor |
| Eventi, incontri e CalDAV | Calendario | Calendario |
| Un Wiki ricercabile organizzato in aree di lavoro | Wiki | Workspace, Workspace Search e Simbioza User |
| Sondaggi con domande, ramificazioni e risultati | Sondaggi | Survey |

Ogni installazione include **Auth, ORM, Menu, Theme, Backup e Audit**. Forniscono autenticazione e autorizzazioni, accesso al database, menu, aspetto, backup e registri delle attività. “Autonomo” significa senza altri moduli aziendali, non senza le basi condivise e le dipendenze richieste.

Il programma di installazione aggiunge automaticamente le dipendenze richieste. Ad esempio, Survey utilizza HTML Editor per introduzioni e spiegazioni; anche il Wiki necessita dell'editor e Simbioza User aggiunge le notifiche. API, l'accessibilità e altre integrazioni opzionali non richiedono funzionalità aziendali non correlate.

<a id="install-the-selected-tool"></a>

## Installa lo strumento selezionato

1. Preparare una nuova installazione utilizzando la [guida all'installazione](installation_it.md) appropriata.
2. Nel passaggio relativo alle impostazioni dell'applicazione della procedura guidata, selezionare una **Preimpostazione di installazione** e fare clic su **Applica preimpostazione**.
3. Esamina i moduli selezionati. Le dipendenze richieste rimangono incluse; seleziona funzionalità aggiuntive solo se ne hai bisogno.
4. Seleziona le lingue di installazione e completa la configurazione. Le lingue di contenuto e di traduzione vengono selezionate dalle lingue attive installate.
5. Accedi come amministratore, crea i tuoi contenuti e aggiungi collegamenti al menu in modo che i visitatori possano trovarli.

Il risultato è un'applicazione per lo scopo prescelto. Pagine di esempio, calendari e sondaggi non sono dati aziendali inviati automaticamente a ogni nuovo sito; crei il tuo contenuto dopo l'installazione.

<a id="share-a-standalone-html-website"></a>

## Condividi un sito web HTML autonomo

Un sito web HTML non richiede Wiki per lavorare con più autori o con pagine private. Come amministratore, apri **Impostazioni → HTML editor → Autorizzazioni pagina** e aggiungi utenti o gruppi. **Aggiungi** consente di creare e modificare le proprie pagine. **Modifica** consente di gestire tutte le pagine autonome, comprese quelle private; non concede l'accesso alle impostazioni dell'amministratore.

In una singola pagina, apri **Impostazioni e autorizzazioni della pagina**. Scegli la lettura pubblica, tutti gli utenti che hanno effettuato l'accesso o solo gli utenti e i gruppi selezionati. Questa scelta protegge anche gli allegati, i collegamenti diretti e le letture API. In un Wiki, continua invece a utilizzare l'area di lavoro esistente e le autorizzazioni della pagina.

<a id="extend-an-existing-installation"></a>

## Estendere un'installazione esistente

Ad esempio, aggiungi un calendario o sondaggi a un sito web HTML oppure aggiungi sondaggi, attività e commenti a un Wiki. **Impostazioni → Configurazione e moduli** mostra cosa è installato, cosa è abilitato e quali dipendenze sono richieste.

In un'installazione FPM dedicata configurata correttamente, un amministratore può fare clic su **Installa** accanto a un altro modulo. Per mod_php e FPM ordinario, un manutentore esegue il comando CLI corrispondente dalla root dell'applicazione utilizzando l'account di manutenzione:

```bash
php vendor/bin/hph modules add calendar --fresh
php vendor/bin/hph modules add survey --fresh
```

Questi sono esempi separati: scegli il modulo che ti serve. Il sistema aggiunge le dipendenze richieste. Utilizzare `task` e `comment` per attività e commenti; un Wiki può successivamente aggiungere `confluence-import` per importare il contenuto esistente. Dopo aver esteso l'installazione, configurare le autorizzazioni, i menu e le impostazioni del nuovo modulo. L'installazione di un modulo non concede a ogni utente il permesso di modificare il contenuto.

Effettuare un backup prima di modificare la composizione dell'installazione. Disabilitare un modulo opzionale non equivale a disinstallarlo: la disabilitazione conserva i dati, mentre la disinstallazione ne rimuove le tabelle e il pacchetto dopo aver preparato un backup di ripristino. I sei moduli richiesti non possono essere disabilitati o disinstallati.

<a id="add-workspaces-to-an-existing-html-website"></a>

### Aggiungi aree di lavoro a un sito web HTML esistente

L'aggiunta di aree di lavoro non sposta le pagine HTML esistenti. Mantengono i loro indirizzi, contenuti, traduzioni, allegati e autorizzazioni autonome. Le pagine HTML autonome e le pagine nelle aree di lavoro possono essere utilizzate insieme, anche tramite API.

Solo un amministratore può spostare una pagina esistente in un'area di lavoro:

1. Apri **Impostazioni → HTML editor → Pagine esterne alle aree di lavoro**.
2. Trova la pagina e scegli **Sposta nell'area di lavoro**.
3. Apri l'elenco delle destinazioni o cerca per nome. Mentre scorri vengono caricate altre aree di lavoro.
4. Controlla la visibilità della destinazione e conferma le sue autorizzazioni, quindi sposta la pagina.

Il risultato è una pagina al livello più alto dell'albero di destinazione. Tutte le traduzioni, la cronologia delle versioni e le versioni degli allegati si spostano con esso. I file vengono trasferiti nell'archivio dell'area di lavoro; il database HTML rimane negli stessi record della versione con la nuova proprietà dell'area di lavoro. I collegamenti nelle pagine archiviate vengono riparati, comprese le scelte linguistiche e gli ancoraggi, e gli indirizzi delle pagine e degli allegati esistenti rimangono utilizzabili con i controlli di accesso della destinazione. Le versioni pubblicate rimangono pubblicate e le bozze di lavoro rimangono bozze.

**Le autorizzazioni di destinazione sostituiscono l'accesso autonomo.** I vecchi lettori non vengono aggiunti automaticamente come eccezioni. Lo spostamento di una pagina pubblica in un'area di lavoro privata può limitarla; lo spostamento di una pagina privata in un'area di lavoro pubblica può rendere pubblico il contenuto pubblicato. Controlla la destinazione prima di confermare. Se hai bisogno di un punto di ritorno, fai prima un backup: questa schermata non offre lo spostamento inverso.

<a id="updates-and-backups"></a>

## Aggiornamenti e backup

Le nuove installazioni ricevono cookie di accesso indipendenti, anche quando più siti condividono un dominio. Un ripristino preserva l'identità della sessione locale del sito di destinazione invece di copiare l'identità del cookie di accesso di un altro sito.

FPM dedicato con un helper limitato configurato correttamente supporta gli aggiornamenti tramite **Impostazioni → Configurazione e moduli → Esegui aggiornamento**. Il semplice utilizzo di FPM non è sufficiente: il controllo dell'ambiente deve confermare che gli aggiornamenti della GUI siano disponibili.

Per altre installazioni classiche, il manutentore esegue questi comandi dalla root dell'applicazione:

```bash
php update.php --check --lang=en
php update.php --lang=en
```

Gli aggiornamenti preservano i moduli selezionati e i dati esistenti. Un nuovo modulo opzionale diventa disponibile per l'installazione; non è abilitato automaticamente. Un backup completo include i dati e le impostazioni portatili dei moduli abilitati. Conserva inoltre una copia separata della configurazione dell'host e dei segreti locali, che non vengono trasferiti dai backup dei dati aziendali.

## Docker

Docker utilizza la stessa selezione di funzionalità. Se un'immagine contiene tutti i moduli, seleziona e abilita solo quelli necessari durante l'installazione. Per un'immagine personalizzata più piccola, crea con i moduli selezionati e le relative dipendenze richieste. Un modulo assente dall'immagine richiede un'immagine sostitutiva, non una modifica del codice all'interno di un contenitore in esecuzione. Gli aggiornamenti sostituiscono l'immagine preservando il database e i dati di runtime. Segui la [guida Docker](docker_en.md).

<a id="guides-and-integration-with-other-systems"></a>

## Guide e integrazione con altri sistemi

Con un Wiki, queste pagine risiedono nell'area di lavoro **Guide per l'utente**. Senza Wiki, lo stesso manuale è disponibile tramite **Guide**, con un elenco di capitoli e navigazione. Non è necessario installare un Wiki solo per leggere il manuale.

API può essere aggiunto a qualsiasi scopo. Una chiave API determina le chiamate consentite ma non ignora mai le autorizzazioni del proprietario. I percorsi e gli ambiti disponibili seguono i moduli installati e abilitati. Ciò supporta l'integrazione con altri sistemi e applicazioni HeartPhrame personalizzate senza ricostruire l'autenticazione, la navigazione, l'accesso al database e altre basi comuni.
