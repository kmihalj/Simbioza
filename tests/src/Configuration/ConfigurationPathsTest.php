<?php

declare(strict_types=1);

namespace Tests\Configuration;

use App\Configuration\ConfigurationPaths;
use HeartPhrame\Config\Config;
use HeartPhrame\Config\ConfigInterface;
use HeartPhrame\Config\ConfigPathResolver;
use HeartPhrame\Helper\Helper;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;

/**
 * EN: Uses only isolated dummy configuration.
 *
 * HR: Koristi samo izolirane lažne postavke.
 */
#[CoversClass(ConfigurationPaths::class)]
final class ConfigurationPathsTest extends TestCase
{
    private string $root;

    /** @var array<string,string|false> */
    private array $environment = [];

    /**
     * EN: Isolates the environment and creates an owned test directory.
     *
     * HR: Izolira okoliš i stvara vlastiti testni direktorij.
     */
    protected function setUp(): void
    {
        foreach (['HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $name) {
            $this->environment[$name] = getenv($name);
            putenv($name);
        }

        $this->root = sys_get_temp_dir() . '/simbioza-paths-' . bin2hex(random_bytes(8));
        $this->assertTrue(mkdir($this->root . '/config', 0770, true));
    }

    /**
     * EN: Restores the environment and removes only owned test files.
     *
     * HR: Vraća okoliš i uklanja samo vlastite testne datoteke.
     */
    protected function tearDown(): void
    {
        foreach ($this->environment as $name => $value) {
            is_string($value) ? putenv($name . '=' . $value) : putenv($name);
        }

        $directories = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($directories as $item) {
            if ($item instanceof \SplFileInfo && !$item->isLink() && $item->isDir()) {
                chmod($item->getPathname(), 0700);
            }
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }

            if ($item->isLink() || !$item->isDir()) {
                unlink($item->getPathname());
            } else {
                chmod($item->getPathname(), 0700);
                rmdir($item->getPathname());
            }
        }

        rmdir($this->root);
    }

    /**
     * EN: Default paths remain application config and nothing is written.
     *
     * HR: Zadane putanje ostaju aplikacijski config i ništa se ne zapisuje.
     */
    public function testDefaultPathsAndMissingDocument(): void
    {
        $paths = ConfigurationPaths::fromEnvironment($this->root);
        $this->assertSame([$this->root . '/config'], $paths->directories());
        $this->assertSame($this->root . '/config', $paths->baseDirectory());
        $this->assertSame([$this->root . '/config/email/settings.json'], $paths->candidateFiles('email/settings.json'));
        $this->assertNull($paths->effectiveFile('email/settings.json'));
        $this->assertSame($this->root . '/config/email/settings.json', $paths->writableFile('email/settings.json'));
        $this->assertDirectoryDoesNotExist($this->root . '/config/email');
    }

