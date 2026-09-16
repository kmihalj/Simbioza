<?php

declare(strict_types=1);

namespace Tests\Localization;

use App\Localization\LanguagePackManager;
use App\Module\ModuleCatalog;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

#[CoversClass(LanguagePackManager::class)]
#[CoversClass(ModuleCatalog::class)]
final class LanguagePackManagerTest extends TestCase
{
    private string $root;

    /** HR: Priprema najmanju izoliranu instalaciju jezika. EN: Prepares the smallest isolated language installation. */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/simbioza-language-test-' . bin2hex(random_bytes(8));
        foreach (['config', 'data/languages/flags', 'lang'] as $directory) {
            if (!mkdir($this->root . '/' . $directory, 0770, true) && !is_dir($this->root . '/' . $directory)) {
                throw new RuntimeException('Unable to create the language test directory.');
            }
        }

        file_put_contents($this->root . '/lang/en.php', "<?php return ['welcome' => 'Welcome, :name!'];\n");
        file_put_contents($this->root . '/config/languages.php', <<<'PHP'
<?php
return [
    'en' => [
        'names' => ['en' => 'English'],
        'native_name' => 'English',
        'flag' => 'en.svg',
    ],
];
PHP);
        file_put_contents($this->root . '/config/installation.php', "<?php return ['supported_locales' => ['en']];\n");
    }

    /** HR: Uklanja samo privremene datoteke testa. EN: Removes only the test's temporary files. */
    protected function tearDown(): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $entry) {
            if (!$entry instanceof SplFileInfo) {
                continue;
            }

            $path = $entry->getPathname();
            $entry->isFile() || $entry->isLink() ? unlink($path) : rmdir($path);
        }

        rmdir($this->root);
    }

    /**
     * HR: Dodani jezik ostavlja privatnu instalacijsku konfiguraciju zapisivu
     *     vlasniku i isključivoj runtime grupi.
     * EN: An added locale leaves private installation configuration writable
     *     by its owner and the exclusive runtime group.
     */
    public function testInstalledLanguageKeepsSharedRuntimeConfigurationMode(): void
    {
        $manager = new LanguagePackManager($this->root, new ModuleCatalog());
        $template = $manager->createTemplate('de', 'en', $this->root . '/data/de.json');
        $payload = json_decode((string)file_get_contents($template), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($payload);
        $payload['names'] = ['de' => 'Deutsch', 'en' => 'German'];
        $payload['native_name'] = 'Deutsch';
        $payload['flag_svg'] = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 2">'
        . '<rect width="3" height="2" fill="#000"/></svg>';
        file_put_contents($template, json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        $manager->install($template);

        $this->assertSame(0660, fileperms($this->root . '/config/installation.php') & 0777);
        $configuration = require $this->root . '/config/installation.php';
        $this->assertContains('de', $configuration['supported_locales']);
    }
}
