<?php

declare(strict_types=1);

namespace Tests\Database;

use AaiEduHr\HeartPhrameModuleAuth\Backup\AuthBackupIdentityResolver;
use AaiEduHr\HeartPhrameModuleAuth\Backup\AuthBackupProvider;
use AaiEduHr\HeartPhrameModuleAuth\Configuration\AuthConfigurationStore;
use AaiEduHr\HeartPhrameModuleAuth\Configuration\AuthConfigurationSynchronizer;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveReader;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveWriter;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupConfig;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupExportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupImportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupScope;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use HeartPhrame\Bridge\ComposerBridge;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * EN: Backup checks use only fictional data and temporary SQLite.
 *
 * HR: Backup provjere koriste samo izmišljene podatke i privremeni SQLite.
 */
#[CoversNothing]
final class AuthConfigurationBackupTest extends TestCase
{
    private string $root;

    private Database $database;

    private AuthConfigurationStore $store;

    private AuthBackupProvider $provider;

    private BackupConfig $backup;

    /** @var array<string,string|false> */
    private array $environment = [];

    /** @var list<BackupArchiveReader> */
    private array $readers = [];

    /**
     * EN: Never opens the development HFC database or copies its secrets.
     *
     * HR: Ne otvara razvojnu HFC bazu niti kopira njezine tajne.
     */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/simbioza-auth-backup-' . bin2hex(random_bytes(8));
        mkdir($this->root, 0700);
        file_put_contents($this->root . '/app.php', '<?php return [];');
        foreach (['HPH_APP_PATH', 'HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $key) {
            $this->environment[$key] = getenv($key);
        }

        putenv('HPH_APP_PATH=' . $this->root);
        putenv('HPH_CONFIG_PATH=' . $this->root);
        putenv('HPH_CONFIG_OVERRIDE_PATHS=');
        $helper = new Helper();
        $config = new Config($helper, ['database' => ['connections' => ['default' => [
            'driver' => 'sqlite', 'database' => ':memory:',
        ]]], 'backup' => [
            'archive_dir' => $this->root . '/archives', 'staging_dir' => $this->root . '/staging',
        ]], $this->root);
        $this->database = new Database($config, $helper);
        $authRoot = dirname(__DIR__, 3) . '/vendor/aaieduhr/heartphrame-module-auth';
        (require $authRoot . '/resources/migrations/initial_auth_schema.php')->up($this->database);
        $this->store = new AuthConfigurationStore($this->root . '/auth/auth.json');
        $this->store->update(static function (array $document): array {
            $document['profiles']['test-cas'] = ['provider' => 'cas', 'profile_name' => 'Test CAS',
                'config' => ['base_url' => 'https://sso.example.test'], 'mapping' => ['login_attribute' => 'uid']];
            return $document;
        });
        (new AuthConfigurationSynchronizer($this->database, $this->store))->synchronize();
        $this->database->table('auth_users')->insert(['login_identifier' => 'test-user']);
        $id = $this->database->table('auth_provider_profiles')->first()['id'];
        $this->database->table('auth_user_identities')->insert([
            'user_id' => 1, 'provider_profile_id' => $id, 'subject_identifier' => 'test-subject',
        ]);
        $resolver = new AuthBackupIdentityResolver($this->database);
        $this->provider = new AuthBackupProvider($this->database, $resolver, $this->store);
        $bridge = new class ($this->root) extends ComposerBridge {
            public function __construct(private readonly string $testRoot)
            {
            }

            /**
             * EN: Replaces the root in the complete package descriptor.
             *
             * HR: Zamjenjuje korijen u potpunom opisu paketa.
             */
            public function getRootPackage(): array
            {
                return array_replace(parent::getRootPackage(), ['install_path' => $this->testRoot]);
            }
        };
        $this->backup = new BackupConfig(
            $config,
            $bridge,
            dirname(__DIR__, 3) . '/vendor/aaieduhr/heartphrame-module-backup',
        );
    }

