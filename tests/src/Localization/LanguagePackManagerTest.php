<?php

declare(strict_types=1);

namespace Tests\Localization;

use App\Localization\LanguagePackManager;
use App\Localization\LanguageRepository;
use App\Localization\RepositoryLanguageManager;
use App\Module\ModuleCatalog;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

#[CoversClass(LanguagePackManager::class)]
#[CoversClass(LanguageRepository::class)]
#[CoversClass(RepositoryLanguageManager::class)]
#[CoversClass(ModuleCatalog::class)]
final class LanguagePackManagerTest extends TestCase
{
    private string $root;

    /** HR: Priprema najmanju izoliranu instalaciju jezika. EN: Prepares the smallest isolated language installation. */
    protected function setUp(): void
    {
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

    /** HR: Uklanja samo privremene datoteke testa. EN: Removes only the test's temporary files. */
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
    }

    /**
     * HR: Dodani jezik ostavlja privatnu instalacijsku konfiguraciju zapisivu
     *     vlasniku i isključivoj runtime grupi.
     * EN: An added locale leaves private installation configuration writable
     *     by its owner and the exclusive runtime group.
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
     * HR: Potvrđuje ćirilični UTF-8 jezik i zabranu isključivanja zadnjega aktivnog jezika.
     * EN: Verifies a Cyrillic UTF-8 locale and the final-active-locale guard.
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
        $this->assertSame('Добро дошли, :name!', (require $this->root . '/lang/sr-cyrl.php')['Dobro došli, :name!']);
        $manager->setActive('en', false);
        $this->expectException(RuntimeException::class);
        $manager->setActive('sr-cyrl', false);
    }

    /**
     * HR: Engleski kao jezik predloška ne mijenja hrvatski ključ pri izvođenju.
     * EN: English as a template source does not change the Croatian runtime key.
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

        $italian = require $this->root . '/lang/it.php';
        $this->assertSame('Benvenuto, :name!', $italian['Dobro došli, :name!']);
        $this->assertArrayNotHasKey('Welcome, :name!', $italian);
    }

    /**
     * HR: Nova revizija repozitorija ažurira isključeni jezik bez ponovnog uključivanja.
     * EN: A newer repository revision refreshes a disabled locale without re-enabling it.
     */
    public function testRepositoryUpdatePreservesDisabledLanguage(): void
    {
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
        $publish('2026.09.22.2');
        $this->assertSame(['de'], $repository->updateInstalled());
        $this->assertSame('Herzlich willkommen, :name!', (require $this->root . '/lang/de.php')['Dobro došli, :name!']);
        $configuration = require $this->root . '/config/installation.php';
        $this->assertSame(['en'], $configuration['supported_locales']);
        unlink($this->root . '/lang/de.php');
        $this->assertSame(['de'], $repository->updateInstalled());
        $this->assertSame('Herzlich willkommen, :name!', (require $this->root . '/lang/de.php')['Dobro došli, :name!']);
        $configuration = require $this->root . '/config/installation.php';
        $this->assertSame(['en'], $configuration['supported_locales']);
    }
}
