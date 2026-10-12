<?php

declare(strict_types=1);

namespace Tests\Localization;

use App\Configuration\ConfigurationPaths;
use App\Configuration\PhpConfigurationReader;
use App\Localization\LanguagePackManager;
use App\Localization\LanguageRepository;
use App\Localization\RepositoryLanguageManager;
use App\Localization\RuntimeLanguageStorage;
use App\Module\ModuleCatalog;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

#[CoversClass(LanguagePackManager::class)]
#[CoversClass(LanguageRepository::class)]
#[CoversClass(RepositoryLanguageManager::class)]
#[CoversClass(ModuleCatalog::class)]
#[UsesClass(ConfigurationPaths::class)]
#[UsesClass(PhpConfigurationReader::class)]
#[UsesClass(RuntimeLanguageStorage::class)]
final class LanguagePackManagerTest extends TestCase
{
    private string $root;

    /** @var array<string,string|false> */
    private array $environment = [];

    /**
     * EN: Prepares the smallest isolated language installation.
     *
     * HR: Priprema najmanju izoliranu instalaciju jezika.
     */
    protected function setUp(): void
    {
        foreach (['HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $name) {
            $this->environment[$name] = getenv($name);
            putenv($name);
        }

        $this->root = sys_get_temp_dir() . '/simbioza-language-test-' . bin2hex(random_bytes(8));
        foreach (['config', 'data/languages/flags', 'lang'] as $directory) {
            if (!mkdir($this->root . '/' . $directory, 0770, true) && !is_dir($this->root . '/' . $directory)) {
                throw new RuntimeException('Unable to create the language test directory.');
            }
        }

        file_put_contents(
            $this->root . '/lang/hr.php',
            "<?php return ['Dobro došli, :name!' => 'Dobro došli, :name!'];\n",
        );
        file_put_contents($this->root . '/lang/en.php', "<?php return ['Dobro došli, :name!' => 'Welcome, :name!'];\n");
        file_put_contents($this->root . '/config/languages.php', <<<'PHP'
<?php
return [
    'en' => [
        'names' => ['en' => 'English'],
        'native_name' => 'English',
        'flag' => 'en.svg',
    ],
];
PHP);
        file_put_contents($this->root . '/config/installation.php', "<?php return ['supported_locales' => ['en']];\n");
    }

    /**
     * EN: Removes only the test's temporary files.
     *
     * HR: Uklanja samo privremene datoteke testa.
     */
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

            $path = $entry->getPathname();
            $entry->isFile() || $entry->isLink() ? unlink($path) : rmdir($path);
        }

