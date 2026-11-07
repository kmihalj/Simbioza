# API: connessione ad altri sistemi

<a id="api--connecting-other-systems"></a>


> Le schermate mostrano l’interfaccia in inglese.

Questa guida è rivolta agli sviluppatori che collegano la propria applicazione a Simbioza. API funziona con utenti, contenuti, aree di lavoro, calendari, sondaggi e dati personali già consentiti al proprietario della chiave. Non sostituisce l'amministrazione dell'installazione o l'accesso dei partecipanti ai sondaggi.

<a id="getting-started"></a>

## Iniziare

1. Un amministratore installa/abilita API ed emette una chiave con gli ambiti richiesti.
2. Utilizzare [Introduzione](api-pocetak_it.md) per impostare l'indirizzo e leggere `/api/v1/me`.
3. Controlla le risorse disponibili e OpenAPI della tua installazione.
4. Apri il capitolo delle risorse e segui l'esempio di creazione, lettura, aggiornamento o esportazione.
5. Gestisci anche gli errori: la chiave non consente l'accesso ai contenuti privati di un altro utente.

<a id="chapters"></a>

## Capitoli

| Capitolo | Operazioni verificate |
|---|---|
| [API — nozioni di base e regole condivise](api-pocetak_it.md) | 3 |
| [API — utenti e gruppi](api-korisnici-grupe_it.md) | 11 |
| [API — aree di lavoro, alberi, autorizzazioni e temi](api-podrucja_it.md) | 30 |
| [API — pagine HTML, bozze e allegati](api-html-stranice_it.md) | 22 |
| [API — calendari ed eventi](api-kalendari_it.md) | 13 |
| [API — sondaggi e risultati](api-ankete_it.md) | 18 |
| [API — attività nel contenuto](api-zadaci_it.md) | 4 |
| [API — notifiche ed elementi seguiti personali](api-obavijesti-pracenja_it.md) | 11 |
| [API — webhook e consegne](api-webhookovi_it.md) | 9 |
| [API — controllo e ricerca](api-audit-pretraga_it.md) | 2 |
| [Limiti delle API, CalDAV e ICS](api-granice-protokola_it.md) | — |

Questo manuale copre tutti i **114 percorsi REST registrati** in un'installazione completa. Il percorso di azione Sondaggio denominato supporta 10 operazioni, fornendo **123 operazioni verificate separatamente**. Un'installazione modulare espone solo le risorse i cui moduli sono installati e attivi. CORS `OPTIONS` e il protocollo separato CalDAV non rientrano in questo totale.

Ogni esempio indica il metodo, il percorso, le autorizzazioni, i parametri, il comando curl, la risposta effettiva e i codici di errore verificati. L'indirizzo viene sostituito con il segnaposto di installazione; chiavi e segreti non vengono pubblicati. Le risposte utilizzano dati sintetici, quindi nomi, ID e timestamp differiscono dai tuoi. Alcune risposte di grandi dimensioni utilizzano una proiezione `jq` esplicita; il JSON visualizzato è quindi l'output effettivo di quel filtro.

[Amministratore API-gestione chiavi](admin-api_it.md). [Regole condivise, errori e un client PHP completo](api-pocetak_it.md).
