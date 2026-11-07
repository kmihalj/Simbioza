# API — exemples PHP

<a id="api--php-examples"></a>


> Les captures d’écran présentent l’interface en anglais.

[Pour les développeurs — API](ankete-developeri_fr.md)

**Objectif :** lisez et créez des sondages et récupérez les résultats autorisés à partir de votre propre application PHP. Il s'agit d'un exemple PHP distinct ; [exemples de curl en ligne de commande](ankete-api_fr.md) restent dans leur propre chapitre.

<a id="1-prepare-php-and-a-key"></a>

## 1. Préparez PHP et une clé

Vous avez besoin de **PHP 8.1 ou version ultérieure** et de l'**extension cURL**. Aucun package Composer ou Simbioza framework bootstrap n’est requis. Le client utilise les commandes `curl_init` et `curl_exec` de PHP, et non les commandes shell.

L'administrateur active API et émet une clé dont le propriétaire dispose des autorisations requises. La lecture nécessite `surveys:read` ; ajoutez `surveys:write` pour la création, `surveys:publish` pour la publication, `surveys:results` pour les résultats et `surveys:export` pour CSV. Les étendues ne remplacent pas les autorisations du propriétaire.

Définissez deux variables d'environnement dans la configuration du serveur de votre application PHP. Avec PHP-FPM ou des conteneurs, ils doivent être disponibles pour le processus exécutant l'application ; les définir uniquement dans un terminal est insuffisant.

```text
SIMBIOZA_SURVEY_API_URL=https://simbioza.example/api/v1/surveys
SIMBIOZA_SURVEY_API_TOKEN=YOUR_SCOPED_API_KEY
```

Remplacez l'URL et la clé par vos propres valeurs. Incluez n'importe quel sous-répertoire d'installation, par exemple `https://simbioza.example/portal/api/v1/surveys`. Conservez la clé côté serveur, en dehors du répertoire et du référentiel Web publics. Ne l'envoyez jamais vers JavaScript, le navigateur ou une URL. Gardez la vérification du certificat TLS activée.

<a id="2-copy-the-files"></a>

## 2. Copiez les fichiers

Copiez les blocs ci-dessous dans un répertoire de travail privé distinct, en utilisant les noms de fichiers exacts. Votre propre application n'a besoin que de `SurveyApiClient.php` comme client ; les autres fichiers démontrent des opérations individuelles dans CLI.

<div class="table-responsive">

| Déposer | But |
|----|----|
| SurveyApiClient.php | Client HTTPS, réponses JSON, erreurs et téléchargement CSV. |
| SurveyDefinition.php | Attribuez de nouveaux UUID aux questions, aux choix et aux entités traduites. |
| api-feedback-template.json | Exemple complet de définition du chapitre séparé sur le modèle. |
| php-create-survey.php | Créez un brouillon et enregistrez une introduction et trois questions. |
| php-publish-survey.php | Après votre examen, soumettez et publiez le brouillon. |
| php-read-results.php | Lisez chaque page de résultats globaux. |
| php-export-results.php | Téléchargez CSV dans un fichier privé. |

</div>

Afficher et copier SurveyApiClient.php

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

Afficher et copier SurveyDefinition.php

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

Copiez `api-feedback-template.json` de [API — modèle et exemples](ankete-api-primjeri_fr.md) dans le **même répertoire**. L'exemple PHP n'a pas besoin de l'assistant Python. Le modèle utilise le croate et l'anglais ; les deux doivent être installés et actifs sur la cible. Ajustez le texte, les langues et la politique de résultats avant utilisation.

<a id="3-make-the-first-request-from-php"></a>

## 3. Faites la première demande auprès de PHP

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

`request()` renvoie l'enveloppe complète : lisez les charges utiles de `data` et l'identifiant de la demande de `meta.request_id`. Les listes contiennent `items`, `total`, `page` et `pages` ; ne chargez pas tous les sondages en même temps.

<a id="4-create-your-own-draft"></a>

## 4. Créez votre propre brouillon

Afficher et copier php-create-survey.php

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

Exécuter depuis un terminal :

```sh
php php-create-survey.php
```

