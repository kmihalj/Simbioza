<?php

declare(strict_types=1);

namespace Tests\Localization;

use AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem;
use App\Localization\RuntimeLanguageStorage;
use App\Localization\RuntimeTranslator;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use HeartPhrame\Http\Request;
use HeartPhrame\Module\ModuleInfo;
use HeartPhrame\Module\ModuleManager;
use HeartPhrame\Module\ModuleManifestInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * EN: Isolates runtime languages from HFC, private configuration, and the network.
 *
 * HR: Izolira runtime jezike od HFC-a, privatnih postavki i mreže.
 */
#[CoversNothing]
final class RuntimeLanguageStorageTest extends TestCase
{
    private string $root;

    /**
     * EN: Creates fictional package catalogues and one data directory.
     *
     * HR: Stvara izmišljene paketne kataloge i jedan podatkovni direktorij.
     */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/simbioza-runtime-languages-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/lang', 0770, true);
        mkdir($this->root . '/module/lang', 0770, true);
        mkdir($this->root . '/data', 0770);
        file_put_contents(
            $this->root . '/lang/en.php',
            '<?php return ["shared"=>"application", "new-key"=>"new code"];',
        );
        file_put_contents(
            $this->root . '/module/lang/en.php',
            '<?php return ["shared"=>"module", "module-key"=>"module fallback"];',
        );
    }

    /**
     * EN: Restores writable fixture modes and removes only owned files, without following links.
     *
     * HR: Vraća zapisiva prava fixturea i uklanja samo vlastite datoteke, bez praćenja linkova.
     */
    protected function tearDown(): void
    {
        chmod($this->root . '/lang', 0770);
        (new BackupFilesystem())->removeDirectory($this->root);
    }

    /**
     * EN: Uses the unchanged framework's module loader and fallback catalogue semantics.
     *
     * HR: Koristi nepromijenjeni frameworkov module loader i semantiku fallback kataloga.
     */
    private function translator(string $locale = 'en', bool $detect = false): RuntimeTranslator
    {
        $helper = new Helper();
        $config = new Config($helper, ['app' => ['localization' => [
            'locale' => $locale, 'fallback_locale' => 'en', 'detect_browser_locale' => $detect,
            'translations_dir' => $this->root . '/lang',
        ]]], $this->root);
        $modules = $this->createStub(ModuleManager::class);
        $modules->method('getLoadedModules')->willReturn([new ModuleInfo(
            'fictional',
            $this->root . '/module',
            $this->createStub(ModuleManifestInterface::class),
        )]);
        $modules->method('getDeferredModules')->willReturn([]);
        return new RuntimeTranslator(
            $config,
            $helper,
            $modules,
            new Request(headers: ['Accept-Language' => 'de,en;q=0.8']),
        );
    }

    /**
     * EN: Installed values win; missing keys still see updated application and module code.
     *
     * HR: Instalirane vrijednosti imaju prioritet; nedostajući ključevi vide novi kod aplikacije i modula.
     */
    public function testOverlayPrecedenceAndCodeFallback(): void
    {
        $storage = new RuntimeLanguageStorage($this->root);
        $storage->install('en', ['shared' => 'runtime', 'literal' => '<?php not executable ?>']);

        $translator = $this->translator();
        $this->assertSame('runtime', $translator->trans('shared'));
        $this->assertSame('new code', $translator->trans('new-key'));
        $this->assertSame('module fallback', $translator->trans('module-key'));
        $this->assertSame('<?php not executable ?>', $translator->trans('literal'));
        $storage->remove('catalogs/en.json');
        $this->assertSame('application', $this->translator()->trans('shared'));
    }

    /**
     * EN: A JSON-only locale works with browser detection and a package fallback language.
     *
     * HR: Jezik samo u JSON-u radi s odabirom prema pregledniku i paketnim fallback jezikom.
     */
    public function testJsonOnlyLocaleBrowserDetectionAndFallback(): void
    {
        (new RuntimeLanguageStorage($this->root))->install('de', ['shared' => 'Deutsch']);
        $translator = $this->translator(detect: true);
        $this->assertSame('de', $translator->getLocale());
        $this->assertSame('Deutsch', $translator->trans('shared'));
        $this->assertSame('module fallback', $translator->trans('module-key'));
        $this->assertFileDoesNotExist($this->root . '/lang/de.php');
    }

    /**
     * EN: Read-only packaged code does not prevent data-only installation or replacement.
     *
     * HR: Read-only paketni kod ne sprječava podatkovnu instalaciju ili zamjenu.
     */
    public function testCodeIsNotWrittenAndStateSurvivesCodeReplacement(): void
    {
        $original = file_get_contents($this->root . '/lang/en.php');
        chmod($this->root . '/lang', 0550);
        $storage = new RuntimeLanguageStorage($this->root);
        $storage->install('de', ['shared' => 'first']);
        $storage->install('de', ['shared' => 'updated']);
        $this->assertSame('updated', $storage->catalog('de')['shared']);
        $this->assertSame($original, file_get_contents($this->root . '/lang/en.php'));
        $this->assertSame(0660, fileperms($storage->path('catalogs/de.json')) & 0777);
        chmod($this->root . '/lang', 0770);
        file_put_contents($this->root . '/lang/en.php', '<?php return ["next"=>"new image"];');
        $this->assertSame('updated', $this->translator('de')->trans('shared'));
        $this->assertSame('new image', $this->translator('de')->trans('next'));
    }

    /**
     * EN: Traversal and symlinked data targets are rejected before outside writes.
     *
     * HR: Traversal i podatkovni ciljevi preko symlinka odbijaju se prije vanjskog zapisa.
     */
    public function testUnsafePathsAndSymlinksAreRejected(): void
    {
        $storage = new RuntimeLanguageStorage($this->root);
        foreach (['../en', 'de/../../x', 'php://input', 'EN', 'de--x'] as $locale) {
            try {
                $storage->install($locale, ['shared' => 'unsafe']);
                $this->fail('Unsafe locale accepted.');
            } catch (RuntimeException $error) {
                $this->assertSame('Invalid runtime locale.', $error->getMessage());
            }
        }

        mkdir($this->root . '/data/languages', 0770);
        symlink($this->root . '/lang', $this->root . '/data/languages/catalogs');
        $this->expectException(RuntimeException::class);
        $storage->install('de', ['shared' => 'unsafe']);
    }

    /**
     * EN: Invalid JSON cannot execute PHP or fall back silently to stale translations.
     *
     * HR: Nevaljan JSON ne može izvršiti PHP niti prešutno koristiti stare prijevode.
     */
    public function testInvalidJsonIsExplicit(): void
    {
        $storage = new RuntimeLanguageStorage($this->root);
        $storage->write('catalogs/en.json', '<?php throw new Exception("must not execute");');
        $this->expectException(\JsonException::class);
        $this->translator()->trans('shared');
    }

    /**
     * EN: Resource locks block a second mutator and are reusable after release.
     *
     * HR: Lock resursa blokira drugog zapisivača i ponovno je upotrebljiv nakon oslobađanja.
     */
    public function testRuntimeLockSerializesChanges(): void
    {
        $storage = new RuntimeLanguageStorage($this->root);
        $lock = $storage->lock();
        try {
            try {
                $storage->lock();
                $this->fail('Concurrent runtime lock accepted.');
            } catch (RuntimeException $error) {
                $this->assertStringContainsString('being edited', $error->getMessage());
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        $next = $storage->lock();
        $this->assertSame('stream', get_resource_type($next));
        fclose($next);
    }
}
