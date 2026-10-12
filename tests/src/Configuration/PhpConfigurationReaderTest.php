<?php

declare(strict_types=1);

namespace Tests\Configuration;

use App\Configuration\ConfigurationPaths;
use App\Configuration\PhpConfigurationReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(PhpConfigurationReader::class)]
#[UsesClass(ConfigurationPaths::class)]
final class PhpConfigurationReaderTest extends TestCase
{
    private string $root;

    /** @var array<string,string|false> */
    private array $environment = [];

    /**
     * EN: Isolates two layers with dummy settings.
     *
     * HR: Izolira dva sloja s lažnim postavkama.
     */
    protected function setUp(): void
    {
        foreach (['HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $name) {
            $this->environment[$name] = getenv($name);
        }

        $this->root = sys_get_temp_dir() . '/simbioza-php-config-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/base', 0770, true);
        mkdir($this->root . '/last', 0770);
        putenv('HPH_CONFIG_PATH=' . $this->root . '/base');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/last');
    }

    /**
     * EN: Removes only isolated files and restores the environment.
     *
     * HR: Uklanja samo izolirane datoteke i vraća okoliš.
     */
    protected function tearDown(): void
    {
        foreach (['base', 'last'] as $directory) {
            foreach (glob($this->root . '/' . $directory . '/*') ?: [] as $file) {
                is_dir($file) ? rmdir($file) : unlink($file);
            }

            rmdir($this->root . '/' . $directory);
        }

        rmdir($this->root);
        foreach ($this->environment as $name => $value) {
            is_string($value) ? putenv($name . '=' . $value) : putenv($name);
        }
    }

    /**
     * EN: Reads use native key merging, not just the last file.
     *
     * HR: Čitanje koristi izvorno spajanje polja, ne samo zadnju datoteku.
     */
    public function testNativeMergePreservesLowerKeysAndReplacesLists(): void
    {
        file_put_contents(
            $this->root . '/base/setup.php',
            '<?php return ["helper"=>"fixture", "options"=>["keep"=>1,"change"=>1],"items"=>[1,2]];',
        );
        file_put_contents($this->root . '/last/setup.php', '<?php return ["options"=>["change"=>2],"items"=>[]];');
        $reader = new PhpConfigurationReader(ConfigurationPaths::fromEnvironment($this->root));
        $this->assertSame(
            ['helper' => 'fixture', 'options' => ['keep' => 1, 'change' => 2], 'items' => []],
            $reader->read('setup.php'),
        );
        $this->assertSame([], $reader->read('missing.php'));
    }

    /**
     * EN: Config-file variables cannot overwrite reader state or subsequent layers.
     *
     * HR: Lokalne varijable config datoteke ne prepisuju stanje učitavača ni sljedećih slojeva.
     */
    public function testConfigurationVariablesAreIsolatedFromReaderState(): void
    {
        file_put_contents(
            $this->root . '/base/app.php',
            '<?php $configuration = null; $path = "not-a-file"; return ["keep" => 1, "change" => 1];',
        );
        file_put_contents(
            $this->root . '/last/app.php',
            '<?php $configuration = "local-value"; $relativeFile = "other.php"; return ["change" => 2];',
        );
        $reader = new PhpConfigurationReader(ConfigurationPaths::fromEnvironment($this->root));
        $this->assertSame(['keep' => 1, 'change' => 2], $reader->read('app.php'));
        $this->assertSame(['change' => 2], $reader->readDocument('app.php'));
        $this->assertSame(['keep' => 1, 'change' => 2], $reader->read('app.php'));
    }

    /**
     * EN: An invalid higher file never yields a lower fallback.
     *
     * HR: Nevaljana jača datoteka ne daje niži fallback.
     */
    public function testInvalidArrayFailsWithoutFallback(): void
    {
        file_put_contents($this->root . '/base/setup.php', '<?php return ["keep"=>true];');
        file_put_contents($this->root . '/last/setup.php', '<?php return false;');
        $this->expectException(RuntimeException::class);
        (new PhpConfigurationReader(ConfigurationPaths::fromEnvironment($this->root)))->read('setup.php');
    }

    /**
     * EN: An invalid lower layer is not skipped even with a valid higher layer.
     *
     * HR: Niži nevaljani sloj se ne preskače ni kad postoji valjan jači.
     */
    public function testInvalidLowerLayerIsNotSkipped(): void
    {
        mkdir($this->root . '/base/setup.php');
        file_put_contents($this->root . '/last/setup.php', '<?php return [];');
        $this->expectException(RuntimeException::class);
        (new PhpConfigurationReader(ConfigurationPaths::fromEnvironment($this->root)))->read('setup.php');
    }

    /**
     * EN: A complete higher registry never restores deleted lower keys.
     *
     * HR: Cijeli registar iz jačeg sloja ne vraća obrisane niže ključeve.
     */
    public function testDocumentUsesOnlyHighestCompleteRegistry(): void
    {
        file_put_contents($this->root . '/base/languages.php', '<?php return ["en"=>[],"de"=>[]];');
        $reader = new PhpConfigurationReader(ConfigurationPaths::fromEnvironment($this->root));
        $this->assertSame(['en' => [], 'de' => []], $reader->readDocument('languages.php'));
        file_put_contents($this->root . '/last/languages.php', '<?php return ["en"=>[]];');
        $this->assertSame(['en' => []], $reader->readDocument('languages.php'));
        file_put_contents($this->root . '/last/languages.php', '<?php return [];');
        $this->assertSame([], $reader->readDocument('languages.php'));
        $this->assertSame([], $reader->readDocument('missing.php'));
    }

    /**
     * EN: An invalid complete registry fails without lower fallback.
     *
     * HR: Nevaljani cijeli registar je greška, ne niži fallback.
     */
    public function testInvalidDocumentFailsWithoutFallback(): void
    {
        file_put_contents($this->root . '/base/languages.php', '<?php return ["en"=>[]];');
        file_put_contents($this->root . '/last/languages.php', '<?php return false;');
        $this->expectException(RuntimeException::class);
        (new PhpConfigurationReader(ConfigurationPaths::fromEnvironment($this->root)))->readDocument('languages.php');
    }
}
