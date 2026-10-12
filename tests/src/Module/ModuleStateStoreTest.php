<?php

declare(strict_types=1);

namespace Tests\Module;

use App\Configuration\ConfigurationPaths;
use App\Module\ModuleCatalog;
use App\Module\ModuleStateStore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(ModuleStateStore::class)]
#[UsesClass(ConfigurationPaths::class)]
#[UsesClass(ModuleCatalog::class)]
final class ModuleStateStoreTest extends TestCase
{
    private string $root;

    /** @var array<string,string|false> */
    private array $environment = [];

    /**
     * EN: Prepares only an owned empty installation.
     *
     * HR: Priprema samo vlastitu praznu instalaciju.
     */
    protected function setUp(): void
    {
        foreach (['HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $name) {
            $this->environment[$name] = getenv($name);
            putenv($name);
        }

        $this->root = sys_get_temp_dir() . '/simbioza-module-config-' . bin2hex(random_bytes(8));
        foreach (['config', 'external', 'last', 'data/config'] as $directory) {
            mkdir($this->root . '/' . $directory, 0770, true);
        }
    }

    /**
     * EN: Restores the environment and removes only the test tree.
     *
     * HR: Vraća okoliš i uklanja samo testno stablo.
     */
    protected function tearDown(): void
    {
        foreach (['config', 'external', 'last', 'data/config'] as $directory) {
            chmod($this->root . '/' . $directory, 0700);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo) {
                $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
        }

        rmdir($this->root);
        foreach ($this->environment as $name => $value) {
            is_string($value) ? putenv($name . '=' . $value) : putenv($name);
        }
    }

    /**
     * EN: Fresh selection and all actions retain constraints in config.
     *
     * HR: Novi odabir i sve radnje čuvaju ograničenja u configu.
     */
    public function testFreshStorePreservesConstraintsAndRemovedSelection(): void
    {
        $store = $this->store();
        $store->initialize(['api', 'audit']);
        $store->rememberRequirementConstraint('api', 'dev-main');
        $store->disable('audit');
        $store->markRemoved('api');
        $this->assertSame(['api'], $store->removedSlugs());
        $this->assertFalse($store->isEnabled('audit'));
        $store->enable('api');
        $this->assertTrue($store->isEnabled('api'));
        $this->assertSame([], $store->removedSlugs());
        $this->assertSame('dev-main', $store->requirementConstraint('api'));
        $this->assertSame(0660, fileperms($this->root . '/config/modules.php') & 0777);
        $this->assertFileDoesNotExist($this->root . '/data/config/modules.php');
    }

    /**
     * EN: Higher complete documents are preserved and all actions write only the last layer.
     *
     * HR: Potpuni dokument jačeg sloja se očuva i sve radnje pišu samo zadnji sloj.
     */
    public function testLayeredWritesPreserveCompleteEffectiveState(): void
    {
        $this->store()->initialize(['api']);
        $lowerHash = hash_file('sha256', $this->root . '/config/modules.php');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/last');
        $store = $this->store();
        $store->rememberRequirementConstraint('api', '^0.2');
        $store->enable('audit');
        $this->assertTrue($store->isEnabled('api'));
        $this->assertTrue($store->isEnabled('audit'));
        $this->assertSame($lowerHash, hash_file('sha256', $this->root . '/config/modules.php'));
        $this->assertFileExists($this->root . '/last/modules.php');
        $this->assertSame('^0.2', $store->requirementConstraint('api'));
        $state = require $this->root . '/last/modules.php';
        $state['custom_option'] = ['keep' => true];
        file_put_contents($this->root . '/last/modules.php', '<?php return ' . var_export($state, true) . ';');
        $store->disable('audit');
        $saved = require $this->root . '/last/modules.php';
        $this->assertSame(['keep' => true], $saved['custom_option']);
    }

    /**
     * EN: A read-only higher layer can be read, but writes never fall back lower.
     *
     * HR: Read-only jači sloj može se čitati, ali zapis nikada ne pada na niži.
     */
    public function testReadOnlyLayerRejectsWrites(): void
    {
        $this->store()->initialize(['api']);
        $before = hash_file('sha256', $this->root . '/config/modules.php');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/last');
        chmod($this->root . '/last', 0500);
        $this->assertTrue($this->store()->isEnabled('api'));
        $this->expectException(RuntimeException::class);
        try {
            $this->store()->enable('audit');
        } finally {
            $this->assertSame($before, hash_file('sha256', $this->root . '/config/modules.php'));
            $this->assertFileDoesNotExist($this->root . '/last/modules.php');
        }
    }

    /**
     * EN: Invalid active documents are never replaced with defaults.
     *
     * HR: Nevaljani aktivni dokument se ne zamjenjuje zadanim vrijednostima.
     */
    public function testInvalidConfigurationFailsClosed(): void
    {
        file_put_contents($this->root . '/config/modules.php', '<?php return false;');
        $this->expectException(RuntimeException::class);
        $this->store()->enable('audit');
    }

    /**
     * EN: Parallel CLI processes must not lose each other's selection changes.
     *
     * HR: Paralelni CLI procesi ne smiju izgubiti tuđu promjenu odabira.
     */
    public function testConcurrentProcessesRetainBothChanges(): void
    {
        $this->store()->initialize([]);
        $script = <<<'PHP'
require $argv[1];
putenv('HPH_CONFIG_PATH=' . $argv[2] . '/config');
putenv('HPH_CONFIG_OVERRIDE_PATHS');
$store = new App\Module\ModuleStateStore($argv[2], new App\Module\ModuleCatalog());
for ($attempt = 0; $attempt < 30; ++$attempt) { $store->enable($argv[3]); }
PHP;
        $processes = [];
        foreach (['api', 'audit'] as $slug) {
            $process = proc_open(
                [PHP_BINARY, '-r', $script, dirname(__DIR__, 3) . '/vendor/autoload.php', $this->root, $slug],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
            );
            $this->assertIsResource($process);
            fclose($pipes[0]);
            $processes[] = [$process, $pipes[1], $pipes[2]];
        }

        foreach ($processes as [$process, $stdout, $stderr]) {
            $output = stream_get_contents($stdout) . stream_get_contents($stderr);
            fclose($stdout);
            fclose($stderr);
            $this->assertSame(0, proc_close($process), $output);
        }

        $this->assertTrue($this->store()->isEnabled('api'));
        $this->assertTrue($this->store()->isEnabled('audit'));
    }

    /**
     * EN: Returns a store with the currently declared paths.
     *
     * HR: Vraća spremište s trenutno deklariranim putanjama.
     */
    /**
     * EN: Content directories are never a configuration fallback.
     *
     * HR: Direktoriji sadržaja nikada nisu rezervni izvor konfiguracije.
     */
    public function testConfigurationReadsOnlyDeclaredPath(): void
    {
        $this->store()->initialize(['api']);
        file_put_contents($this->root . '/data/config/modules.php', '<?php return ["enabled"=>[]];');
        $this->assertTrue($this->store()->isEnabled('api'));
        putenv('HPH_CONFIG_PATH=' . $this->root . '/external');
        $this->assertFalse($this->store()->isEnabled('api'));
    }

    /**
     * EN: An installed site cannot silently activate modules when its configuration is missing.
     *
     * HR: Instalirani site ne može tiho uključiti module kada nedostaje konfiguracija.
     */
    public function testInstalledSiteRequiresExplicitSelection(): void
    {
        file_put_contents($this->root . '/data/installation.lock', '{}');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('require modules.php');
        $this->store()->enabledPackages();
    }

    private function store(): ModuleStateStore
    {
        return new ModuleStateStore($this->root, new ModuleCatalog());
    }
}
