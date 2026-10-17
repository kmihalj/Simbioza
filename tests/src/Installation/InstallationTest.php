<?php

declare(strict_types=1);

namespace Tests\Installation;

use AaiEduHr\HeartPhrameModuleAuth\ModuleAuth;
use AaiEduHr\HeartPhrameModuleCalendar\ModuleCalendar;
use AaiEduHr\HeartPhrameModuleComment\ModuleComment;
use AaiEduHr\HeartPhrameModuleEditorHtml\ModuleEditorHtml;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlDocumentFormatter;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleTask\ModuleTask;
use AaiEduHr\SimbiozaModuleWorkspace\ModuleWorkspace;
use App\Configuration\ConfigurationPaths;
use App\Configuration\PhpConfigurationReader;
use App\Installation\InstallationAccessToken;
use App\Installation\InstallationConfigWriter;
use App\Installation\InstallationDatabaseTester;
use App\Installation\InstallationInputValidator;
use App\Installation\InstallationLogger;
use App\Installation\InstallationPaths;
use App\Installation\InstallationPrepareCommand;
use App\Installation\InstallationRequirements;
use App\Installation\InstallationRunner;
use App\Installation\InstallationValidationException;
use App\Installation\InstallationWebApplication;
use App\Module\ModuleCatalog;
use App\Update\BundledAssetsUpdater;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

#[CoversNothing]
final class InstallationTest extends TestCase
{
    /** @var list<string> */
    private array $temporaryDirectories = [];

    /** @var array<string,string|false> */
    private array $environment = [];

    /**
     * EN: Tests never read the active HFC config.
     *
     * HR: Testovi nikada ne čitaju aktivni HFC config.
     */
    protected function setUp(): void
    {
        foreach (['HPH_APP_PATH', 'HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $name) {
            $this->environment[$name] = getenv($name);
            putenv($name);
        }
    }

    /**
     * EN: Removes only test-owned temp directories.
     *
     * HR: Nakon testa uklanja samo njegove jedinstvene temp direktorije.
     */
    protected function tearDown(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            $this->deleteDirectory($directory);
        }

        $this->temporaryDirectories = [];
        foreach ($this->environment as $name => $value) {
            is_string($value) ? putenv($name . '=' . $value) : putenv($name);
        }
    }

