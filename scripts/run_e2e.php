<?php

/**
 * HR: Izrađuje izoliranu HeartPhrame aplikaciju sa svim modulima, dodaje
 *     isključivo testne korisnike i API ključ, pokreće pravi HTTP poslužitelj
 *     te izvršava Playwright browser/API scenarije. Privremena aplikacija se
 *     briše čak i nakon neuspješnog testa, osim uz opciju `--keep`.
 *
 * EN: Builds an isolated HeartPhrame application with every module, adds only
 *     test users and an API key, starts a real HTTP server, and executes the
 *     Playwright browser/API scenarios. The temporary application is removed
 *     even after a failed test unless `--keep` is provided.
 *
 * Usage / Uporaba:
 *   php scripts/run_e2e.php
 *   php scripts/run_e2e.php --local
 *   php scripts/run_e2e.php --headed --keep
 */

declare(strict_types=1);

namespace HFClean\Tools;

use AaiEduHr\HeartPhrameModuleApi\Service\ApiScopeRegistry;
use AaiEduHr\HeartPhrameModuleAuth\Service\AuthApiKeyService;
use AaiEduHr\HeartPhrameModuleAuth\Service\AuthSettingsService;
use AaiEduHr\HeartPhrameModuleAuth\Service\AuthUserService;
use HeartPhrame\App;
use RuntimeException;
use Throwable;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/Tools/CleanInstallMatrix.php';

const E2E_ADMIN_LOGIN = 'e2e-admin';
const E2E_ADMIN_PASSWORD = 'E2eAdmin!2026';
const E2E_USER_LOGIN = 'e2e-user';
const E2E_USER_PASSWORD = 'E2eUser!2026';

/**
 * HR: Čita jednostavne E2E CLI zastavice.
 * EN: Reads the simple E2E CLI flags.
 *
 * @return array{local:bool,headed:bool,keep:bool}
 */
function e2eOptions(): array
{
    $options = getopt('', ['local', 'headed', 'keep']);

    return [
        'local' => array_key_exists('local', $options),
        'headed' => array_key_exists('headed', $options),
        'keep' => array_key_exists('keep', $options),
    ];
}

/**
 * HR: Pronalazi slobodan lokalni TCP port bez korištenja slučajnog fiksnog raspona.
 * EN: Finds a free local TCP port without relying on a random fixed range.
 */
function e2eFreePort(): int
{
    $errorCode = 0;
    $errorMessage = '';
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
    if (!is_resource($socket)) {
        throw new RuntimeException('Unable to reserve an E2E port: ' . $errorMessage);
    }

    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    if (!is_string($address) || preg_match('/:(\d+)$/D', $address, $matches) !== 1) {
        throw new RuntimeException('Unable to resolve the reserved E2E port.');
    }

    return (int)$matches[1];
}

/**
 * HR: Potvrđuje da je projekt stvarno unutar kontroliranog privremenog korijena.
 * EN: Confirms that the project is genuinely inside the controlled temporary root.
 */
function assertSafeE2eProject(string $projectDirectory, string $temporaryRoot): string
{
    $realProject = realpath($projectDirectory);
    $realTemporaryRoot = realpath($temporaryRoot);
    if (
        !is_string($realProject)
        || !is_string($realTemporaryRoot)
        || $realProject === $realTemporaryRoot
        || !str_starts_with($realProject . '/', $realTemporaryRoot . '/')
    ) {
        throw new RuntimeException('Refusing to use an unsafe E2E project directory.');
    }

    return $realProject;
}

/**
 * HR: Prilagođava samo privremenu aplikaciju za lokalni HTTP test bez sigurnog
 *     cookieja te unaprijed postavlja nedostupni lokalni SMTP. Time se stvarni
 *     e-mail red čekanja može provjeriti bez vanjskog mrežnog poziva.
 *
 * EN: Adjusts only the temporary application for a local HTTP test without a
 *     secure cookie and preconfigures an unavailable local SMTP endpoint. This
 *     allows the real e-mail queue to be tested without an external network call.
 */
