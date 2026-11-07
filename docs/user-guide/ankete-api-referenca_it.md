# API: percorsi, ambiti ed errori

<a id="api--routes-scopes-and-errors"></a>


> Le schermate mostrano l’interfaccia in inglese.

[Per sviluppatori — API](ankete-developeri_it.md)

Il percorso di base è `/api/v1/surveys`, senza barra finale. Prefisso con la sottodirectory di installazione, ove applicabile. Ogni richiesta necessita di una chiave valida, dell'ambito richiesto e dell'attuale autorizzazione del dominio del proprietario.

<div class="table-responsive">

| Metodo e suffisso del percorso                                | Ambito           |
|-------------------------------------------------------|-----------------|
| GET /, GET /{survey}                                  | surveys:read    |
| POST /, PATCH /{survey}                               | surveys:write   |
| POST /{survey}/submit-review, withdraw, copy, delete, discard-draft | surveys:write |
| POST /{survey}/publish, return-review, close, archive, restore | surveys:publish |
| GET /{survey}/results, responses                      | surveys:results |
| POST /{survey}/exports; GET /{survey}/exports/{token} | surveys:export  |

</div>

Le transizioni utilizzano `version`, `revision` ed esplicito `confirmed`. La copia accetta un nuovo titolo. Non modificare la struttura in vigore dopo che sono emerse le risposte: copiare il sondaggio.

L'elenco supporta `q`, `state`, `page` e `per_page`. I risultati accettano la lingua e l'impaginazione; i manager possono anche filtrare i giorni di reporting `from`/`to` inclusi. I singoli invii richiedono la gestione; la sola autorizzazione a leggere i grafici non basta.

Le esportazioni accettano `mode` (aggregate/responses), `lang` ed esplicito `include_identity`. Il token restituito scade e rimane limitato allo stesso creatore autorizzato. Non è un collegamento pubblico o un backup.

<div class="table-responsive">

| Stato | Azione |
|----|----|
| 401 / 403 | Controlla la chiave, l'ambito, l'account attivo e le autorizzazioni attuali. |
| 404 | La risorsa non è disponibile o un modulo richiesto è disabilitato; non aggirare i controlli di accesso. |
| 409 | Rileggere la versione attuale e riconciliare la modifica. |
| 422 | Correggere la definizione, la lingua o i filtri utilizzando il messaggio di risposta. |

</div>

Le risposte riuscite utilizzano `data`, `meta` e `links`. Gli identificatori di richiesta aiutano la diagnosi. Non registrare pubblicamente le risposte o le chiavi. Non è previsto l'invio di solo token API che agisce come partecipante; si applicano ancora l'accesso normale, la convalida e la protezione dalla ripetizione.

<a id="removal-and-recovery-through-the-api"></a>

## Rimozione e ripristino tramite API

`delete` sposta l'intero sondaggio nel cestino e conserva le risposte. `discard-draft` scarta una bozza funzionante; una pubblicazione esistente rimane invariata. Entrambi richiedono gli attuali `version`, `revision` e il valore booleano `confirmed: true`. `restore` richiede `version`, `confirmed: true` e un amministratore, oltre all'ambito chiave sopra. Una pubblicazione precedente ritorna chiusa. La risposta include l'operazione UUID e `deleted`, che indica se il sondaggio è ora nel cestino. HTTP 409 significa rileggere lo stato attuale prima di decidere di nuovo.
