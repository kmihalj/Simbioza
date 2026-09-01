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
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store, max-age=0');
    header('Retry-After: 60');
    echo '<!doctype html><html lang="hr"><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>Simbioza se ažurira / Simbioza is being updated</title>'
        . '<style>body{font-family:system-ui,sans-serif;max-width:48rem;margin:12vh auto;padding:2rem;'
        . 'color:#123f4b;background:#fffaf2}main{background:#fff;border:1px solid #c9d8d7;'
        . 'border-radius:.75rem;padding:2rem}h1{margin-top:0}</style><main>'
        . '<h1>Simbioza se ažurira</h1><p>Aplikacija će ponovno biti dostupna nakon sigurnog dovršetka ažuriranja.</p>'
        . '<hr><h1 lang="en">Simbioza is being updated</h1>'
        . '<p lang="en">The application will be available again after the update completes safely.</p>'
        . '</main></html>';
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
    $inputValidator = new InstallationInputValidator();
    $requirements = new InstallationRequirements($installationPaths);
    $logger = new InstallationLogger($installationPaths);
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