function configureE2eApplication(string $projectDirectory): void
{
    $configPath = $projectDirectory . '/config/app.php';
    $configValue = require $configPath;
    if (!is_array($configValue)) {
        throw new RuntimeException('E2E application configuration is invalid.');
    }

    $config = matrixStringKeyedArray($configValue, 'E2E application configuration');
    $config['name'] = 'HFClean E2E';
    $localization = is_array($config['localization'] ?? null) ? $config['localization'] : [];
    $localization['locale'] = 'en';
    $localization['fallback_locale'] = 'en';
    $localization['detect_browser_locale'] = false;
    $config['localization'] = $localization;

    $session = is_array($config['session'] ?? null) ? $config['session'] : [];
    $sessionOptions = is_array($session['options'] ?? null) ? $session['options'] : [];
    $sessionOptions['cookie_secure'] = 0;
    $sessionOptions['name'] = 'HEARTPHRAME_E2E_SESSION';
    $session['options'] = $sessionOptions;
    $config['session'] = $session;
    writeMatrixPhpConfig($configPath, $config);

    $apiConfigPath = $projectDirectory . '/config/api.php';
    $apiConfigValue = require $apiConfigPath;
    if (!is_array($apiConfigValue)) {
        throw new RuntimeException('E2E API configuration is invalid.');
    }

    $apiConfig = matrixStringKeyedArray($apiConfigValue, 'E2E API configuration');
    $cors = is_array($apiConfig['cors'] ?? null) ? $apiConfig['cors'] : [];
    $cors['enabled'] = true;
    $cors['allowed_origins'] = ['https://client.example'];
    $apiConfig['cors'] = $cors;
    writeMatrixPhpConfig($apiConfigPath, $apiConfig);

    writeMatrixPhpConfig($projectDirectory . '/config/email.php', [
        'enabled' => true,
        'smtp' => [
            'host' => '127.0.0.1',
            'port' => 9,
            'encryption' => 'none',
            'username' => '',
            'password' => '',
            'connect_timeout' => 1,
            'io_timeout' => 1,
            'verify_peer' => false,
            'allow_self_signed' => false,
        ],
        'sender' => [
            'email' => 'e2e@example.invalid',
            'name' => 'HFClean E2E',
        ],
        'application_base_url' => 'http://127.0.0.1',
        'notifications_enabled' => false,
        'worker' => [
            'max_attempts' => 1,
            'retry_delay_seconds' => 1,
        ],
        'menu' => ['auto_register_settings' => true],
    ]);
}

/**
 * HR: U praznu privremenu bazu dodaje administratorskog i običnog korisnika te
 *     vraća njihove jednokratne API tokene bez ispisa u log. Oba ključa dobivaju
 *     cijeli dinamički katalog scopeova kako bi E2E mogao dokazati da scope sam
 *     po sebi nikada ne podiže stvarna prava običnog korisnika.
 *
 * EN: Adds an administrator and a regular user to the empty temporary database
 *     and returns their one-time API tokens without logging them. Both keys
 *     receive the complete dynamic scope catalog so E2E can prove that a scope
 *     alone never elevates the regular user's real permissions.
 *
 * @return array{admin_api_token:string,user_api_token:string}
 */