    /**
     * EN: Removes only its own archives and restores the environment.
     *
     * HR: Uklanja samo vlastite arhive i vraća env.
     */
    protected function tearDown(): void
    {
        foreach ($this->readers as $reader) {
            $reader->close();
        }

        foreach ($this->environment as $key => $value) {
            putenv($value === false ? $key : $key . '=' . $value);
        }

        (new BackupFilesystem())->removeDirectory($this->root);
    }

    /**
     * EN: Encrypted restore recovers JSON and correct user links without legacy tables.
     *
     * HR: Šifrirani povrat vraća JSON i ispravne korisničke veze bez starih tablica.
     */
    public function testEncryptedConfigurationRoundTrip(): void
    {
        $expected = $this->store->read();
        $reader = $this->archive();
        $this->store->update(static function (array $document): array {
            $document['profiles']['test-cas']['profile_name'] = 'Changed';
            return $document;
        });
        $context = $this->context();
        $this->assertTrue($this->provider->preflight($context, $reader)->isAllowed());
        $this->database->transaction(function () use ($context, $reader): void {
            $this->provider->prepareImport($context, $reader);
            $this->provider->import($context, $reader);
            $this->provider->finalizeImport($context, $reader);
        });
        $this->assertSame($expected, $this->store->read());
        $this->assertFalse($this->database->schema()->hasTable('auth_provider_settings'));
        $this->assertFalse($this->database->schema()->hasColumn('auth_provider_profiles', 'config_json'));
        $profile = $this->database->table('auth_provider_profiles')->first();
        $this->assertSame(
            (int)$profile['id'],
            (int)$this->database->table('auth_user_identities')->first()['provider_profile_id'],
        );
    }

    /**
     * EN: Failure after publishing the file restores both the file and database.
     *
     * HR: Neuspjeh nakon objave datoteke vraća datoteku i bazu.
     */
    public function testRollbackRestoresPreviousConfiguration(): void
    {
        $reader = $this->archive();
        $this->store->update(static function (array $document): array {
            $document['login']['login_duration_days'] = 7;
            return $document;
        });
        $previous = $this->store->read();
        $context = $this->context();
        try {
            $this->database->transaction(function () use ($context, $reader): never {
                $this->provider->prepareImport($context, $reader);
                $this->provider->import($context, $reader);
                $this->provider->finalizeImport($context, $reader);
                throw new \RuntimeException('Simulated later provider failure');
            });
        } catch (\RuntimeException) {
            $this->provider->abortImport($context);
        }

        $this->assertSame($previous, $this->store->read());
        $this->assertCount(1, $this->database->table('auth_users')->get());
        $this->assertCount(1, $this->database->table('auth_user_identities')->get());
    }

    /**
     * EN: Uses an encrypted isolated archive with a test passphrase.
     *
     * HR: Koristi šifriranu izoliranu arhivu s probnom lozinkom.
     */
    private function archive(): BackupArchiveReader
    {
        $scope = new BackupScope(BackupScope::SITE);
        $writer = new BackupArchiveWriter(
            $this->backup,
            new BackupFilesystem(),
            $scope,
            [],
            'Auth test',
            'fictional-test-passphrase',
        );
        $writer->beginProvider($this->provider->metadata());

        $this->provider->export(new BackupExportContext($scope, ['auth']), $writer);
        $reader = new BackupArchiveReader(
            $writer->finish(),
            $this->backup,
            new BackupFilesystem(),
            'fictional-test-passphrase',
        );
        $this->readers[] = $reader;
        return $reader;
    }

    /**
     * EN: Replace remains scoped to the test database.
     *
     * HR: Replace ostaje ograničen na testnu bazu.
     */
    private function context(): BackupImportContext
    {
        return new BackupImportContext(
            new BackupScope(BackupScope::SITE),
            BackupImportContext::CONFLICT_REPLACE,
            ['auth'],
        );
    }
}
