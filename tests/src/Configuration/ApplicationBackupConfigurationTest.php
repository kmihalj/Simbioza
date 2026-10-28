<?php

declare(strict_types=1);

namespace Tests\Configuration;

use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveReader;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveWriter;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupConfig;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem;
use AaiEduHr\HeartPhrameModuleBackup\Service\StructuredConfigBackupProvider;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupExportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupImportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupProviderMetadata;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupScope;
use App\Configuration\ApplicationBackupConfiguration;
use App\Configuration\ConfigurationPaths;
use HeartPhrame\Bridge\ComposerBridge;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * EN: Transfers only fictional settings, never development data/secrets.
 *
 * HR: Prenosi samo izmišljene postavke, nikada razvojnu bazu/tajne.
 */
#[CoversNothing]
final class ApplicationBackupConfigurationTest extends TestCase
{
    /**
     * EN: Minimal installations export policy only for their enabled optional modules.
     *
     * HR: Minimalne instalacije izvoze pravila samo svojih uključenih opcionalnih modula.
     */
    public function testAbsentModulePoliciesDoNotEnterTheRestorePlan(): void
    {
        $definitions = (require dirname(__DIR__, 3) . '/config/backup/backup.php')['application_configuration'];
        $resolver = new ApplicationBackupConfiguration(ConfigurationPaths::fromEnvironment($this->root));
        $this->assertSame(['application'], array_column($resolver->resolve($definitions, []), 'key'));
        $this->assertSame(['application', 'api-policy'], array_column($resolver->resolve(
            $definitions,
            ['aaieduhr/heartphrame-module-api'],
        ), 'key'));
        $this->assertSame(['application', 'workspace-search-policy'], array_column($resolver->resolve(
            $definitions,
            ['aaieduhr/simbioza-module-workspace-search'],
        ), 'key'));
    }

    private string $root;

    /** @var array<string,string|false> */
    private array $environment = [];

    /**
     * EN: Isolates the base and override directories.
     *
     * HR: Izolira bazni i override direktorij.
     */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/simbioza-application-backup-' . bin2hex(random_bytes(6));
        mkdir($this->root . '/base', 0700, true);
        mkdir($this->root . '/override', 0700);
        foreach (['HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $key) {
            $this->environment[$key] = getenv($key);
        }

        putenv('HPH_CONFIG_PATH=' . $this->root . '/base');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/override');
    }

    /**
     * EN: Removes only owned fixtures and restores the environment.
     *
     * HR: Briše samo vlastite fixture i vraća okruženje.
     */
    protected function tearDown(): void
    {
        foreach ($this->environment as $key => $value) {
            putenv($value === false ? $key : $key . '=' . $value);
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $this->root,
            \FilesystemIterator::SKIP_DOTS,
        ), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) {
            $item->isDir() && !$item->isLink() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($this->root);
    }