    /**
     * EN: Source themes and the installation package must not contain the development web path.
     *
     * HR: Izvorne teme i instalacijski paket ne smiju sadržavati razvojnu web-putanju.
     */
    public function testBundledThemesDoNotHardCodeDevelopmentBasePath(): void
    {
        $projectRoot = dirname(__DIR__, 3);
        $themesJson = file_get_contents($projectRoot . '/config/theme/themes.json');
        $this->assertIsString($themesJson);
        $this->assertStringNotContainsString('/hfc', strtolower($themesJson));

        $archive = new ZipArchive();
        $this->assertTrue($archive->open($projectRoot . '/resources/installation/theme/simbioza.zip'));
        $packagedTheme = $archive->getFromName('theme.json');
        $archive->close();
        $this->assertIsString($packagedTheme);
        $this->assertStringNotContainsString('/hfc', strtolower($packagedTheme));

        $theme = json_decode($packagedTheme, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($theme);
        $hero = $theme['components']['hero'] ?? null;
        $this->assertIsArray($hero);
        $this->assertSame(72, $theme['components']['header']['height_px'] ?? null);
        $this->assertSame(56, $theme['components']['navigation']['height_px'] ?? null);
        $this->assertFalse($hero['visual_allow_overflow'] ?? null);
        $this->assertSame(0, $hero['visual_max_height_px'] ?? null);
        $this->assertSame(560, $hero['visual_width_px'] ?? null);
        $this->assertSame(-48, $hero['visual_top_px'] ?? null);
        $this->assertSame(24, $hero['visual_right_px'] ?? null);
    }

    /**
     * EN: Verifies every supported database type.
     *
     * HR: Provjerava normalizaciju svakog podržanog tipa baze.
     */
    public function testInputValidatorNormalizesSupportedDatabases(): void
    {
        $validator = new InstallationInputValidator();

        $this->assertSame(['driver' => 'sqlite'], $validator->database(['driver' => 'SQLite']));
        $this->assertSame([
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'simbioza_test',
            'username' => 'simbioza',
            'password' => 'private',
        ], $validator->database([
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'simbioza_test',
            'username' => 'simbioza',
            'password' => 'private',
        ]));
        $this->assertSame('pgsql', $validator->database([
            'driver' => 'pgsql',
            'host' => 'localhost',
            'port' => 5432,
            'database' => 'simbioza_test',
            'username' => 'simbioza',
            'password' => '',
        ])['driver']);
    }

    /**
     * EN: New forms select all optional modules while an explicit empty selection remains empty.
     *
     * HR: Novi obrazac uključuje sve opcionalne module, ali izričit prazan odabir ostaje prazan.
     */
    public function testOptionalModulesDefaultToAllAndCanAllBeDeselected(): void
    {
        $validator = new InstallationInputValidator();
        $input = [
            'name' => 'Simbioza',
            'primary_locale' => 'hr',
            'supported_locales' => ['hr', 'en'],
            'timezone' => 'Europe/Zagreb',
        ];

        $this->assertSame((new ModuleCatalog())->optionalSlugs(), $validator->application($input)['optional_modules']);
        $this->assertSame([], $validator->application([
            ...$input,
            'module_selection_present' => '1',
        ])['optional_modules']);
    }

    /**
     * EN: Rejects weak administrator passwords.
     *
     * HR: Odbija slabe administratorske lozinke.
     */
    public function testInputValidatorRejectsWeakAdministratorPassword(): void
    {
        $validator = new InstallationInputValidator();

        try {
            $validator->administrator([
                'login' => 'administrator',
                'display_name' => 'Administrator',
                'first_name' => 'First',
                'last_name' => 'Administrator',
                'email' => 'admin@example.test',
                'password' => 'administrator123',
                'password_confirmation' => 'administrator123',
            ]);
            self::fail('A weak identity-derived password must be rejected.');
        } catch (InstallationValidationException $installationValidationException) {
            $this->assertContains('administrator_password', $installationValidationException->errorCodes());
        }
    }

    /**
     * EN: Proves one-time use and hash-only storage.
     *
     * HR: Dokazuje da token vrijedi samo jednom i da se na disku nalazi samo sažetak.
     */
    public function testAccessTokenIsHashedAndConsumedOnce(): void
    {
        $root = $this->minimalRoot();
        $paths = new InstallationPaths($root);
        $tokens = new InstallationAccessToken($paths);
        $token = $tokens->generate();

        $stored = file_get_contents($paths->tokenFile());
        $this->assertIsString($stored);
        $this->assertStringNotContainsString($token, $stored);
        $this->assertSame(0640, fileperms($paths->tokenFile()) & 0777);
        $this->assertTrue($tokens->consume($token));
        $this->assertFileDoesNotExist($paths->tokenFile());
        $this->assertFalse($tokens->consume($token));
    }

    /**
     * EN: CLI accepts only a safe secret-free base URL.
     *
     * HR: CLI prihvaća samo sigurnu baznu adresu bez tajni i queryja.
     */
    public function testPrepareCommandBuildsValidatedOneTimeUrl(): void
    {
        $root = $this->minimalRoot();
        $command = new InstallationPrepareCommand(new InstallationAccessToken(new InstallationPaths($root)));
        $url = $command->prepare('https://example.test/subdir/');

        $this->assertMatchesRegularExpression('~^https://example\.test/subdir/install\?token=[a-f0-9]{64}$~', $url);

        $this->expectException(\InvalidArgumentException::class);
        $command->prepare('https://user:password@example.test/?unsafe=1');
    }

    /**
     * EN: Starter-guide import requires writable menu configuration.
     *
     * HR: Uvoz početnih uputa zahtijeva zapisivu konfiguraciju izbornika.
     */
    public function testRequirementsRejectReadOnlyMenuConfigurationStorage(): void
    {
        $root = $this->minimalRoot();
        $paths = new InstallationPaths($root);
        $menuDirectory = $paths->menuConfigDirectory();
        $this->assertTrue(chmod($menuDirectory, 0500));

        try {
            $requirements = new InstallationRequirements($paths);
            $checks = array_column($requirements->checks('sqlite'), null, 'id');

            $this->assertArrayHasKey('menu_config_writable', $checks);
            $this->assertFalse($checks['menu_config_writable']['passed']);
            $this->assertTrue($checks['menu_config_writable']['required']);
            $this->assertFalse($requirements->passes('sqlite'));
        } finally {
            chmod($menuDirectory, 0770);
        }
    }

    /**
     * EN: Installer explicitly checks ICU support required for locale-aware sorting.
     *
     * HR: Installer izričito provjerava ICU podršku potrebnu za jezično sortiranje.
     */
    public function testRequirementsIncludeIntlExtension(): void
    {
        $requirements = new InstallationRequirements(new InstallationPaths($this->minimalRoot()));
        $checks = array_column($requirements->checks('sqlite'), null, 'id');

        $this->assertArrayHasKey('extension_intl', $checks);
        $this->assertTrue($checks['extension_intl']['required']);
        $this->assertSame(extension_loaded('intl'), $checks['extension_intl']['passed']);
    }

    /**
     * EN: Web access exchanges the token for a session and security headers.
     *
     * HR: Web pristup token zamjenjuje sesijom, uklanja URL tajnu i postavlja sigurnosna zaglavlja.
     */
    public function testWebApplicationConsumesTokenAndProtectsTheSession(): void
    {
        $root = $this->minimalRoot();
        $paths = new InstallationPaths($root);
        $accessToken = new InstallationAccessToken($paths);
        $token = $accessToken->generate();
        $web = $this->webApplication($paths, $accessToken);
        $session = [];

        $exchange = $web->handle(
            'GET',
            '/subdir/install?token=' . $token,
            '/subdir/index.php',
            ['token' => $token],
            [],
            $session,
        );

        $this->assertSame(303, $exchange->status);
        $this->assertSame('/subdir/install', $exchange->headers['Location']);
        $this->assertTrue($session['authorized']);
        $this->assertArrayNotHasKey('token', $session);
        $this->assertFileDoesNotExist($paths->tokenFile());

        $page = $web->handle('GET', '/subdir/install', '/subdir/index.php', [], [], $session);
        $this->assertSame(200, $page->status);
        $this->assertStringContainsString('data-step="requirements"', $page->body);
        $this->assertStringNotContainsString($token, $page->body);
        $this->assertSame('DENY', $page->headers['X-Frame-Options']);
        $this->assertStringContainsString("frame-ancestors 'none'", $page->headers['Content-Security-Policy']);

        $secondSession = [];
        $reuse = $web->handle(
            'GET',
            '/subdir/install?token=' . $token,
            '/subdir/index.php',
            ['token' => $token],
            [],
            $secondSession,
        );
        $this->assertSame(404, $reuse->status);
    }

    /**
     * EN: Invalid CSRF stops POST before any mutation.
     *
     * HR: Nevaljani CSRF zaustavlja POST prije ikakve promjene.
     */
    public function testWebApplicationRejectsInvalidCsrf(): void
    {
        $root = $this->minimalRoot();
        $paths = new InstallationPaths($root);
        $accessToken = new InstallationAccessToken($paths);
        $token = $accessToken->generate();
        $web = $this->webApplication($paths, $accessToken);
        $session = [];
        $web->handle('GET', '/install?token=' . $token, '/index.php', ['token' => $token], [], $session);

        $response = $web->handle(
            'POST',
            '/install',
            '/index.php',
            [],
            ['csrf' => 'wrong', 'action' => 'continue_requirements'],
            $session,
        );

        $this->assertSame(400, $response->status);
        $this->assertSame('requirements', $session['stage']);
    }

    /**
     * EN: Installation can finish deploy-prepared configuration without
     *     replacing its ownership in a sticky FPM directory.
     *
     * HR: Instalacija može dovršiti deploy-pripremljenu konfiguraciju bez
     *     zamjene njezina vlasništva u sticky FPM direktoriju.
     */
    public function testExistingInstallationConfigWritePreservesMetadata(): void
    {
        $root = $this->completeRoot();
        $path = $root . '/config/installation.php';
        file_put_contents($path, "<?php return ['supported_locales' => ['hr']];\n");
        chmod($path, 0660);
        clearstatcache(true, $path);
        $inode = fileinode($path);
        $owner = fileowner($path);
        $group = filegroup($path);

        $writer = new InstallationConfigWriter(new InstallationPaths($root));
        $write = new \ReflectionMethod($writer, 'writeExistingConfigPreservingMetadata');
        $contents = "<?php return ['supported_locales' => ['hr', 'en']];\n";
        $write->invoke($writer, $path, $contents);

        clearstatcache(true, $path);
        $this->assertSame($contents, file_get_contents($path));
        $this->assertSame($inode, fileinode($path));
        $this->assertSame($owner, fileowner($path));
        $this->assertSame($group, filegroup($path));
        $this->assertSame(0660, fileperms($path) & 0777);
    }

    /**
     * EN: Installer writes use the external final layer and retain extra keys.
     *
     * HR: Instalacijski zapis koristi vanjski zadnji sloj i čuva dodatne ključeve.
     */
    public function testWriterUsesExternalLayerAndPreservesMergedConfiguration(): void
    {
        $root = $this->minimalRoot();
        mkdir($root . '/external', 0770);
        file_put_contents(
            $root . '/config/installation.php',
            '<?php return ["custom"=>["keep"=>true],"session_name"=>"FIXTURE_SESSION"];',
        );
        file_put_contents($root . '/config/database.php', '<?php return ["custom"=>"fixture"];');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $root . '/external');
        $paths = new InstallationPaths($root);
        (new InstallationConfigWriter($paths))->write(['driver' => 'sqlite'], [
            'name' => 'External fixture', 'primary_locale' => 'en', 'supported_locales' => ['en'],
            'timezone' => 'UTC', 'optional_modules' => ['api'],
        ]);
        $this->assertSame($root . '/external', $paths->configDirectory());
        $this->assertSame($root . '/external/modules.php', $paths->moduleStateConfig());
        $this->assertFileDoesNotExist($root . '/data/config/modules.php');
        $this->assertFileDoesNotExist($root . '/config/env.php');
        $reader = new PhpConfigurationReader(ConfigurationPaths::fromEnvironment($root));
        $installation = $reader->read('installation.php');
        $this->assertSame(['keep' => true], $installation['custom']);
        $this->assertSame('FIXTURE_SESSION', $installation['session_name']);
        $database = require $paths->databaseConfig();
        $this->assertSame('fixture', $database['custom']);
        $this->assertSame($root . '/data/simbioza.sqlite', $database['connections']['default']['database']);
        $this->assertSame($root . '/data/installation.lock', $paths->lockFile());
    }

    /**
     * EN: Missing external config is neither created nor replaced with defaults.
     *
     * HR: Nedostajući vanjski config se ne stvara ni zamjenjuje zadanim.
     */
    public function testWriterRejectsMissingExternalDirectoryWithoutWrites(): void
    {
        $root = $this->minimalRoot();
        putenv('HPH_CONFIG_PATH=' . $root . '/missing');
        $writer = new InstallationConfigWriter(new InstallationPaths($root));
        $this->expectException(RuntimeException::class);
        try {
            $writer->write(['driver' => 'sqlite'], [
                'name' => 'Missing fixture', 'primary_locale' => 'en',
                'supported_locales' => ['en'], 'timezone' => 'UTC',
            ]);
        } finally {
            $this->assertDirectoryDoesNotExist($root . '/missing');
            $this->assertFileDoesNotExist($root . '/config/database.php');
        }
    }