    /**
     * EN: An external path does not depend on the application's working directory.
     *
     * HR: Vanjska putanja ne ovisi o radnom direktoriju aplikacije.
     */
    public function testExternalBaseAndNativeLayerOrder(): void
    {
        $this->assertTrue(mkdir($this->root . '/external'));
        $this->assertTrue(mkdir($this->root . '/override'));
        putenv('HPH_CONFIG_PATH=' . $this->root . '/external/');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . implode(PATH_SEPARATOR, [
            '', $this->root . '/override/', $this->root . '/external', ' ', $this->root . '/override',
        ]));
        $paths = ConfigurationPaths::fromEnvironment($this->root);
        $this->assertSame(ConfigPathResolver::fromEnvironment($this->root), $paths->directories());
        $this->assertSame([$this->root . '/external', $this->root . '/override'], $paths->directories());
        $this->assertSame($this->root . '/external', $paths->baseDirectory());
        $this->assertSame([
            $this->root . '/external/email.php', $this->root . '/override/email.php',
        ], $paths->candidateFiles('email.php'));
        $this->assertSame(
            $paths->directories(),
            ConfigurationPaths::fromEnvironment($this->root . '/another-app')->directories(),
        );
    }

    /**
     * EN: The container exposes the same helper without loading real settings.
     *
     * HR: Kontejner nudi istu pomoćnu klasu bez učitavanja stvarnih postavki.
     */
    public function testServiceFactoryUsesTheConfiguredApplicationRoot(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('getAppRootDir')->willReturn($this->root);
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with(ConfigInterface::class)->willReturn($config);
        $services = require dirname(__DIR__, 3) . '/config/services.php';
        $paths = $services[ConfigurationPaths::class]($container);
        $this->assertInstanceOf(ConfigurationPaths::class, $paths);
        $this->assertSame([$this->root . '/config'], $paths->directories());
    }

    /**
     * EN: Clearly reports invalid container configuration.
     *
     * HR: Jasno prijavljuje neispravnu konfiguraciju kontejnera.
     */
    public function testServiceFactoryRejectsMissingConfiguration(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with(ConfigInterface::class)->willReturn(null);
        $services = require dirname(__DIR__, 3) . '/config/services.php';
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('configuration paths are unavailable');
        $services[ConfigurationPaths::class]($container);
    }

    /**
     * EN: Empty environment values use the resolver default.
     *
     * HR: Prazan env koristi zadanu putanju resolvera.
     */
    public function testBlankEnvironmentAndRootDirectory(): void
    {
        putenv('HPH_CONFIG_PATH=   ');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=   ');
        $this->assertSame([$this->root . '/config'], ConfigurationPaths::fromEnvironment($this->root)->directories());
        putenv('HPH_CONFIG_PATH=' . DIRECTORY_SEPARATOR);
        $this->assertSame(
            [DIRECTORY_SEPARATOR . 'email.php'],
            ConfigurationPaths::fromEnvironment($this->root)->candidateFiles('email.php'),
        );
    }

    /**
     * EN: Does not change existing framework rules for relative directories.
     *
     * HR: Ne mijenja postojeća framework pravila relativnih direktorija.
     */
    public function testResolutionPreservesFrameworkSemantics(): void
    {
        putenv('HPH_CONFIG_PATH=relative-config');
        $this->assertSame(
            ConfigPathResolver::fromEnvironment($this->root),
            ConfigurationPaths::fromEnvironment($this->root)->directories(),
        );
    }

    /**
     * EN: Complete documents read the highest existing version; writes target the last layer even when empty.
     *
     * HR: Potpuni dokument čita najvišu postojeću verziju; zapis cilja zadnji sloj čak i kada je prazan.
     */
    public function testDocumentPriorityAndWriteTargetWithoutFallback(): void
    {
        $this->assertTrue(mkdir($this->root . '/override'));
        $this->assertTrue(mkdir($this->root . '/last'));
        file_put_contents($this->root . '/config/settings.json', '{"value":"base"}');
        file_put_contents($this->root . '/override/settings.json', '{"value":"override"}');
        $overrides = implode(PATH_SEPARATOR, [$this->root . '/override', $this->root . '/last']);
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $overrides);
        $paths = ConfigurationPaths::fromEnvironment($this->root);
        $this->assertSame($this->root . '/override/settings.json', $paths->effectiveFile('settings.json'));
        $this->assertSame($this->root . '/last/settings.json', $paths->writableFile('settings.json'));
        $this->assertFileDoesNotExist($this->root . '/last/settings.json');
        $this->assertSame('{"value":"base"}', file_get_contents($this->root . '/config/settings.json'));
    }

    /**
     * EN: PHP key merging stays with the framework without changing contents.
     *
     * HR: Spajanje PHP polja ostaje odgovornost frameworka, bez promjene sadržaja.
     */
    public function testPhpConfigurationUsesNativeLayeredMerge(): void
    {
        $this->assertTrue(mkdir($this->root . '/override'));
        file_put_contents($this->root . '/config/example.php', '<?php return ["keep" => "base", "change" => "base"];');
        file_put_contents($this->root . '/override/example.php', '<?php return ["change" => "override"];');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/override');
        $config = new Config(new Helper(), [], $this->root);
        $config->loadLayeredDirectories(ConfigurationPaths::fromEnvironment($this->root)->directories());
        $this->assertSame('base', $config->get('example.keep'));
        $this->assertSame('override', $config->get('example.change'));
    }

    /**
     * EN: A missing declared layer is not a silent fallback.
     *
     * HR: Nedostajući deklarirani sloj nije prešutni fallback.
     */
    public function testMissingLayerIsReportedWithoutCreatingIt(): void
    {
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/missing');
        $paths = ConfigurationPaths::fromEnvironment($this->root);
        $this->assertDirectoryDoesNotExist($this->root . '/missing');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($this->root . '/missing');
        $paths->effectiveFile('settings.json');
    }

    /**
     * EN: A directory in place of a document must not expose a lower version.
     *
     * HR: Direktorij umjesto dokumenta ne smije otkriti slabiju verziju.
     */
    public function testInvalidHigherDocumentDoesNotFallBack(): void
    {
        $this->assertTrue(mkdir($this->root . '/override/settings.json', 0770, true));
        file_put_contents($this->root . '/config/settings.json', '{}');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/override');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($this->root . '/override/settings.json');
        ConfigurationPaths::fromEnvironment($this->root)->effectiveFile('settings.json');
    }

    /**
     * EN: A broken symlink is not missing configuration or an allowed write target.
     *
     * HR: Slomljeni symlink nije nedostajući config niti dopušten cilj zapisa.
     */
    public function testBrokenDocumentLinkDoesNotFallBack(): void
    {
        $this->assertTrue(mkdir($this->root . '/override'));
        $this->assertTrue(symlink($this->root . '/not-there', $this->root . '/override/settings.json'));
        file_put_contents($this->root . '/config/settings.json', '{}');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/override');
        $this->expectException(RuntimeException::class);
        ConfigurationPaths::fromEnvironment($this->root)->effectiveFile('settings.json');
    }

    /**
     * EN: An existing document symlink can be read, but the helper does not offer it for replacement.
     *
     * HR: Postojeći dokument symlink može se čitati, ali ga pomoćni sloj ne nudi za zamjenu.
     */
    public function testReadableDocumentLinkIsNotAWriteTarget(): void
    {
        file_put_contents($this->root . '/source.json', '{}');
        $this->assertTrue(symlink($this->root . '/source.json', $this->root . '/config/settings.json'));
        $paths = ConfigurationPaths::fromEnvironment($this->root);
        $this->assertSame($this->root . '/config/settings.json', $paths->effectiveFile('settings.json'));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('symbolic link');
        $paths->writableFile('settings.json');
    }

    /**
     * EN: Headless reading works from a read-only layer; writing never falls back to a lower layer.
     *
     * HR: Headless čitanje radi iz read-only sloja; zapis ne pada na niži sloj.
     */
    public function testReadOnlyOverrideAllowsReadingButRejectsWriting(): void
    {
        $this->assertTrue(mkdir($this->root . '/override'));
        file_put_contents($this->root . '/override/settings.json', '{}');
        $this->assertTrue(chmod($this->root . '/override', 0550));
        clearstatcache();
        if (is_writable($this->root . '/override')) {
            self::markTestSkipped('This account bypasses directory write permissions.');
        }

        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/override');
        $paths = ConfigurationPaths::fromEnvironment($this->root);
        $this->assertSame($this->root . '/override/settings.json', $paths->effectiveFile('settings.json'));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not writable');
        $paths->writableFile('new/settings.json');
    }

    /**
     * EN: A read-only file stays protected even when its directory is writable.
     *
     * HR: Read-only datoteka ostaje zaštićena i kada je njen direktorij zapisiv.
     */
    public function testReadOnlyDocumentCannotBeReplaced(): void
    {
        file_put_contents($this->root . '/config/settings.json', '{}');
        $this->assertTrue(chmod($this->root . '/config/settings.json', 0440));
        clearstatcache();
        if (is_writable($this->root . '/config/settings.json')) {
            self::markTestSkipped('This account bypasses file write permissions.');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration file is not writable');
        ConfigurationPaths::fromEnvironment($this->root)->writableFile('settings.json');
    }

    /**
     * EN: An unsearchable declared directory is not a valid empty layer.
     *
     * HR: Nepretraživ deklarirani direktorij nije valjan prazan sloj.
     */
    public function testUnsearchableDirectoryIsReported(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            self::markTestSkipped('Unix directory search permission is required.');
        }

        $this->assertTrue(chmod($this->root . '/config', 0440));
        clearstatcache();
        if (is_executable($this->root . '/config')) {
            self::markTestSkipped('This account bypasses directory search permissions.');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($this->root . '/config');
        ConfigurationPaths::fromEnvironment($this->root)->assertReadable();
    }

    /**
     * EN: A file occupying the parent directory blocks writing.
     *
     * HR: Datoteka na mjestu roditeljskog direktorija blokira zapis.
     */
    public function testNonDirectoryParentIsReported(): void
    {
        file_put_contents($this->root . '/config/email', 'not a directory');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration parent directory is not readable');
        ConfigurationPaths::fromEnvironment($this->root)->writableFile('email/settings.json');
    }

    /**
     * EN: An invalid parent in a higher layer does not allow reading a lower document.
     *
     * HR: Nevaljan roditelj u jačem sloju ne dopušta čitanje slabijeg dokumenta.
     */
    public function testInvalidReadParentDoesNotFallBack(): void
    {
        $this->assertTrue(mkdir($this->root . '/config/email'));
        $this->assertTrue(mkdir($this->root . '/override'));
        file_put_contents($this->root . '/config/email/settings.json', '{}');
        file_put_contents($this->root . '/override/email', 'not a directory');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/override');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Configuration parent directory is not readable');
        ConfigurationPaths::fromEnvironment($this->root)->effectiveFile('email/settings.json');
    }

    /**
     * EN: Writing must not escape its layer through a parent symlink.
     *
     * HR: Zapis ne smije izaći iz sloja preko roditeljskog symlinka.
     */
    public function testParentSymbolicLinkRejectsWriting(): void
    {
        $this->assertTrue(mkdir($this->root . '/external'));
        file_put_contents($this->root . '/external/settings.json', '{}');
        $this->assertTrue(symlink($this->root . '/external', $this->root . '/config/email'));
        $paths = ConfigurationPaths::fromEnvironment($this->root);
        $this->assertSame($this->root . '/config/email/settings.json', $paths->effectiveFile('email/settings.json'));
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('write parent must not be a symbolic link');
        $paths->writableFile('email/settings.json');
    }

    /**
     * EN: Stream directories fail clearly without reading contents.
     *
     * HR: Stream direktoriji daju jasnu grešku bez čitanja sadržaja.
     */
    #[DataProvider('invalidDirectories')]
    public function testInvalidDirectory(string $directory): void
    {
        putenv('HPH_CONFIG_PATH=' . $directory);
        $this->expectException(InvalidArgumentException::class);
        ConfigurationPaths::fromEnvironment($this->root)->assertReadable();
    }

    /**
     * EN: Invalid local directories for isolated validation.
     *
     * HR: Nevaljani lokalni direktoriji za izoliranu provjeru.
     *
     * @return \Iterator<int,array{string}>
     */
    public static function invalidDirectories(): \Iterator
    {
        yield ['php://memory'];
        yield ['file://localhost/config'];
    }

    /**
     * EN: Rejects layer traversal, absolute names, and stream names.
     *
     * HR: Zabranjuje izlazak iz sloja, apsolutna i stream imena.
     */
    #[DataProvider('invalidFiles')]
    public function testInvalidRelativeFile(string $relativeFile): void
    {
        $this->expectException(InvalidArgumentException::class);
        ConfigurationPaths::fromEnvironment($this->root)->candidateFiles($relativeFile);
    }

    /**
     * EN: Unsafe or ambiguous file names.
     *
     * HR: Opasna ili nejednoznačna imena datoteka.
     *
     * @return \Iterator<int,array{string}>
     */
    public static function invalidFiles(): \Iterator
    {
        yield [''];
        yield ['/etc/settings.php'];
        yield ['../settings.php'];
        yield ['auth/../../settings.json'];
        yield ['./settings.php'];
        yield ['auth//auth.json'];
        yield ['auth/'];
        yield ['C:/settings.php'];
        yield ['php://memory'];
        yield ['auth\\auth.json'];
        yield ["auth\0.json"];
        yield [' settings.json '];
    }
}
