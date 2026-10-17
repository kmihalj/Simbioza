<?php

declare(strict_types=1);

namespace Tests\Setup;

use HeartPhrame\Config\ConfigArrayMerger;
use HeartPhrame\Config\ConfigPathResolver;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * EN: Tests the real generator without service changes or HFC secrets.
 *
 * HR: Provjerava stvarni generator, bez servisnih promjena ili HFC tajni.
 */
#[CoversNothing]
final class FpmConfigurationPathsTest extends TestCase
{
    /**
     * EN: Transient workers omit absent bind properties and preserve quoted non-empty paths.
     *
     * HR: Privremeni workeri izostavljaju prazna bind svojstva i čuvaju navodnike nepraznih putanja.
     */
    public function testTransientWorkerOmitsEmptyBindProperties(): void
    {
        $this->assertSame('', \systemdBindArgument('BindReadOnlyPaths', []));
        $this->assertSame('', \systemdBindArgument('BindPaths', []));
        $argument = \systemdBindArgument('BindReadOnlyPaths', ['/srv/private config', '/srv/override']);
        $this->assertStringContainsString('--property=BindReadOnlyPaths=', (string)$argument);
        $this->assertStringContainsString('"/srv/private config" "/srv/override"', (string)$argument);
        $this->assertStringEndsWith(" \\\n", $argument);
    }

    /**
     * EN: Optional SimpleSAMLphp paths do not block a fresh Linux service.
     *
     * HR: Opcionalne SimpleSAMLphp putanje ne blokiraju novi Linux servis.
     */
    public function testAbsentSimpleSamlDirectoriesDoNotBlockServiceStartup(): void
    {
        $source = file_get_contents(dirname(__DIR__, 3) . '/scripts/configure_fpm_setup.php');
        $this->assertStringContainsString('InaccessiblePaths=-/usr/share/simplesamlphp-aai/config', (string) $source);
        $this->assertStringContainsString(
            '-/usr/share/simplesamlphp-aai/cache -/var/lib/php -/run/php',
            (string)$source,
        );
    }

    /**
     * EN: Runtime permissions follow current module directories, not removed legacy PHP files.
     *
     * HR: Runtime prava prate aktualne modulne direktorije, ne uklonjene stare PHP datoteke.
     */
    public function testCurrentRuntimePathsDoNotRecreateLegacyConfiguration(): void
    {
        $source = (string)file_get_contents(dirname(__DIR__, 3) . '/scripts/configure_fpm_setup.php');
        $this->assertStringContainsString("'theme', 'backup'", $source);
        foreach (['data/config', 'config/editor-html.php', 'config/email.php', 'config/workspace.php'] as $path) {
            $this->assertStringNotContainsString("'" . $path . "'", $source);
        }
    }

    private string $root;

    /** @var array<string,string|false> */
    private array $environment = [];

    /**
     * EN: Loads only the configurator's pure functions.
     *
     * HR: Učitava samo čiste funkcije konfiguratora.
     */
    public static function setUpBeforeClass(): void
    {
        class_exists(ConfigPathResolver::class);
        class_exists(ConfigArrayMerger::class);
        enum_exists(\HeartPhrame\CodeBook\EnvKeyEnum::class);
        define('SIMBIOZA_FPM_SETUP_LIBRARY', true);
        foreach (['FPM_USER', 'FPM_APP_GROUP', 'FPM_CONFIG_NAME', 'FPM_HELPER'] as $name) {
            define($name, 'fixture-' . strtolower($name));
        }

        require_once dirname(__DIR__, 3) . '/scripts/configure_fpm_setup.php';
    }

    /**
     * EN: Isolates the declared layers and request directory.
     *
     * HR: Izolira deklarirane slojeve i request direktorij.
     */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/simbioza-fpm-paths-' . bin2hex(random_bytes(6));
        foreach (['config', 'override', 'requests', 'src/Configuration'] as $directory) {
            mkdir($this->root . '/' . $directory, 0700, true);
        }

        foreach (['ConfigurationPaths.php', 'PhpConfigurationReader.php'] as $file) {
            copy(dirname(__DIR__, 3) . '/src/Configuration/' . $file, $this->root . '/src/Configuration/' . $file);
        }

        file_put_contents($this->root . '/override/setup.php', '<?php return ' . var_export([
            'request_dir' => $this->root . '/requests',
        ], true) . ';');
        foreach (['HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $name) {
            $this->environment[$name] = getenv($name);
        }

        putenv('HPH_CONFIG_PATH=' . $this->root . '/config');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/override');
    }

