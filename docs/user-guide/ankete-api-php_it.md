# API — esempi PHP

<a id="api--php-examples"></a>


> Le schermate mostrano l’interfaccia in inglese.

[Per sviluppatori — API](ankete-developeri_it.md)

**Obiettivo:** leggere e creare sondaggi e recuperare i risultati consentiti dalla propria applicazione PHP. Questo è un esempio PHP separato; gli [esempi curl da riga di comando](ankete-api_it.md) rimangono nel proprio capitolo.

<a id="1-prepare-php-and-a-key"></a>

## 1. Preparare PHP e una chiave

Sono necessari **PHP 8.1 o versione successiva** e l'**estensione cURL**. Non è richiesto il bootstrap del pacchetto Composer o del framework Simbioza. Il client utilizza `curl_init` e `curl_exec` di PHP, non i comandi shell.

L'amministratore abilita API ed emette una chiave il cui proprietario dispone delle autorizzazioni richieste. La lettura richiede `surveys:read`; aggiungere `surveys:write` per la creazione, `surveys:publish` per la pubblicazione, `surveys:results` per i risultati e `surveys:export` per CSV. Gli ambiti non sostituiscono le autorizzazioni del proprietario.

Imposta due variabili di ambiente nella configurazione del server dell'applicazione PHP. Con PHP-FPM o contenitori, devono essere disponibili per il processo che esegue l'applicazione; impostarli solo in un terminale non è sufficiente.

```text
SIMBIOZA_SURVEY_API_URL=https://simbioza.example/api/v1/surveys
SIMBIOZA_SURVEY_API_TOKEN=YOUR_SCOPED_API_KEY
```

Sostituisci l'URL e la chiave con i tuoi valori. Includere qualsiasi sottodirectory di installazione, ad esempio `https://simbioza.example/portal/api/v1/surveys`. Conserva la chiave sul lato server, all'esterno della directory Web pubblica e del repository. Non inviarlo mai a JavaScript, al browser o a un URL. Mantieni abilitata la verifica del certificato TLS.

<a id="2-copy-the-files"></a>

## 2. Copia i file

Copia i blocchi seguenti in una directory di lavoro privata separata, utilizzando i nomi file esatti. La tua applicazione necessita solo di `SurveyApiClient.php` come client; gli altri file dimostrano le singole operazioni in CLI.

<div class="table-responsive">

| File | Scopo |
|----|----|
| SurveyApiClient.php | HTTPS client, risposte JSON, errori e download di CSV. |
| SurveyDefinition.php | Assegna nuovi UUID a domande, scelte ed entità tradotte. |
| api-feedback-template.json | Definizione completa dell'esempio dal capitolo separato del modello. |
| php-create-survey.php | Crea una bozza e salva un'introduzione e tre domande. |
| php-publish-survey.php | Dopo la revisione, invia e pubblica la bozza. |
| php-read-results.php | Leggi ogni pagina dei risultati aggregati. |
| php-export-results.php | Scarica CSV in un file privato. |

</div>

Mostra e copia SurveyApiClient.php