function seedE2eFixtures(string $projectDirectory): array
{
    putenv('HPH_APP_PATH=' . $projectDirectory);
    putenv('HPH_CONFIG_PATH=' . $projectDirectory . '/config');

    $app = new App($projectDirectory . '/config');
    $container = $app->getContainer();
    $users = $container->get(AuthUserService::class);
    if (!$users instanceof AuthUserService || $users->countUsers() !== 0) {
        throw new RuntimeException('E2E fixture seeding requires an empty Auth user table.');
    }

    $admin = $users->createUserFromSetup(
        E2E_ADMIN_LOGIN,
        'E2E Administrator',
        'E2E',
        'Administrator',
        'e2e-admin@example.invalid',
        [AuthSettingsService::PROVIDER_LOCAL => true],
        true,
        true,
        E2E_ADMIN_PASSWORD,
    );
    $adminId = is_numeric($admin['id'] ?? null) ? (int)$admin['id'] : 0;
    if ($adminId <= 0) {
        throw new RuntimeException('The E2E administrator was not created.');
    }

    $users->changeLocalPasswordByUserId(
        $adminId,
        E2E_ADMIN_PASSWORD,
        null,
        false,
    );
    $user = $users->createLocalUser(
        E2E_USER_LOGIN,
        'E2E User',
        'e2e-user@example.invalid',
        E2E_USER_PASSWORD,
    );
    $userId = is_numeric($user['id'] ?? null) ? (int)$user['id'] : 0;
    if ($userId <= 0) {
        throw new RuntimeException('The E2E regular user was not created.');
    }

    $apiKeys = $container->get(AuthApiKeyService::class);
    if (!$apiKeys instanceof AuthApiKeyService) {
        throw new RuntimeException('The Auth API-key service is unavailable in the all-module installation.');
    }

    $scopeRegistry = $container->get(ApiScopeRegistry::class);
    if (!$scopeRegistry instanceof ApiScopeRegistry) {
        throw new RuntimeException('The API scope registry is unavailable in the all-module installation.');
    }
    $scopes = $scopeRegistry->all();
    if ($scopes === []) {
        throw new RuntimeException('The all-module E2E scope catalog is empty.');
    }

    return [
        'admin_api_token' => $apiKeys->issue(
            $adminId,
            'HFClean E2E administrator',
            'Ephemeral administrator key for the isolated E2E suite.',
            $scopes,
            [],
            null,
            $adminId,
        )->plainTextToken,
        'user_api_token' => $apiKeys->issue(
            $userId,
            'HFClean E2E regular user',
            'Ephemeral regular-user key for authorization boundary tests.',
            $scopes,
            [],
            null,
            $adminId,
        )->plainTextToken,
    ];
}

/**
 * HR: Pokreće PHP razvojni poslužitelj i sprema njegov izlaz u ignorirani build log.
 * EN: Starts PHP's development server and stores its output in an ignored build log.
 *
 * @return resource
 */
function startE2eServer(
    string $sourceRoot,
    string $projectDirectory,
    int $port,
) {
    $logPath = $sourceRoot . '/build/e2e-server.log';
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['file', $logPath, 'a'],
        2 => ['file', $logPath, 'a'],
    ];
    $environment = getenv();
    $environment = is_array($environment) ? array_filter($environment, is_string(...)) : [];
    $environment['HPH_APP_PATH'] = $projectDirectory;
    $environment['HPH_CONFIG_PATH'] = $projectDirectory . '/config';
    $environment['HPH_E2E_PROJECT'] = $projectDirectory;

    $process = proc_open(
        [
            PHP_BINARY,
            '-S',
            '127.0.0.1:' . $port,
            '-t',
            $projectDirectory . '/public',
            $sourceRoot . '/scripts/dev_router.php',
        ],
        $descriptors,
        $pipes,
        $projectDirectory,
        $environment,
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start the E2E HTTP server.');
    }
    fclose($pipes[0]);

    return $process;
}

/**
 * HR: Čeka stvarni HTTP odgovor ili rano prijavljuje pad poslužitelja.
 * EN: Waits for a real HTTP response or reports an early server failure.
 *
 * @param resource $server
 */
function awaitE2eServer($server, string $baseUrl): void
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 0.5,
            'ignore_errors' => true,
        ],
    ]);
    for ($attempt = 0; $attempt < 100; $attempt++) {
        $status = proc_get_status($server);
        if (!$status['running']) {
            throw new RuntimeException('The E2E HTTP server stopped before becoming ready.');
        }

        $response = @file_get_contents($baseUrl . '/', false, $context);
        if (is_string($response) && str_contains($response, '<html')) {
            return;
        }
        usleep(100_000);
    }

    throw new RuntimeException('The E2E HTTP server did not become ready within ten seconds.');
}

/**
 * HR: Zaustavlja samo proces poslužitelja koji je pokrenuo ovaj testni alat.
 * EN: Stops only the server process started by this test tool.
 *
 * @param resource|null $server
 */
function stopE2eServer($server): void
{
    if (!is_resource($server)) {
        return;
    }

    $status = proc_get_status($server);
    if ($status['running']) {
        proc_terminate($server);
        usleep(200_000);
        $status = proc_get_status($server);
        if ($status['running']) {
            proc_terminate($server, 9);
        }
    }
    proc_close($server);
}

