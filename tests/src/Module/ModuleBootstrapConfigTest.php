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

        $this->writeModuleState([
            'aaieduhr/heartphrame-module-orm',
        ]);
        $disabledConfiguration = require $configDirectory . '/app.php';
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
        $enabledConfiguration = require $configDirectory . '/app.php';
        $this->assertContains(
            'aaieduhr/heartphrame-module-theme',
            $enabledConfiguration['modules']['enabled'],
        );
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