```php
<?php

declare(strict_types=1);

namespace SimbiozaSurveyExample;

use InvalidArgumentException;
use RuntimeException;

/** EN: Safe diagnostic fields, without tokens or raw responses.
 * HR: Sigurni dijagnostički podaci, bez tokena ili izvornih odgovora.
 */
final class SurveyApiException extends RuntimeException
{
    public function __construct(
        public readonly int $status,
        public readonly string $requestId,
        public readonly string $errorCode,
    ) {
        parent::__construct('Survey API HTTP ' . $status . ' (' . $errorCode . ')');
    }
}

/** EN: Standalone PHP 8.1+ client; no Simbioza bootstrap or Composer dependency.
 * HR: Samostalni PHP 8.1+ klijent, bez pokretanja Simbioze ili ovisnosti o Composeru.
 */
// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses -- EN: Self-contained example. HR: Samostalan primjer.
final class SurveyApiClient
{
    private string $baseUrl;

    public function __construct(string $baseUrl, private string $token)
    {
        $url = parse_url($baseUrl);
        if (
            !is_array($url) || filter_var($baseUrl, FILTER_VALIDATE_URL) === false
            || ($url['scheme'] ?? '') !== 'https' || empty($url['host'])
            || isset($url['pass']) || isset($url['user']) || isset($url['query'])
            || isset($url['fragment']) || !str_ends_with(rtrim($url['path'] ?? '', '/'), '/api/v1/surveys')
            || $token === '' || preg_match('/\s/', $token)
        ) {
            throw new InvalidArgumentException('Configure a trusted HTTPS Survey API URL and token.');
        }
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /** EN: Read secrets from server configuration, never browser input.
     * HR: Čitaj tajne iz konfiguracije poslužitelja, nikada iz unosa preglednika.
     */
    public static function fromEnvironment(): self
    {
        return new self(
            (string)getenv('SIMBIOZA_SURVEY_API_URL'),
            (string)getenv('SIMBIOZA_SURVEY_API_TOKEN'),
        );
    }

    /** EN: Validate a survey identifier before constructing a route.
     * HR: Provjeri identifikator ankete prije sastavljanja rute.
     */
    public static function id(string $value): string
    {
        if (!preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/Di', $value)) {
            throw new InvalidArgumentException('A survey UUID is required.');
        }
        return $value;
    }

    /** EN: Return the complete data/meta/links envelope; do not retry writes automatically.
     * HR: Vrati cijeli omot data/meta/links; ne ponavljaj upise automatski.
     */
    public function request(string $method, string $path = '', ?array $body = null, array $query = []): array
    {
        [$raw, $type] = $this->send($method, $path, $body, $query, 'application/json');
        if (!str_starts_with(strtolower($type), 'application/json')) {
            throw new RuntimeException('Expected a JSON API response.');
        }
        $result = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($result) || !array_key_exists('data', $result)) {
            throw new RuntimeException('Invalid API response envelope.');
        }
        return $result;
    }

    /** EN: Download only the API-issued token from the same configured installation.
     * HR: Preuzmi samo token koji je izdao API, s iste konfigurirane instalacije.
     */
    public function csv(string $surveyId, string $token): string
    {
        if (!preg_match('/\A[a-f0-9]{64}\z/D', $token)) {
            throw new InvalidArgumentException('Invalid CSV export token.');
        }
        [$raw, $type] = $this->send('GET', '/' . self::id($surveyId) . '/exports/' . $token, null, [], 'text/csv');
        if (!str_starts_with(strtolower($type), 'text/csv')) {
            throw new RuntimeException('Expected a CSV export.');
        }
        return $raw;
    }

    /** EN: Keep TLS verification enabled and never forward credentials through redirects.
     * HR: Zadrži TLS provjeru i nikada ne prosljeđuj vjerodajnice preusmjeravanjem.
     */
    private function send(string $method, string $path, ?array $body, array $query, string $accept): array
    {
        if (!in_array($method, ['GET', 'POST', 'PATCH'], true) || !preg_match('~\A(?:/[a-zA-Z0-9_-]+)*\z~D', $path)) {
            throw new InvalidArgumentException('Invalid API method or relative path.');
        }
        $url = $this->baseUrl . $path
        . ($query === [] ? '' : '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986));
        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException('Cannot initialize the HTTP client.');
        }
        $headers = ['Authorization: Bearer ' . $this->token, 'Accept: ' . $accept];
        $options = [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 60, CURLOPT_CUSTOMREQUEST => $method];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_POSTFIELDS] = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        }
        $options[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($handle, $options);
        $raw = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $type = (string)curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
        unset($handle);
        if (!is_string($raw)) {
            throw new RuntimeException('Survey API connection failed. Check connectivity and TLS.');
        }
        if ($status < 200 || $status >= 300) {
            $problem = json_decode($raw, true);
            $code = is_array($problem) ? ($problem['code'] ?? '') : '';
            $requestId = is_array($problem) ? ($problem['request_id'] ?? '') : '';
            throw new SurveyApiException(
                $status,
                is_string($requestId) && preg_match('/\A[A-Za-z0-9._:-]{8,128}\z/D', $requestId) ? $requestId : '',
                is_string($code) && preg_match('/\A[a-z0-9_]{1,80}\z/D', $code) ? $code : 'http_error',
            );
        }
        return [$raw, $type];
    }
}
```

