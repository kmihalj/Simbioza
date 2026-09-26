<?php

declare(strict_types=1);

namespace Tests\Module;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

use function dirname;

#[CoversNothing]
final class ModuleBootstrapConfigTest extends TestCase
{
    private ?string $temporaryDirectory = null;

    /**
     * HR: CLI ne može poništiti OPcache web procesa; oba čitača moraju odmah
     *     uočiti promjenu podataka čak i kada su vremenske provjere isključene.
     * EN: CLI cannot invalidate the web process OPcache; both readers must see
     *     changed data immediately even when timestamp validation is disabled.
     */
    public function testRuntimeStateIsFreshWithWarmedOpcache(): void
    {
        if (!extension_loaded('Zend OPcache')) {
            self::markTestSkipped('OPcache is required for this cache-coherence regression.');
        }

        $this->temporaryDirectory = sys_get_temp_dir() . '/simbioza-app-config-' . bin2hex(random_bytes(6));
        $this->assertTrue(mkdir($this->temporaryDirectory . '/config', 0770, true));
        $this->assertTrue(copy(dirname(__DIR__, 3) . '/config/app.php', $this->temporaryDirectory . '/config/app.php'));
        $this->writeModuleState(['aaieduhr/heartphrame-module-theme']);
        $script = <<<'PHP'
require $argv[2];
$root = $argv[1];
$path = $root . '/data/config/modules.php';
$installationPath = $root . '/config/installation.php';
file_put_contents($installationPath, '<?php return ["name" => "Before"];');
$store = new App\Module\ModuleStateStore($root, new App\Module\ModuleCatalog());
$before = require $root . '/config/app.php';
$initialState = $store->isEnabled('theme');
$warmed = opcache_is_script_cached($path);
file_put_contents($path, '<?php return ["enabled" => []];');
file_put_contents($installationPath, '<?php return ["name" => "After"];');
$afterState = $store->isEnabled('theme');
$after = require $root . '/config/app.php';
echo json_encode([
    'warmed' => $warmed, 'initial' => $initialState, 'after_state' => $afterState,
    'before_name' => $before['name'], 'after_name' => $after['name'],
    'after_theme' => in_array('aaieduhr/heartphrame-module-theme', $after['modules']['enabled'], true),
]);
PHP;
        $process = proc_open([
            PHP_BINARY, '-d', 'opcache.enable_cli=1', '-d', 'opcache.validate_timestamps=0',
            '-d', 'opcache.file_update_protection=0', '-r', $script,
            $this->temporaryDirectory, dirname(__DIR__, 3) . '/vendor/autoload.php',
        ], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($process);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($process), $errors . $output);
        $this->assertSame([
            'warmed' => true, 'initial' => true, 'after_state' => false,
            'before_name' => 'Before', 'after_name' => 'After', 'after_theme' => false,
        ], json_decode((string)$output, true, flags: JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        if (!is_string($this->temporaryDirectory) || !is_dir($this->temporaryDirectory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->temporaryDirectory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if ($item instanceof \SplFileInfo) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
        }

        rmdir($this->temporaryDirectory);
        $this->temporaryDirectory = null;
    }

    /**
     * HR: Dinamički app.php pri svakom bootstrapu poštuje trajno stanje
     *     opcionalnih modula, dok obvezna jezgra uvijek ostaje uključena.
     * EN: Dynamic app.php honors persistent optional-module state on every
     *     bootstrap while the required core always remains enabled.
     */
    public function testOptionalModuleStateControlsBootstrapConfiguration(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/simbioza-app-config-' . bin2hex(random_bytes(6));
        $configDirectory = $this->temporaryDirectory . '/config';
        $languageDirectory = $this->temporaryDirectory . '/lang';
        $this->assertTrue(mkdir($configDirectory, 0770, true));
        $this->assertTrue(mkdir($languageDirectory, 0770, true));
        $this->assertTrue(copy(dirname(__DIR__, 3) . '/config/app.php', $configDirectory . '/app.php'));
        file_put_contents($languageDirectory . '/hr.php', "<?php return [];\n");
        file_put_contents($languageDirectory . '/en.php', "<?php return [];\n");
        // HR: Vraćene postavke ostaju podaci; ne smiju zamrznuti kasnije promjene jezika.
        // EN: Restored settings remain data and must not freeze later language changes.
        file_put_contents($configDirectory . '/installation.php', "<?php return [
            'name' => 'Restored application', 'primary_locale' => 'en',
            'supported_locales' => ['hr', 'en'], 'timezone' => 'Europe/Paris',
            'session_options' => ['cookie_lifetime' => 123, 'name' => 'not-allowlisted'],
        ];\n");

        $this->writeModuleState([
            'aaieduhr/heartphrame-module-orm',
        ]);
        $disabledConfiguration = require $configDirectory . '/app.php';
        $this->assertSame('Restored application', $disabledConfiguration['name']);
        $this->assertSame('en', $disabledConfiguration['localization']['locale']);
        $this->assertSame('Europe/Paris', $disabledConfiguration['timezone']);
        $this->assertSame(123, $disabledConfiguration['session']['options']['cookie_lifetime']);
        $this->assertSame('HEARTPHRAME_SESSION', $disabledConfiguration['session']['options']['name']);

        file_put_contents($configDirectory . '/installation.php', "<?php return [
            'primary_locale' => 'en', 'supported_locales' => ['hr', 'en'],
            'session_name' => 'SIMBIOZA_DEMO_SESSION',
        ];\n");
        $isolatedSessionConfiguration = require $configDirectory . '/app.php';
        $this->assertSame('SIMBIOZA_DEMO_SESSION', $isolatedSessionConfiguration['session']['options']['name']);
        $this->assertNotContains(
            'aaieduhr/heartphrame-module-theme',
            $disabledConfiguration['modules']['enabled'],
        );
        $this->assertContains(
            'aaieduhr/heartphrame-module-auth',
            $disabledConfiguration['modules']['enabled'],
        );

        $this->writeModuleState([
            'aaieduhr/heartphrame-module-orm',
            'aaieduhr/heartphrame-module-theme',
        ]);
        file_put_contents($configDirectory . '/installation.php', "<?php return [
            'primary_locale' => 'hr', 'supported_locales' => ['hr'],
        ];\n");
        $enabledConfiguration = require $configDirectory . '/app.php';
        $this->assertSame('hr', $enabledConfiguration['localization']['locale']);
        $this->assertSame(['hr'], $enabledConfiguration['localization']['supported_locales']);
        $this->assertContains(
            'aaieduhr/heartphrame-module-theme',
            $enabledConfiguration['modules']['enabled'],
        );

        // HR: Lokalni privatni paket nije u javnom opcionalnom katalogu.
        // EN: An installation-private package is not in the public optional catalog.
        file_put_contents($configDirectory . '/modules.local.php', "<?php return ['local/simbioza-module-demo'];\n");
        $localConfiguration = require $configDirectory . '/app.php';
        $this->assertContains('local/simbioza-module-demo', $localConfiguration['modules']['enabled']);
    }

    /**
     * HR: Zapisuje stanje modula za sljedeći izolirani bootstrap.
     * EN: Writes module state for the next isolated bootstrap.
     *
     * @param list<string> $enabled
     */
    private function writeModuleState(array $enabled): void
    {
        if (!is_string($this->temporaryDirectory)) {
            self::fail('The temporary application directory is unavailable.');
        }

        $path = $this->temporaryDirectory . '/data/config/modules.php';
        if (!is_dir(dirname($path))) {
            $this->assertTrue(mkdir(dirname($path), 0770, true));
        }

        file_put_contents($path, "<?php\n\nreturn " . var_export([
            'enabled' => $enabled,
            'removed' => [],
        ], true) . ";\n");
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($path, true);
        }
    }
}
