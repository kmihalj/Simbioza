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

    /** @var array<string,string|false> */
    private array $environment = [];

    /**
     * EN: Isolates configuration parameters from HFC.
     *
     * HR: Izolira konfiguracijske parametre od HFC-a.
     */
    protected function setUp(): void
    {
        foreach (['HPH_APP_PATH', 'HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $name) {
            $this->environment[$name] = getenv($name);
            putenv($name);
        }
    }

    /**
     * EN: CLI cannot invalidate the web process OPcache; both readers must see
     *     changed data immediately even when timestamp validation is disabled.
     *
     * HR: CLI ne može poništiti OPcache web procesa; oba čitača moraju odmah
     *     uočiti promjenu podataka čak i kada su vremenske provjere isključene.
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
putenv('HPH_APP_PATH=' . $root);
putenv('HPH_CONFIG_PATH=' . $root . '/config');
putenv('HPH_CONFIG_OVERRIDE_PATHS');
$path = $root . '/config/modules.php';
$installationPath = $root . '/config/installation.php';
mkdir($root . '/override');
mkdir($root . '/lang');
putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $root . '/override');
$languagePath = $root . '/config/languages.php';
file_put_contents($root . '/lang/en.php', '<?php return [];');
file_put_contents($root . '/lang/de.php', '<?php return [];');
file_put_contents($languagePath, '<?php return ["en"=>[],"de"=>[]];');
file_put_contents($installationPath, '<?php return ["name" => "Before", "supported_locales"=>["en","de"]];');
$store = new App\Module\ModuleStateStore($root, new App\Module\ModuleCatalog());
$before = require $root . '/config/app.php';
$initialState = $store->isEnabled('theme');
$warmed = opcache_is_script_cached($path);
$languageWarmed = opcache_is_script_cached($languagePath);
file_put_contents($path, '<?php return ["enabled" => []];');
file_put_contents($installationPath, '<?php return ["name" => "After"];');
file_put_contents($root . '/override/languages.php', '<?php return ["en"=>[]];');
$afterState = $store->isEnabled('theme');
$after = require $root . '/config/app.php';
echo json_encode([
    'warmed' => $warmed, 'initial' => $initialState, 'after_state' => $afterState,
    'before_name' => $before['name'], 'after_name' => $after['name'],
    'language_warmed' => $languageWarmed,
    'before_locales' => $before['localization']['supported_locales'],
    'after_locales' => $after['localization']['supported_locales'],
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
            'before_name' => 'Before', 'after_name' => 'After',
            'language_warmed' => true, 'before_locales' => ['en', 'de'], 'after_locales' => ['en'],
            'after_theme' => false,
        ], json_decode((string)$output, true, flags: JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        foreach ($this->environment as $name => $value) {
            is_string($value) ? putenv($name . '=' . $value) : putenv($name);
        }

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
     * EN: Dynamic app.php honors persistent optional-module state on every
     *     bootstrap while the required core always remains enabled.
     *
     * HR: Dinamički app.php pri svakom bootstrapu poštuje trajno stanje
     *     opcionalnih modula, dok obvezna jezgra uvijek ostaje uključena.
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
        // EN: Restored settings remain data and must not freeze later language changes.
        //
        // HR: Vraćene postavke ostaju podaci; ne smiju zamrznuti kasnije promjene jezika.
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

        // EN: An installation-private package is not in the public optional catalog.
        //
        // HR: Lokalni privatni paket nije u javnom opcionalnom katalogu.
        file_put_contents($configDirectory . '/modules.local.php', "<?php return ['local/simbioza-module-demo'];\n");
        $localConfiguration = require $configDirectory . '/app.php';
        $this->assertContains('local/simbioza-module-demo', $localConfiguration['modules']['enabled']);
    }

    /**
     * EN: External config uses the code root for resources and shared module selection.
     *
     * HR: Vanjski config koristi putanju koda za resurse i zajednički odabir modula.
     */
    public function testExternalBootstrapUsesCodeRootAndLayeredSettings(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir() . '/simbioza-app-config-' . bin2hex(random_bytes(6));
        foreach (['config', 'external', 'last', 'lang'] as $directory) {
            mkdir($this->temporaryDirectory . '/' . $directory, 0770, true);
        }

        $root = $this->temporaryDirectory;
        copy(dirname(__DIR__, 3) . '/config/app.php', $root . '/external/app.php');
        file_put_contents(
            $root . '/external/installation.php',
            '<?php return ["name"=>"External", "primary_locale"=>"en", "supported_locales"=>["en"]];',
        );
        file_put_contents($root . '/last/installation.php', '<?php return ["timezone"=>"Europe/Paris"];');
        file_put_contents($root . '/lang/en.php', '<?php return [];');
        putenv('HPH_APP_PATH=' . $root);
        putenv('HPH_CONFIG_PATH=' . $root . '/external');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $root . '/last');
        $state = new \App\Module\ModuleStateStore($root, new \App\Module\ModuleCatalog());
        $state->initialize(['api']);

        $before = require $root . '/external/app.php';
        $this->assertSame('External', $before['name']);
        $this->assertSame('Europe/Paris', $before['timezone']);
        $this->assertSame($root . '/views', $before['views']['path']);
        $this->assertSame($root . '/lang', $before['localization']['translations_dir']);
        $this->assertContains('aaieduhr/heartphrame-module-api', $before['modules']['enabled']);
        $state->disable('api');
        $after = require $root . '/external/app.php';
        $this->assertNotContains('aaieduhr/heartphrame-module-api', $after['modules']['enabled']);
        $this->assertContains('aaieduhr/heartphrame-module-auth', $after['modules']['enabled']);
        $this->assertFileDoesNotExist($root . '/external/modules.php');
        $this->assertFileExists($root . '/last/modules.php');
    }

    /**
     * EN: Writes module state for the next isolated bootstrap.
     *
     * HR: Zapisuje stanje modula za sljedeći izolirani bootstrap.
     *
     * @param list<string> $enabled
     */
    private function writeModuleState(array $enabled): void
    {
        if (!is_string($this->temporaryDirectory)) {
            self::fail('The temporary application directory is unavailable.');
        }

        putenv('HPH_APP_PATH=' . $this->temporaryDirectory);
        putenv('HPH_CONFIG_PATH=' . $this->temporaryDirectory . '/config');
        $path = $this->temporaryDirectory . '/config/modules.php';
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