    /**
     * EN: Restore selects the highest layer, preserves local keys, and never replaces app.php with data.
     *
     * HR: Restore bira najviši sloj, čuva lokalne ključeve i ne pretvara app.php u podatke.
     */
    public function testLayeredExportRestoreAndAbort(): void
    {
        file_put_contents(
            $this->root . '/base/app.php',
            '<?php return ["name" => "Base", "modules" => ["enabled" => ["base"]]];',
        );
        file_put_contents(
            $this->root . '/override/app.php',
            '<?php return ["name" => "Saved", "modules" => ["enabled" => ["saved"]]];',
        );
        file_put_contents(
            $this->root . '/base/installation.php',
            '<?php return ["local_only" => "retained", "session_name" => "TARGET_SESSION", '
                . '"session_options" => ["local" => true]];',
        );
        file_put_contents(
            $this->root . '/base/modules.php',
            '<?php return ["enabled" => ["base"], "removed" => ["wrong"]];',
        );
        file_put_contents(
            $this->root . '/override/modules.php',
            '<?php return ["enabled" => ["target"], "removed" => ["calendar"]];',
        );
        chmod($this->root . '/override/modules.php', 0640);
        $moduleOriginal = file_get_contents($this->root . '/override/modules.php');
        $code = file_get_contents($this->root . '/base/app.php');
        [$provider, $backup] = $this->provider();
        $scope = new BackupScope(BackupScope::SITE);
        $writer = new BackupArchiveWriter($backup, new BackupFilesystem(), $scope, [], 'Fixture');
        $writer->beginProvider($provider->metadata());

        $provider->export(new BackupExportContext($scope, ['application-config']), $writer);
        $reader = new BackupArchiveReader($writer->finish(), $backup, new BackupFilesystem());
        $context = new BackupImportContext($scope, BackupImportContext::CONFLICT_MERGE, ['application-config']);
        try {
            $records = iterator_to_array($reader->records('application-config', 'configuration'));
            $this->assertSame('Saved', $records[0]['values']['name']);
            $this->assertSame(['saved'], $records[0]['values']['modules']['enabled']);
            $this->assertTrue($provider->preflight($context, $reader)->isAllowed());
            $provider->prepareImport($context, $reader);
            $provider->import($context, $reader);
            $provider->finalizeImport($context, $reader);
            $installation = require $this->root . '/override/installation.php';
            $module = require $this->root . '/override/modules.php';
            $this->assertSame('Saved', $installation['name']);
            $this->assertSame('retained', $installation['local_only']);
            $this->assertSame('TARGET_SESSION', $installation['session_name']);
            $this->assertSame(['saved'], $module['enabled']);
            $this->assertSame(['calendar'], $module['removed']);
            $this->assertSame(0640, fileperms($this->root . '/override/modules.php') & 0777);
            $this->assertSame(0660, fileperms($this->root . '/override/installation.php') & 0777);
            $this->assertSame($code, file_get_contents($this->root . '/base/app.php'));
            $provider->abortImport($context);
            $this->assertFileDoesNotExist($this->root . '/override/installation.php');
            $this->assertSame($moduleOriginal, file_get_contents($this->root . '/override/modules.php'));
            $this->assertSame(0640, fileperms($this->root . '/override/modules.php') & 0777);
        } finally {
            $reader->close();
        }
    }

