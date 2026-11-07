<?php

declare(strict_types=1);

namespace Tests\Tools;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class E2eApplicationConfigurationTest extends TestCase
{
    private string $root;

    /**
     * EN: Creates only the small configuration tree consumed by the actual E2E preparation helper.
     *
     * HR: Stvara samo malo stablo postavki koje koristi stvarna pomoćna funkcija pripreme E2E instalacije.
     */
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 3) . '/scripts/run_e2e.php';
        $this->root = sys_get_temp_dir() . '/simbioza-e2e-config-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/config/api', 0700, true);
        mkdir($this->root . '/config/theme', 0700, true);
        file_put_contents($this->root . '/config/app.php', "<?php return ['cookie_secure' => 1,];\n");
        file_put_contents($this->root . '/config/api/api.php', "<?php return [];\n");
        file_put_contents($this->root . '/config/theme/settings.json', '{}');
    }

    /**
     * EN: Removes only this test's temporary files, never a real application configuration.
     *
     * HR: Uklanja samo privremene datoteke ovoga testa, nikada postavke stvarne aplikacije.
     */
    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->root);
    }

    /**
     * EN: Tests clean installations, existing restrictions and already-configured fixtures with duplicate rows.
     *
     * HR: Provjerava čiste instalacije, postojeća ograničenja i već podešene testne tipove s ponovljenim retcima.
     *
     * @return \Iterator<string, array{list<string>|null, list<string>}>
     */
    public static function mimeConfigurations(): \Iterator
    {
        yield 'missing editor JSON' => [null, ['text/plain', 'image/png']];
        yield 'existing custom restriction' => [
            ['application/pdf', 'text/plain', 'application/pdf'],
            ['application/pdf', 'text/plain', 'image/png'],
        ];
        yield 'existing PNG and repeated fixture types' => [
            ['image/png', 'application/pdf', 'text/plain', 'image/png', 'text/plain'],
            ['image/png', 'application/pdf', 'text/plain'],
        ];
    }

    /**
     * EN: Preparation explicitly admits both binary PNG and text fixtures while retaining existing settings.
     *
     * HR: Priprema izričito dopušta binarne PNG i tekstne testne datoteke te čuva postojeće postavke.
     *
     * @param ?list<string> $existing
     * @param list<string> $expected
     */
    #[DataProvider('mimeConfigurations')]
    public function testAttachmentFixturePolicyPreservesExistingMimeTypes(?array $existing, array $expected): void
    {
        $path = $this->root . '/config/editor-html/editor-html.json';
        if ($existing !== null) {
            mkdir(dirname($path), 0700);
            file_put_contents($path, json_encode([
                'uploads' => ['allowed_mime_types' => $existing, 'max_size_mb' => 17],
                'storage' => ['driver' => 'database'],
            ], JSON_THROW_ON_ERROR));
        } else {
            $this->assertFileDoesNotExist($path);
        }

        \HFClean\Tools\configureE2eApplication($this->root);

        $configured = json_decode((string)file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($expected, $configured['uploads']['allowed_mime_types']);
        if ($existing !== null) {
            $this->assertSame(17, $configured['uploads']['max_size_mb']);
            $this->assertSame(['driver' => 'database'], $configured['storage']);
        }
    }
}
