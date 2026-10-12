<?php

declare(strict_types=1);

namespace Tests\Localization;

use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveReader;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveWriter;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupConfig;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem;
use AaiEduHr\HeartPhrameModuleBackup\Service\StructuredConfigBackupProvider;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupExportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupImportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupProviderMetadata;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupScope;
use App\Configuration\ConfigurationPaths;
use App\Localization\ApplicationLanguageBackupProvider;
use App\Localization\LanguagePackManager;
use App\Localization\RuntimeLanguageStorage;
use App\Module\ModuleCatalog;
use HeartPhrame\Bridge\ComposerBridge;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * EN: Exercises real archive round trips and rollback using fictional installations only.
 *
 * HR: Provjerava stvarne arhivske round tripove i rollback samo na izmišljenim instalacijama.
 */
#[CoversNothing]
final class ApplicationLanguageBackupProviderTest extends TestCase
{
    private string $root;

    /** @var array<string,string|false> */
    private array $environment = [];

    /** @var list<BackupArchiveReader> */
    private array $readers = [];

    /**
     * EN: Creates source and destination roots without HFC data or credentials.
     *
     * HR: Stvara izvorni i odredišni root bez HFC podataka ili vjerodajnica.
     */
    protected function setUp(): void
    {
        foreach (['HPH_APP_PATH', 'HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $key) {
            $this->environment[$key] = getenv($key);
        }

        $this->root = sys_get_temp_dir() . '/simbioza-language-backup-' . bin2hex(random_bytes(8));
        foreach (['source', 'target'] as $name) {
            $root = $this->root . '/' . $name;
            mkdir($root . '/config', 0770, true);
            mkdir($root . '/lang', 0770);
            mkdir($root . '/data', 0770);
            file_put_contents($root . '/lang/en.php', '<?php return ["hello"=>"stock EN"];');
            file_put_contents(
                $root . '/config/installation.php',
                '<?php return ["supported_locales"=>["en"],"primary_locale"=>"en"];',
            );
            $this->registry($root, ['en' => 'English', 'de' => 'Deutsch']);
            $storage = new RuntimeLanguageStorage($root);
            $storage->install('de', ['hello' => $name === 'source' ? 'Guten Tag' : 'before']);
            $storage->write(
                'flags/de.svg',
                '<svg xmlns="http://www.w3.org/2000/svg"><rect width="3" height="2"/></svg>',
            );
            $storage->writeVersions(['de' => [
                'version' => '2026.10.12.1', 'sha256' => str_repeat($name === 'source' ? 'a' : 'b', 64),
            ]]);
        }
    }

    /**
     * EN: Closes readers before removing only the owned test roots.
     *
     * HR: Zatvara readere prije uklanjanja samo vlastitih testnih rootova.
     */
    protected function tearDown(): void
    {
        foreach ($this->readers as $reader) {
            $reader->close();
        }

        (new BackupFilesystem())->removeDirectory($this->root);
        foreach ($this->environment as $key => $value) {
            putenv($value === false ? $key : $key . '=' . $value);
        }
    }

    /**
     * EN: Produces normalized fictional registry metadata.
     *
     * HR: Stvara normalizirane izmišljene metapodatke registra.
     *
     * @param array<string,string> $locales
     */
    private function registry(string $root, array $locales): void
    {
        $registry = [];
        foreach ($locales as $locale => $name) {
            $registry[$locale] = ['names' => [$locale => $name], 'native_name' => $name, 'flag' => $locale . '.svg'];
        }

        file_put_contents($root . '/config/languages.php', '<?php return ' . var_export($registry, true) . ';');
    }

    /**
     * EN: Builds the same application wrapper as the real container, without config-lock duplication.
     *
     * HR: Stvara isti aplikacijski wrapper kao stvarni container, bez ponavljanja config locka.
     */
    private function provider(string $name): ApplicationLanguageBackupProvider
    {
        $root = $this->root . '/' . $name;
        putenv('HPH_APP_PATH=' . $root);
        putenv('HPH_CONFIG_PATH=' . $root . '/config');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=');
        $generic = new StructuredConfigBackupProvider(new BackupProviderMetadata(
            'application-config',
            'aaieduhr/simbioza',
            3,
            ['en' => 'Settings', 'hr' => 'Postavke'],
        ), new BackupFilesystem(), [[
            'key' => 'application', 'path' => $root . '/config/installation.php',
            'include_keys' => ['supported_locales', 'primary_locale'],
        ]]);
        return new ApplicationLanguageBackupProvider(
            $generic,
            ConfigurationPaths::fromEnvironment($root),
            new LanguagePackManager($root, new ModuleCatalog()),
            new RuntimeLanguageStorage($root),
        );
    }

    /**
     * EN: Archives contain data, not deployment paths or executable PHP catalogues.
     *
     * HR: Arhive sadrže podatke, ne deployment putanje niti izvršive PHP kataloge.
     *
     * @param array<string,mixed>|null $invalid
     */
    private function archive(?array $invalid = null): BackupArchiveReader
    {
        $root = $this->root . '/source';
        $helper = new Helper();
        $bridge = new class ($root) extends ComposerBridge {
            public function __construct(private readonly string $root)
            {
            }

            /**
             * EN: Uses a fictional root with the real descriptor shape.
             *
             * HR: Koristi izmišljeni root sa stvarnim oblikom descriptora.
             */
            public function getRootPackage(): array
            {
                return array_replace(parent::getRootPackage(), ['install_path' => $this->root]);
            }
        };
        $config = new BackupConfig(
            new Config($helper, [], $root),
            $bridge,
            dirname(__DIR__, 3) . '/vendor/aaieduhr/heartphrame-module-backup',
        );
        $scope = new BackupScope(BackupScope::SITE);
        $writer = new BackupArchiveWriter($config, new BackupFilesystem(), $scope, [], 'Synthetic languages');
        $provider = $this->provider('source');
        $writer->beginProvider($provider->metadata());
        if ($invalid === null) {
            $provider->export(new BackupExportContext($scope, ['application-config']), $writer);
        } else {
            $writer->writeRecord('application-config', 'configuration', ['key' => 'application', 'values' => []]);
            $writer->writeRecord('application-config', 'languages', $invalid);
        }

        $reader = new BackupArchiveReader($writer->finish(), $config, new BackupFilesystem());
        $this->readers[] = $reader;
        return $reader;
    }

    /**
     * EN: Uses explicit site replacement, never a live database.
     *
     * HR: Koristi izričiti site replace, nikada stvarnu bazu.
     */
    private function context(string $mode = BackupImportContext::CONFLICT_REPLACE): BackupImportContext
    {
        return new BackupImportContext(new BackupScope(BackupScope::SITE), $mode, ['application-config']);
    }

    /**
     * EN: Another root receives registry, JSON, SVG, and revisions without source-code writes.
     *
     * HR: Drugi root dobiva registar, JSON, SVG i revizije bez zapisivanja izvornog koda.
     */
    public function testCrossSiteRoundTripPreservesStockCode(): void
    {
        $reader = $this->archive();
        $target = $this->provider('target');
        $context = $this->context();
        $stock = file_get_contents($this->root . '/target/lang/en.php');
        $this->assertTrue($target->preflight($context, $reader)->isAllowed());
        $target->prepareImport($context, $reader);
        $target->import($context, $reader);
        $target->finalizeImport($context, $reader);
        $target->completeImport($context);

        $storage = new RuntimeLanguageStorage($this->root . '/target');
        $this->assertSame('Guten Tag', $storage->catalog('de')['hello']);
        $this->assertSame(str_repeat('a', 64), $storage->versions()['de']['sha256']);
        $this->assertSame($stock, file_get_contents($this->root . '/target/lang/en.php'));
        $this->assertFileDoesNotExist($this->root . '/target/lang/de.php');
        $this->assertStringContainsString('<svg', (string)$storage->bytes('flags/de.svg'));
    }

    /**
     * EN: Incoming bundled-only locales replace destination overlays even during merge.
     * Local-only locales remain; absence of an overlay is authoritative for an incoming locale.
     *
     * HR: Dolazni isključivo paketni jezici zamjenjuju odredišne overlaye i pri mergeu.
     * Lokalni jezici ostaju; odsutnost overlaya mjerodavna je za dolazni jezik.
     */
    public function testMergeRestoresBundledOnlyLocaleWithoutTargetOverlay(): void
    {
        $storage = new RuntimeLanguageStorage($this->root . '/target');
        $storage->install('en', ['hello' => 'target override']);
        $storage->write('flags/en.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');

        $reader = $this->archive();
        $target = $this->provider('target');
        $context = $this->context(BackupImportContext::CONFLICT_MERGE);
        $target->prepareImport($context, $reader);
        $target->import($context, $reader);
        $target->finalizeImport($context, $reader);
        $target->completeImport($context);
        $this->assertFalse($storage->installed('en'));
        $this->assertNull($storage->bytes('flags/en.svg'));
        $this->assertSame('Guten Tag', $storage->catalog('de')['hello']);
    }

    /**
     * EN: A later provider failure rolls back JSON, flags, registry, revisions, and modes.
     *
     * HR: Greška kasnijeg providera vraća JSON, zastavice, registar, revizije i prava.
     */
    public function testAbortRollsBackEveryResourceAndAllowsNextImport(): void
    {
        $reader = $this->archive();
        $target = $this->provider('target');
        $storage = new RuntimeLanguageStorage($this->root . '/target');
        chmod($storage->path('catalogs/de.json'), 0600);
        $previous = [];
        foreach (['catalogs/de.json', 'flags/de.svg', 'repository-versions.json'] as $file) {
            $previous[$file] = $storage->bytes($file);
        }

        $registry = file_get_contents($this->root . '/target/config/languages.php');
        $context = $this->context();
        $target->prepareImport($context, $reader);
        $target->import($context, $reader);
        $target->finalizeImport($context, $reader);
        $target->abortImport($context);
        foreach ($previous as $file => $bytes) {
            $this->assertSame($bytes, $storage->bytes($file));
        }

        $this->assertSame($registry, file_get_contents($this->root . '/target/config/languages.php'));
        $this->assertSame(0600, fileperms($storage->path('catalogs/de.json')) & 0777);
        $target->prepareImport($context, $reader);
        $target->abortImport($context);
    }

    /**
     * EN: Merge preserves local-only locales; explicit replace removes their runtime data.
     *
     * HR: Merge čuva lokalne jezike; izričiti replace uklanja njihove runtime podatke.
     */
    public function testMergeAndReplacePolicies(): void
    {
        $reader = $this->archive();
        $this->registry($this->root . '/target', ['en' => 'English', 'de' => 'Deutsch', 'it' => 'Italiano']);
        $storage = new RuntimeLanguageStorage($this->root . '/target');
        $storage->install('it', ['hello' => 'Ciao']);
        foreach ([BackupImportContext::CONFLICT_MERGE, BackupImportContext::CONFLICT_REPLACE] as $mode) {
            $provider = $this->provider('target');
            $context = $this->context($mode);
            $provider->prepareImport($context, $reader);
            $provider->import($context, $reader);
            $provider->finalizeImport($context, $reader);
            $provider->completeImport($context);
            $this->assertSame($mode === BackupImportContext::CONFLICT_MERGE, $storage->installed('it'));
        }
    }

    /**
     * EN: Malicious locale paths and executable SVG are rejected without modifying the destination.
     *
     * HR: Zlonamjerne jezične putanje i izvršivi SVG odbijaju se bez izmjene odredišta.
     */
    public function testUntrustedRecordsFailBeforeMutation(): void
    {
        $valid = ['locale' => 'de', 'definition' => ['names' => ['de' => 'Deutsch'],
            'native_name' => 'Deutsch', 'flag' => 'de.svg'], 'translations' => ['hello' => 'test'],
            'flag_svg' => null, 'revision' => null];
        foreach (
            [array_replace($valid, ['locale' => '../../escape']),
            array_replace($valid, ['flag_svg' => '<svg><script>alert(1)</script></svg>'])] as $record
        ) {
            $reader = $this->archive($record);
            $provider = $this->provider('target');
            $this->assertFalse($provider->preflight($this->context(), $reader)->isAllowed());
            $this->assertSame('before', (new RuntimeLanguageStorage($this->root . '/target'))->catalog('de')['hello']);
        }
    }
}