Mostra e copia SurveyDefinition.php

```php
<?php

declare(strict_types=1);

namespace SimbiozaSurveyExample;

use InvalidArgumentException;

/** EN: Prepare the accompanying HR/EN template with fresh entity identifiers.
 * HR: Pripremi priloženi HR/EN predložak s novim identifikatorima elemenata.
 */
function prepareSurveyDefinition(string $file, string $surveyId, int $version): array
{
    SurveyApiClient::id($surveyId);
    if ($version < 0) {
        throw new InvalidArgumentException('The current version is required.');
    }
    $template = json_decode((string)file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    // EN: The first English text in this template belongs to the survey itself.
    // HR: Prvi engleski tekst ovog predloška pripada samoj anketi.
    $sourceId = array_key_first($template['definition']['texts']['en']);
    $identifiers = [$sourceId => $surveyId];
    $uuid = static function (): string {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 15) | 64);
        $bytes[8] = chr((ord($bytes[8]) & 63) | 128);
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4)
        . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    };
    $remap = static function (mixed $value) use (&$remap, &$identifiers, $uuid): mixed {
        if (is_string($value) && preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/Di', $value)) {
            return $identifiers[$value] ??= $uuid();
        }
        if (!is_array($value)) {
            return $value;
        }
        $result = [];
        foreach ($value as $key => $item) {
            $result[is_string($key) ? $remap($key) : $key] = $remap($item);
        }
        return $result;
    };
    $result = $remap($template);
    $result['version'] = $version;
    return $result;
}
```

Copia `api-feedback-template.json` da [API — modello ed esempi](ankete-api-primjeri_it.md) nella **stessa directory**. L'esempio PHP non necessita dell'helper Python. Il modello utilizza croato e inglese; entrambi devono essere installati e attivi sulla destinazione. Modificare testo, lingue e criteri relativi ai risultati prima dell'uso.

<a id="3-make-the-first-request-from-php"></a>

## 3. Effettua la prima richiesta da PHP

```php
<?php
use SimbiozaSurveyExample\SurveyApiClient;
require_once __DIR__ . '/SurveyApiClient.php';
$api = SurveyApiClient::fromEnvironment();
$response = $api->request('GET', '', null, ['page' => 1, 'per_page' => 10]);
$items = $response['data']['items'];
$total = $response['data']['total'];
$requestId = $response['meta']['request_id'];
```

`request()` restituisce l'intera struttura della risposta: leggi i dati da `data` e l'identificatore della richiesta da `meta.request_id`. Gli elenchi contengono `items`, `total`, `page` e `pages`; non caricare tutti i sondaggi contemporaneamente.

<a id="4-create-your-own-draft"></a>

## 4. Crea la tua bozza

Mostra e copia php-create-survey.php

```php
<?php

declare(strict_types=1);

namespace SimbiozaSurveyExample;

use RuntimeException;

require_once __DIR__ . '/SurveyApiClient.php';
require_once __DIR__ . '/SurveyDefinition.php';

if (PHP_SAPI !== 'cli') {
    throw new RuntimeException('Run this example in the CLI.');
}
$api = SurveyApiClient::fromEnvironment();
$created = $api->request('POST', '', [
    'title' => 'Workshop feedback',
    'primary_language' => 'en',
]);
$id = SurveyApiClient::id($created['data']['uuid']);
// EN: Keep the new ID even if saving the questions fails; do not create another draft on a blind retry.
// HR: Sačuvaj novi ID i ako spremanje pitanja ne uspije; ne kreiraj drugi nacrt naslijepim ponavljanjem.
echo 'Created draft: ', $id, PHP_EOL;
$state = $api->request('GET', '/' . $id)['data'];
$payload = prepareSurveyDefinition(__DIR__ . '/api-feedback-template.json', $id, $state['survey']['lock_version']);
$saved = $api->request('PATCH', '/' . $id, $payload);
echo json_encode([
    'uuid' => $id,
    'version' => $saved['data']['survey']['lock_version'],
    'state' => $saved['data']['survey']['state'],
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), PHP_EOL;
```

Esegui da un terminale:

```sh
php php-create-survey.php
```

