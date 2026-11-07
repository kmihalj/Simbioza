# API: ejemplos en PHP

<a id="api--php-examples"></a>


> Las capturas de pantalla muestran la interfaz en inglés.

[Para desarrolladores: API](ankete-developeri_es.md)

**Objetivo:** leer y crear encuestas y recuperar resultados permitidos desde su propia aplicación PHP. Este es un ejemplo PHP separado; [ejemplos de curl de línea de comando](ankete-api_es.md) permanecen en su propio capítulo.

<a id="1-prepare-php-and-a-key"></a>

## 1. Prepare PHP y una clave

Necesita **PHP 8.1 o posterior** y la **extensión cURL**. No se requiere ningún paquete Composer ni programa de arranque del marco Simbioza. El cliente utiliza `curl_init` y `curl_exec` de PHP, no comandos de shell.

El administrador habilita API y emite una clave cuyo propietario tiene los permisos necesarios. Necesidades de lectura `surveys:read`; agregue `surveys:write` para creación, `surveys:publish` para publicación, `surveys:results` para resultados y `surveys:export` para CSV. Los ámbitos no reemplazan los permisos del propietario.

Establezca dos variables de entorno en la configuración del servidor de su aplicación PHP. Con PHP-FPM o contenedores, deben estar disponibles para el proceso que ejecuta la aplicación; configurarlos sólo en un terminal es insuficiente.

```text
SIMBIOZA_SURVEY_API_URL=https://simbioza.example/api/v1/surveys
SIMBIOZA_SURVEY_API_TOKEN=YOUR_SCOPED_API_KEY
```

Reemplace la URL y la clave con sus propios valores. Incluya cualquier subdirectorio de instalación, por ejemplo `https://simbioza.example/portal/api/v1/surveys`. Mantenga la clave del lado del servidor, fuera del directorio web público y del repositorio. Nunca lo envíes a JavaScript, al navegador o a una URL. Mantenga habilitada la verificación del certificado TLS.

<a id="2-copy-the-files"></a>

## 2. Copia los archivos

Copie los bloques siguientes en un directorio de trabajo privado independiente, utilizando los nombres de archivo exactos. Su propia aplicación sólo necesita `SurveyApiClient.php` como cliente; los otros archivos demuestran operaciones individuales en CLI.

<div class="table-responsive">

| Archivo | Finalidad |
|----|----|
| SurveyApiClient.php | HTTPS cliente, JSON respuestas, errores y CSV descarga. |
| SurveyDefinition.php | Asigne UUID nuevos a preguntas, opciones y entidades traducidas. |
| api-feedback-template.json | Definición de ejemplo completa del capítulo de plantilla independiente. |
| php-create-survey.php | Crea un borrador y guarda una introducción y tres preguntas. |
| php-publish-survey.php | Después de su revisión, envíe y publique el borrador. |
| php-read-results.php | Lea cada página de resultados agregados. |
| php-export-results.php | Descargue CSV en un archivo privado. |

</div>

Mostrar y copiar SurveyApiClient.php

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

Mostrar y copiar SurveyDefinition.php

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

Copie `api-feedback-template.json` de [API — plantilla y ejemplos](ankete-api-primjeri_es.md) en el **mismo directorio**. El ejemplo PHP no necesita el asistente de Python. La plantilla utiliza croata e inglés; ambos deben estar instalados y activos en el destino. Ajuste la política de texto, idiomas y resultados antes de su uso.

<a id="3-make-the-first-request-from-php"></a>

## 3. Realice la primera solicitud desde PHP

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

`request()` devuelve el sobre completo: lee las cargas útiles de `data` y el identificador de solicitud de `meta.request_id`. Las listas contienen `items`, `total`, `page` y `pages`; no cargue todas las encuestas a la vez.

<a id="4-create-your-own-draft"></a>

## 4. Crea su propio borrador

Mostrar y copiar php-create-survey.php

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

Ejecutar desde una terminal:

```sh
php php-create-survey.php
```