    /**
     * EN: An unwritable target is detected before the first write of another config.
     *
     * HR: Nezapisivi cilj se otkriva prije prvog zapisivanja drugog configa.
     */
    public function testWriterPreflightsAllTargetsBeforeWriting(): void
    {
        $root = $this->minimalRoot();
        file_put_contents($root . '/config/modules.php', '<?php return ["enabled"=>[]];');
        chmod($root . '/config/modules.php', 0400);
        $writer = new InstallationConfigWriter(new InstallationPaths($root));
        $this->expectException(RuntimeException::class);
        try {
            $writer->write(['driver' => 'sqlite'], [
                'name' => 'Readonly fixture', 'primary_locale' => 'en',
                'supported_locales' => ['en'], 'timezone' => 'UTC',
            ]);
        } finally {
            $this->assertFileDoesNotExist($root . '/config/database.php');
            $this->assertFileDoesNotExist($root . '/config/env.php');
        }
    }

    /**
     * EN: An empty external config is not a prepared installation and never gets a default fallback.
     *
     * HR: Prazni vanjski config nije pripremljena instalacija i ne dobiva zadani fallback.
     */
    public function testRequirementsRejectEmptyExternalConfiguration(): void
    {
        $root = $this->completeRoot();
        mkdir($root . '/external', 0770);
        putenv('HPH_CONFIG_PATH=' . $root . '/external');
        $requirements = new InstallationRequirements(new InstallationPaths($root));
        $checks = array_column($requirements->checks('sqlite'), null, 'id');
        $this->assertFalse($checks['configuration_ready']['passed']);
        $this->assertFileDoesNotExist($root . '/external/database.php');
        $this->assertFileDoesNotExist($root . '/data/simbioza.sqlite');
    }