    /**
     * EN: Restores the environment and removes only owned fixtures.
     *
     * HR: Vraća okoliš i briše samo vlastitu fixture.
     */
    protected function tearDown(): void
    {
        foreach ($this->environment as $name => $value) {
            putenv($value === false ? $name : $name . '=' . $value);
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
            $this->root,
            \FilesystemIterator::SKIP_DOTS,
        ), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) {
            $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->root);
    }

    /**
     * EN: The clear_env pool and sandbox use the same highest layer, keeping the lower one read-only.
     *
     * HR: clear_env pool i sandbox koriste isti najviši sloj, uz read-only niži.
     */
    public function testPoolAndSandboxFollowDeclaredLayers(): void
    {
        $policy = \fpmFilesystemPolicy($this->root);
        $this->assertSame([realpath($this->root . '/config')], $policy['read_only_config']);
        $this->assertSame([
            realpath($this->root . '/override'), realpath($this->root . '/requests'),
        ], $policy['write_bind']);
        $this->assertSame($this->root . '/requests', \setupRequestDirectory($this->root));
        $pool = \fpmPool($this->root, '127.0.0.1:9999', true, null);
        $this->assertStringContainsString('clear_env = yes', (string) $pool);
        $this->assertStringContainsString('env[HPH_CONFIG_PATH] = "' . $this->root . '/config"', (string) $pool);
        $this->assertStringContainsString(
            'env[HPH_CONFIG_OVERRIDE_PATHS] = "' . $this->root . '/override"',
            (string) $pool,
        );
    }

    /**
     * EN: A deploy upgrade can replace shipped config code without relaxing the FPM sandbox.
     *
     * HR: Deploy nadogradnja može zamijeniti isporučeni config kod bez ublažavanja FPM sandboxa.
     */
    public function testDeployWorkerCanUpdateReleaseConfigurationWithAnOverrideLayer(): void
    {
        $web = \fpmFilesystemPolicy($this->root);
        $worker = \setupFilesystemPolicy($this->root);
        $this->assertSame([realpath($this->root . '/config')], $web['read_only_config']);
        $this->assertSame([], $worker['read_only_config']);
        $this->assertSame($web['write_bind'], $worker['write_bind']);
        $source = (string)file_get_contents(dirname(__DIR__, 3) . '/scripts/configure_fpm_setup.php');
        $this->assertStringContainsString('$policy = setupFilesystemPolicy($root);', $source);
    }

    /**
     * EN: Non-release layers remain read-only for the trusted worker as well.
     *
     * HR: Slojevi izvan konfiguracije izdanja ostaju read-only i za pouzdani worker.
     */
    public function testDeployWorkerKeepsPrivateLowerLayersReadOnly(): void
    {
        mkdir($this->root . '/private', 0700);
        putenv('HPH_CONFIG_PATH=' . $this->root . '/private');
        $web = \fpmFilesystemPolicy($this->root);
        $worker = \setupFilesystemPolicy($this->root);
        $this->assertSame([realpath($this->root . '/private')], $worker['read_only_config']);
        $this->assertSame($web, $worker);
    }

    /**
     * EN: A symlinked config root is private, not an updatable release directory.
     *
     * HR: Symlinkani config korijen je privatan, ne direktorij izdanja za nadogradnju.
     */
    public function testDeployWorkerDoesNotExemptSymlinkedConfiguration(): void
    {
        rename($this->root . '/config', $this->root . '/private');
        symlink($this->root . '/private', $this->root . '/config');
        $worker = \setupFilesystemPolicy($this->root);
        $this->assertSame([realpath($this->root . '/private')], $worker['read_only_config']);
        $this->assertSame(\fpmFilesystemPolicy($this->root), $worker);
    }

    /**
     * EN: FPM rejects an empty env value, so absent/empty override layers must be omitted.
     * clear_env still prevents inherited worker environment values from leaking in.
     *
     * HR: FPM odbija praznu env vrijednost pa izostali/prazni dodatni slojevi ne ulaze u pool.
     * clear_env i dalje sprječava nasljeđivanje okolišnih vrijednosti u workerima.
     */
    public function testEmptyOverrideLayersAreOmittedFromPool(): void
    {
        putenv('HPH_CONFIG_PATH');
        foreach ([false, '', " \t "] as $override) {
            putenv($override === false ? 'HPH_CONFIG_OVERRIDE_PATHS' : 'HPH_CONFIG_OVERRIDE_PATHS=' . $override);
            $pool = \fpmPool($this->root, '127.0.0.1:9999', false, null);
            $this->assertStringNotContainsString('env[HPH_CONFIG_OVERRIDE_PATHS]', (string) $pool);
            $this->assertStringContainsString('clear_env = yes', (string) $pool);
            $this->assertStringContainsString('env[HPH_CONFIG_PATH] = "' . $this->root . '/config"', (string) $pool);
        }
    }

    /**
     * EN: Explicit nonempty layers preserve their order and are not removed by the empty-value guard.
     *
     * HR: Izričiti neprazni slojevi zadržavaju redoslijed i ne uklanjaju se provjerom prazne vrijednosti.
     */
    public function testMultipleOverrideLayersRemainInPool(): void
    {
        $overrides = $this->root . '/config' . PATH_SEPARATOR . $this->root . '/override';
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $overrides);
        $pool = \fpmPool($this->root, '127.0.0.1:9999', true, null);
        $this->assertStringContainsString('env[HPH_CONFIG_OVERRIDE_PATHS] = "' . $overrides . '"', (string) $pool);
        $this->assertSame(1, substr_count($pool, 'env[HPH_CONFIG_OVERRIDE_PATHS]'));
    }

    /**
     * EN: Two names for one directory must not alter the final layer's priority.
     *
     * HR: Dva imena istog direktorija ne smiju promijeniti prioritet zadnjeg.
     */
    public function testDirectoryAliasRetainsFinalPriority(): void
    {
        symlink($this->root . '/config', $this->root . '/alias');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/override' . PATH_SEPARATOR . $this->root . '/alias');
        $policy = \fpmFilesystemPolicy($this->root);
        $this->assertSame([realpath($this->root . '/override')], $policy['read_only_config']);
        $this->assertSame(realpath($this->root . '/config'), $policy['write_bind'][0]);
    }
}
