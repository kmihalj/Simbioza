# API – PHP-Beispiele

<a id="api--php-examples"></a>


> Die Bildschirmaufnahmen zeigen die englische Benutzeroberfläche.

[Für Entwickler – API](ankete-developeri_de.md)

**Ziel:** Umfragen lesen und erstellen und zulässige Ergebnisse aus Ihrer eigenen PHP-Anwendung abrufen. Dies ist ein separates PHP-Beispiel; [Befehlszeilen-Curl-Beispiele](ankete-api_de.md) bleiben in einem eigenen Kapitel.

<a id="1-prepare-php-and-a-key"></a>

## 1. Bereiten Sie PHP und einen Schlüssel vor

Sie benötigen **PHP 8.1 oder höher** und die **cURL-Erweiterung**. Es ist kein Composer-Paket oder Simbioza-Framework-Bootstrap erforderlich. Der Client verwendet `curl_init` und `curl_exec` von PHP, keine Shell-Befehle.

Der Administrator aktiviert API und stellt einen Schlüssel aus, dessen Besitzer über die erforderlichen Berechtigungen verfügt. Lesebedarf `surveys:read`; Fügen Sie `surveys:write` für die Erstellung, `surveys:publish` für die Veröffentlichung, `surveys:results` für Ergebnisse und `surveys:export` für CSV hinzu. Scopes ersetzen nicht die Berechtigungen des Eigentümers.

Legen Sie zwei Umgebungsvariablen in der Serverkonfiguration Ihrer PHP-Anwendung fest. Bei PHP-FPM oder Containern müssen diese für den Prozess verfügbar sein, der die Anwendung ausführt. Es reicht nicht aus, sie nur in einem Terminal festzulegen.

```text
SIMBIOZA_SURVEY_API_URL=https://simbioza.example/api/v1/surveys
SIMBIOZA_SURVEY_API_TOKEN=YOUR_SCOPED_API_KEY
```

Ersetzen Sie die URL und den Schlüssel durch Ihre eigenen Werte. Schließen Sie ein beliebiges Installationsunterverzeichnis ein, zum Beispiel `https://simbioza.example/portal/api/v1/surveys`. Bewahren Sie den Schlüssel serverseitig außerhalb des öffentlichen Webverzeichnisses und Repositorys auf. Senden Sie es niemals an JavaScript, den Browser oder eine URL. Lassen Sie die Zertifikatsüberprüfung TLS aktiviert.

<a id="2-copy-the-files"></a>

## 2. Kopieren Sie die Dateien

Kopieren Sie die folgenden Blöcke in ein separates privates Arbeitsverzeichnis und verwenden Sie dabei die genauen Dateinamen. Ihre eigene Anwendung benötigt lediglich `SurveyApiClient.php` als Client; die anderen Dateien demonstrieren einzelne Vorgänge im CLI.

<div class="table-responsive">

| Datei | Zweck |
|----|----|
| SurveyApiClient.php | HTTPS Client, JSON Antworten, Fehler und CSV Download. |
| SurveyDefinition.php | Weisen Sie Fragen, Auswahlmöglichkeiten und übersetzten Entitäten neue UUIDs zu. |
| api-feedback-template.json | Vollständige Beispieldefinition aus dem separaten Vorlagenkapitel. |
| php-create-survey.php | Erstellen Sie einen Entwurf und speichern Sie eine Einleitung und drei Fragen. |
| php-publish-survey.php | Nach Ihrer Prüfung reichen Sie den Entwurf ein und veröffentlichen ihn. |
| php-read-results.php | Lesen Sie jede Seite mit aggregierten Ergebnissen. |
| php-export-results.php | Laden Sie CSV in eine private Datei herunter. |

</div>

SurveyApiClient.php anzeigen und kopieren

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

SurveyDefinition.php anzeigen und kopieren

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

Kopieren Sie `api-feedback-template.json` aus [API – Vorlage und Beispiele](ankete-api-primjeri_de.md) in das **gleiche Verzeichnis**. Das Beispiel PHP benötigt den Python-Helfer nicht. Die Vorlage verwendet Kroatisch und Englisch; beide müssen auf dem Ziel installiert und aktiv sein. Passen Sie vor der Verwendung Text, Sprachen und Ergebnisrichtlinien an.

<a id="3-make-the-first-request-from-php"></a>

## 3. Stellen Sie die erste Anfrage von PHP

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

`request()` gibt den vollständigen Umschlag zurück: Nutzlasten von `data` und die Anforderungskennung von `meta.request_id` lesen. Listen enthalten `items`, `total`, `page` und `pages`; Laden Sie nicht jede Umfrage auf einmal.

<a id="4-create-your-own-draft"></a>

## 4. Erstellen Sie Ihren eigenen Entwurf

php-create-survey.php anzeigen und kopieren

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

Von einem Terminal aus ausführen:

```sh
php php-create-survey.php
```

