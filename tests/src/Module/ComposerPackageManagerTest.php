<?php

declare(strict_types=1);

namespace Tests\Module;

use App\Module\CommandResult;
use App\Module\ComposerPackageManager;
use App\Module\ModuleCatalog;
use App\Module\ModuleStateStore;
use App\Module\ProcessRunnerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

#[CoversClass(ComposerPackageManager::class)]
#[CoversClass(ModuleCatalog::class)]
#[CoversClass(ModuleStateStore::class)]
#[CoversClass(CommandResult::class)]
final class ComposerPackageManagerTest extends TestCase
{
    private string $root;

    /** HR: Stvara minimalni Composer runtime za provjeru osvježavanja metapodataka. EN: Creates a minimal Composer runtime for metadata refresh testing. */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/simbioza-composer-package-test-' . bin2hex(random_bytes(8));
        if (!mkdir($this->root . '/vendor/composer', 0770, true)) {
            throw new RuntimeException('Unable to create Composer package test directory.');
        }

        file_put_contents($this->root . '/composer.json', "{}\n");
    }

    /** HR: Uklanja samo datoteke privremene Composer instalacije. EN: Removes only the temporary Composer installation files. */
    protected function tearDown(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $entry) {
            if (!$entry instanceof SplFileInfo) {
                continue;
            }

            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }

        rmdir($this->root);
    }

    /**
     * HR: Nakon promjene koju je napravio drugi proces sljedeće čitanje mora
     *     odmah prikazati novoinstalirani paket u postojećem FPM procesu.
     * EN: After another process changes packages, the next read must expose the
     *     newly installed package immediately in the existing FPM process.
     */
    public function testReadsPackagesChangedByAnotherProcessWithoutStaleGlobalCache(): void
    {
        $this->writeInstalledMetadata([]);
        $manager = new ComposerPackageManager(
            new ModuleCatalog(),
            new class implements ProcessRunnerInterface {
                /** HR: Ovaj test ne pokreće procese. EN: This test never launches processes. */
                public function run(array $command, string $workingDirectory): CommandResult
                {
                    return new CommandResult(0, '', '');
                }
            },
            $this->root,
        );

        $this->assertFalse($manager->isInstalled('audit'));
        $this->writeInstalledMetadata(['aaieduhr/heartphrame-module-audit']);
        $this->assertTrue($manager->isInstalled('audit'));
    }

    /**
     * HR: Svježa mapa mora učitati klasu paketa dodanog nakon početka zahtjeva.
     * EN: A fresh map must load a package class added after the request started.
     */
    public function testRegistersAutoloadMapGeneratedByAnotherProcess(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $namespace = 'SimbiozaSetupDynamic' . $suffix;
        $source = $this->root . '/dynamic-src';
        if (!mkdir($source, 0770, true)) {
            throw new RuntimeException('Unable to create dynamic package source directory.');
        }

        file_put_contents(
            $source . '/LoadedClass.php',
            "<?php\nnamespace {$namespace};\nfinal class LoadedClass {}\n",
        );
        $this->writeAutoloadMap('autoload_psr4.php', [$namespace . '\\' => [$source]]);
        $this->writeAutoloadMap('autoload_namespaces.php', []);
        $this->writeAutoloadMap('autoload_classmap.php', []);
        $this->writeAutoloadMap('autoload_files.php', []);

        $manager = new ComposerPackageManager(
            new ModuleCatalog(),
            new class implements ProcessRunnerInterface {
                /** HR: Ovaj test ne pokreće procese. EN: This test never launches processes. */
                public function run(array $command, string $workingDirectory): CommandResult
                {
                    return new CommandResult(0, '', '');
                }
            },
            $this->root,
        );

        $manager->registerCurrentAutoloader();

        $this->assertTrue(class_exists($namespace . '\\LoadedClass'));
    }

    /**
     * HR: Razvojno ograničenje paketa preživljava uklanjanje i ponovnu instalaciju.
     * EN: A development package constraint survives removal and reinstallation.
     */
    public function testRemovalAndReinstallationPreserveCurrentConstraint(): void
    {
        $package = 'aaieduhr/heartphrame-module-api';
        file_put_contents(
            $this->root . '/composer.json',
            json_encode(['require' => [$package => 'dev-main']], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . "\n",
        );
        $this->writeInstalledMetadata([$package]);
        $state = new ModuleStateStore($this->root, new ModuleCatalog());
        $state->initialize(['api']);

        $runner = new class ($this->root, $package) implements ProcessRunnerInterface {
            /** @var list<string|null> */
            public array $observedConstraints = [];

            /** HR: Prima probni korijen i paket. EN: Receives the test root and package. */
            public function __construct(private readonly string $root, private readonly string $package)
            {
            }

            /**
             * HR: Oponaša Composerovu osvježenu installed.json datoteku.
             * EN: Emulates Composer's refreshed installed.json file.
             */
            public function run(array $command, string $workingDirectory): CommandResult
            {
                $manifest = json_decode(
                    (string)file_get_contents($this->root . '/composer.json'),
                    true,
                    512,
                    JSON_THROW_ON_ERROR,
                );
                $constraint = is_array($manifest['require'] ?? null)
                && is_string($manifest['require'][$this->package] ?? null)
                ? $manifest['require'][$this->package]
                : null;
                $this->observedConstraints[] = $constraint;
                $packages = $constraint === null ? [] : [[
                    'name' => $this->package,
                    'version' => 'dev-main',
                    'type' => 'heartphrame-module',
                ]];
                file_put_contents(
                    $this->root . '/vendor/composer/installed.json',
                    json_encode(['packages' => $packages], JSON_THROW_ON_ERROR) . "\n",
                );

                return new CommandResult(0, '', '');
            }
        };
        $manager = new ComposerPackageManager(new ModuleCatalog(), $runner, $this->root, $state);

        $manager->uninstall('api');
        $this->assertSame('dev-main', $state->requirementConstraint('api'));
        $manager->install('api');

        $this->assertSame([null, 'dev-main'], $runner->observedConstraints);
        $manifest = json_decode(
            (string)file_get_contents($this->root . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->assertSame('dev-main', $manifest['require'][$package] ?? null);
    }

    /**
     * HR: GUI instalacija novog opcionalnog modula mora uzeti ograničenje iz
     *     manifesta izdanja, a ne iz ručno održavanog zastarjelog popisa.
     * EN: GUI installation of a new optional module must use the release
     *     manifest constraint rather than a stale hand-maintained list.
     */
    public function testInstallsAccessibilityUsingReleaseManifestConstraint(): void
    {
        $package = 'aaieduhr/heartphrame-module-accessibility';
        $this->writeInstalledMetadata([]);
        $runner = new class ($this->root, $package) implements ProcessRunnerInterface {
            /** HR: Pamti Composer naredbu. EN: Records the Composer command. */
            public array $command = [];

            /** HR: Prima izolirani korijen i paket. EN: Receives the isolated root and package. */
            public function __construct(private readonly string $root, private readonly string $package)
            {
            }

            /** HR: Oponaša uspješnu instalaciju paketa. EN: Simulates a successful package installation. */
            public function run(array $command, string $workingDirectory): CommandResult
            {
                $this->command = $command;
                file_put_contents(
                    $this->root . '/vendor/composer/installed.json',
                    json_encode(['packages' => [[
                        'name' => $this->package,
                        'version' => '0.1.1',
                        'type' => 'heartphrame-module',
                    ]]], JSON_THROW_ON_ERROR) . "\n",
                );

                return new CommandResult(0, '', '');
            }
        };

        $catalog = new ModuleCatalog();
        $manager = new ComposerPackageManager($catalog, $runner, $this->root);
        $manager->install('accessibility');

        $manifest = json_decode(
            (string)file_get_contents($this->root . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->assertSame($catalog->constraintFor('accessibility'), $manifest['require'][$package]);
        $this->assertContains('update', $runner->command);
        $this->assertContains($package, $runner->command);
        $this->assertTrue($manager->isInstalled('accessibility'));
    }

    /** HR: Svaki opcionalni modul mora imati izdano ograničenje. EN: Every optional module must have a release constraint. */
    public function testEveryOptionalModuleHasReleaseConstraint(): void
    {
        $catalog = new ModuleCatalog();
        $release = json_decode(
            (string)file_get_contents(dirname(__DIR__, 3) . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        foreach ($catalog->optionalSlugs() as $slug) {
            $definition = $catalog->definitionFor($slug);
            $this->assertSame(
                $release['extra']['simbioza']['optional-modules'][$definition['package']],
                $catalog->constraintFor($slug),
                $slug,
            );
        }
    }

    /**
     * HR: Zapisuje valjani minimalni `installed.php` za zadane pakete.
     * EN: Writes valid minimal `installed.php` metadata for the requested packages.
     *
     * @param list<string> $packages
     */
    private function writeInstalledMetadata(array $packages): void
    {
        $installed = [];
        foreach ($packages as $package) {
            $installed[] = [
                'name' => $package,
                'version' => '1.0.0.0',
                'type' => 'heartphrame-module',
            ];
        }

        file_put_contents(
            $this->root . '/vendor/composer/installed.json',
            json_encode(['packages' => $installed], JSON_THROW_ON_ERROR) . "\n",
        );
    }

    /** HR: Zapisuje jednu generiranu Composer autoload mapu. EN: Writes one generated Composer autoload map. */
    private function writeAutoloadMap(string $file, array $map): void
    {
        file_put_contents(
            $this->root . '/vendor/composer/' . $file,
            '<?php return ' . var_export($map, true) . ";\n",
        );
    }
}
