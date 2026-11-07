# Contenuti HTML e sicurezza

<a id="html-content-and-safety"></a>


> Le schermate mostrano l’interfaccia in inglese.

[Per sviluppatori — API](ankete-developeri_it.md)

Il contenuto HTML integrato passa attraverso lo stesso filtro di sicurezza lato server utilizzato per i dati dell'editor. Modificare il sorgente non costituisce un'eccezione alla politica di sicurezza.

<div class="table-responsive">

| Contenuto | Comportamento |
|----|----|
| Testo ordinario, b/strong, i/em, intestazioni, elenchi, tabelle e supporti sicuri | Consentito nei criteri dell'editor HTML. |
| script, style e gestori come onclick/onerror | RIMOSSO; non usarli mai per la logica del sondaggio. |
| javascript:, data: e URL eseguibili simili | Respinto. |
| Attributi e CSS non supportati | Può essere rimosso anche se non pericoloso. |
| Grafici, sequenze temporali, schede, pannelli espandibili, contenuti a discesa e tabelle interattive | Il markup e le impostazioni dell'editor supportati vengono conservati e utilizzano la visualizzazione dell'editor condiviso. |
| Blocchi di calendario, attività, pagine incluse e area di lavoro | I riferimenti e le impostazioni sicuri vengono conservati. Senza un servizio host che risolva il blocco, rimane un segnaposto inerte. |
| Incorporamenti iframe | Vengono conservati solo gli incorporamenti accettati dalla policy dell'editor condiviso, comprese le impostazioni di protezione richieste. |

</div>

Usa le strutture standard create dall'editor anziché codice eseguibile arbitrario. L'applicazione e il salvataggio mantengono i contenuti supportati; la visualizzazione generata di un grafico o di una sequenza temporale non sostituisce il relativo sorgente modificabile. Survey non trasforma i riferimenti del registro degli script in script eseguibili e non concede accesso alle destinazioni dei blocchi dinamici. Fornisci le condizioni delle domande come definizioni dichiarative supportate, non come JavaScript.

Rispettare l'autorizzazione agli allegati durante l'integrazione dei sistemi. Un collegamento non garantisce l'accesso ai contenuti privati. Tratta sempre le risposte dei partecipanti come dati, non eseguibili HTML.