Das Skript gibt zuerst `Created draft: …` aus, dann eine Zusammenfassung des UUID, der Version und des `draft`-Status. Speichern Sie den zurückgegebenen UUID in Ihrer Bewerbung. Vorlagenbezeichner werden durch neue Werte ersetzt; PATCH sendet die vollständige Definition und die aktuelle Version. Wenn das Speichern der Fragen fehlschlägt, fahren Sie mit dem bereits erstellten Entwurf fort, anstatt blind eine weitere Umfrage zu erstellen.

<a id="5-review-and-publish"></a>

## 5. Überprüfen und veröffentlichen

Öffnen Sie in Ihrer eigenen Installation `/surveys/edit/` und das zurückgegebene UUID. Überprüfen Sie den Inhalt und den Testablauf. Führen Sie dann erst dieses Beispiel aus:

php-publish-survey.php anzeigen und kopieren

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

Von einem Terminal aus ausführen:

```sh
php php-publish-survey.php SURVEY_UUID --reviewed
```

Ersetzen Sie `SURVEY_UUID` durch die zurückgegebene Kennung. Bei jedem Übergang wird die aktuelle Version und Revision erneut gelesen. Das Beispiel beginnt mit einem Entwurf und einem Konto, das zum Verfassen und Veröffentlichen berechtigt ist. Wenn eine andere Person veröffentlicht, senden Sie nur `submit-review` und überlassen Sie die Genehmigung diesem Herausgeber. Setzen Sie die teilweise Fertigstellung gemäß dem aktuellen Status fort, anstatt das gesamte Skript erneut auszuführen.

**Ergebnis:** Der Status ist `published`. Finden Sie die Umfrage unter Umfragen; die Teilnehmer antworten über das reguläre Webformular. Der Schlüssel kann in ihrem Namen keine Antworten einreichen.

<a id="6-read-results"></a>

## 6. Ergebnisse lesen

php-read-results.php anzeigen und kopieren

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

Von einem Terminal aus ausführen:

```sh
php php-read-results.php SURVEY_UUID
```

Die Ergebnisse enthalten Fragetexte, Antwortreihen und Statistiken. Das Beispiel liest alle Seiten, nicht nur die erste. Legen Sie `lang` auf eine aktivierte Umfragesprache fest. Ohne Übermittlungen sind die Ergebnisse leer oder null; das ist kein Fehler. Für *einzelne Einreichungen* verwenden Sie die separate Route und Verwaltungsberechtigung:

```php
$response = $api->request('GET', '/' . SurveyApiClient::id($id) . '/responses', null, [
    'page' => 1, 'per_page' => 10,
]);
$submissions = $response['data']['items'];
$pages = $response['data']['pages'];
```

Erhöhen Sie `page` bis zu `pages` für nachfolgende Seiten. Die Berechtigung zum Lesen von Diagrammen ermöglicht keinen Zugriff auf einzelne Antworten. Anonyme Antworten bleiben ohne Identität. Überprüfen Sie die Berechtigungen Ihrer eigenen Benutzer, bevor Sie Ergebnisse in Ihrer Anwendung rendern und Text für HTML codieren, zum Beispiel:

```php
foreach ($data['questions'] as $question) {
    echo '<h2>' . htmlspecialchars($question['question'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h2>';
}
```

<a id="7-save-csv"></a>

## 7. Speichern Sie CSV

php-export-results.php anzeigen und kopieren

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

Von einem Terminal aus ausführen:

```sh
php php-export-results.php SURVEY_UUID /private/path/results.csv
```

Das Zielverzeichnis muss außerhalb des öffentlichen Webverzeichnisses vorhanden sein. Das Beispiel erstellt eine neue Datei mit `0600`-Berechtigungen und überschreibt keine vorhandene Datei. CSV verwendet UTF-8 mit einer Stückliste und Semikolon-Trennzeichen. Exporttokens laufen ab und erfordern dasselbe autorisierte Konto; es handelt sich nicht um öffentliche Links. Passen Sie bei größeren Produktionsexporten die Grenzwerte an und streamen Sie sie in den Speicher, anstatt die gesamte Datei im Speicher zu belassen.

<a id="8-handle-errors-and-protect-the-integration"></a>

## 8. Behandeln Sie Fehler und schützen Sie die Integration

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

**401/403:** Überprüfen Sie den Schlüssel, die Scopes und die aktuellen Rechte. **409:** Status erneut lesen und Änderung abgleichen. **422:** Korrigieren Sie die Eingabe oder Definition. Ein Netzwerkfehler nach POST beweist nicht, dass der Schreibvorgang fehlgeschlagen ist – überprüfen Sie zuerst das Ergebnis; Diese API garantiert keine idempotenten Wiederholungsversuche.

Rufen Sie die Bibliothek von Ihrer Serviceschicht aus auf, nachdem Sie die Anmeldung und Autorisierung in Ihrer eigenen Anwendung überprüft haben. Lösen Sie Änderungen durch explizite Benutzeraktionen mit CSRF-Schutz aus, nicht durch das Laden einer öffentlichen Seite. Entnehmen Sie niemals die URL oder den Schlüssel API einer Benutzereingabe. Der Client folgt keinen Weiterleitungen und leitet den Bearer-Schlüssel nicht an ein anderes Ziel weiter.