        rmdir($this->root);
        foreach ($this->environment as $name => $value) {
            is_string($value) ? putenv($name . '=' . $value) : putenv($name);
        }
    }

    /**
     * EN: Deploy refreshes a catalogue even when FPM created the old file.
     *
     * HR: Deploy osvježava katalog čak i kada je staru datoteku stvorio FPM.
     */
    public function testRepositoryRefreshReplacesReadOnlyCacheFile(): void
    {
        $directory = $this->root . '/repository';
        $this->assertTrue(mkdir($directory, 0770, true));
        $manifest = $directory . '/manifest.json';
        $contents = json_encode([
            'format' => 'simbioza-language-catalog',
            'version' => 1,
            'languages' => [[
                'locale' => 'de',
                'native_name' => 'Deutsch',
                'version' => '2026.09.25.1',
                'file' => 'packs/de.json',
                'sha256' => str_repeat('a', 64),
                'status' => 'released',
            ]],
        ], JSON_THROW_ON_ERROR);
        file_put_contents($manifest, $contents);

        $cacheDirectory = $this->root . '/data/cache';
        $this->assertTrue(mkdir($cacheDirectory, 0770, true));
        $cache = $cacheDirectory . '/language-catalog.json';
        file_put_contents($cache, '{"old":true}');
        chmod($cache, 0444);

        $repository = new LanguageRepository($this->root, 'file://' . $manifest);
        $this->assertSame('2026.09.25.1', $repository->available(true)['de']['version']);
        $this->assertSame($contents, file_get_contents($cache));
        clearstatcache(true, $cache);
        $this->assertSame(0660, fileperms($cache) & 0777);
    }

    /**
     * EN: An added locale leaves private installation configuration writable
     *     by its owner and the exclusive runtime group.
     *
     * HR: Dodani jezik ostavlja privatnu instalacijsku konfiguraciju zapisivu
     *     vlasniku i isključivoj runtime grupi.
     */
    public function testInstalledLanguageKeepsSharedRuntimeConfigurationMode(): void
    {
        $manager = new LanguagePackManager($this->root, new ModuleCatalog());
        $template = $manager->createTemplate('de', 'en', $this->root . '/data/de.json');
        $payload = json_decode((string)file_get_contents($template), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($payload);
        $payload['names'] = ['de' => 'Deutsch', 'en' => 'German'];
        $payload['native_name'] = 'Deutsch';
        $payload['flag_svg'] = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 2">'
        . '<rect width="3" height="2" fill="#000"/></svg>';
        file_put_contents($template, json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        $manager->install($template);

        $this->assertSame(0660, fileperms($this->root . '/config/installation.php') & 0777);
        $configuration = require $this->root . '/config/installation.php';
        $this->assertContains('de', $configuration['supported_locales']);
    }

    /**
     * EN: Languages use the final config layer while resources stay with the application.
     *
     * HR: Jezici koriste zadnji config sloj, a resursi ostaju uz aplikaciju.
     */
    public function testExternalLayerInstallActivationAndRemovalPreserveOtherSettings(): void
    {
        $localRegistry = (string)file_get_contents($this->root . '/config/languages.php');
        $localInstallation = (string)file_get_contents($this->root . '/config/installation.php');
        mkdir($this->root . '/external', 0770);
        mkdir($this->root . '/override', 0770);
        file_put_contents($this->root . '/external/languages.php', $localRegistry);
        file_put_contents($this->root . '/external/installation.php', '<?php return [
            "supported_locales"=>["en"], "primary_locale"=>"en", "name"=>"Keep site name",
            "timezone"=>"Europe/Zagreb", "custom"=>["base"=>true,"change"=>"before"]
        ];');
        file_put_contents($this->root . '/override/installation.php', '<?php return ["custom"=>["change"=>"after"]];');
        putenv('HPH_CONFIG_PATH=' . $this->root . '/external');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/override');
        $manager = new LanguagePackManager($this->root, new ModuleCatalog());
        $manager->install($this->completedPack($manager));
        $this->assertFileExists($this->root . '/data/languages/catalogs/de.json');
        $this->assertFileExists($this->root . '/data/languages/flags/de.svg');
        $this->assertDirectoryDoesNotExist($this->root . '/external/lang');
        $this->assertDirectoryDoesNotExist($this->root . '/override/data');
        $configuration = require $this->root . '/override/installation.php';
        $this->assertSame(['en', 'de'], $configuration['supported_locales']);
        $this->assertSame('Keep site name', $configuration['name']);
        $this->assertSame('Europe/Zagreb', $configuration['timezone']);
        $this->assertSame(['base' => true, 'change' => 'after'], $configuration['custom']);
        foreach (['languages.php', 'installation.php', '.languages.lock'] as $file) {
            $this->assertSame(0660, fileperms($this->root . '/override/' . $file) & 0777);
        }

        // EN: The lower-layer registry must not resurrect a removed locale.
        //
        // HR: Registar u nižem sloju ne smije vratiti uklonjeni jezik.
        copy($this->root . '/override/languages.php', $this->root . '/external/languages.php');
        $baseRegistry = (string)file_get_contents($this->root . '/external/languages.php');
        $baseInstallation = (string)file_get_contents($this->root . '/external/installation.php');
        $manager->setActive('de', false);
        $this->assertSame(['en'], (require $this->root . '/override/installation.php')['supported_locales']);
        $manager->setActive('de', true);
        $manager->uninstall('de');
        $this->assertSame(['en'], array_column($manager->status('en'), 'locale'));
        $this->assertArrayNotHasKey('de', require $this->root . '/override/languages.php');
        $this->assertFileDoesNotExist($this->root . '/data/languages/catalogs/de.json');
        $this->assertFileDoesNotExist($this->root . '/data/languages/flags/de.svg');
        $this->assertSame($baseRegistry, file_get_contents($this->root . '/external/languages.php'));
        $this->assertSame($baseInstallation, file_get_contents($this->root . '/external/installation.php'));
        $this->assertSame($localRegistry, file_get_contents($this->root . '/config/languages.php'));
        $this->assertSame($localInstallation, file_get_contents($this->root . '/config/installation.php'));
        $this->assertFileDoesNotExist($this->root . '/config/.languages.lock');
    }

    /**
     * EN: Read-only configuration stops installation before resource replacement.
     *
     * HR: Read-only konfiguracija zaustavlja instalaciju prije zamjene resursa.
     */
    public function testReadOnlyFinalLayerNeverWritesLowerConfigurationOrResources(): void
    {
        mkdir($this->root . '/override', 0770);
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/override');
        $manager = new LanguagePackManager($this->root, new ModuleCatalog());
        $pack = $this->completedPack($manager);
        $original = (string)file_get_contents($this->root . '/config/installation.php');
        file_put_contents($this->root . '/override/installation.php', '<?php return ["supported_locales"=>["en"]];');
        chmod($this->root . '/override/installation.php', 0400);
        try {
            $manager->install($pack);
            $this->fail('A read-only configuration must reject installation.');
        } catch (RuntimeException $runtimeException) {
            $this->assertStringContainsString('not writable', $runtimeException->getMessage());
        } finally {
            chmod($this->root . '/override/installation.php', 0660);
        }

        $this->assertSame($original, file_get_contents($this->root . '/config/installation.php'));
        $this->assertFileDoesNotExist($this->root . '/override/languages.php');
        $this->assertFileDoesNotExist($this->root . '/data/languages/catalogs/de.json');
        $this->assertFileDoesNotExist($this->root . '/data/languages/flags/de.svg');
    }

    /**
     * EN: An invalid registry fails before writing translations.
     *
     * HR: Nevaljani registar staje prije zapisivanja prijevoda.
     */
    public function testInvalidRegistryDoesNotPartiallyInstallLanguageResources(): void
    {
        $manager = new LanguagePackManager($this->root, new ModuleCatalog());
        $pack = $this->completedPack($manager);
        file_put_contents($this->root . '/config/languages.php', '<?php return false;');
        try {
            $manager->install($pack);
            $this->fail('An invalid registry must reject installation.');
        } catch (RuntimeException $runtimeException) {
            $this->assertStringContainsString('array', $runtimeException->getMessage());
        }

        $this->assertFileDoesNotExist($this->root . '/data/languages/catalogs/de.json');
        $this->assertFileDoesNotExist($this->root . '/data/languages/flags/de.svg');
    }

    /**
     * EN: Builds a valid pack exclusively from isolated test translations.
     *
     * HR: Izrađuje valjani paket isključivo iz izoliranih testnih prijevoda.
     */
    private function completedPack(LanguagePackManager $manager): string
    {
        $path = $manager->createTemplate('de', 'en', $this->root . '/data/de.json');
        $payload = json_decode((string)file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($payload);
        $payload['names'] = ['de' => 'Deutsch', 'en' => 'German'];
        $payload['native_name'] = 'Deutsch';
        file_put_contents($path, json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        return $path;
    }

    /**
     * EN: A blocked flag target rejects replacement before changing catalogue, registry, or revision.
     *
     * HR: Blokirani cilj zastavice odbija zamjenu prije promjene kataloga, registra ili revizije.
     */
    public function testReadOnlyFlagRejectsReplacementWithoutPartialState(): void
    {
        $manager = new LanguagePackManager($this->root, new ModuleCatalog());
        $pack = $this->completedPack($manager);
        $revision = ['version' => '2026.10.12.1', 'sha256' => str_repeat('a', 64)];
        $manager->install($pack, repositoryRevision: $revision);
        $storage = new RuntimeLanguageStorage($this->root);
        $before = [];
        foreach (['catalogs/de.json', 'flags/de.svg', 'repository-versions.json'] as $file) {
            $before[$file] = $storage->bytes($file);
        }
        $registry = file_get_contents($this->root . '/config/languages.php');
        $flag = $storage->path('flags/de.svg');
        chmod($flag, 0400);
        try {
            $manager->install($pack, true);
            $this->fail('Read-only flag accepted.');
        } catch (RuntimeException $error) {
            $this->assertStringContainsString('not writable', $error->getMessage());
        } finally {
            chmod($flag, 0660);
        }
        foreach ($before as $file => $bytes) {
            $this->assertSame($bytes, $storage->bytes($file));
        }
        $this->assertSame($registry, file_get_contents($this->root . '/config/languages.php'));
        $manager->install($pack, true);
        $this->assertSame([], $storage->versions());
    }

    /**
     * EN: Manually replacing a repository pack invalidates its recorded digest, without changing code.
     *
     * HR: Ručna zamjena repository paketa poništava evidentirani digest, bez izmjene koda.
     */
    public function testManualReplacementClearsRepositoryRevision(): void
    {
        $manager = new LanguagePackManager($this->root, new ModuleCatalog());
        $pack = $this->completedPack($manager);
        $revision = ['version' => '2026.10.12.1', 'sha256' => str_repeat('a', 64)];
        $manager->install($pack, repositoryRevision: $revision);
        $storage = new RuntimeLanguageStorage($this->root);
        $this->assertSame($revision, $storage->versions()['de']);
        $stock = file_get_contents($this->root . '/lang/en.php');
        $manager->install($pack, true);
        $this->assertSame([], $storage->versions());
        $this->assertSame($stock, file_get_contents($this->root . '/lang/en.php'));
        $this->assertFileDoesNotExist($this->root . '/lang/de.php');
    }

    /**
     * EN: Enabling the first extra locale without private configuration preserves default HR and EN.
     *
     * HR: Prvo uključivanje dodatnog jezika bez privatne konfiguracije čuva zadane HR i EN jezike.
     */
    public function testFirstAdditionalLanguagePreservesBundledDefaults(): void
    {
        unlink($this->root . '/config/installation.php');
        file_put_contents($this->root . '/config/languages.php', <<<'PHP'
<?php
return [
    'en' => ['names' => ['en' => 'English'], 'native_name' => 'English', 'flag' => 'en.svg'],
    'hr' => ['names' => ['hr' => 'Hrvatski'], 'native_name' => 'Hrvatski', 'flag' => 'hr.svg'],
];
PHP);
        $manager = new LanguagePackManager($this->root, new ModuleCatalog());
        $template = $manager->createTemplate('de', 'en', $this->root . '/data/de.json');
        $payload = json_decode((string)file_get_contents($template), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($payload);
        $payload['names'] = ['de' => 'Deutsch', 'en' => 'German', 'hr' => 'Njemački'];
        $payload['native_name'] = 'Deutsch';
        $payload['flag_svg'] = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 2">'
        . '<rect width="3" height="2" fill="#000"/></svg>';
        file_put_contents($template, json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        $manager->install($template);

        $configuration = require $this->root . '/config/installation.php';
        $this->assertSame(['hr', 'en', 'de'], $configuration['supported_locales']);
        $this->assertSame('hr', $configuration['primary_locale']);
    }

    /**
     * EN: Verifies a Cyrillic UTF-8 locale and the final-active-locale guard.
     *
     * HR: Potvrđuje ćirilični UTF-8 jezik i zabranu isključivanja zadnjega aktivnog jezika.
     */
    public function testCyrillicPackAndLastActiveGuard(): void
    {
        $manager = new LanguagePackManager($this->root, new ModuleCatalog());
        $template = $manager->createTemplate('sr-cyrl', 'en', $this->root . '/data/sr-cyrl.json');
        $pack = json_decode((string)file_get_contents($template), true, 512, JSON_THROW_ON_ERROR);
        $pack['names'] = ['en' => 'Serbian (Cyrillic)', 'sr-cyrl' => 'Српски'];
        $pack['native_name'] = 'Српски';
        $pack['flag_svg'] = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 2">'
        . '<rect width="3" height="2" fill="#c6363c"/></svg>';
        $pack['translations']['Dobro došli, :name!'] = 'Добро дошли, :name!';
        file_put_contents($template, json_encode($pack, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $manager->install($template);
        $this->assertSame('Српски', (require $this->root . '/config/languages.php')['sr-cyrl']['native_name']);
        $this->assertSame(
            'Добро дошли, :name!',
            (new RuntimeLanguageStorage($this->root))->catalog('sr-cyrl')['Dobro došli, :name!'],
        );
        $manager->setActive('en', false);
        $this->expectException(RuntimeException::class);
        $manager->setActive('sr-cyrl', false);
    }

    /**
     * EN: English as a template source does not change the Croatian runtime key.
     *
     * HR: Engleski kao jezik predloška ne mijenja hrvatski ključ pri izvođenju.
     */
    public function testEnglishSourcePackUsesCroatianRuntimeKeyDirectly(): void
    {
        $manager = new LanguagePackManager($this->root, new ModuleCatalog());
        $path = $manager->createTemplate('it', 'en', $this->root . '/data/it.json');
        $pack = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('en', $pack['source_locale']);
        $this->assertSame('Welcome, :name!', $pack['translations']['Dobro došli, :name!']);
        $pack['names'] = ['en' => 'Italian', 'it' => 'Italiano'];
        $pack['native_name'] = 'Italiano';
        $pack['flag_svg'] = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 2">'
        . '<rect width="3" height="2" fill="#009246"/></svg>';
        $pack['translations']['Dobro došli, :name!'] = 'Benvenuto, :name!';
        file_put_contents($path, json_encode($pack, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        $manager->install($path);

        $italian = (new RuntimeLanguageStorage($this->root))->catalog('it');
        $this->assertSame('Benvenuto, :name!', $italian['Dobro došli, :name!']);
        $this->assertArrayNotHasKey('Welcome, :name!', $italian);
    }

    /**
     * EN: One request installs multiple unique published languages.
     *
     * HR: Jedan zahtjev instalira više jedinstvenih objavljenih jezika.
     */
    public function testRepositoryInstallsMultipleSelectedLanguages(): void
    {
        $manager = new LanguagePackManager($this->root, new ModuleCatalog());
        $directory = $this->root . '/repository/packs';
        mkdir($directory, 0770, true);
        $definitions = [
            'de' => ['native_name' => 'Deutsch', 'translation' => 'Willkommen, :name!'],
            'it' => ['native_name' => 'Italiano', 'translation' => 'Benvenuto, :name!'],
        ];
        $manifestLanguages = [];
        foreach ($definitions as $locale => $definition) {
            $path = $directory . '/' . $locale . '.json';
            $template = $manager->createTemplate($locale, 'en', $path);
            $pack = json_decode((string)file_get_contents($template), true, 512, JSON_THROW_ON_ERROR);
            $pack['names'] = ['en' => $definition['native_name'], $locale => $definition['native_name']];
            $pack['native_name'] = $definition['native_name'];
            $pack['flag_svg'] = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 2">'
            . '<rect width="3" height="2" fill="#123456"/></svg>';
            $pack['translations']['Dobro došli, :name!'] = $definition['translation'];
            file_put_contents($path, json_encode($pack, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
            $manifestLanguages[] = [
                'locale' => $locale,
                'native_name' => $definition['native_name'],
                'version' => '2026.09.22.1',
                'file' => 'packs/' . $locale . '.json',
                'sha256' => hash_file('sha256', $path),
                'status' => 'released',
            ];
        }

        $manifestPath = $this->root . '/repository/manifest.json';
        file_put_contents($manifestPath, json_encode([
            'format' => 'simbioza-language-catalog',
            'version' => 1,
            'languages' => $manifestLanguages,
        ], JSON_THROW_ON_ERROR));
        $repository = new RepositoryLanguageManager(
            new LanguageRepository($this->root, 'file://' . $manifestPath),
            $manager,
            $this->root,
        );

        $this->assertSame(['de', 'it'], $repository->installMany(['de', 'it', 'de']));
        $this->assertFileExists($this->root . '/data/languages/catalogs/de.json');
        $this->assertFileExists($this->root . '/data/languages/catalogs/it.json');
        $configuration = require $this->root . '/config/installation.php';
        $this->assertSame(['en', 'de', 'it'], $configuration['supported_locales']);
    }

    /**
     * EN: A newer repository revision refreshes a disabled locale without re-enabling it.
     *
     * HR: Nova revizija repozitorija ažurira isključeni jezik bez ponovnog uključivanja.
     */
    public function testRepositoryUpdatePreservesDisabledLanguage(): void
    {
        mkdir($this->root . '/external', 0770);
        copy($this->root . '/config/languages.php', $this->root . '/external/languages.php');
        copy($this->root . '/config/installation.php', $this->root . '/external/installation.php');
        putenv('HPH_CONFIG_PATH=' . $this->root . '/external');
        $manager = new LanguagePackManager($this->root, new ModuleCatalog());
        $directory = $this->root . '/repository/packs';
        mkdir($directory, 0770, true);
        $packPath = $directory . '/de.json';
        $template = $manager->createTemplate('de', 'en', $packPath);
        $pack = json_decode((string)file_get_contents($template), true, 512, JSON_THROW_ON_ERROR);
        $pack['names'] = ['en' => 'German', 'de' => 'Deutsch'];
        $pack['native_name'] = 'Deutsch';
        $pack['flag_svg'] = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 2">'
        . '<rect width="3" height="2" fill="#ffce00"/></svg>';
        $pack['translations']['Dobro došli, :name!'] = 'Willkommen, :name!';
        file_put_contents($packPath, json_encode($pack, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        $manifestPath = $this->root . '/repository/manifest.json';
        $publish = static function (string $version) use ($manifestPath, $packPath): void {
            file_put_contents($manifestPath, json_encode([
                'format' => 'simbioza-language-catalog',
                'version' => 1,
                'languages' => [[
                    'locale' => 'de', 'native_name' => 'Deutsch', 'version' => $version,
                    'file' => 'packs/de.json', 'sha256' => hash_file('sha256', $packPath), 'status' => 'released',
                ]],
            ], JSON_THROW_ON_ERROR));
        };
        $publish('2026.09.22.1');
        $repository = new RepositoryLanguageManager(
            new LanguageRepository($this->root, 'file://' . $manifestPath),
            $manager,
            $this->root,
        );
        $repository->install('de');

        $manager->setActive('de', false);
        $pack['translations']['Dobro došli, :name!'] = 'Herzlich willkommen, :name!';
        file_put_contents($packPath, json_encode($pack, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        // EN: A digest-only translation change needs no application/module release.
        //
        // HR: Promjena prijevoda samo digestom ne treba release aplikacije/modula.
        $publish('2026.09.22.1');
        $this->assertFalse($repository->status()[0]['update_available']);
        $checked = $repository->status(true)[0];
        $this->assertTrue($checked['update_available']);
        $this->assertTrue($checked['revision_known']);
        $this->assertSame('2026.09.22.1', $checked['local_version']);
        $this->assertSame(['de'], $repository->updateInstalled());
        $this->assertSame(
            'Herzlich willkommen, :name!',
            (new RuntimeLanguageStorage($this->root))->catalog('de')['Dobro došli, :name!'],
        );
        $configuration = require $this->root . '/external/installation.php';
        $this->assertSame(['en'], $configuration['supported_locales']);
        unlink($this->root . '/data/languages/catalogs/de.json');
        $this->assertSame(['de'], $repository->updateInstalled());
        $this->assertSame(
            'Herzlich willkommen, :name!',
            (new RuntimeLanguageStorage($this->root))->catalog('de')['Dobro došli, :name!'],
        );
        $configuration = require $this->root . '/external/installation.php';
        $this->assertSame(['en'], $configuration['supported_locales']);
        $this->assertFileExists($this->root . '/data/languages/repository-versions.json');
        $this->assertFileDoesNotExist($this->root . '/external/repository-versions.json');
    }

    /**
     * EN: An explicit check never reports success from an offline cached catalogue.
     *     Bundled/manual locales without a repository record remain visibly unknown.
     *
     * HR: Izričita provjera nikad ne prikazuje uspjeh iz offline cachea.
     *     Ugrađeni/ručni jezici bez zapisa revizije ostaju jasno neevidentirani.
     */
    public function testFreshCheckRejectsStaleCacheAndReportsUnrecordedRevision(): void
    {
        $directory = $this->root . '/data/cache';
        mkdir($directory, 0770, true);
        $cached = ['format' => 'simbioza-language-catalog', 'version' => 1, 'languages' => [[
            'locale' => 'en', 'native_name' => 'English', 'version' => '2026.10.12.1',
            'file' => 'packs/en.json', 'sha256' => str_repeat('a', 64), 'status' => 'released',
        ]]];
        file_put_contents($directory . '/language-catalog.json', json_encode($cached, JSON_THROW_ON_ERROR));
        $manager = new RepositoryLanguageManager(
            new LanguageRepository(
                $this->root,
                'file://' . $this->root . '/missing-manifest.json',
            ),
            new LanguagePackManager($this->root, new ModuleCatalog()),
            $this->root,
        );
        $status = $manager->status();
        $this->assertSame('en', $status[0]['locale']);
        $this->assertFalse($status[0]['revision_known']);
        $this->assertSame('', $status[0]['local_version']);
        $this->assertFalse($status[0]['update_available']);
        $this->expectException(RuntimeException::class);
        $manager->status(true);
    }
}
