<?php

declare(strict_types=1);

use App\Localization\LanguagePackManager;
use App\Localization\LanguageRepository;
use App\Localization\RepositoryLanguageManager;
use App\Module\ComposerPackageManager;
use App\Module\ModuleCatalog;
use App\Module\NativeProcessRunner;
use App\Setup\SetupRequestStore;

// HR: Worker se smije pokrenuti samo kroz CLI/helper, nikada kao web skripta.
// EN: The worker may run only through CLI/helper, never as a web script.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
$setup = require $root . '/config/setup.php';
$requestDirectory = is_array($setup) && is_string($setup['request_dir'] ?? null)
    ? $setup['request_dir']
    : $root . '/data/setup-requests';
$requests = new SetupRequestStore($requestDirectory);
$id = trim((string)($argv[1] ?? ''));
$resultPath = '';

try {
    $requestPath = $requests->requestPath($id);
    $resultPath = $requests->resultPath($id);
    $request = is_file($requestPath)
        ? json_decode((string)file_get_contents($requestPath), true, 512, JSON_THROW_ON_ERROR)
        : null;
    if (!is_array($request) || ($request['id'] ?? null) !== $id) {
        throw new RuntimeException('Setup request is missing or invalid.');
    }

    $action = is_string($request['action'] ?? null) ? $request['action'] : '';
    $parameters = is_array($request['parameters'] ?? null) ? $request['parameters'] : [];
    $catalog = new ModuleCatalog();
    $runner = new NativeProcessRunner();
    $packages = new ComposerPackageManager($catalog, $runner, $root);
    $message = match ($action) {
        'health-check' => 'Setup helper is ready.',
        'application-update-runtime-check' => applicationUpdateRuntimeCheck(),
        'packages-prepare' => packageOperation($packages, $catalog, $parameters, true),
        'packages-cleanup' => packageOperation($packages, $catalog, $parameters, false),
        'package-install' => singlePackageOperation($packages, $catalog, $parameters, true),
        'package-uninstall' => singlePackageOperation($packages, $catalog, $parameters, false),
        'language-add' => languageOperation($catalog, $root, $requestDirectory, $parameters),
        'language-install', 'language-enable', 'language-disable', 'language-remove' => repositoryLanguageOperation(
            $catalog,
            $root,
            $action,
            $parameters,
        ),
        'application-update-check' => applicationUpdateCheck($runner, $root, $parameters),
        'application-update-start' => applicationUpdateStart($root, $parameters),
        default => throw new RuntimeException('Unsupported Setup worker action.'),
    };
    writeResult($resultPath, true, $message);
} catch (Throwable $throwable) {
    if ($resultPath !== '') {
        writeResult($resultPath, false, $throwable->getMessage());
        exit(0);
    }

    fwrite(STDERR, $throwable->getMessage() . PHP_EOL);
    exit(2);
}

/**
 * HR: Instalira ili uklanja samo katalogizirane opcionalne pakete.
 * EN: Installs or removes only catalogued optional packages.
 *
 * @param array<string,mixed> $parameters
 */
function packageOperation(
    ComposerPackageManager $packages,
    ModuleCatalog $catalog,
    array $parameters,
    bool $install,
): string {
    $slugs = is_array($parameters['modules'] ?? null) ? $parameters['modules'] : [];
    $handled = [];
    foreach ($slugs as $slug) {
        if (!is_string($slug) || !in_array($slug, $catalog->optionalSlugs(), true)) {
            throw new RuntimeException('Setup request contains an unknown optional module.');
        }

        if ($install) {
            $packages->install($slug);
        } else {
            $packages->uninstall($slug);
        }
        $handled[] = $slug;
    }

    return ($install ? 'Prepared packages: ' : 'Removed packages: ')
        . ($handled === [] ? 'none' : implode(', ', $handled));
}

/**
 * HR: Mijenja jedan katalogizirani paket bez pokretanja aplikacijskog
 *     bootstrapa pod ovlastima deploy korisnika.
 * EN: Changes one catalogued package without running the application
 *     bootstrap with deploy-user privileges.
 *
 * @param array<string,mixed> $parameters
 */
function singlePackageOperation(
    ComposerPackageManager $packages,
    ModuleCatalog $catalog,
    array $parameters,
    bool $install,
): string {
    $slug = is_string($parameters['module'] ?? null) ? strtolower(trim($parameters['module'])) : '';
    if (!in_array($slug, $catalog->optionalSlugs(), true)) {
        throw new RuntimeException('Setup request contains an unknown optional module.');
    }

    if ($install) {
        $packages->install($slug);
    } else {
        $packages->uninstall($slug);
    }

    return ($install ? 'Installed package: ' : 'Removed package: ') . $slug;
}

