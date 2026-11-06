# API — PHP primjeri

[Za developere — API](ankete-developeri_hr.md)

**Cilj:** iz svoje PHP aplikacije čitati i izrađivati ankete te dohvaćati dopuštene rezultate. Ovo je zaseban PHP primjer; [primjeri s naredbom curl](ankete-api_hr.md) ostaju u drugom poglavlju.

## 1. Pripremite PHP i ključ

Potrebni su **PHP 8.1 ili noviji** i proširenje **cURL**. Composer i pokretanje Simbiozina frameworka nisu potrebni. Klijent koristi PHP funkcije `curl_init` i `curl_exec`, a ne izvršavanje naredbi na poslužitelju.

Administrator uključuje API i izdaje ključ čiji vlasnik ima potrebna prava. Za čitanje je dovoljan `surveys:read`; za izradu dodajte `surveys:write`, za objavu `surveys:publish`, za rezultate `surveys:results`, a za CSV `surveys:export`. Opseg ključa ne zamjenjuje ovlasti njegova vlasnika.

U konfiguraciji svoje PHP aplikacije postavite dvije varijable okruženja. Kod PHP-FPM-a ili kontejnera moraju biti dostupne upravo procesu koji izvršava aplikaciju; postavka samo u terminalu nije dovoljna.

```text
SIMBIOZA_SURVEY_API_URL=https://simbioza.example/api/v1/surveys
SIMBIOZA_SURVEY_API_TOKEN=YOUR_SCOPED_API_KEY
```

Zamijenite adresu i ključ vlastitim vrijednostima. Ako je aplikacija u poddirektoriju, uključite i njega, primjerice `https://simbioza.example/portal/api/v1/surveys`. Ključ čuvajte na poslužitelju, izvan javnog direktorija i repozitorija. Ne šaljite ga JavaScriptu, pregledniku ni u URL-u. Zadržite TLS provjeru certifikata.

## 2. Kopirajte datoteke

U zasebnu privatnu radnu mapu kopirajte sadržaj sljedećih blokova. Nazivi datoteka moraju odgovarati prikazanima. Za klijent u vlastitoj aplikaciji potrebna je samo `SurveyApiClient.php`; ostale datoteke pokazuju pojedine radnje kroz CLI.

<div class="table-responsive">

| Datoteka | Za što služi |
|----|----|
| SurveyApiClient.php | HTTPS klijent, JSON odgovori, greške i CSV preuzimanje. |
| SurveyDefinition.php | Novim pitanjima, odgovorima i prijevodima dodjeljuje nove UUID-ove. |
| api-feedback-template.json | Potpuna ogledna definicija iz zasebnog poglavlja s predloškom. |
| php-create-survey.php | Kreira nacrt i sprema uvod te tri pitanja. |
| php-publish-survey.php | Nakon vaše provjere šalje na pregled i objavljuje. |
| php-read-results.php | Čita sve stranice zbirnih rezultata. |
| php-export-results.php | Preuzima CSV u privatnu datoteku. |

</div>

Prikaži i kopiraj SurveyApiClient.php

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

Prikaži i kopiraj SurveyDefinition.php

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

Datoteku `api-feedback-template.json` kopirajte iz [API — predložak i primjeri](ankete-api-primjeri_hr.md) u **istu mapu**. PHP primjer ne treba Python skriptu. Predložak koristi hrvatski i engleski; oba jezika moraju biti instalirana i aktivna na cilju. Prilagodite tekstove, jezike i pravila rezultata prije uporabe.

## 3. Prvi zahtjev iz PHP aplikacije

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

Metoda `request()` vraća cijeli omot: podatke čitate iz `data`, a identifikator zahtjeva iz `meta.request_id`. Popis sadrži `items`, `total`, `page` i `pages`; ne učitavajte sve ankete odjednom.

## 4. Kreirajte vlastiti nacrt

Prikaži i kopiraj php-create-survey.php

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

Pokrenite u terminalu:

```sh
php php-create-survey.php
```

