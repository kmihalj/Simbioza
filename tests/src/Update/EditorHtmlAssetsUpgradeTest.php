<?php

declare(strict_types=1);

namespace Tests\Update;

use App\Update\EditorHtmlAssetsUpgrade;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class EditorHtmlAssetsUpgradeTest extends TestCase
{
    private string $root;

    private string|false $configPath;

    /**
     * EN: Uses isolated configuration and payload paths without loading application services or a database.
     *
     * HR: Koristi izdvojene postavke i podatkovne putanje bez učitavanja aplikacijskih servisa ili baze.
     */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/simbioza-editor-css-upgrade-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/config/editor-html', 0700, true);
        $this->configPath = getenv('HPH_CONFIG_PATH');
        putenv('HPH_CONFIG_PATH=' . $this->root . '/config');
        file_put_contents($this->root . '/config/editor-html/editor-html.json', json_encode([
            'storage' => ['filesystem_path' => 'custom-editor'],
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * EN: Removes only test-owned paths and restores the previous configuration environment.
     *
     * HR: Uklanja samo testne putanje i vraća prethodnu konfiguracijsku varijablu okoline.
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
        $this->configPath === false
        ? putenv('HPH_CONFIG_PATH')
        : putenv('HPH_CONFIG_PATH=' . $this->configPath);
    }

    /**
     * EN: An installed but disabled editor upgrades only existing CSS at its configured storage location.
     *
     * HR: Instalirani isključeni editor nadograđuje samo postojeći CSS na podešenoj podatkovnoj lokaciji.
     */
    public function testExplicitUpgradeRefreshesConfiguredSnapshotWithoutEnablingEditor(): void
    {
        $config = new Config(new Helper(), ['app' => ['modules' => ['enabled' => []]]], $this->root);
        $this->assertFalse(EditorHtmlAssetsUpgrade::apply($config));
        $this->assertDirectoryDoesNotExist($this->root . '/data');

        $directory = $this->root . '/data/custom-editor/documents/assets';
        mkdir($directory, 0700, true);
        $path = $directory . '/editor-html-standalone.css';
        file_put_contents($path, 'old CSS');
        file_put_contents($directory . '/theme.css', 'existing theme');
        file_put_contents($directory . '/old.html', '<p>Existing HTML version</p>');

        $this->assertTrue(EditorHtmlAssetsUpgrade::apply($config));
        $this->assertStringContainsString('max-inline-size:', (string)file_get_contents($path));
        $this->assertSame('existing theme', file_get_contents($directory . '/theme.css'));
        $this->assertSame('<p>Existing HTML version</p>', file_get_contents($directory . '/old.html'));
        $this->assertFalse(EditorHtmlAssetsUpgrade::apply($config));
        $this->assertDirectoryDoesNotExist($this->root . '/data/editor-html');
    }
}
