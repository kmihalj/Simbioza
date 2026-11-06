# API — PHP examples

[For developers — API](ankete-developeri_en.md)

**Goal:** read and create surveys and retrieve permitted results from your own PHP application. This is a separate PHP example; [command-line curl examples](ankete-api_en.md) remain in their own chapter.

## 1. Prepare PHP and a key

You need **PHP 8.1 or later** and the **cURL extension**. No Composer package or Simbioza framework bootstrap is required. The client uses PHP's `curl_init` and `curl_exec`, not shell commands.

The administrator enables API and issues a key whose owner has the required permissions. Reading needs `surveys:read`; add `surveys:write` for creation, `surveys:publish` for publication, `surveys:results` for results and `surveys:export` for CSV. Scopes do not replace the owner's permissions.

Set two environment variables in your PHP application's server configuration. With PHP-FPM or containers, they must be available to the process running the application; setting them only in a terminal is insufficient.

```text
SIMBIOZA_SURVEY_API_URL=https://simbioza.example/api/v1/surveys
SIMBIOZA_SURVEY_API_TOKEN=YOUR_SCOPED_API_KEY
```

Replace the URL and key with your own values. Include any installation subdirectory, for example `https://simbioza.example/portal/api/v1/surveys`. Keep the key server-side, outside the public web directory and repository. Never send it to JavaScript, the browser or a URL. Keep TLS certificate verification enabled.

## 2. Copy the files

Copy the blocks below into a separate private working directory, using the exact filenames. Your own application needs only `SurveyApiClient.php` as the client; the other files demonstrate individual operations in the CLI.

<div class="table-responsive">

| File | Purpose |
|----|----|
| SurveyApiClient.php | HTTPS client, JSON responses, errors and CSV download. |
| SurveyDefinition.php | Assign fresh UUIDs to questions, choices and translated entities. |
| api-feedback-template.json | Complete example definition from the separate template chapter. |
| php-create-survey.php | Create a draft and save an introduction and three questions. |
| php-publish-survey.php | After your review, submit and publish the draft. |
| php-read-results.php | Read every aggregate-results page. |
| php-export-results.php | Download CSV into a private file. |

</div>

Show and copy SurveyApiClient.php

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

Show and copy SurveyDefinition.php

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

Copy `api-feedback-template.json` from [API — template and examples](ankete-api-primjeri_en.md) into the **same directory**. The PHP example does not need the Python helper. The template uses Croatian and English; both must be installed and active on the target. Adjust text, languages and results policy before use.

## 3. Make the first request from PHP

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

`request()` returns the full envelope: read payloads from `data` and the request identifier from `meta.request_id`. Lists contain `items`, `total`, `page` and `pages`; do not load every survey at once.

## 4. Create your own draft

Show and copy php-create-survey.php

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

Run from a terminal:

```sh
php php-create-survey.php
```

The script first prints `Created draft: …`, then a summary of the UUID, version and `draft` state. Save the returned UUID in your application. Template identifiers are replaced with fresh values; PATCH sends the complete definition and current version. If saving the questions fails, continue with the draft already created rather than blindly creating another survey.

## 5. Review and publish

On your own installation, open `/surveys/edit/` plus the returned UUID. Check the content and trial flow. Only then run this example:

Show and copy php-publish-survey.php

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

Run from a terminal:

```sh
php php-publish-survey.php SURVEY_UUID --reviewed
```

Replace `SURVEY_UUID` with the returned identifier. Each transition rereads the current version and revision. The example starts with a draft and an account allowed to author and publish. If a different person publishes, send only `submit-review` and leave approval to that publisher. Resume partial completion according to the current state rather than rerunning the whole script.

**Result:** the state is `published`. Find the survey in Surveys; participants answer through the regular web form. The key cannot submit answers on their behalf.

## 6. Read results

Show and copy php-read-results.php

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

Run from a terminal:

```sh
php php-read-results.php SURVEY_UUID
```

Results contain question text, answer series and statistics. The example reads all pages, not only the first. Set `lang` to an enabled survey language. With no submissions, results are empty or zero; that is not an error. For *individual submissions*, use the separate route and management permission:

```php
$response = $api->request('GET', '/' . SurveyApiClient::id($id) . '/responses', null, [
    'page' => 1, 'per_page' => 10,
]);
$submissions = $response['data']['items'];
$pages = $response['data']['pages'];
```

Increase `page` up to `pages` for subsequent pages. Chart-reader permission does not allow access to individual answers. Anonymous answers remain without identity. Check your own users' permissions before rendering results in your application and encode text for HTML, for example:

```php
foreach ($data['questions'] as $question) {
    echo '<h2>' . htmlspecialchars($question['question'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h2>';
}
```

## 7. Save CSV

Show and copy php-export-results.php

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

Run from a terminal:

```sh
php php-export-results.php SURVEY_UUID /private/path/results.csv
```

The destination directory must exist outside the public web directory. The example creates a new file with `0600` permissions and will not overwrite an existing file. CSV uses UTF-8 with a BOM and semicolon separators. Export tokens expire and require the same authorized account; they are not public links. For larger production exports, adapt limits and stream to storage rather than keeping the entire file in memory.

## 8. Handle errors and protect the integration

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

**401/403:** check the key, scopes and current rights. **409:** reread state and reconcile the change. **422:** correct the input or definition. A network failure after POST does not prove the write failed — check the outcome first; this API does not guarantee idempotent retries.

Call the library from your service layer after checking sign-in and authorization in your own application. Trigger changes through explicit user actions with CSRF protection, not by loading a public page. Never take the API URL or key from user input. The client does not follow redirects or forward the Bearer key to another destination.
