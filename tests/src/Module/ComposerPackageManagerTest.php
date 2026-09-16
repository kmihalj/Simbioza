<?php

declare(strict_types=1);

namespace Tests\Module;

use App\Module\CommandResult;
use App\Module\ComposerPackageManager;
use App\Module\ModuleCatalog;
use App\Module\ProcessRunnerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

#[CoversClass(ComposerPackageManager::class)]
#[CoversClass(ModuleCatalog::class)]
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
