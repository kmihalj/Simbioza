<?php

/**
 * Entry point for all HTTP requests
 */

declare(strict_types=1);

// HR: Samostalni updater uključuje ovu statičnu zaštitu prije autoloadera kako HTTP zahtjev
//     nikada ne bi pokrenuo djelomično ažuriran kod ili vendor direktorij.
// EN: The standalone updater enables this static guard before the autoloader so an HTTP request
//     never executes partially updated application code or a partially updated vendor directory.
$updateMaintenanceFile = dirname(__DIR__) . '/data/update-maintenance.json';
if (is_file($updateMaintenanceFile)) {
    // HR: Statusna putanja radi bez autoloadera i tijekom zamjene koda. Vraća
    //     samo ograničene podatke potrebne već otvorenom progress prikazu.
    // EN: The status path works without the autoloader while code is replaced.
    //     It returns only restricted data required by the already-open progress UI.
    $requestUri = is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '';
    $requestPath = parse_url($requestUri, PHP_URL_PATH);
    if (is_string($requestPath) && str_ends_with(
        rtrim($requestPath, '/'),
        '/settings/setup/application-update-status',
    )) {
        $statusPath = dirname(__DIR__) . '/data/application-update-status.json';
        $statusPayload = is_file($statusPath)
            ? json_decode((string)file_get_contents($statusPath), true)
            : null;
        $statusPayload = is_array($statusPayload) ? $statusPayload : [];
        $state = is_string($statusPayload['state'] ?? null) ? $statusPayload['state'] : 'running';
        $state = in_array($state, ['queued', 'running', 'success', 'failed'], true) ? $state : 'running';
        $stage = is_string($statusPayload['stage'] ?? null) ? $statusPayload['stage'] : 'running';
        $stage = preg_match('/\A[a-z][a-z0-9_-]{0,31}\z/D', $stage) === 1 ? $stage : 'running';
        $progress = is_int($statusPayload['progress'] ?? null)
            ? max(0, min(100, $statusPayload['progress']))
            : null;
        $versionPath = dirname(__DIR__) . '/VERSION';
        $currentVersion = is_file($versionPath) ? trim((string)file_get_contents($versionPath)) : '?';
        $safeStatus = [
            'ok' => true,
            'update' => [
                'state' => $state,
                'stage' => $stage,
                'progress' => $progress,
                'started_at' => is_string($statusPayload['started_at'] ?? null)
                    ? $statusPayload['started_at']
                    : null,
                'finished_at' => null,
                'pid' => null,
                'message' => '',
                'current_version' => preg_match('/\A\d+\.\d+\.\d+\z/D', $currentVersion) === 1
                    ? $currentVersion
                    : '?',
            ],
        ];
        http_response_code(200);
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store, max-age=0');
        header('Retry-After: 1');
        echo json_encode($safeStatus, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        exit;
    }

    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, max-age=0');
    header('Retry-After: 1');
    $scriptName = is_string($_SERVER['SCRIPT_NAME'] ?? null) ? $_SERVER['SCRIPT_NAME'] : '/index.php';
    $basePath = str_replace('\\', '/', dirname($scriptName));
    $basePath = $basePath === '/' || $basePath === '.' ? '' : rtrim($basePath, '/');
    $statusUrl = $basePath . '/settings/setup/application-update-status';
    $encodedStatusUrl = json_encode(
        $statusUrl,
        JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
    );
    $maintenancePage = <<<'HTML'
<!doctype html>
<html lang="hr">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Simbioza se ažurira</title>
<style>
    :root { color-scheme: light dark; }
    * { box-sizing: border-box; }
    body {
        align-items: center;
        background: linear-gradient(135deg, #244b96, #42beb0);
        color: #123f4b;
        display: flex;
        font-family: system-ui, sans-serif;
        justify-content: center;
        margin: 0;
        min-height: 100vh;
        padding: 1rem;
    }
    main {
        background: #fffaf2;
        border: 1px solid #c9d8d7;
        border-radius: 1rem;
        box-shadow: 0 1rem 3rem rgb(0 0 0 / 22%);
        max-width: 46rem;
        padding: clamp(1.5rem, 5vw, 3rem);
        width: 100%;
    }
    h1 { margin: 0 0 .75rem; }
    p { line-height: 1.55; }
    .stage { font-size: 1.15rem; font-weight: 650; margin-top: 1.75rem; }
    .track { background: #d8e4e3; border-radius: 999px; height: 1.25rem; overflow: hidden; }
    .bar {
        animation: stripes 1s linear infinite;
        background: repeating-linear-gradient(135deg, #0d6f75 0 12px, #16868b 12px 24px);
        height: 100%;
        min-width: 8%;
        transition: width .35s ease;
        width: 12%;
    }
    .meta { display: flex; justify-content: space-between; margin-top: .6rem; }
    .error { color: #9f1d2d; font-weight: 650; }
    @keyframes stripes { to { background-position: 34px 0; } }
    @media (prefers-color-scheme: dark) {
        main { background: #092f37; border-color: #37616a; color: #ecf6f5; }
        .track { background: #31545b; }
    }
</style>
<main>
    <h1>Simbioza se ažurira</h1>
    <p>Ovaj prikaz ostaje otvoren dok se izrađuje sigurnosna kopija, ažuriraju moduli i provjeravaju migracije.</p>
    <p lang="en">This screen remains open while the backup, module update, and migration checks run.</p>
    <p class="stage" id="stage" aria-live="polite">Nadogradnja aplikacije je u tijeku.</p>
    <div class="track" role="progressbar" aria-valuemin="0" aria-valuemax="100" id="track">
        <div class="bar" id="bar"></div>
    </div>
    <div class="meta"><strong id="percent">—</strong><strong id="elapsed">00:00</strong></div>
</main>
<script>
(() => {
    'use strict';
    const statusUrl = __STATUS_URL__;
    const stages = {
        queued: 'Nadogradnja čeka sigurno pokretanje.',
        preparing: 'Pripremam nadogradnju i provjeravam okruženje.',
        download: 'Dohvaćam označeno izdanje Simbioze.',
        backup: 'Izrađujem sigurnosnu kopiju aplikacijskog koda.',
        sync: 'Ažuriram aplikacijske datoteke i čuvam privatne postavke.',
        configuration: 'Dopunjujem konfiguraciju postojećih tema.',
        dependencies: 'Ažuriram i provjeravam Composer module.',
        platform: 'Provjeravam PHP i platformske preduvjete.',
        preflight: 'Provjeravam pokretanje aplikacije i pristup bazi.',
        migrations: 'Primjenjujem migracije baze.',
        verification: 'Provjeravam da nema migracija na čekanju.',
        cache: 'Čistim aplikacijsku predmemoriju.',
        rollback: 'Vraćam prethodno izdanje.',
        complete: 'Nadogradnja je uspješno završena.',
        failed: 'Nadogradnja nije uspjela.',
        running: 'Nadogradnja aplikacije je u tijeku.'
    };
    const stage = document.getElementById('stage');
    const track = document.getElementById('track');
    const bar = document.getElementById('bar');
    const percent = document.getElementById('percent');
    const elapsed = document.getElementById('elapsed');
    let startedAt = Date.now();
    const renderElapsed = () => {
        const total = Math.max(0, Math.floor((Date.now() - startedAt) / 1000));
        const minutes = Math.floor(total / 60);
        const seconds = total % 60;
        elapsed.textContent = [minutes, seconds].map((value) => String(value).padStart(2, '0')).join(':');
    };
    const poll = async () => {
        try {
            const response = await fetch(statusUrl, {cache: 'no-store', headers: {Accept: 'application/json'}});
            const payload = await response.json();
            const update = payload.update || {};
            const parsedStart = Date.parse(String(update.started_at || ''));
            if (Number.isFinite(parsedStart)) startedAt = parsedStart;
            const value = Number.isInteger(update.progress) ? Math.max(0, Math.min(100, update.progress)) : null;
            stage.textContent = stages[update.stage] || stages.running;
            bar.style.width = String(value === null ? 12 : value) + '%';
            percent.textContent = value === null ? '—' : String(value) + '%';
            if (value === null) track.removeAttribute('aria-valuenow');
            else track.setAttribute('aria-valuenow', String(value));
            if (update.state === 'success') {
                window.setTimeout(() => window.location.reload(), 1200);
                return;
            }
            if (update.state === 'failed') {
                stage.classList.add('error');
                return;
            }
        } catch (_error) {
            stage.textContent = stages.running;
        }
        window.setTimeout(poll, 750);
    };
    window.setInterval(renderElapsed, 1000);
    renderElapsed();
    poll();
})();
</script>
</html>
HTML;
    echo str_replace('__STATUS_URL__', $encodedStatusUrl, $maintenancePage);
    exit;
}

use App\Installation\InstallationAccessToken;
use App\Installation\InstallationConfigWriter;
use App\Installation\InstallationDatabaseTester;
use App\Installation\InstallationInputValidator;
use App\Installation\InstallationLogger;
use App\Installation\InstallationPaths;
use App\Installation\InstallationRequirements;
use App\Installation\InstallationRunner;
use App\Installation\InstallationWebApplication;
use App\Module\NativeProcessRunner;
use App\Setup\SetupGateway;
use App\Setup\SetupRequestStore;
use HeartPhrame\App;
use HeartPhrame\CodeBook\EnvKeyEnum;

$configuredAppPath = getenv('HPH_APP_PATH');
$hphAppPath = is_string($configuredAppPath) && trim($configuredAppPath) !== ''
? $configuredAppPath
: dirname(__DIR__);

// Autoload
require_once $hphAppPath . implode(DIRECTORY_SEPARATOR, ['', 'vendor', 'autoload.php']);

$installationPaths = new InstallationPaths($hphAppPath);
if (!$installationPaths->isInstalled()) {
    // HR: Installer koristi vlastitu kratku i strogu sesiju prije nego što
    //     aplikacijska konfiguracija i framework uopće postoje.
    // EN: The installer uses its own short, strict session before application
    //     configuration and the framework are available.
    $scriptName = is_string($_SERVER['SCRIPT_NAME'] ?? null) ? $_SERVER['SCRIPT_NAME'] : '/index.php';
    $cookiePath = str_replace('\\', '/', dirname($scriptName));
    $cookiePath = $cookiePath === '/' || $cookiePath === '.' ? '/' : rtrim($cookiePath, '/') . '/';
    $remoteAddress = is_string($_SERVER['REMOTE_ADDR'] ?? null) ? $_SERVER['REMOTE_ADDR'] : '';
    $forwardedProtocol = is_string($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null)
    ? strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'])[0]))
    : '';
    $httpsValue = $_SERVER['HTTPS'] ?? '';
    $serverPortValue = $_SERVER['SERVER_PORT'] ?? '';
    $https = (is_scalar($httpsValue) && strtolower((string)$httpsValue) === 'on')
    || (is_scalar($serverPortValue) && (string)$serverPortValue === '443')
    || (in_array($remoteAddress, ['127.0.0.1', '::1'], true) && $forwardedProtocol === 'https');

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    session_name('SIMBIOZA_INSTALL_SESSION');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookiePath,
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();

    $accessToken = new InstallationAccessToken($installationPaths);
    $configWriter = new InstallationConfigWriter($installationPaths);
    $databaseTester = new InstallationDatabaseTester($configWriter);
    $languageRepository = new \App\Localization\LanguageRepository($hphAppPath);
    $inputValidator = new InstallationInputValidator($languageRepository);
    $requirements = new InstallationRequirements($installationPaths);
    $logger = new InstallationLogger($installationPaths);
    $setupConfiguration = require $hphAppPath . '/config/setup.php';
    $setupRequestDirectory = is_array($setupConfiguration)
        && is_string($setupConfiguration['request_dir'] ?? null)
        ? $setupConfiguration['request_dir']
        : $hphAppPath . '/data/setup-requests';
    $setupHelper = is_array($setupConfiguration) && is_string($setupConfiguration['helper'] ?? null)
        ? $setupConfiguration['helper']
        : '/usr/local/sbin/simbioza-setup';
    $directLocalSetup = getenv('SIMBIOZA_SETUP_DIRECT') === '1'
        || (is_array($setupConfiguration) && ($setupConfiguration['direct_local_testing'] ?? false) === true);
    $setupGateway = new SetupGateway(
        new SetupRequestStore($setupRequestDirectory),
        new NativeProcessRunner(),
        $hphAppPath,
        $setupHelper,
        $directLocalSetup,
    );
    $runner = new InstallationRunner(
        $installationPaths,
        $accessToken,
        $configWriter,
        $databaseTester,
        $inputValidator,
        $requirements,
        $logger,
    );
    $installer = new InstallationWebApplication(
        $installationPaths,
        $accessToken,
        $requirements,
        $databaseTester,
        $inputValidator,
        $runner,
        $logger,
        $setupGateway,
        $languageRepository,
    );
    $method = is_string($_SERVER['REQUEST_METHOD'] ?? null) ? $_SERVER['REQUEST_METHOD'] : 'GET';
    $requestUri = is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '/';
    $installerSession = $_SESSION;
    $response = $installer->handle($method, $requestUri, $scriptName, $_GET, $_POST, $installerSession);
    $_SESSION = $installerSession;

    if (($_SESSION['regenerate_id'] ?? false) === true) {
        unset($_SESSION['regenerate_id']);
        session_regenerate_id(true);
    }

    $destroySession = ($_SESSION['destroy'] ?? false) === true;
    if ($destroySession) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $parameters = session_get_cookie_params();
            $sessionCookieName = session_name();
            if ($sessionCookieName !== false) {
                setcookie($sessionCookieName, '', [
                    'expires' => time() - 42000,
                    'path' => $parameters['path'],
                    'domain' => $parameters['domain'],
                    'secure' => $parameters['secure'],
                    'httponly' => $parameters['httponly'],
                    'samesite' => $parameters['samesite'],
                ]);
            }
        }

        session_destroy();
    } else {
        session_write_close();
    }

    http_response_code($response->status);
    foreach ($response->headers as $name => $value) {
        header($name . ': ' . $value);
    }

    echo $response->body;
    return;
}

$configuredPath = getenv(EnvKeyEnum::HPH_CONFIG_PATH->value);
$configPath = is_string($configuredPath) && trim($configuredPath) !== ''
? $configuredPath
: $hphAppPath . DIRECTORY_SEPARATOR . 'config';

// Create and run the application
(new App($configPath, $hphAppPath))->run();