Le script imprime d'abord `Created draft: …`, puis un résumé d’UUID, de la version et de l'état `draft`. Enregistrez l’UUID renvoyé dans votre application. Les identifiants de modèle sont remplacés par de nouvelles valeurs ; PATCH envoie la définition complète et la version actuelle. Si l'enregistrement des questions échoue, continuez avec le brouillon déjà créé plutôt que de créer aveuglément un autre sondage.

<a id="5-review-and-publish"></a>

## 5. Examiner et publier

Sur votre propre installation, ouvrez `/surveys/edit/` plus l’UUID renvoyé. Vérifiez le contenu et le déroulement de l'essai. Ensuite seulement, exécutez cet exemple :

Afficher et copier php-publish-survey.php

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

Exécuter depuis un terminal :

```sh
php php-publish-survey.php SURVEY_UUID --reviewed
```

Remplacez `SURVEY_UUID` par l'identifiant renvoyé. Chaque transition relit la version et la révision actuelles. L'exemple commence par un brouillon et un compte autorisé à créer et à publier. Si une autre personne publie, envoyez uniquement `submit-review` et laissez l'approbation à cet responsable de publication. Reprenez l'achèvement partiel en fonction de l'état actuel plutôt que de réexécuter l'intégralité du script.

**Résultat :** l'état est `published`. Retrouvez le sondage dans Sondages ; les participants répondent via le formulaire Web habituel. La clé ne peut pas soumettre de réponses en son nom.

<a id="6-read-results"></a>

## 6. Lire les résultats

Afficher et copier php-read-results.php

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

Exécuter depuis un terminal :

```sh
php php-read-results.php SURVEY_UUID
```

Les résultats contiennent le texte des questions, les séries de réponses et les statistiques. L'exemple lit toutes les pages, pas seulement la première. Définissez `lang` sur une langue d’interrogation activée. Sans soumission, les résultats sont vides ou nuls ; ce n'est pas une erreur. Pour les *soumissions individuelles*, utilisez la route et l'autorisation de gestion distincts :

```php
$response = $api->request('GET', '/' . SurveyApiClient::id($id) . '/responses', null, [
    'page' => 1, 'per_page' => 10,
]);
$submissions = $response['data']['items'];
$pages = $response['data']['pages'];
```

Augmentez `page` jusqu'à `pages` pour les pages suivantes. L'autorisation du lecteur de graphique ne permet pas d'accéder aux réponses individuelles. Les réponses anonymes restent sans identité. Vérifiez les autorisations de vos propres utilisateurs avant d'afficher les résultats dans votre application et encodez le texte pour HTML, par exemple :

```php
foreach ($data['questions'] as $question) {
    echo '<h2>' . htmlspecialchars($question['question'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h2>';
}
```

<a id="7-save-csv"></a>

## 7. Enregistrez CSV

Afficher et copier php-export-results.php

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

Exécuter depuis un terminal :

```sh
php php-export-results.php SURVEY_UUID /private/path/results.csv
```

Le répertoire de destination doit exister en dehors du répertoire Web public. L'exemple crée un nouveau fichier avec les autorisations `0600` et n'écrasera pas un fichier existant. CSV utilise UTF-8 avec une nomenclature et des séparateurs de point-virgule. Les jetons d'exportation expirent et nécessitent le même compte autorisé ; ce ne sont pas des liens publics. Pour les exportations de production plus importantes, adaptez les limites et diffusez vers le stockage plutôt que de conserver l’intégralité du fichier en mémoire.

<a id="8-handle-errors-and-protect-the-integration"></a>

## 8. Gérer les erreurs et protéger l’intégration

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

**401/403 :** vérifiez la clé, les étendues et les droits actuels. **409 :** relisez l'état et réconciliez la modification. **422 :** corrige l'entrée ou la définition. Une panne de réseau après POST ne prouve pas l'échec de l'écriture — vérifiez d'abord le résultat ; ce API ne garantit pas les tentatives idempotentes.

Appelez la bibliothèque depuis votre couche de service après avoir vérifié la connexion et l'autorisation dans votre propre application. Déclenchez des modifications via des actions utilisateur explicites avec la protection CSRF, et non en chargeant une page publique. Ne prenez jamais l’URL ou la clé API de la saisie de l’utilisateur. Le client ne suit pas les redirections et ne transmet pas la clé Bearer vers une autre destination.