/**
 * HR: Izvršava cijeli izolirani E2E tijek i vraća CLI izlazni kod.
 * EN: Executes the complete isolated E2E flow and returns a CLI exit code.
 */
function runEndToEndSuite(): int
{
    $sourceRoot = dirname(__DIR__);
    $temporaryRoot = sys_get_temp_dir() . '/heartphrame-clean-matrix';
    if (!is_dir($temporaryRoot) && !mkdir($temporaryRoot, 0775, true) && !is_dir($temporaryRoot)) {
        throw new RuntimeException('Unable to create the E2E temporary root.');
    }

    $options = e2eOptions();
    $projectDirectory = null;
    $server = null;

    try {
        $rootComposerValue = json_decode(
            (string)file_get_contents($sourceRoot . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        if (!is_array($rootComposerValue)) {
            throw new RuntimeException('Root Composer metadata is not an array.');
        }

        $rootComposer = matrixStringKeyedArray($rootComposerValue, 'Root Composer metadata');
        if ($options['local']) {
            $rootComposer = withLocalMatrixRepositories($rootComposer, dirname($sourceRoot));
        }

        fwrite(STDOUT, "[START] Preparing isolated all-module E2E application.\n");
        $result = verifyMatrixCase(
            'e2e-all',
            MATRIX_CASES['all'],
            $rootComposer,
            $sourceRoot,
            $temporaryRoot,
            'sqlite',
            true,
        );
        $candidate = $result['project_directory'] ?? null;
        if (!is_string($candidate)) {
            throw new RuntimeException('The clean installer did not return an E2E project directory.');
        }
        $projectDirectory = assertSafeE2eProject($candidate, $temporaryRoot);
        if (($result['status'] ?? null) !== 'success') {
            $error = is_string($result['error'] ?? null) ? $result['error'] : 'Unknown clean-install failure.';
            throw new RuntimeException($error);
        }

        configureE2eApplication($projectDirectory);
        $fixtures = seedE2eFixtures($projectDirectory);
        $port = e2eFreePort();
        $baseUrl = 'http://127.0.0.1:' . $port;
        $server = startE2eServer($sourceRoot, $projectDirectory, $port);
        awaitE2eServer($server, $baseUrl);

        $command = [
            'npx',
            '--no-install',
            'playwright',
            'test',
            '--config=tests/E2E/playwright.config.js',
        ];
        if ($options['headed']) {
            $command[] = '--headed';
        }

        fwrite(STDOUT, "[START] Running browser and API E2E scenarios.\n");
        $testResult = runMatrixCommand(
            $command,
            $sourceRoot,
            [
                'HPH_E2E_BASE_URL' => $baseUrl,
                'HPH_E2E_ADMIN_LOGIN' => E2E_ADMIN_LOGIN,
                'HPH_E2E_ADMIN_PASSWORD' => E2E_ADMIN_PASSWORD,
                'HPH_E2E_USER_LOGIN' => E2E_USER_LOGIN,
                'HPH_E2E_USER_PASSWORD' => E2E_USER_PASSWORD,
                'HPH_E2E_API_TOKEN' => $fixtures['admin_api_token'],
                'HPH_E2E_USER_API_TOKEN' => $fixtures['user_api_token'],
            ],
        );
        fwrite(STDOUT, $testResult->output);
        if ($testResult->exitCode !== 0) {
            fwrite(STDERR, "[FAIL] E2E scenarios failed. See build artifacts for details.\n");
            return $testResult->exitCode;
        }

        fwrite(STDOUT, "[SUCCESS] Browser and API E2E scenarios passed.\n");
        return 0;
    } catch (Throwable $throwable) {
        fwrite(STDERR, '[FAIL] ' . $throwable->getMessage() . PHP_EOL);
        return 1;
    } finally {
        stopE2eServer($server);
        if (is_string($projectDirectory)) {
            if ($options['keep']) {
                fwrite(STDOUT, '[KEEP] ' . $projectDirectory . PHP_EOL);
            } else {
                removeMatrixDirectory($projectDirectory, $temporaryRoot);
            }
        }
    }
}

exit(runEndToEndSuite());
