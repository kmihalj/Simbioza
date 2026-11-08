# Scegli un'installazione di Simbioza

<a id="choose-a-simbioza-installation"></a>


> Le schermate mostrano l’interfaccia in inglese.

[Versione croata](installation_hr.md)

Queste guide installano Simbioza **0.3.46**. Consulta [versioni e dipendenze del modulo](module-dependencies_en.md) per l'inventario dei pacchetti corrente. Il framework rimane **0.0.25**. La baseline 0.3.x inizia con una nuova directory applicativa, configurazione e database. Un aggiornamento da 0.2.x o il riutilizzo dei relativi backup Simbioza non è supportato.

Scegli **una guida completa** di seguito. Ciascuna inizia da un terminale amministratore, installa/controlla gli strumenti host, scarica la versione, prepara il database e le autorizzazioni, configura il server Web, esegue la procedura guidata e verifica il primo accesso. Non utilizzare questa panoramica come passaggio di installazione aggiuntivo.

| Installazione | Identità PHP | Preparazione del pacchetto e successivi aggiornamenti dell'applicazione | Guida completa |
|---|---|---|---|
| Apache mod_php | Account Apache condiviso | CLI, come proprietario del codice | [Avvia l'installazione di mod_php](installation_mod_php_it.md) |
| PHP-FPM ordinario | Account Web di sistema condiviso in un pool FPM | CLI, come proprietario del codice | [Avvia l'installazione ordinaria di FPM](installation_fpm_it.md) |
| PHP-FPM protetto (consigliato) | Account FPM e deploy dedicati, helper con autorizzazioni limitate | Interfaccia grafica tramite il processo di lavoro oppure CLI con account deploy di manutenzione | [Avvia l'installazione protetta di FPM](installation_fpm_secured_it.md) |

Tutti e tre hanno un **programma di installazione iniziale** grafico e una GUI di configurazione. L'FPM ordinario non è uguale all'FPM protetto: l'aggiunta di un gestore FastCGI da solo non crea l'isolamento della distribuzione né abilita gli aggiornamenti della GUI Composer/applicazione. Nginx richiede FPM; non può eseguire mod_php di Apache. [Installazione Docker](docker_en.md) è un percorso separato e facoltativo basato su immagini.

<a id="before-choosing-a-php-mode"></a>

## Prima di scegliere una modalità PHP

Gli esempi predefiniti riguardano un nuovo host Linux o macOS, non un pool di produzione esistente. Avere una shell di amministratore, un certificato di dominio/TLS per un sito pubblico e una scelta di database. SQLite non necessita di servizio database. Ogni guida prescelta ripete gli esatti comandi di preparazione; non si presuppone alcun account FPM/deploy esistente Simbioza.

Per la reinstallazione, l'amministratore di sistema gestisce innanzitutto i processi e i dati del vecchio sito, controlla le identità esistenti e seleziona l'istanza e la porta corrette. Le guide non eliminano mai un'altra installazione né ricreano un sito in esecuzione semplicemente perché esiste la directory di download.

La configurazione del modulo privato utilizza `HPH_CONFIG_PATH` (predefinito `config/`); i file persistenti utilizzano `data/`. Solo `public/` viene esposto dal server web.

<a id="after-installation"></a>

## Dopo l'installazione

Imposta la [rotazione del registro](logging_en.md) per cron, programma di aggiornamento e output FPM. Il gestore di rotazione `app.log` dell'applicazione non ruota questi file separati.

Ogni guida completa include il proprio modulo, comandi di lingua e aggiornamento, limiti di backup e controlli finali. Il lavoro di routine su pacchetti/codici appartiene all'identità del manutentore/distribuzione, non all'account Web e non a root. I Language Pack possono essere aggiornati indipendentemente dal rilascio dell'applicazione.

Il programma di aggiornamento dell'applicazione aggiorna le guide per l'utente in bundle dai pacchetti di rilascio installati. La lettura di GitHub `main` non sostituisce automaticamente le pagine già archiviate su un sito; il loro contenuto può appartenere a una versione precedente. Le pagine di installazione in bundle vengono generate da queste origini Markdown.