/**
 * HR: Instalira samo prethodno spremljeni JSON paket iz privatnog request direktorija.
 * EN: Installs only a previously stored JSON pack from the private request directory.
 *
 * @param array<string,mixed> $parameters
 */
function languageOperation(
    ModuleCatalog $catalog,
    string $root,
    string $requestDirectory,
    array $parameters,
): string {
    $file = is_string($parameters['file'] ?? null) ? basename($parameters['file']) : '';
    if (preg_match('/\A[a-f0-9]{48}\.language\.json\z/D', $file) !== 1) {
        throw new RuntimeException('Setup request contains an invalid language-pack file.');
    }

    $path = rtrim($requestDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
    if (!is_file($path)) {
        throw new RuntimeException('Uploaded language pack is unavailable.');
    }

    try {
        $languages = new LanguagePackManager($root, $catalog);
        $languages->install($path, !empty($parameters['replace']));
    } finally {
        if (is_file($path)) {
            unlink($path);
        }
    }

    return 'Language installed.';
}

/**
 * HR: Izvršava samo dopuštenu radnju nad jednim provjerenim jezikom.
 * EN: Performs only an allowlisted operation on one validated locale.
 * @param array<string,mixed> $parameters
 */
function repositoryLanguageOperation(ModuleCatalog $catalog, string $root, string $action, array $parameters): string
{
    $locale = $parameters['locale'] ?? null;
    if (!is_string($locale) || preg_match('/\A[a-z0-9]+(?:[-_][a-z0-9]+)*\z/D', $locale) !== 1
        || strlen($locale) > 32) {
        throw new RuntimeException('Setup request contains an invalid locale.');
    }
    $languages = new LanguagePackManager($root, $catalog);
    $manager = new RepositoryLanguageManager(new LanguageRepository($root), $languages, $root);
    return match ($action) {
        'language-install' => (function () use ($manager, $locale, $parameters): string {
            $manager->install($locale, !empty($parameters['replace']));
            return 'Language installed: ' . $locale;
        })(),
        'language-remove' => (function () use ($manager, $locale): string {
            $manager->uninstall($locale);
            return 'Language removed: ' . $locale;
        })(),
        'language-enable', 'language-disable' => (function () use ($languages, $locale, $action): string {
            $languages->setActive($locale, $action === 'language-enable');
            return 'Language ' . ($action === 'language-enable' ? 'enabled: ' : 'disabled: ') . $locale;
        })(),
        default => throw new RuntimeException('Unsupported language operation.'),
    };
}

/**
 * HR: Izvršava samo read-only provjeru postojećeg samostalnog updatera.
 * EN: Runs only the existing standalone updater's read-only check.
 *
 * @param array<string,mixed> $parameters
 */
function applicationUpdateCheck(NativeProcessRunner $runner, string $root, array $parameters): string
{
    $locale = updateLocale($parameters);
    $result = $runner->run([PHP_BINARY, $root . '/update.php', '--check', '--lang=' . $locale], $root);
    if ($result->exitCode !== 0) {
        throw new RuntimeException(trim($result->stderr) !== '' ? trim($result->stderr) : trim($result->stdout));
    }

    return trim($result->stdout) !== '' ? trim($result->stdout) : 'Provjera ažuriranja je dovršena.';
}

/** HR: Provjerava CLI mogućnosti potrebne sigurnom pozadinskom updateru. EN: Checks CLI capabilities required by the safe background updater. */
function applicationUpdateRuntimeCheck(): string
{
    foreach (['pcntl_fork', 'pcntl_exec', 'posix_setsid', 'posix_kill'] as $function) {
        if (!function_exists($function)) {
            throw new RuntimeException('Missing CLI function required by the application updater: ' . $function);
        }
    }

    return 'Background application updater is ready.';
}

/**
 * HR: Pokreće updater u odvojenoj pozadinskoj sesiji i odmah vraća kontrolu
 *     FPM zahtjevu. Argumenti su strogo validirani, a ljuska dobiva samo
 *     programski sastavljene i escapeane vrijednosti.
 * EN: Starts the updater in a detached background session and immediately
 *     returns control to the FPM request. Arguments are strictly validated,
 *     and the shell receives only programmatically built escaped values.
 *
 * @param array<string,mixed> $parameters
 */
function applicationUpdateStart(string $root, array $parameters): string
{
    if (!function_exists('pcntl_fork') || !function_exists('posix_setsid') || !function_exists('pcntl_exec')) {
        throw new RuntimeException('The CLI pcntl and posix extensions are required for background updates.');
    }
    $locale = updateLocale($parameters);
    $tag = is_string($parameters['tag'] ?? null) ? trim($parameters['tag']) : '';
    if ($tag !== '' && preg_match('/\A(?:v)?\d+\.\d+\.\d+\z/D', $tag) !== 1) {
        throw new RuntimeException('Invalid application update tag.');
    }

    $statusPath = $root . '/data/application-update-status.json';
    $current = is_file($statusPath) ? json_decode((string)file_get_contents($statusPath), true) : null;
    $currentPid = is_array($current) && is_int($current['pid'] ?? null) ? $current['pid'] : 0;
    if (
        is_array($current)
        && in_array($current['state'] ?? null, ['queued', 'running'], true)
        && $currentPid > 0
        && processExists($currentPid)
    ) {
        throw new RuntimeException('An application update is already running.');
    }

    $logDirectory = $root . '/data/logs';
    if (!is_dir($logDirectory) && !mkdir($logDirectory, 0770, true) && !is_dir($logDirectory)) {
        throw new RuntimeException('Application log directory could not be created.');
    }
    $logPath = $logDirectory . '/application-update.log';
    $arguments = ['--lang=' . $locale];
    if ($tag !== '') {
        $arguments[] = '--tag=' . $tag;
    }

    $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    if (!is_array($sockets) || count($sockets) !== 2) {
        throw new RuntimeException('Application update synchronization channel could not be created.');
    }
    $pid = pcntl_fork();
    if ($pid < 0) {
        fclose($sockets[0]);
        fclose($sockets[1]);
        throw new RuntimeException('Application update process could not be created.');
    }
    if ($pid === 0) {
        fclose($sockets[0]);
        fread($sockets[1], 1);
        fclose($sockets[1]);
        if (posix_setsid() < 0) {
            exit(127);
        }
        $parts = [PHP_BINARY, $root . '/scripts/application_update_job.php', ...$arguments];
        $command = 'exec ' . implode(' ', array_map(escapeshellarg(...), $parts))
            . ' >> ' . escapeshellarg($logPath) . ' 2>&1 < /dev/null';
        pcntl_exec('/bin/sh', ['-c', $command]);
        exit(127);
    }

    fclose($sockets[1]);
    try {
        writeUpdateLaunchStatus($statusPath, $pid);
    } catch (Throwable $throwable) {
        posix_kill($pid, SIGTERM);
        fclose($sockets[0]);
        throw $throwable;
    }
    fwrite($sockets[0], '1');
    fclose($sockets[0]);

    return 'Application update started in the background.';
}

/** HR: Ograničava jezik updatera. EN: Restricts the updater locale. */
function updateLocale(array $parameters): string
{
    return ($parameters['locale'] ?? null) === 'en' ? 'en' : 'hr';
}

/** HR: Provjerava postoji li još odvojeni proces. EN: Checks whether a detached process still exists. */
function processExists(int $pid): bool
{
    return function_exists('posix_kill') && $pid > 0 && @posix_kill($pid, 0);
}

/** HR: Atomski bilježi da je pozadinski updater predan. EN: Atomically records that the background updater was submitted. */
function writeUpdateLaunchStatus(string $path, int $pid): void
{
    $current = is_file($path) ? json_decode((string)file_get_contents($path), true) : null;
    if (
        is_array($current)
        && ($current['state'] ?? null) === 'running'
        && ($current['pid'] ?? null) === $pid
    ) {
        return;
    }
    $temporary = tempnam(dirname($path), '.application-update-launch-');
    if (!is_string($temporary)) {
        throw new RuntimeException('Application update launch status could not be prepared.');
    }
    try {
        $json = json_encode([
            'state' => 'queued',
            'stage' => 'queued',
            'progress' => 0,
            'started_at' => gmdate(DATE_ATOM),
            'finished_at' => null,
            'pid' => $pid,
            'message' => 'Application update was queued.',
        ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        if (file_put_contents($temporary, $json . "\n", LOCK_EX) === false || !chmod($temporary, 0640)) {
            throw new RuntimeException('Application update launch status could not be written.');
        }
        if (!rename($temporary, $path)) {
            throw new RuntimeException('Application update launch status could not be activated.');
        }
    } finally {
        if (is_file($temporary)) {
            unlink($temporary);
        }
    }
}

/** HR: Atomski zapisuje mali rezultat bez tehničkog tracea. EN: Atomically writes a small result without a technical trace. */
function writeResult(string $path, bool $ok, string $message): void
{
    $temporary = $path . '.tmp-' . bin2hex(random_bytes(6));
    $json = json_encode(
        ['ok' => $ok, 'message' => mb_substr($message, 0, 4000)],
        JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
    );
    if (file_put_contents($temporary, $json . "\n", LOCK_EX) === false || !chmod($temporary, 0640)) {
        throw new RuntimeException('Setup result could not be written.');
    }
    if (!rename($temporary, $path)) {
        @unlink($temporary);
        throw new RuntimeException('Setup result could not be activated.');
    }
}