    /**
     * EN: The local allowlist, not the archive, defines allowed paths.
     *
     * HR: Lokalni popis, ne arhiv, definira dopuštene putanje.
     */
    public function testDefinitionTraversalIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new ApplicationBackupConfiguration(ConfigurationPaths::fromEnvironment($this->root)))->resolve([
            ['key' => 'bad', 'file' => '../outside.php', 'include_keys' => ['value']],
        ]);
    }

    /**
     * EN: A link at the declared directory uses its verified physical target.
     *
     * HR: Symlink samog deklariranog direktorija koristi provjeren fizički cilj.
     */
    public function testDeclaredDirectoryLinkUsesPhysicalRestoreTarget(): void
    {
        symlink($this->root . '/override', $this->root . '/declared');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/declared');
        $definitions = (new ApplicationBackupConfiguration(ConfigurationPaths::fromEnvironment($this->root)))->resolve([
            ['key' => 'modules', 'file' => 'modules.php', 'include_keys' => ['enabled']],
        ]);
        $this->assertSame(
            realpath($this->root . '/override') . '/modules.php',
            $definitions[0]['restore_targets'][0]['path'],
        );
        $this->assertSame($this->root . '/declared/modules.php', $definitions[0]['read_paths'][1]);
    }

    /**
     * EN: Preflight never writes a lower layer when the final file is read-only or a link.
     *
     * HR: Preflight ne zapisuje niži sloj kada je završna datoteka read-only ili poveznica.
     */
    public function testReadOnlyAndLinkedTargetsAreRejected(): void
    {
        file_put_contents(
            $this->root . '/base/app.php',
            '<?php return ["name" => "Saved", "modules" => ["enabled" => []]];',
        );
        file_put_contents($this->root . '/base/modules.php', '<?php return ["enabled" => []];');
        symlink($this->root . '/base/modules.php', $this->root . '/override/modules.php');
        [$provider, $backup] = $this->provider();
        [$reader, $context] = $this->archive($provider, $backup);
        try {
            $this->assertFalse($provider->preflight($context, $reader)->isAllowed());
            unlink($this->root . '/override/modules.php');
            file_put_contents($this->root . '/override/modules.php', '<?php return ["enabled" => []];');
            chmod($this->root . '/override/modules.php', 0440);
            if (!is_writable($this->root . '/override/modules.php')) {
                $this->assertFalse($provider->preflight($context, $reader)->isAllowed());
            }

            $this->assertFileDoesNotExist($this->root . '/override/installation.php');
            $this->assertFileDoesNotExist($this->root . '/override/.modules.lock');
        } finally {
            $reader->close();
        }
    }

    /**
     * EN: An active GUI lock blocks restore before any mutation.
     *
     * HR: Aktivni GUI lock sprječava restore prije bilo kakve izmjene.
     */
    public function testSharedLockBlocksRestore(): void
    {
        file_put_contents(
            $this->root . '/base/app.php',
            '<?php return ["name" => "Saved", "modules" => ["enabled" => []]];',
        );
        [$provider, $backup] = $this->provider();
        [$reader, $context] = $this->archive($provider, $backup);
        $lock = fopen($this->root . '/override/.modules.lock', 'c+');
        $this->assertIsResource($lock);
        flock($lock, LOCK_EX);
        try {
            $this->expectException(\AaiEduHr\HeartPhrameModuleBackup\Exception\BackupException::class);
            $provider->prepareImport($context, $reader);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
            $reader->close();
            $this->assertFileDoesNotExist($this->root . '/override/installation.php');
            $this->assertFileDoesNotExist($this->root . '/override/modules.php');
        }
    }

    /**
     * EN: Prepares a portable archive containing fictional data.
     *
     * HR: Priprema prenosivi arhiv s izmišljenim podacima.
     *
     * @return array{BackupArchiveReader,BackupImportContext}
     */
    private function archive(StructuredConfigBackupProvider $provider, BackupConfig $backup): array
    {
        $scope = new BackupScope(BackupScope::SITE);
        $writer = new BackupArchiveWriter($backup, new BackupFilesystem(), $scope, [], 'Fixture');
        $writer->beginProvider($provider->metadata());

        $provider->export(new BackupExportContext($scope, ['application-config']), $writer);
        return [new BackupArchiveReader($writer->finish(), $backup, new BackupFilesystem()),
            new BackupImportContext($scope, BackupImportContext::CONFLICT_MERGE, ['application-config'])];
    }

    /**
     * EN: Builds the real provider without the application GUI or database.
     *
     * HR: Sastavlja stvarni provider bez aplikacijskog GUI-ja ili baze.
     *
     * @return array{StructuredConfigBackupProvider,BackupConfig}
     */
    private function provider(): array
    {
        $definitions = require dirname(__DIR__, 3) . '/config/backup/backup.php';
        $files = (new ApplicationBackupConfiguration(ConfigurationPaths::fromEnvironment($this->root)))
            ->resolve($definitions['application_configuration']);
        $provider = new StructuredConfigBackupProvider(new BackupProviderMetadata(
            'application-config',
            'heartphrame/simbioza',
            1,
            ['en' => 'Settings', 'hr' => 'Postavke'],
        ), new BackupFilesystem(), $files);
        $config = new Config(new Helper(), ['backup' => [
            'archive_dir' => $this->root . '/archives', 'staging_dir' => $this->root . '/staging',
        ]], $this->root);
        return [$provider, new BackupConfig(
            $config,
            new ComposerBridge(),
            dirname(__DIR__, 3) . '/vendor/aaieduhr/heartphrame-module-backup',
        )];
    }
}
