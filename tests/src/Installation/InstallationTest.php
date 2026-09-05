<?php

declare(strict_types=1);

namespace Tests\Installation;

use AaiEduHr\HeartPhrameModuleAuth\ModuleAuth;
use AaiEduHr\HeartPhrameModuleCalendar\ModuleCalendar;
use AaiEduHr\HeartPhrameModuleEditorHtml\ModuleEditorHtml;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleTask\ModuleTask;
use AaiEduHr\SimbiozaModuleWorkspace\ModuleWorkspace;
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

    /** HR: Nakon testa uklanja samo njegove jedinstvene temp direktorije. EN: Removes only test-owned temp directories. */
    protected function tearDown(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            $this->deleteDirectory($directory);
        }

        $this->temporaryDirectories = [];
    }

    /**
     * HR: Izvorne teme i instalacijski paket ne smiju sadržavati razvojnu web-putanju.
     * EN: Source themes and the installation package must not contain the development web path.
     */
    public function testBundledThemesDoNotHardCodeDevelopmentBasePath(): void
    {
        $projectRoot = dirname(__DIR__, 3);
        $themesJson = file_get_contents($projectRoot . '/resources/config/theme/themes.json');
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
        $this->assertFalse($hero['visual_allow_overflow'] ?? null);
        $this->assertSame(0, $hero['visual_max_height_px'] ?? null);
        $this->assertSame(560, $hero['visual_width_px'] ?? null);
        $this->assertSame(-48, $hero['visual_top_px'] ?? null);
        $this->assertSame(24, $hero['visual_right_px'] ?? null);
    }

    /** HR: Provjerava normalizaciju svakog podržanog tipa baze. EN: Verifies every supported database type. */
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

    /** HR: Odbija slabe administratorske lozinke. EN: Rejects weak administrator passwords. */
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

    /** HR: Dokazuje da token vrijedi samo jednom i da se na disku nalazi samo sažetak. EN: Proves one-time use and hash-only storage. */
    public function testAccessTokenIsHashedAndConsumedOnce(): void
    {
        $root = $this->minimalRoot();
        $paths = new InstallationPaths($root);
        $tokens = new InstallationAccessToken($paths);
        $token = $tokens->generate();

        $stored = file_get_contents($paths->tokenFile());
        $this->assertIsString($stored);
        $this->assertStringNotContainsString($token, $stored);
        $this->assertTrue($tokens->consume($token));
        $this->assertFileDoesNotExist($paths->tokenFile());
        $this->assertFalse($tokens->consume($token));
    }

    /** HR: CLI prihvaća samo sigurnu baznu adresu bez tajni i queryja. EN: CLI accepts only a safe secret-free base URL. */
    public function testPrepareCommandBuildsValidatedOneTimeUrl(): void
    {
        $root = $this->minimalRoot();
        $command = new InstallationPrepareCommand(new InstallationAccessToken(new InstallationPaths($root)));
        $url = $command->prepare('https://example.test/subdir/');

        $this->assertMatchesRegularExpression('~^https://example\.test/subdir/install\?token=[a-f0-9]{64}$~', $url);

        $this->expectException(\InvalidArgumentException::class);
        $command->prepare('https://user:password@example.test/?unsafe=1');
    }

    /** HR: Uvoz početnih uputa zahtijeva zapisivu konfiguraciju izbornika. EN: Starter-guide import requires writable menu configuration. */
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

    /** HR: Web pristup token zamjenjuje sesijom, uklanja URL tajnu i postavlja sigurnosna zaglavlja. EN: Web access exchanges the token for a session and security headers. */
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

    /** HR: Nevaljani CSRF zaustavlja POST prije ikakve promjene. EN: Invalid CSRF stops POST before any mutation. */
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

    /** HR: Pokreće stvarnu čistu SQLite instalaciju s migracijama, računom, temom i lockom. EN: Runs a real clean SQLite install. */
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

        $this->assertSame(27, $result['migration_count']);
        $this->assertSame('simbioza', $result['theme_id']);
        $this->assertSame('korisnicke-upute', $result['workspace_slug']);
        $this->assertFileExists($paths->lockFile());
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
        $this->assertCount(27, $database->table('_hph_migrations')->get());

        $workspaces = $database->table(ModuleWorkspace::TABLE_WORKSPACES)->get();
        $this->assertCount(1, $workspaces);
        $this->assertSame('korisnicke-upute', $workspaces[0]['slug']);
        $this->assertSame('public', $workspaces[0]['visibility']);
        $workspaceNames = json_decode((string)$workspaces[0]['name_translations'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($workspaceNames);
        $this->assertSame('Korisničke upute', $workspaceNames['hr']);
        $this->assertSame('User guides', $workspaceNames['en']);
        $this->assertSame($workspaceNames['en'], $workspaces[0]['name']);
        $this->assertCount(7, $database->table(ModuleEditorHtml::TABLE_DOCUMENTS)->get());
        $guideVersions = $database->table(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS)->get();
        $guideHtml = implode("\n", array_map(
            static fn(array $version): string => is_string($version['content_html'] ?? null)
                ? $version['content_html']
                : '',
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
            $assetPath = $root . '/data/editor-html/uploads/' . $assetRow['content_path'];
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
            $this->assertFileExists($root . '/data/editor-html/uploads/' . $contentPath);
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
        $themeDirectories = array_values(array_filter(
            scandir($root . '/data/themes') ?: [],
            static fn(string $entry): bool => !str_starts_with($entry, '.'),
        ));
        $this->assertSame(['simbioza'], $themeDirectories);
    }

    /** HR: Pokreće cijeli web-tijek do locka bez druge kopije lozinke u sessionu. EN: Runs the full web flow to the lock with one session password copy. */
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

    /** HR: Gradi web-aplikaciju sa stvarnim servisima u temp korijenu. EN: Builds the web app with real temp-root services. */
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
     * HR: Stvara najmanji privatni korijen za sigurnosne testove.
     * EN: Creates a minimal private root for security tests.
     */
    private function minimalRoot(): string
    {
        $root = sys_get_temp_dir() . '/simbioza-install-test-' . bin2hex(random_bytes(8));
        $directories = [
            'config',
            'data',
            'database/migrations',
            'resources/config/theme',
            'resources/config/menu',
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

        return $root;
    }

    /** HR: Priprema izolirani korijen sa stvarnim migracijama i paketom teme. EN: Prepares an isolated root with real migrations and theme package. */
    private function completeRoot(): string
    {
        $root = $this->minimalRoot();
        $projectRoot = dirname(__DIR__, 3);
        unlink($root . '/vendor/autoload.php');
        rmdir($root . '/vendor');
        if (!symlink($projectRoot . '/vendor', $root . '/vendor')) {
            throw new RuntimeException('Unable to link installation test dependencies.');
        }

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
                'api.php',
                'app.php',
                'backup-providers.php',
                'backup.php',
                'bootstrap.php',
                'commands.php',
                'editor-html.php',
                'listeners.php',
                'menu.php',
                'middleware.php',
                'routes.php',
                'services.php',
                'theme.php',
                'workspace.php',
            ] as $configFile
        ) {
            copy($projectRoot . '/config/' . $configFile, $root . '/config/' . $configFile);
        }

        foreach (['themes.json', 'settings.json'] as $themeFile) {
            copy(
                $projectRoot . '/resources/config/theme/' . $themeFile,
                $root . '/resources/config/theme/' . $themeFile,
            );
        }

        foreach (['contexts.json', 'settings.json', 'top.json'] as $menuFile) {
            copy(
                $projectRoot . '/resources/config/menu/' . $menuFile,
                $root . '/resources/config/menu/' . $menuFile,
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

        return $root;
    }

    /** HR: Rekurzivno uklanja potvrđeni temp direktorij testa. EN: Recursively removes a confirmed test temp directory. */
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