    /**
     * EN: The clean installer honors the external layer and creates only current schemas.
     *
     * HR: Čisti installer poštuje vanjski sloj i stvara samo aktualne sheme.
     */
    public function testFreshRunnerUsesExternalConfigurationLayer(): void
    {
        $root = $this->completeRoot();
        mkdir($root . '/external', 0770);
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $root . '/external');
        $paths = new InstallationPaths($root);
        $writer = new InstallationConfigWriter($paths);
        $runner = new InstallationRunner(
            $paths,
            new InstallationAccessToken($paths),
            $writer,
            new InstallationDatabaseTester($writer),
            new InstallationInputValidator(),
            new InstallationRequirements($paths),
            new InstallationLogger($paths),
        );
        $result = $runner->run(['driver' => 'sqlite'], [
            'name' => 'Layered install', 'primary_locale' => 'en', 'supported_locales' => ['en'],
            'timezone' => 'UTC', 'optional_modules' => [], 'module_selection_present' => '1',
        ], [
            'login' => 'layer-admin', 'display_name' => 'Fixture Admin', 'email' => 'fixture@example.test',
            'first_name' => 'Fixture', 'last_name' => 'Admin', 'password' => 'Unique#Strong987',
            'password_confirmation' => 'Unique#Strong987',
        ]);
        $this->assertSame(6, $result['migration_count']);
        $this->assertFileExists($root . '/external/modules.php');
        $this->assertFileDoesNotExist($root . '/config/modules.php');
        $this->assertFileExists($root . '/data/installation.lock');
        $databaseConfig = require $paths->databaseConfig();
        $database = new Database(new Config(new Helper(), ['database' => $databaseConfig]), new Helper());
        $this->assertFalse($database->schema()->hasTable('auth_provider_settings'));
        $this->assertFalse($database->schema()->hasColumn(ModuleAuth::TABLE_AUTH_PROVIDER_PROFILES, 'config_json'));
        $this->assertNull($database->table('_hph_migrations')
            ->where('migration', '=', '20261009000000_file_auth_configuration')->first());
    }

    /**
     * EN: An unwritable theme file stops installation before schema creation.
     *
     * HR: Nedostupna datoteka teme zaustavlja instalaciju prije izrade sheme.
     */
    public function testThemeWritePreflightPrecedesDatabaseMigrations(): void
    {
        $root = $this->completeRoot();
        $themeFile = $root . '/config/theme/themes.json';
        $this->assertTrue(chmod($themeFile, 0444));
        $paths = new InstallationPaths($root);
        $writer = new InstallationConfigWriter($paths);
        $runner = new InstallationRunner(
            $paths,
            new InstallationAccessToken($paths),
            $writer,
            new InstallationDatabaseTester($writer),
            new InstallationInputValidator(),
            new InstallationRequirements($paths),
            new InstallationLogger($paths),
        );

        try {
            $runner->run(
                ['driver' => 'sqlite'],
                [
                    'name' => 'Theme preflight',
                    'primary_locale' => 'hr',
                    'supported_locales' => ['hr', 'en'],
                    'timezone' => 'Europe/Zagreb',
                    'optional_modules' => ['theme'],
                    'module_selection_present' => '1',
                ],
                [
                    'login' => 'preflight-admin',
                    'display_name' => 'Preflight Administrator',
                    'first_name' => 'Preflight',
                    'last_name' => 'Administrator',
                    'email' => 'preflight@example.test',
                    'password' => 'Strong#Check987',
                    'password_confirmation' => 'Strong#Check987',
                ],
            );
            $this->fail('The unwritable theme file must stop the installation.');
        } catch (RuntimeException $runtimeException) {
            $this->assertStringContainsString(
                'installation requirements are not satisfied',
                $runtimeException->getMessage(),
            );
            $databaseFile = $root . '/data/simbioza.sqlite';
            if (is_file($databaseFile)) {
                $database = new \PDO('sqlite:' . $databaseFile);
                $this->assertSame(
                    0,
                    (int)$database->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table'")->fetchColumn(),
                );
            }
        } finally {
            chmod($themeFile, 0660);
        }
    }

    /**
     * EN: Runs a real clean SQLite install.
     *
     * HR: Pokreće stvarnu čistu SQLite instalaciju s migracijama, računom, temom i lockom.
     */
    public function testRunnerPerformsCompleteSqliteInstallation(): void
    {
        $root = $this->completeRoot();
        $paths = new InstallationPaths($root);
        $accessToken = new InstallationAccessToken($paths);
        $accessToken->generate();

        $writer = new InstallationConfigWriter($paths);
        $tester = new InstallationDatabaseTester($writer);
        $validator = new InstallationInputValidator();
        $requirements = new InstallationRequirements($paths);
        $logger = new InstallationLogger($paths);
        $runner = new InstallationRunner(
            $paths,
            $accessToken,
            $writer,
            $tester,
            $validator,
            $requirements,
            $logger,
        );

        $result = $runner->run(
            ['driver' => 'sqlite'],
            [
                'name' => 'Simbioza Test',
                'primary_locale' => 'en',
                'supported_locales' => ['en', 'hr'],
                'timezone' => 'Europe/Zagreb',
                'optional_modules' => (new \App\Module\ModuleCatalog())->optionalSlugs(),
                'module_selection_present' => '1',
            ],
            [
                'login' => 'first-admin',
                'display_name' => 'First Administrator',
                'first_name' => 'First',
                'last_name' => 'Administrator',
                'email' => 'first.admin@example.test',
                'password' => 'Secure#Install987',
                'password_confirmation' => 'Secure#Install987',
            ],
            '/test-simbioza',
        );

        $this->assertSame(16, $result['migration_count']);
        $this->assertSame('simbioza', $result['theme_id']);
        $this->assertSame('korisnicke-upute', $result['workspace_slug']);
        $this->assertFileExists($paths->lockFile());
        $this->assertSame(0640, fileperms($paths->lockFile()) & 0777);
        $this->assertFileDoesNotExist($paths->tokenFile());
        $this->assertSame(0600, fileperms($paths->databaseConfig()) & 0777);
        $this->assertSame(0600, fileperms($paths->environmentConfig()) & 0777);

        $installation = require $paths->installationConfig();
        $this->assertIsArray($installation);
        $this->assertSame('Simbioza Test', $installation['name']);
        $this->assertSame('en', $installation['primary_locale']);

        $databaseConfig = require $paths->databaseConfig();
        $this->assertIsArray($databaseConfig);
        $helper = new Helper();
        $config = new Config($helper, ['database' => $databaseConfig]);
        $database = new Database($config, $helper);
        $administrator = $database->table(ModuleAuth::TABLE_AUTH_USERS)->first();
        $this->assertIsArray($administrator);
        $this->assertSame('first-admin', $administrator['login_identifier']);
        $this->assertTrue(password_verify('Secure#Install987', (string)$administrator['password_hash']));
        $this->assertSame(1, (int)$administrator['is_admin']);
        $this->assertSame(0, (int)$administrator['must_change_password']);
        $this->assertCount(1, $database->table(ModuleAuth::TABLE_AUTH_USERS)->get());
        $this->assertCount(16, $database->table('_hph_migrations')->get());
        $this->assertFalse($database->schema()->hasTable('auth_provider_settings'));
        $this->assertFalse($database->schema()->hasColumn(ModuleAuth::TABLE_AUTH_PROVIDER_PROFILES, 'config_json'));
        $calendarManagerGroup = $database->table(ModuleAuth::TABLE_AUTH_GROUPS)
            ->where('group_key', '=', ModuleCalendar::GROUP_KEY_CALENDAR_MANAGERS)
            ->first();
        $this->assertIsArray($calendarManagerGroup);
        $this->assertSame('Kalendari', $calendarManagerGroup['group_name']);
        $this->assertSame(1, (int)$calendarManagerGroup['is_system']);
        $this->assertSame(1, (int)$calendarManagerGroup['is_enabled']);

        $workspaces = $database->table(ModuleWorkspace::TABLE_WORKSPACES)->get();
        $this->assertCount(1, $workspaces);
        $this->assertSame('korisnicke-upute', $workspaces[0]['slug']);
        $this->assertSame('public', $workspaces[0]['visibility']);
        $workspaceNames = json_decode((string)$workspaces[0]['name_translations'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($workspaceNames);
        $this->assertSame('Korisničke upute', $workspaceNames['hr']);
        $this->assertSame('User guides', $workspaceNames['en']);
        $this->assertSame($workspaceNames['en'], $workspaces[0]['name']);
        $this->assertCount(10, $database->table(ModuleEditorHtml::TABLE_DOCUMENTS)->get());
        $meetingPage = $database->table(ModuleWorkspace::TABLE_WORKSPACE_NODES)
            ->where('slug', '=', 'sastanci')->first();
        $this->assertIsArray($meetingPage);
        $this->assertNotNull($meetingPage['parent_id']);
        $guideVersions = $database->table(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS)->get();
        $meetingDocument = $database->table(ModuleEditorHtml::TABLE_DOCUMENTS)
            ->where('document_key', '=', $meetingPage['document_key'])->first();
        $this->assertIsArray($meetingDocument);
        $this->assertSame('none', $meetingDocument['attachment_visibility']);
        $meetingWorkflows = $database->table(ModuleWorkspace::TABLE_WORKSPACE_NODE_WORKFLOWS)
            ->where('node_id', '=', $meetingPage['id'])->get();
        $this->assertCount(2, $meetingWorkflows);
        $payloadConfig = new \AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlConfig(
            new Config($helper, [], $root),
            dirname(__DIR__, 3) . '/vendor/aaieduhr/heartphrame-module-editor-html',
        );
        $formatter = new \AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlDocumentFormatter();
        foreach ($meetingWorkflows as $workflow) {
            $published = $database->table(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS)
                ->where('document_id', '=', $meetingDocument['id'])
                ->where('language_code', '=', $workflow['language_code'])
                ->where('version_number', '=', $workflow['published_version_number'])->first();
            $this->assertIsArray($published);
            $this->assertSame('published', $workflow['status']);
            $publishedHtml = BundledAssetsUpdater::guideVersionHtml($published, $payloadConfig, $formatter);
            $this->assertGreaterThan(30000, strlen($publishedHtml));
            $this->assertSame(36, substr_count($publishedHtml, '<img '));
        }

        $guideHtml = implode("\n", array_map(
            static fn(array $version): string => BundledAssetsUpdater::guideVersionHtml(
                $version,
                $payloadConfig,
                $formatter,
            ),
            $guideVersions,
        ));
        $this->assertStringNotContainsString('/hfc', strtolower($guideHtml));
        $this->assertStringContainsString('/test-simbioza/editor-html/asset/', $guideHtml);
        $this->assertStringContainsString('/test-simbioza/workspace/korisnicke-upute/', $guideHtml);
        preg_match_all(
            '~(?:^|/)test-simbioza/editor-html/asset/([0-9a-f-]{36})(?:[?&#"\'\s<]|$)~i',
            $guideHtml,
            $assetMatches,
        );
        $referencedAssetUuids = array_values(array_unique($assetMatches[1]));
        $this->assertNotEmpty($referencedAssetUuids);
        $assetRows = $database->table(ModuleEditorHtml::TABLE_ASSETS)->get();
        $assetsByUuid = [];
        foreach ($assetRows as $assetRow) {
            $assetsByUuid[(string)$assetRow['uuid']] = $assetRow;
            $assetPath = $payloadConfig->attachmentPayloadPath((string)$assetRow['content_path']);
            $this->assertFileExists($assetPath);
            $this->assertSame(
                (int)$assetRow['file_size'],
                filesize($assetPath),
                'Imported guide asset metadata must match the stored file size.',
            );
        }

        foreach ($referencedAssetUuids as $assetUuid) {
            $this->assertArrayHasKey($assetUuid, $assetsByUuid, 'Imported guide asset reference must resolve.');
            $contentPath = (string)$assetsByUuid[$assetUuid]['content_path'];
            $this->assertNotSame('', $contentPath);
            $this->assertFileExists($payloadConfig->attachmentPayloadPath($contentPath));
        }

        $this->assertCount(0, $database->table(ModuleCalendar::TABLE_CALENDARS)->get());
        $this->assertCount(0, $database->table(ModuleTask::TABLE_STATES)->get());
        $this->assertCount(0, $database->table(ModuleTask::TABLE_EVENTS)->get());

        $workspaceId = (int)$workspaces[0]['id'];
        $rootNodes = $database->table(ModuleWorkspace::TABLE_WORKSPACE_NODES)
            ->where('workspace_id', '=', $workspaceId)
            ->whereRaw('parent_id IS NULL')
            ->orderBy('sort_order')
            ->get();
        $this->assertSame([
            'simbioza',
            'instalacija',
            'prijava-i-korisnici',
            'kalendari',
            'podrucja',
            'uredivanje-stranica',
        ], array_column($rootNodes, 'slug'));
        $translatedTitles = [];
        foreach ($rootNodes as $node) {
            $translations = json_decode((string)$node['title_translations'], true, 512, JSON_THROW_ON_ERROR);
            $this->assertIsArray($translations);
            $this->assertArrayHasKey('hr', $translations);
            $this->assertArrayHasKey('en', $translations);
            $translatedTitles[(string)$node['slug']] = $translations;
            $this->assertSame((int)$administrator['id'], (int)$node['created_by_user_id']);
        }

        $this->assertSame('Installation', $translatedTitles['instalacija']['en']);
        $this->assertSame('Calendars', $translatedTitles['kalendari']['en']);
        $this->assertSame('Workspaces', $translatedTitles['podrucja']['en']);
        $this->assertSame('Editing pages', $translatedTitles['uredivanje-stranica']['en']);

        $workspaceAcl = $database->table(ModuleWorkspace::TABLE_WORKSPACE_ACL)
            ->where('workspace_id', '=', $workspaceId)
            ->get();
        $this->assertCount(1, $workspaceAcl);
        $this->assertSame('public', $workspaceAcl[0]['subject_type']);
        $this->assertSame(1, (int)$workspaceAcl[0]['can_view']);

        $settingsJson = file_get_contents($paths->themeConfigDirectory() . '/settings.json');
        $this->assertIsString($settingsJson);
        $settings = json_decode($settingsJson, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($settings);
        $this->assertSame($result['theme_id'], $settings['active_theme']);
        $this->assertSame('auto', $settings['mode_policy']);
        $this->assertFileExists($root . '/data/themes/' . $result['theme_id'] . '/theme-assets.json');
        $themesJson = file_get_contents($paths->themeConfigDirectory() . '/themes.json');
        $this->assertIsString($themesJson);
        $themes = json_decode($themesJson, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($themes);
        $this->assertCount(1, $themes);
        $this->assertSame('simbioza', $themes[0]['id']);
        $this->assertSame(72, $themes[0]['components']['header']['height_px'] ?? null);
        $this->assertSame(56, $themes[0]['components']['navigation']['height_px'] ?? null);
        $themeDirectories = array_values(array_filter(
            scandir($root . '/data/themes') ?: [],
            static fn(string $entry): bool => !str_starts_with($entry, '.'),
        ));
        $this->assertSame(['simbioza'], $themeDirectories);

        // EN: A real existing-install update preserves user themes, guide
        //     identity and ACL, and does not reimport an unchanged package.
        //
        // HR: Stvarna nadogradnja postojeće instalacije čuva korisničke teme,
        //     položaj i ACL uputa te ne ponavlja import istog paketa.
        copy(
            dirname(__DIR__, 3) . '/scripts/update_bundled_assets.php',
            $root . '/scripts/update_bundled_assets.php',
        );
        copy(
            dirname(__DIR__, 3) . '/resources/installation/workspace/sastanci.zip',
            $root . '/resources/installation/workspace/sastanci.zip',
        );
        copy(
            dirname(__DIR__, 3) . '/resources/installation/workspace/instalacija.zip',
            $root . '/resources/installation/workspace/instalacija.zip',
        );
        foreach (
            ['apache-mod-php-instalacija.zip', 'php-fpm-instalacija.zip', 'confluence-import.zip'] as $guideArchive
        ) {
            copy(
                dirname(__DIR__, 3) . '/resources/installation/workspace/' . $guideArchive,
                $root . '/resources/installation/workspace/' . $guideArchive,
            );
        }

        // EN: Reproduces the public CLI path of an old update.php, not a direct new-step call.
        //
        // HR: Reproducira javni CLI put starog update.php, ne izravni poziv novog koraka.
        file_put_contents($root . '/scripts/bundled-assets-finalize.php', <<<'PHP'
<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$root = dirname(__DIR__);
$app = new \HeartPhrame\App([$root . '/config'], $root);
$app->loadCommands();
$container = $app->getContainer();
$command = $container->get(\HeartPhrame\Command\CommandManager::class)->getCommand('orm-migrate:up');
$handler = $container->get(\HeartPhrame\Factory\CallableFactory::class)->buildCallable($command->getHandler());
exit($handler([], ['connection' => 'default', 'path' => 'database/migrations']));
PHP);
        file_put_contents($root . '/data/update-maintenance.json', '{"target_tag":"0.1.68"}');
        $customTheme = $themes[0];
        $customTheme['id'] = 'custom-user-theme';
        $customTheme['name'] = 'User custom theme';
        $customTheme['system'] = false;
        $customTheme['light']['colors']['primary'] = '#ABCDEF';
        $themes[] = $customTheme;
        file_put_contents($paths->themeConfigDirectory() . '/themes.json', json_encode($themes, JSON_THROW_ON_ERROR));
        $settings['active_theme'] = 'custom-user-theme';
        $settings['mode_policy'] = 'dark';
        $settingsPath = $paths->themeConfigDirectory() . '/settings.json';
        file_put_contents($settingsPath, json_encode($settings, JSON_THROW_ON_ERROR));
        $database->table(ModuleWorkspace::TABLE_WORKSPACE_NODE_ACL)->insert([
            'node_id' => $meetingPage['id'], 'subject_type' => 'user',
            'subject_id' => $administrator['id'], 'can_view' => 1, 'can_manage' => 1,
        ]);
        $installationPage = $database->table(ModuleWorkspace::TABLE_WORKSPACE_NODES)
            ->where('slug', '=', 'instalacija')->first();
        $this->assertIsArray($installationPage);
        $database->table(ModuleWorkspace::TABLE_WORKSPACE_NODE_ACL)->insert([
            'node_id' => $installationPage['id'], 'subject_type' => 'user',
            'subject_id' => $administrator['id'], 'can_view' => 1, 'can_manage' => 1,
        ]);
        $aclBefore = $database->table(ModuleWorkspace::TABLE_WORKSPACE_NODE_ACL)
            ->where('node_id', '=', $meetingPage['id'])->get();
        $installationAclBefore = $database->table(ModuleWorkspace::TABLE_WORKSPACE_NODE_ACL)
            ->where('node_id', '=', $installationPage['id'])->get();
        $managedPageIds = [(int)$meetingPage['id'], (int)$installationPage['id']];
        foreach (['apache-mod-php-instalacija', 'php-fpm-instalacija'] as $guideSlug) {
            $guideNode = $database->table(ModuleWorkspace::TABLE_WORKSPACE_NODES)
                ->where('slug', '=', $guideSlug)->first();
            $this->assertIsArray($guideNode);
            $this->assertSame((int)$installationPage['id'], (int)$guideNode['parent_id']);
            $managedPageIds[] = (int)$guideNode['id'];
        }

        // EN: The new package upgrades only the import guide and preserves its position and ACL.
        //
        // HR: Novi paket nadograđuje samo uputu za uvoz i čuva njen položaj i ACL.
        $confluencePage = $database->table(ModuleWorkspace::TABLE_WORKSPACE_NODES)
            ->where('slug', '=', 'confluence-import')->first();
        $this->assertIsArray($confluencePage);
        $managedPageIds[] = (int)$confluencePage['id'];
        $database->table(ModuleWorkspace::TABLE_WORKSPACE_NODE_ACL)->insert([
            'node_id' => $confluencePage['id'], 'subject_type' => 'user',
            'subject_id' => $administrator['id'], 'can_view' => 1, 'can_manage' => 1,
        ]);
        $confluenceAclBefore = $database->table(ModuleWorkspace::TABLE_WORKSPACE_NODE_ACL)
            ->where('node_id', '=', $confluencePage['id'])->get();
        $confluenceDocument = $database->table(ModuleEditorHtml::TABLE_DOCUMENTS)
            ->where('document_key', '=', $confluencePage['document_key'])->first();
        $this->assertIsArray($confluenceDocument);
        $database->table(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS)
            ->where('document_id', '=', $confluenceDocument['id'])
            ->update(['content_html' => '<p>Previous Confluence import guide without a video.</p>']);

        $otherNodesBefore = array_values(array_filter(
            $database->table(ModuleWorkspace::TABLE_WORKSPACE_NODES)->get(),
            static fn(array $node): bool => !in_array((int)$node['id'], $managedPageIds, true),
        ));
        $otherDocumentKey = $otherNodesBefore[0]['document_key'];
        $this->assertIsString($otherDocumentKey);
        $database->table(ModuleTask::TABLE_STATES)->insert([
            'document_id' => $otherDocumentKey, 'task_uuid' => 'other-page-task',
            'task_list_uuid' => 'other-page-list', 'is_completed' => 1,
        ]);
        $database->table(ModuleComment::TABLE_COMMENTS)->insert([
            'uuid' => 'other-page-comment', 'document_id' => $otherDocumentKey,
            'language_code' => 'en', 'user_id' => $administrator['id'],
            'author_display_name' => 'Administrator', 'body' => 'Keep the other page unchanged.',
        ]);
        $otherTasks = $database->table(ModuleTask::TABLE_STATES)->where('document_id', '=', $otherDocumentKey)->get();
        $otherComments = $database->table(ModuleComment::TABLE_COMMENTS)
            ->where('document_id', '=', $otherDocumentKey)->get();
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/scripts/bundled-assets-finalize.php')
        . ' 2>&1';
        $output = [];
        exec($command, $output, $exit);
        $this->assertSame(0, $exit, implode("\n", $output));
        $updatedThemes = json_decode((string)file_get_contents($paths->themeConfigDirectory() . '/themes.json'), true);
        $updatedThemes = array_column($updatedThemes, null, 'id');
        $this->assertSame('#ABCDEF', $updatedThemes['custom-user-theme']['light']['colors']['primary']);
        $updatedSettings = json_decode((string)file_get_contents($settingsPath), true);
        $this->assertSame($settings, $updatedSettings);
        $this->assertSame($aclBefore, $database->table(ModuleWorkspace::TABLE_WORKSPACE_NODE_ACL)
            ->where('node_id', '=', $meetingPage['id'])->get());
        $this->assertSame($installationAclBefore, $database->table(ModuleWorkspace::TABLE_WORKSPACE_NODE_ACL)
            ->where('node_id', '=', $installationPage['id'])->get());
        $this->assertSame($confluenceAclBefore, $database->table(ModuleWorkspace::TABLE_WORKSPACE_NODE_ACL)
            ->where('node_id', '=', $confluencePage['id'])->get());
        $updatedConfluencePage = $database->table(ModuleWorkspace::TABLE_WORKSPACE_NODES)
            ->where('id', '=', $confluencePage['id'])->first();
        $this->assertIsArray($updatedConfluencePage);
        $this->assertSame(
            array_diff_key($confluencePage, ['updated_at' => true]),
            array_diff_key($updatedConfluencePage, ['updated_at' => true]),
        );
        $otherNodesAfter = array_values(array_filter(
            $database->table(ModuleWorkspace::TABLE_WORKSPACE_NODES)->get(),
            static fn(array $node): bool => !in_array((int)$node['id'], $managedPageIds, true),
        ));
        $this->assertSame($otherNodesBefore, $otherNodesAfter);
        $this->assertSame($otherTasks, $database->table(ModuleTask::TABLE_STATES)
            ->where('document_id', '=', $otherDocumentKey)->get());
        $this->assertSame($otherComments, $database->table(ModuleComment::TABLE_COMMENTS)
            ->where('document_id', '=', $otherDocumentKey)->get());
        $workflows = $database->table(ModuleWorkspace::TABLE_WORKSPACE_NODE_WORKFLOWS)
            ->where('node_id', '=', $meetingPage['id'])->get();
        $this->assertSame(['published', 'published'], array_column($workflows, 'status'));
        $meetingDocument = $database->table(ModuleEditorHtml::TABLE_DOCUMENTS)
            ->where('document_key', '=', $meetingPage['document_key'])->first();
        $this->assertIsArray($meetingDocument);
        $updatedVersions = $database->table(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS)
            ->where('document_id', '=', $meetingDocument['id'])->get();
        $updatedAssets = $database->table(ModuleEditorHtml::TABLE_ASSETS)
            ->where('document_id', '=', $meetingDocument['id'])->get();
        $updatedAssetsByUuid = array_column($updatedAssets, null, 'uuid');
        foreach ($updatedVersions as $version) {
            $html = BundledAssetsUpdater::guideVersionHtml(
                $version,
                $payloadConfig,
                new EditorHtmlDocumentFormatter(),
            );
            $this->assertGreaterThan(30000, strlen($html));
            $this->assertSame(36, substr_count($html, '<img '));
            preg_match_all('~/editor-html/asset/([0-9a-f-]{36})~i', $html, $updatedAssetMatches);
            foreach (array_unique($updatedAssetMatches[1]) as $uuid) {
                $this->assertArrayHasKey($uuid, $updatedAssetsByUuid);
                $assetPath = $payloadConfig->attachmentPayloadPath((string)$updatedAssetsByUuid[$uuid]['content_path']);
                $this->assertFileExists($assetPath);
                $this->assertIsReadable($assetPath);
                $this->assertSame(fileowner($payloadConfig->dataRoot()), fileowner($assetPath));
                $this->assertSame(filegroup($payloadConfig->dataRoot()), filegroup($assetPath));
            }

            $this->assertStringContainsString('/test-simbioza/editor-html/asset/', $html, json_encode([
                'document_key' => $meetingDocument['document_key'], 'version' => $version['version_number'],
                'language' => $version['language_code'], 'driver' => $version['storage_driver'],
                'path' => $version['content_path'],
            ], JSON_THROW_ON_ERROR));
        }

        $this->assertSame('none', $meetingDocument['attachment_visibility']);

        // EN: The video is transferred locally with correctly rebased URLs in both guide locales.
        //
        // HR: Video se prenosi lokalno, s ispravnim putanjama u HR i EN uputi.
        $confluenceVersions = $database->table(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS)
            ->where('document_id', '=', $confluenceDocument['id'])->get();
        $this->assertSame(['hr', 'en'], array_column($confluenceVersions, 'language_code'));
        $confluenceAssets = array_column($database->table(ModuleEditorHtml::TABLE_ASSETS)
            ->where('document_id', '=', $confluenceDocument['id'])->get(), null, 'uuid');
        foreach ($confluenceVersions as $version) {
            $html = BundledAssetsUpdater::guideVersionHtml(
                $version,
                $payloadConfig,
                new EditorHtmlDocumentFormatter(),
            );
            $this->assertSame(1, preg_match(
                '~<video[^>]* src="/test-simbioza/editor-html/asset/([0-9a-f-]{36})"~',
                $html,
                $videoMatch,
            ));
            $this->assertStringNotContainsString('github.com', substr($html, 0, strpos($html, '</video>') ?: 0));
            $this->assertArrayHasKey($videoMatch[1], $confluenceAssets);
            $video = $confluenceAssets[$videoMatch[1]];
            $this->assertSame('video/mp4', $video['mime_type']);
            $videoPath = $payloadConfig->attachmentPayloadPath((string)$video['content_path']);
            $this->assertFileExists($videoPath);
            $this->assertGreaterThan(1000000, filesize($videoPath));
            $this->assertSame(fileowner($payloadConfig->dataRoot()), fileowner($videoPath));
            $this->assertSame(filegroup($payloadConfig->dataRoot()), filegroup($videoPath));
        }

        $this->assertSame(0600, fileperms($root . '/data/bundled-assets.json') & 0777);
        $stateHash = hash_file('sha256', $root . '/data/bundled-assets.json');
        $repairPath = null;
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $repairPath = $payloadConfig->attachmentPayloadPath((string)$updatedAssets[0]['content_path']);
            $this->assertTrue(chown($repairPath, 65534));
            $this->assertTrue(chgrp($repairPath, 65534));
        }

        $versionCount = count($database->table(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS)->get());
        $output = [];
        exec($command, $output, $exit);
        $this->assertSame(0, $exit, implode("\n", $output));
        $this->assertStringNotContainsString('updated;', implode("\n", $output));
        $this->assertCount($versionCount, $database->table(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS)->get());
        $this->assertSame($stateHash, hash_file('sha256', $root . '/data/bundled-assets.json'));
        if ($repairPath !== null) {
            clearstatcache();
            $this->assertSame(fileowner($root . '/data/editor-html/uploads'), fileowner($repairPath));
            $this->assertSame(filegroup($root . '/data/editor-html/uploads'), filegroup($repairPath));
        }
    }

    /**
     * EN: A clean installation without optional modules skips their migrations
     *     while still importing the starter user guides.
     *
     * HR: Čista instalacija bez opcionalnih modula preskače njihove migracije,
     *     ali ipak uvozi početne korisničke upute.
     */
    public function testRunnerInstallsRequiredModulesWithoutOptionalSchemas(): void
    {
        $root = $this->completeRoot();
        $paths = new InstallationPaths($root);
        $accessToken = new InstallationAccessToken($paths);
        $accessToken->generate();

        $writer = new InstallationConfigWriter($paths);
        $tester = new InstallationDatabaseTester($writer);
        $validator = new InstallationInputValidator();
        $runner = new InstallationRunner(
            $paths,
            $accessToken,
            $writer,
            $tester,
            $validator,
            new InstallationRequirements($paths),
            new InstallationLogger($paths),
        );

        $result = $runner->run(
            ['driver' => 'sqlite'],
            [
                'name' => 'Minimal Simbioza',
                'primary_locale' => 'hr',
                'supported_locales' => ['hr', 'en'],
                'timezone' => 'Europe/Zagreb',
                'optional_modules' => [],
                'module_selection_present' => '1',
            ],
            [
                'login' => 'minimal-admin',
                'display_name' => 'Minimal Administrator',
                'first_name' => 'Minimal',
                'last_name' => 'Administrator',
                'email' => 'minimal.admin@example.test',
                'password' => 'Secure#Minimal987',
                'password_confirmation' => 'Secure#Minimal987',
            ],
            '/minimal',
        );

        $this->assertSame(6, $result['migration_count']);
        $this->assertSame('', $result['theme_id']);
        $this->assertSame('korisnicke-upute', $result['workspace_slug']);
        $moduleState = require $paths->moduleStateConfig();
        $this->assertIsArray($moduleState);
        // EN: The web installer writes privately first; the one-time FPM
        //     `--finalize` then applies the shared runtime group and mode 0660.
        //
        // HR: Web-installer prvo piše privatno; jednokratni FPM `--finalize`
        //     zatim postavlja zajedničku runtime grupu i 0660.
        $this->assertSame(0600, fileperms($paths->moduleStateConfig()) & 0777);
        $this->assertNotContains('aaieduhr/heartphrame-module-calendar', $moduleState['enabled']);
        $this->assertNotContains('aaieduhr/heartphrame-module-theme', $moduleState['enabled']);

        $databaseConfig = require $paths->databaseConfig();
        $helper = new Helper();
        $config = new Config($helper, ['database' => $databaseConfig]);
        $database = new Database($config, $helper);
        $this->assertFalse($database->schema()->hasTable('calendar_calendars'));
        $this->assertFalse($database->schema()->hasTable('document_comments'));
        $this->assertFalse($database->schema()->hasTable('simbioza_confluence_import_jobs'));
        $this->assertTrue($database->schema()->hasTable(ModuleWorkspace::TABLE_WORKSPACES));

        // EN: The updater must be able to refresh bundled guides without the
        //     optional Backup and Theme modules being persistently enabled.
        //
        // HR: Updater mora moći osvježiti ugrađene upute i bez trajno
        //     uključenih opcionalnih modula Backup i Theme.
        copy(
            dirname(__DIR__, 3) . '/scripts/update_bundled_assets.php',
            $root . '/scripts/update_bundled_assets.php',
        );
        copy(
            dirname(__DIR__, 3) . '/resources/installation/workspace/sastanci.zip',
            $root . '/resources/installation/workspace/sastanci.zip',
        );
        copy(
            dirname(__DIR__, 3) . '/resources/installation/workspace/instalacija.zip',
            $root . '/resources/installation/workspace/instalacija.zip',
        );
        foreach (
            ['apache-mod-php-instalacija.zip', 'php-fpm-instalacija.zip', 'confluence-import.zip'] as $guideArchive
        ) {
            copy(
                dirname(__DIR__, 3) . '/resources/installation/workspace/' . $guideArchive,
                $root . '/resources/installation/workspace/' . $guideArchive,
            );
        }

        $output = [];
        exec(
            escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/scripts/update_bundled_assets.php')
                . ' --base-path=/minimal 2>&1',
            $output,
            $exit,
        );
        $this->assertSame(0, $exit, implode("\n", $output));
        $this->assertStringNotContainsString('Simbioza theme updated', implode("\n", $output));
        $unchangedModuleState = require $paths->moduleStateConfig();
        $this->assertNotContains('aaieduhr/heartphrame-module-backup', $unchangedModuleState['enabled']);
        $this->assertNotContains('aaieduhr/heartphrame-module-theme', $unchangedModuleState['enabled']);
    }

    /**
     * EN: The web wizard completes a real SQLite installation.
     *
     * HR: Web čarobnjak dovršava stvarnu SQLite instalaciju.
     */
    public function testWebApplicationCompletesRealSqliteInstallation(): void
    {
        $root = $this->completeRoot();
        $paths = new InstallationPaths($root);
        $accessToken = new InstallationAccessToken($paths);
        $token = $accessToken->generate();
        $web = $this->webApplication($paths, $accessToken);
        $session = [];

        $exchange = $web->handle('GET', '/install?token=' . $token, '/index.php', ['token' => $token], [], $session);
        $this->assertSame(303, $exchange->status);
        $this->assertSame('requirements', $session['stage']);

        $requirements = $web->handle('POST', '/install', '/index.php', [], [
            'csrf' => $session['csrf'],
            'action' => 'continue_requirements',
        ], $session);
        $this->assertSame(303, $requirements->status);
        $this->assertSame('database', $session['stage']);

        $database = $web->handle('POST', '/install', '/index.php', [], [
            'csrf' => $session['csrf'],
            'action' => 'save_database',
            'driver' => 'sqlite',
        ], $session);
        $this->assertSame(303, $database->status);
        $this->assertSame('application', $session['stage']);

        $application = $web->handle('POST', '/install', '/index.php', [], [
            'csrf' => $session['csrf'],
            'action' => 'save_application',
            'name' => 'Simbioza Web Test',
            'primary_locale' => 'hr',
            'supported_locales' => ['hr', 'en'],
            'timezone' => 'Europe/Zagreb',
            'login' => 'web-admin',
            'display_name' => 'Web Administrator',
            'first_name' => 'Web',
            'last_name' => 'Administrator',
            'email' => 'web.admin@example.test',
            'password' => 'Secure#WebInstall987',
            'password_confirmation' => 'Secure#WebInstall987',
        ], $session);
        $this->assertSame(303, $application->status);
        $this->assertSame('review', $session['stage']);
        $this->assertArrayNotHasKey('password_confirmation', $session['administrator']);

        $installation = $web->handle('POST', '/install', '/index.php', [], [
            'csrf' => $session['csrf'],
            'action' => 'install',
        ], $session);
        $this->assertSame(200, $installation->status);
        $this->assertStringContainsString('web-admin', $installation->body);
        $this->assertSame(['destroy' => true], $session);
        $this->assertFileExists($paths->lockFile());

        $lockedSession = [];
        $locked = $web->handle('GET', '/install', '/index.php', [], [], $lockedSession);
        $this->assertSame(404, $locked->status);
    }

    /**
     * EN: Builds the web app with real temp-root services.
     *
     * HR: Gradi web-aplikaciju sa stvarnim servisima u temp korijenu.
     */
    private function webApplication(
        InstallationPaths $paths,
        InstallationAccessToken $accessToken,
    ): InstallationWebApplication {
        $writer = new InstallationConfigWriter($paths);
        $tester = new InstallationDatabaseTester($writer);
        $validator = new InstallationInputValidator();
        $requirements = new InstallationRequirements($paths);
        $logger = new InstallationLogger($paths);
        $runner = new InstallationRunner(
            $paths,
            $accessToken,
            $writer,
            $tester,
            $validator,
            $requirements,
            $logger,
        );

        return new InstallationWebApplication(
            $paths,
            $accessToken,
            $requirements,
            $tester,
            $validator,
            $runner,
            $logger,
        );
    }

    /**
     * EN: A single-locale install does not retain the unnecessary second guide version.
     *
     * HR: Jednojezična instalacija ne zadržava nepotrebnu drugu verziju uputa.
     */
    public function testSingleLocaleInstallationImportsOnlySelectedGuideLanguage(): void
    {
        foreach (['en', 'hr'] as $locale) {
            $root = $this->completeRoot();
            $paths = new InstallationPaths($root);
            $token = new InstallationAccessToken($paths);
            $token->generate();
            $writer = new InstallationConfigWriter($paths);
            $runner = new InstallationRunner(
                $paths,
                $token,
                $writer,
                new InstallationDatabaseTester($writer),
                new InstallationInputValidator(),
                new InstallationRequirements($paths),
                new InstallationLogger($paths),
            );
            $runner->run(
                ['driver' => 'sqlite'],
                [
                    'name' => 'One Language',
                    'primary_locale' => $locale,
                    'supported_locales' => [$locale],
                    'timezone' => 'Europe/Zagreb',
                    'optional_modules' => [],
                    'module_selection_present' => '1',
                ],
                [
                    'login' => 'single-admin',
                    'display_name' => 'Single Administrator',
                    'first_name' => 'Single',
                    'last_name' => 'Administrator',
                    'email' => 'single.admin@example.test',
                    'password' => 'Secure#Single987',
                    'password_confirmation' => 'Secure#Single987',
                ],
            );
            $databaseConfig = require $paths->databaseConfig();
            $database = new Database(new Config(new Helper(), ['database' => $databaseConfig]), new Helper());
            $workspace = $database->table(ModuleWorkspace::TABLE_WORKSPACES)->first();
            $this->assertIsArray($workspace);
            $names = json_decode((string)$workspace['name_translations'], true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame([$locale], array_keys($names));
            foreach ($database->table(ModuleWorkspace::TABLE_WORKSPACE_NODE_WORKFLOWS)->get() as $workflow) {
                $this->assertSame($locale, $workflow['language_code']);
            }

            foreach ($database->table(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS)->get() as $version) {
                $this->assertSame($locale, $version['language_code']);
            }
        }
    }

    /**
     * EN: Creates a minimal private root for security tests.
     *
     * HR: Stvara najmanji privatni korijen za sigurnosne testove.
     */
    private function minimalRoot(): string
    {
        $root = sys_get_temp_dir() . '/simbioza-install-test-' . bin2hex(random_bytes(8));
        $directories = [
            'config',
            'data',
            'scripts',
            'database/migrations',
            'config/theme',
            'config/menu',
            'resources/installation/theme',
            'resources/installation/workspace',
            'vendor',
        ];
        foreach ($directories as $directory) {
            if (!mkdir($root . '/' . $directory, 0770, true) && !is_dir($root . '/' . $directory)) {
                throw new RuntimeException('Unable to create installation test directory.');
            }
        }

        if (file_put_contents($root . '/vendor/autoload.php', "<?php\n", LOCK_EX) === false) {
            throw new RuntimeException('Unable to create installation test autoloader marker.');
        }

        $this->temporaryDirectories[] = $root;
        putenv('HPH_APP_PATH=' . $root);
        putenv('HPH_CONFIG_PATH=' . $root . '/config');
        putenv('HPH_CONFIG_OVERRIDE_PATHS');

        return $root;
    }

    /**
     * EN: Prepares an isolated root with real migrations and theme package.
     *
     * HR: Priprema izolirani korijen sa stvarnim migracijama i paketom teme.
     */
    private function completeRoot(): string
    {
        $root = $this->minimalRoot();
        $projectRoot = dirname(__DIR__, 3);
        unlink($root . '/vendor/autoload.php');
        rmdir($root . '/vendor');
        if (!symlink($projectRoot . '/vendor', $root . '/vendor')) {
            throw new RuntimeException('Unable to link installation test dependencies.');
        }

        copy($projectRoot . '/composer.json', $root . '/composer.json');
        copy($projectRoot . '/bootstrap.php', $root . '/bootstrap.php');

        foreach (['lang', 'views'] as $applicationDirectory) {
            if (!symlink($projectRoot . '/' . $applicationDirectory, $root . '/' . $applicationDirectory)) {
                throw new RuntimeException('Unable to link installation test application resources.');
            }
        }

        foreach (glob($projectRoot . '/database/migrations/*.php') ?: [] as $migrationFile) {
            copy($migrationFile, $root . '/database/migrations/' . basename($migrationFile));
        }

        foreach (
            [
                'api/api.php',
                'app.php',
                'backup/backup-providers.php',
                'backup/backup.php',
                'bootstrap.php',
                'commands.php',
                'listeners.php',
                'menu/menu.php',
                'middleware.php',
                'routes.php',
                'services.php',
                'theme/theme.php',
            ] as $configFile
        ) {
            if (!is_dir(dirname($root . '/config/' . $configFile))) {
                mkdir(dirname($root . '/config/' . $configFile), 0770, true);
            }

            copy($projectRoot . '/config/' . $configFile, $root . '/config/' . $configFile);
        }

        // EN: A clean install has the new path, without legacy calendar.php or its upgrade.
        //
        // HR: Čista instalacija ima novu putanju, bez starog calendar.php i njegova upgradea.
        mkdir($root . '/config/calendar', 0770);
        copy($projectRoot . '/config/calendar/calendar.php', $root . '/config/calendar/calendar.php');

        foreach (['themes.json', 'settings.json'] as $themeFile) {
            copy(
                $projectRoot . '/config/theme/' . $themeFile,
                $root . '/config/theme/' . $themeFile,
            );
        }

        foreach (['settings.json', 'top.json'] as $menuFile) {
            copy(
                $projectRoot . '/config/menu/' . $menuFile,
                $root . '/config/menu/' . $menuFile,
            );
        }

        copy(
            $projectRoot . '/resources/installation/theme/simbioza.zip',
            $root . '/resources/installation/theme/simbioza.zip',
        );
        copy(
            $projectRoot . '/resources/installation/workspace/korisnicke-upute.zip',
            $root . '/resources/installation/workspace/korisnicke-upute.zip',
        );
        copy(
            $projectRoot . '/resources/installation/workspace/instalacija.zip',
            $root . '/resources/installation/workspace/instalacija.zip',
        );
        foreach (
            ['apache-mod-php-instalacija.zip', 'php-fpm-instalacija.zip', 'confluence-import.zip'] as $guideArchive
        ) {
            copy(
                $projectRoot . '/resources/installation/workspace/' . $guideArchive,
                $root . '/resources/installation/workspace/' . $guideArchive,
            );
        }

        copy(
            $projectRoot . '/resources/installation/workspace/sastanci.zip',
            $root . '/resources/installation/workspace/sastanci.zip',
        );

        return $root;
    }

    /**
     * EN: Recursively removes a confirmed test temp directory.
     *
     * HR: Rekurzivno uklanja potvrđeni temp direktorij testa.
     */
    private function deleteDirectory(string $directory): void
    {
        if (!str_starts_with($directory, sys_get_temp_dir() . '/simbioza-install-test-') || !is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $entry) {
            if ($entry->isLink()) {
                unlink($entry->getPathname());
            } elseif ($entry->isDir()) {
                rmdir($entry->getPathname());
            } else {
                unlink($entry->getPathname());
            }
        }

        rmdir($directory);
    }
}