El script primero imprime `Created draft: …`, luego un resumen de UUID, la versión y el estado de `draft`. Guarde el UUID devuelto en su solicitud. Los identificadores de plantilla se reemplazan con valores nuevos; PATCH envíe la definición completa y la versión actual. Si no se pueden guardar las preguntas, continúe con el borrador ya creado en lugar de crear otra encuesta a ciegas.

<a id="5-review-and-publish"></a>

## 5. Revisar y publicar

En su propia instalación, abra `/surveys/edit/` más el UUID devuelto. Consulte el contenido y el flujo de prueba. Sólo entonces ejecute este ejemplo:

Mostrar y copiar php-publish-survey.php

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

Ejecutar desde una terminal:

```sh
php php-publish-survey.php SURVEY_UUID --reviewed
```

Reemplace `SURVEY_UUID` con el identificador devuelto. Cada transición relee la versión y revisión actuales. El ejemplo comienza con un borrador y una cuenta autorizada para crear y publicar. Si publica una persona diferente, envíe solo `submit-review` y deje la aprobación a ese editor. Reanude la finalización parcial según el estado actual en lugar de volver a ejecutar todo el script.

**Resultado:** el estado es `published`. Encuentre la encuesta en Encuestas; los participantes responden a través del formulario web habitual. La clave no puede enviar respuestas en su nombre.

<a id="6-read-results"></a>

## 6. Leer resultados

Mostrar y copiar php-read-results.php

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

Ejecutar desde una terminal:

```sh
php php-read-results.php SURVEY_UUID
```

Los resultados contienen texto de preguntas, series de respuestas y estadísticas. El ejemplo lee todas las páginas, no sólo la primera. Establezca `lang` en un idioma de encuesta habilitado. Sin envíos, los resultados están vacíos o son cero; eso no es un error. Para *envíos individuales*, utilice la ruta separada y el permiso de administración:

```php
$response = $api->request('GET', '/' . SurveyApiClient::id($id) . '/responses', null, [
    'page' => 1, 'per_page' => 10,
]);
$submissions = $response['data']['items'];
$pages = $response['data']['pages'];
```

Aumente `page` hasta `pages` para las páginas siguientes. El permiso del lector de gráficos no permite el acceso a respuestas individuales. Las respuestas anónimas permanecen sin identidad. Verifique los permisos de sus propios usuarios antes de generar resultados en su aplicación y codificar texto para HTML, por ejemplo:

```php
foreach ($data['questions'] as $question) {
    echo '<h2>' . htmlspecialchars($question['question'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</h2>';
}
```

<a id="7-save-csv"></a>

## 7. Guarde CSV

Mostrar y copiar php-export-results.php

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

Ejecutar desde una terminal:

```sh
php php-export-results.php SURVEY_UUID /private/path/results.csv
```

El directorio de destino debe existir fuera del directorio web público. El ejemplo crea un nuevo archivo con permisos `0600` y no sobrescribirá un archivo existente. CSV usa UTF-8 con una lista de materiales y separadores de punto y coma. Los tokens de exportación caducan y requieren la misma cuenta autorizada; No son enlaces públicos. Para exportaciones de producción más grandes, adapte los límites y transmita al almacenamiento en lugar de mantener el archivo completo en la memoria.

<a id="8-handle-errors-and-protect-the-integration"></a>

## 8. Manejar errores y proteger la integración.

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

**401/403:** verifique la clave, alcances y derechos actuales. **409:** vuelva a leer el estado y concilie el cambio. **422:** corrija la entrada o definición. Una falla de red después de POST no prueba que la escritura haya fallado; verifique primero el resultado; este API no garantiza reintentos idempotentes.

Llame a la biblioteca desde su capa de servicio después de verificar el inicio de sesión y la autorización en su propia aplicación. Desencadene cambios mediante acciones explícitas del usuario con protección CSRF, no cargando una página pública. Nunca tome la URL o clave de API de la entrada del usuario. El cliente no sigue redirecciones ni reenvía la clave Bearer a otro destino.