Skripta prvo ispisuje `Created draft: …`, zatim sažetak UUID-a, verzije i stanja `draft`. Sačuvajte dobiveni UUID u svojoj aplikaciji. U predlošku se oznake zamjenjuju novima, a PATCH šalje cijelu definiciju s aktualnom verzijom. Ako spremanje pitanja ne uspije, nastavite s već kreiranim nacrtom; ne stvarajte novu anketu naslijepim ponavljanjem.

## 5. Pregledajte pa objavite

Na svojoj instalaciji otvorite `/surveys/edit/` + dobiveni UUID. Provjerite tekstove i probno ispunjavanje. Tek potom izvršite sljedeći primjer:

Prikaži i kopiraj php-publish-survey.php

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

Pokrenite u terminalu:

```sh
php php-publish-survey.php SURVEY_UUID --reviewed
```

Zamijenite `SURVEY_UUID` dobivenom oznakom. Prije svakog prijelaza ponovno se čitaju verzija i revizija. Primjer je za nacrt i račun s pravom sastavljanja i objave. Ako objavljuje druga osoba, pošaljite samo `submit-review`, a odobrenje prepustite ovlaštenom objavljivaču. Djelomično dovršen postupak nastavite prema aktualnom stanju, ne ponavljanjem cijele skripte.

**Rezultat:** stanje je `published`. Anketu pronađite u popisu Ankete; ispitanici je ispunjavaju redovnim web obrascem. Ključ ne služi predaji odgovora umjesto ispitanika.

## 6. Pročitajte rezultate

Prikaži i kopiraj php-read-results.php

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

Pokrenite u terminalu:

```sh
php php-read-results.php SURVEY_UUID
```

Dobivate podatke po pitanjima, uključujući nazive, serije odgovora i statistike. Primjer prolazi sve stranice, a ne samo prvu. `lang` postavite na uključeni jezik ankete. Bez predaja rezultati su prazni ili s nulama; to nije greška. Za *pojedinačne predaje* koristite zasebnu rutu i pravo upravljanja:

```php
$response = $api->request('GET', '/' . SurveyApiClient::id($id) . '/responses', null, [
    'page' => 1, 'per_page' => 10,
]);
$submissions = $response['data']['items'];
$pages = $response['data']['pages'];
```

Za svaku sljedeću stranicu povećajte `page` do `pages`. Pravo gledanja grafova ne daje pravo čitanja pojedinačnih odgovora. Anonimni odgovori ostaju bez identiteta. Pri prikazu u svojoj web aplikaciji provjerite prava svojih korisnika i kodirajte tekst za HTML, primjerice:

```php
foreach ($data['questions'] as $question) {
    echo '<h2>' . htmlspecialchars($question['question'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h2>';
}
```

## 7. Spremite CSV

Prikaži i kopiraj php-export-results.php

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

Pokrenite u terminalu:

```sh
php php-export-results.php SURVEY_UUID /private/path/results.csv
```

Odredišna mapa mora postojati i biti izvan javnog web direktorija. Primjer stvara novu datoteku s pravima `0600`, ne prepisuje postojeću. CSV je UTF-8 s BOM-om, a razdjelnik je točka sa zarezom. Token izvoza vremenski je ograničen i zahtijeva isti ovlašteni račun; nije javna poveznica. Za veće izvoze u produkciji prilagodite ograničenja i koristite postupno spremanje umjesto držanja cijele datoteke u memoriji.

## 8. Obradite greške i zaštitite integraciju

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

**401/403:** provjerite ključ, opseg i aktualna prava. **409:** ponovno pročitajte stanje i uskladite izmjenu. **422:** ispravite podatke ili definiciju. Mrežna greška nakon POST-a ne dokazuje da upis nije uspio — prvo provjerite rezultat; ovaj API ne jamči idempotentno ponavljanje.

Knjižnicu pozivajte iz vlastitog servisnog sloja nakon provjere prijave i prava u svojoj aplikaciji. Promjene pokrećite izričitom korisničkom radnjom uz CSRF zaštitu, ne učitavanjem javne stranice. Ne uzimajte adresu API-ja ni ključ iz korisničkog unosa. Klijent ne slijedi preusmjeravanja i ne šalje Bearer ključ drugom odredištu.
