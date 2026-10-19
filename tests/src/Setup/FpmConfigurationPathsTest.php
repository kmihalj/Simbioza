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
        foreach (['FPM_USER', 'FPM_APP_GROUP', 'FPM_CONFIG_NAME', 'FPM_HELPER', 'FPM_SERVICE_LABEL'] as $name) {
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

    /**
     * EN: Linux helpers omit empty bind lists and quote each actual path as one property.
     * A shell-only capture checks the real argument boundary without sudo or systemd.
     *
     * HR: Linux helperi izostavljaju prazne bind popise i svaku stvarnu putanju navode zasebno.
     * Hvatanje u ljusci provjerava stvarne argumente bez sudo ili systemd poziva.
     */
    public function testLinuxHelperOmitsEmptyBindSourcesAndPreservesQuotedPaths(): void
    {
        foreach (
            [
            [[], []],
            [[], ['/fixture/config', '/fixture/requests']],
            [['/fixture/lower config'], ['/fixture/upper "config"', '/fixture/requests']],
            ] as [$readOnly, $writable]
        ) {
            $helper = \linuxSetupHelper($readOnly, $writable);
            $this->assertStringNotContainsString("BindReadOnlyPaths=''", (string)$helper);
            $this->assertStringNotContainsString("BindPaths=''", (string)$helper);
            $command = explode("\n# EN: The worker returns", $helper, 2)[0];
            $command = str_replace('/usr/bin/systemd-run', "printf '%s\\n'", $command);
            $process = proc_open(
                ['/bin/sh', '-c', $command, 'fixture-helper', str_repeat('a', 48)],
                [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
            );
            $this->assertIsResource($process);
            $output = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $this->assertSame(0, proc_close($process), $error);
            $arguments = explode("\n", trim($output));
            foreach (['BindReadOnlyPaths' => $readOnly, 'BindPaths' => $writable] as $property => $paths) {
                $actual = array_values(array_filter(
                    $arguments,
                    static fn(string $argument): bool => str_starts_with($argument, '--property=' . $property . '='),
                ));
                $expected = array_map(
                    static fn(string $path): string => '--property=' . $property . '=' . \systemdQuote($path),
                    $paths,
                );
                $this->assertSame($expected, $actual);
            }
        }
    }

    /**
     * EN: Relative bind paths fail before a root-owned helper can be written.
     *
     * HR: Relativne bind putanje odbijaju se prije zapisivanja root-owned helpera.
     */
    public function testLinuxHelperRejectsRelativeBindPaths(): void
    {
        $this->expectException(\RuntimeException::class);
        \linuxSetupHelper(['relative/config'], []);
    }

    /**
     * EN: launchd receives an integer umask, preserving shared writes without world access.
     * The generator remains pure: no installed service is read or restarted by this test.
     *
     * HR: launchd prima cjelobrojnu masku, uz zajednički upis i bez pristupa ostalih korisnika.
     * Generator je čist: ovaj test ne čita niti ponovno pokreće instalirani servis.
     */
    public function testDarwinServiceUsesPrivateSharedRuntimeMask(): void
    {
        $document = new \DOMDocument();
        $this->assertTrue($document->loadXML(\darwinFpmPlist(
            $this->root . '/A & B',
            '/fixture/php-fpm',
            '/fixture/pool & config.conf',
        ), LIBXML_NONET));
        $xpath = new \DOMXPath($document);
        $mask = 'string(/plist/dict/key[.="Umask"]/following-sibling::*[1][self::integer])';
        $user = 'string(/plist/dict/key[.="UserName"]/following-sibling::string[1])';
        $this->assertSame('7', $xpath->evaluate($mask));
        $this->assertSame(FPM_USER, $xpath->evaluate($user));
        $this->assertSame('/fixture/pool & config.conf', $xpath->evaluate('string(/plist/dict/array/string[last()])'));
    }

    /**
     * EN: The worker sets its mask after the account switch, even under restrictive sudo defaults.
     * This exercises the real generated shell wrapper and verifies its argument boundary.
     *
     * HR: Worker postavlja masku nakon promjene računa, i uz restriktivne sudo postavke.
     * Provjerava stvarni generirani omotač ljuske i granicu njegovih argumenata.
     */
    public function testDarwinWorkerSetsUmaskInsideTargetAccount(): void
    {
        $helper = \darwinSetupHelper();
        $this->assertStringContainsString("/bin/sh -c 'umask 0007; exec \"\$@\"' simbioza-setup", (string)$helper);
        $this->assertStringContainsString('[ "${#1}" -eq 48 ] || exit 64', (string)$helper);
        $this->assertStringContainsString('__WORKER__ "$1"', (string)$helper);
        $this->assertStringContainsString('TMPDIR=__ROOT__/data/tmp', (string)$helper);
        $process = proc_open([
            '/bin/sh', '-c', 'umask 0077; exec /bin/sh -c \'umask 0007; exec "$@"\' simbioza-setup "$@"',
            'fixture', PHP_BINARY, '-r', 'echo sprintf("%04o", umask()), "|", $argv[1];', 'literal token; not code',
        ], [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($process);
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($process), $output);
        $this->assertSame('0007|literal token; not code', $output);
    }
}