Lo script stampa prima `Created draft: …`, quindi un riepilogo di UUID, versione e stato `draft`. Salva il UUID restituito nella tua applicazione. Gli identificatori del modello vengono sostituiti con nuovi valori; PATCH invia la definizione completa e la versione corrente. Se il salvataggio delle domande fallisce, continua con la bozza già creata anziché creare alla cieca un altro sondaggio.

<a id="5-review-and-publish"></a>

## 5. Rivedi e pubblica

Nella tua installazione, apri `/surveys/edit/` più il file UUID restituito. Controlla il contenuto e il flusso di prova. Solo allora esegui questo esempio:

Mostra e copia php-publish-survey.php

```php
<?php

declare(strict_types=1);

namespace SimbiozaSurveyExample;

use RuntimeException;

require_once __DIR__ . '/SurveyApiClient.php';

// EN: Explicit CLI confirmation after the author has checked the saved draft in the browser.
// HR: Izričita potvrda kroz CLI nakon što je sastavljač pregledao spremljeni nacrt u pregledniku.
if (PHP_SAPI !== 'cli' || ($argv[2] ?? '') !== '--reviewed') {
    throw new RuntimeException('Usage: php php-publish-survey.php SURVEY_UUID --reviewed');
}
$id = SurveyApiClient::id($argv[1] ?? '');
$api = SurveyApiClient::fromEnvironment();
foreach (['submit-review', 'publish'] as $action) {
    $state = $api->request('GET', '/' . $id)['data'];
    $api->request('POST', '/' . $id . '/' . $action, [
        'version' => $state['survey']['lock_version'],
        'revision' => $state['revision']['uuid'],
        'confirmed' => true,
    ]);
}
$state = $api->request('GET', '/' . $id)['data'];
echo json_encode(['uuid' => $id, 'state' => $state['survey']['state']], JSON_THROW_ON_ERROR), PHP_EOL;
```

Esegui da un terminale:

```sh
php php-publish-survey.php SURVEY_UUID --reviewed
```

Sostituisci `SURVEY_UUID` con l'identificatore restituito. Ogni transizione rilegge la versione e la revisione corrente. L'esempio inizia con una bozza e un account autorizzato a creare e pubblicare. Se una persona diversa pubblica, invia solo `submit-review` e lascia l'approvazione a quell'editore. Riprendi il completamento parziale in base allo stato corrente anziché rieseguire l'intero script.

**Risultato:** lo stato è `published`. Trova il sondaggio in Sondaggi; i partecipanti rispondono tramite il normale modulo web. La chiave non può inviare risposte per loro conto.

<a id="6-read-results"></a>

## 6. Leggi i risultati

Mostra e copia php-read-results.php

```php
<?php

declare(strict_types=1);

namespace SimbiozaSurveyExample;

use RuntimeException;

require_once __DIR__ . '/SurveyApiClient.php';

if (PHP_SAPI !== 'cli') {
    throw new RuntimeException('Run this example in the CLI.');
}
$id = SurveyApiClient::id($argv[1] ?? '');
$api = SurveyApiClient::fromEnvironment();
$page = 1;
do {
    $envelope = $api->request('GET', '/' . $id . '/results', null, [
        'lang' => 'en', 'page' => $page++, 'per_page' => 10,
    ]);
    $data = $envelope['data'];
    foreach ($data['questions'] as $question) {
        // EN: CLI output; use htmlspecialchars when displaying any text in HTML.
        // HR: CLI ispis; pri prikazu teksta u HTML-u koristi htmlspecialchars.
        echo json_encode($question, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), PHP_EOL;
    }
} while ($page <= $data['pages']);
```

Esegui da un terminale:

```sh
php php-read-results.php SURVEY_UUID
```

I risultati contengono il testo delle domande, serie di risposte e statistiche. L'esempio legge tutte le pagine, non soltanto la prima. Imposta `lang` su una lingua abilitata per il sondaggio. In assenza di invii, i risultati sono vuoti o pari a zero: non è un errore. Per i *singoli invii*, usa il percorso separato e l'autorizzazione di gestione:

```php
$response = $api->request('GET', '/' . SurveyApiClient::id($id) . '/responses', null, [
    'page' => 1, 'per_page' => 10,
]);
$submissions = $response['data']['items'];
$pages = $response['data']['pages'];
```

