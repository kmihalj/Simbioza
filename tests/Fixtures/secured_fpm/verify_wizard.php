<?php

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects -- Disposable CLI verifier defines and invokes its own request helpers.

// EN: Complete the real HTTP wizard only on a disposable loopback fixture, with fictional credentials.
// Never reads or changes a real installation and never prints the one-time URL or password.
//
// HR: Prolazi stvarni HTTP čarobnjak samo na privremenom lokalnom testu s izmišljenim računom.
// Ne čita niti mijenja stvarnu instalaciju i ne ispisuje jednokratnu adresu ni lozinku.
if (!is_file('/.dockerenv') || !is_file('/source/app/tests/Fixtures/secured_fpm/prepare.sh')) {
    throw new RuntimeException('Disposable fixture required.');
}

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
$process = proc_open(
    [PHP_BINARY, $root . '/bin/simbioza', 'install:prepare', '--base-url=http://127.0.0.1/simbioza'],
    [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes,
    $root,
);
if (!is_resource($process)) {
    throw new RuntimeException('Cannot prepare the fictional installer.');
}

$output = stream_get_contents($pipes[1]);
$error = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
if (
    proc_close($process) !== 0
    || !preg_match('~http://127\.0\.0\.1/simbioza/install\?token=[a-f0-9]+~', $output, $match)
) {
    throw new RuntimeException('Installer preparation failed: ' . $error);
}

$curl = curl_init();
curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEFILE => '', CURLOPT_TIMEOUT => 900]);

/**
 * EN: Performs a fixture request and requires a successful page response.
 *
 * HR: Izvršava testni zahtjev i traži uspješan odgovor stranice.
 *
 * @param array<string,mixed>|null $post
 */
function fixtureRequest(CurlHandle $curl, string $url, ?array $post = null): string
{
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, $post !== null);
    if ($post !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    $body = curl_exec($curl);
    if (!is_string($body) || curl_getinfo($curl, CURLINFO_RESPONSE_CODE) !== 200) {
        throw new RuntimeException('Fixture HTTP request failed: ' . curl_error($curl));
    }

    return $body;
}

/**
 * EN: Reads only the CSRF token and expected next action from the rendered wizard.
 *
 * HR: Iz renderiranog čarobnjaka čita samo CSRF token i očekivanu sljedeću radnju.
 */
function fixtureCsrf(string $body, string $action): string
{
    $document = new DOMDocument();
    @$document->loadHTML($body);
    $xpath = new DOMXPath($document);
    if ($xpath->query('//button[@name="action" and @value="' . $action . '"]')->length !== 1) {
        throw new RuntimeException('Wizard did not reach expected action: ' . $action);
    }

    $token = $xpath->evaluate('string(//input[@name="csrf"]/@value)');
    if (!is_string($token) || $token === '') {
        throw new RuntimeException('Wizard CSRF field is missing.');
    }

    return $token;
}

$body = fixtureRequest($curl, $match[0] . '&lang=en');
$url = 'http://127.0.0.1/simbioza/install';
$body = fixtureRequest($curl, $url, [
    'csrf' => fixtureCsrf($body, 'continue_requirements'), 'action' => 'continue_requirements',
]);
$body = fixtureRequest($curl, $url, [
    'csrf' => fixtureCsrf($body, 'save_database'), 'action' => 'save_database', 'driver' => 'sqlite',
]);
$body = fixtureRequest($curl, $url, [
    'csrf' => fixtureCsrf($body, 'save_application'), 'action' => 'save_application',
    'name' => 'Secured FPM fixture', 'primary_locale' => 'en', 'supported_locales' => ['en'], 'timezone' => 'UTC',
    'module_selection_present' => '1', 'optional_modules' => (new App\Module\ModuleCatalog())->optionalSlugs(),
    'login' => 'secured-admin', 'display_name' => 'Fictional administrator', 'first_name' => 'Fictional',
    'last_name' => 'Administrator', 'email' => 'secured-admin@example.invalid',
    'password' => 'E9!IsolatedRoundtrip2026', 'password_confirmation' => 'E9!IsolatedRoundtrip2026',
]);
$body = fixtureRequest($curl, $url, ['csrf' => fixtureCsrf($body, 'install'), 'action' => 'install']);
if (!(new App\Installation\InstallationPaths($root))->isInstalled()) {
    throw new RuntimeException('The real HTTP wizard did not lock a completed installation.');
}

echo "PASS real HTTP wizard, fictional administrator, guide import and installation lock.\n";