Aumenta `page` fino a `pages` per le pagine successive. L'autorizzazione al lettore di grafici non consente l'accesso alle singole risposte. Le risposte anonime rimangono senza identità. Controlla le autorizzazioni dei tuoi utenti prima di eseguire il rendering dei risultati nella tua applicazione e codificare il testo per HTML, ad esempio:

```php
foreach ($data['questions'] as $question) {
    echo '<h2>' . htmlspecialchars($question['question'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h2>';
}
```

<a id="7-save-csv"></a>

## 7. Salva CSV

Mostra e copia php-export-results.php

```php
<?php

declare(strict_types=1);

namespace SimbiozaSurveyExample;

use RuntimeException;

require_once __DIR__ . '/SurveyApiClient.php';

if (PHP_SAPI !== 'cli' || ($argv[2] ?? '') === '') {
    throw new RuntimeException('Usage: php php-export-results.php SURVEY_UUID /private/path/results.csv');
}
$id = SurveyApiClient::id($argv[1] ?? '');
$api = SurveyApiClient::fromEnvironment();
$export = $api->request('POST', '/' . $id . '/exports', [
    'mode' => 'aggregate', 'lang' => 'en', 'include_identity' => false,
])['data'];
$csv = $api->csv($id, $export['token']);
// EN: Exclusive creation avoids overwriting a file; store exports outside the web root.
// HR: Isključivo kreiranje sprečava prepisivanje datoteke; izvoze spremaj izvan javnog web direktorija.
$previousMask = umask(0077);
try {
    $file = fopen($argv[2], 'xb');
} finally {
    umask($previousMask);
}
if ($file === false) {
    throw new RuntimeException('Cannot create the private CSV file; it may already exist.');
}
try {
    $offset = 0;
    while ($offset < strlen($csv)) {
        $written = fwrite($file, substr($csv, $offset));
        if ($written === false || $written === 0) {
            throw new RuntimeException('Cannot finish writing the CSV export.');
        }
        $offset += $written;
    }
} finally {
    fclose($file);
}
echo 'CSV saved.', PHP_EOL;
```

Esegui da un terminale:

```sh
php php-export-results.php SURVEY_UUID /private/path/results.csv
```

La cartella di destinazione deve esistere al di fuori della cartella web pubblica. L'esempio crea un nuovo file con autorizzazioni `0600` e non sovrascrive file esistenti. CSV usa UTF-8 con BOM e separatori punto e virgola. I token di esportazione scadono e richiedono lo stesso account autorizzato; non sono collegamenti pubblici. Per esportazioni di produzione più grandi, adatta i limiti e scrivi i dati progressivamente nell'archivio anziché tenere l'intero file in memoria.

<a id="8-handle-errors-and-protect-the-integration"></a>

## 8. Gestire gli errori e proteggere l'integrazione

```php
use SimbiozaSurveyExample\SurveyApiException;

try {
    $response = $api->request('GET', '/' . SurveyApiClient::id($id));
} catch (SurveyApiException $error) {
    error_log('Survey API: HTTP ' . $error->status . '; request=' . $error->requestId);
    // EN: Show a friendly local message; never expose the key or raw response.
    // HR: Prikaži razumljivu lokalnu poruku; nikada ne otkrivaj ključ ni izvorni odgovor.
} catch (RuntimeException $error) {
    // EN: Connection, TLS or response-format failure; no automatic write retry.
    // HR: Greška veze, TLS-a ili formata odgovora; bez automatskog ponavljanja upisa.
}
```

**401/403:** controlla la chiave, gli ambiti e i diritti attuali. **409:** rileggere lo stato e riconciliare la modifica. **422:** correggi l'input o la definizione. Un errore di rete dopo POST non dimostra che la scrittura non è riuscita: controlla prima il risultato; questo API non garantisce tentativi idempotenti.

Richiama la libreria dal livello dei servizi dopo aver verificato accesso e autorizzazioni nella tua applicazione. Esegui le modifiche tramite azioni esplicite dell'utente con protezione CSRF, non al caricamento di una pagina pubblica. Non ricavare mai l'URL o la chiave API dai dati inseriti dall'utente. Il client non segue i reindirizzamenti e non inoltra la chiave Bearer a un'altra destinazione.
