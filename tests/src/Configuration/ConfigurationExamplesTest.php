<?php

declare(strict_types=1);

namespace Tests\Configuration;

use AaiEduHr\HeartPhrameModuleApi\Service\ApiConfiguration;
use AaiEduHr\HeartPhrameModuleApi\Service\WebhookConfig;
use AaiEduHr\HeartPhrameModuleAuth\Configuration\AuthConfigurationStore;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupConfig;
use AaiEduHr\HeartPhrameModuleCalendar\Service\CalendarConfig;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlConfigurationStore;
use AaiEduHr\HeartPhrameModuleEmail\Service\EmailConfigurationStore;
use AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceConfigurationStore;
use HeartPhrame\Bridge\ComposerBridge;
use HeartPhrame\Config\Config;
use HeartPhrame\Config\ConfigInterface;
use HeartPhrame\Config\ConfigPathResolver;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * EN: Checks example configurations through real modules without private HFC settings.
 *
 * HR: Ogledne konfiguracije provjerava stvarnim modulima, bez privatnih HFC postavki.
 */
#[CoversNothing]
final class ConfigurationExamplesTest extends TestCase
{
    /**
     * EN: PHP policy examples are usable without the GUI and explain each nested option.
     *
     * HR: PHP primjeri politika upotrebljivi su bez GUI-ja i opisuju svaku ugniježđenu opciju.
     */
    public function testApiAndBackupPhpExamplesWithoutGui(): void
    {
        $app = dirname(__DIR__, 3);
        $root = sys_get_temp_dir() . '/simbioza-policy-example-' . bin2hex(random_bytes(6));
        $environment = [getenv('HPH_CONFIG_PATH'), getenv('HPH_CONFIG_OVERRIDE_PATHS')];
        mkdir($root, 0700);
        putenv('HPH_CONFIG_PATH=' . $root);
        putenv('HPH_CONFIG_OVERRIDE_PATHS=');
        try {
            foreach (['api', 'backup'] as $module) {
                $example = $app . '/config/' . $module . '.php.dist';
                $package = $app . '/vendor/aaieduhr/heartphrame-module-' . $module;
                $contents = (string)file_get_contents($example);
                $this->assertSame(file_get_contents($package . '/' . $module . '.php.dist'), $contents);
                preg_match_all('/^\/\/ (EN|HR):/m', $contents, $languages);
                $this->assertSame(['EN', 'HR'], $languages[1]);
                $this->assertDoesNotMatchRegularExpression('/(?:HR:.*EN:|EN:.*HR:)/', $contents);
                $values = require $example;
                $this->assertIsArray($values);
                $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($values));
                foreach ($iterator as $key => $value) {
                    if (is_string($key)) {
                        $this->assertStringContainsString($key, substr($contents, 0, strpos($contents, 'return [')));
                    }
                }

                mkdir($root . '/' . $module, 0700);
                copy($example, $root . '/' . $module . '/' . $module . '.php');
            }

            $config = new Config(new Helper(), [], $app);
            $config->loadLayeredDirectories(ConfigPathResolver::fromEnvironment($app));
            $this->assertSame(120, ApiConfiguration::resolve($config)->getAsInt('api.rate_limit_per_minute'));
            $this->assertFalse((new WebhookConfig($config))->enabled());
            $backup = new BackupConfig(
                $config,
                new ComposerBridge(),
                $app . '/vendor/aaieduhr/heartphrame-module-backup',
            );
            $this->assertSame(realpath($app), realpath($backup->applicationRoot()));
            $this->assertSame($backup->applicationRoot() . '/data/backups/archives', $backup->archiveDirectory());
            $this->assertSame(8 * 1024 * 1024, $backup->chunkSize());
            $this->assertTrue($backup->requireMaintenanceForFullReplace());
            $this->assertSame(0, $backup->archiveTtl());
            $this->assertDirectoryDoesNotExist($root . '/data');
        } finally {
            foreach (['HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $index => $key) {
                putenv($environment[$index] === false ? $key : $key . '=' . $environment[$index]);
            }

            foreach (['api', 'backup'] as $module) {
                $target = $root . '/' . $module . '/' . $module . '.php';
                if (is_file($target)) {
                    unlink($target);
                }

                if (is_dir($root . '/' . $module)) {
                    rmdir($root . '/' . $module);
                }
            }

            rmdir($root);
        }
    }

    /**
     * EN: Calendar's PHP example remains current; JSON conversion is outside P6.
     *
     * HR: Calendarov PHP primjer ostaje aktualan; JSON pretvorba nije dio P6.
     */
    public function testCurrentCalendarPhpExample(): void
    {
        $app = dirname(__DIR__, 3);
        $module = $app . '/vendor/aaieduhr/heartphrame-module-calendar';
        $example = $app . '/config/calendar.php.dist';
        $this->assertSame(file_get_contents($module . '/config/calendar.php.dist'), file_get_contents($example));
        $contents = (string)file_get_contents($example);
        preg_match_all('/^\/\/ (EN|HR):/m', $contents, $languages);
        $this->assertSame(['EN', 'HR'], $languages[1]);
        $this->assertDoesNotMatchRegularExpression('/(?:HR:.*EN:|EN:.*HR:)/', $contents);
        $root = sys_get_temp_dir() . '/simbioza-calendar-example-' . bin2hex(random_bytes(6));
        mkdir($root . '/calendar', 0700, true);
        $target = $root . '/calendar/calendar.php';
        $environment = [getenv('HPH_CONFIG_PATH'), getenv('HPH_CONFIG_OVERRIDE_PATHS')];
        putenv('HPH_CONFIG_PATH=' . $root);
        putenv('HPH_CONFIG_OVERRIDE_PATHS=');
        try {
            copy($example, $target);
            $config = $this->createStub(ConfigInterface::class);
            $config->method('getAppRootDir')->willReturn($app);
            $calendar = new CalendarConfig($config, $module);

            $this->assertTrue($calendar->isEnabled());
            $this->assertTrue($calendar->isCalDavEnabled());
            $this->assertTrue($calendar->shouldAutoRegisterTopMenu());
            $this->assertTrue($calendar->shouldAutoRegisterSettingsMenu());
            // EN: Does not read the internal help path from the old application root.
            //
            // HR: Ne čita internu putanju uputa iz starog aplikacijskog korijena.
            $this->assertNull($calendar->meetingGuidePath());
            $this->assertFileDoesNotExist($target . '.lock');
            $this->assertSame([$target], glob($root . '/calendar/*'));
        } finally {
            foreach (['HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $index => $key) {
                putenv($environment[$index] === false ? $key : $key . '=' . $environment[$index]);
            }

            unlink($target);
            rmdir($root . '/calendar');
            rmdir($root);
        }
    }

    /**
     * EN: Package example sources must stay identical to their Simbioza copies.
     *
     * HR: Paketni izvori primjera moraju ostati jednaki kopijama u Simbiozi.
     *
     * @return iterable<string,array{string,string,string}>
     */
    public static function examples(): iterable
    {
        yield 'Auth' => ['auth', 'heartphrame-module-auth', 'auth.json.dist'];
        yield 'Email' => ['email', 'heartphrame-module-email', 'config/email.json.dist'];
        yield 'Editor' => ['editor-html', 'heartphrame-module-editor-html', 'config/editor-html.json.dist'];
        yield 'Workspace' => ['workspace', 'simbioza-module-workspace', 'config/workspace.json.dist'];
    }

    /**
     * EN: Descriptions are inert; examples are valid and never change development configuration.
     *
     * HR: Opisi su inertni; primjeri su valjani i ne mijenjaju razvojnu konfiguraciju.
     */
    #[DataProvider('examples')]
    public function testCurrentJsonExample(string $name, string $package, string $relative): void
    {
        $app = dirname(__DIR__, 3);
        $module = $app . '/vendor/aaieduhr/' . $package;
        $example = $app . '/config/' . $name . '.json.dist';
        $this->assertSame(file_get_contents($module . '/' . $relative), file_get_contents($example));
        $values = json_decode((string)file_get_contents($example), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['en', 'hr'], array_keys($values['_documentation']));
        $this->assertIsArray($values['_documentation']['hr']);
        $this->assertIsArray($values['_documentation']['en']);
        $this->assertFileDoesNotExist($app . '/config/' . $name . '.php.dist');

        $root = sys_get_temp_dir() . '/simbioza-config-example-' . bin2hex(random_bytes(6));
        mkdir($root . '/' . $name, 0700, true);
        $environment = [getenv('HPH_CONFIG_PATH'), getenv('HPH_CONFIG_OVERRIDE_PATHS')];
        putenv('HPH_CONFIG_PATH=' . $root);
        putenv('HPH_CONFIG_OVERRIDE_PATHS=');
        try {
            $target = $root . '/' . $name . '/' . $name . '.json';
            copy($example, $target);
            $loaded = match ($name) {
                'auth' => (new AuthConfigurationStore($target))->read(),
                'email' => (new EmailConfigurationStore($root, require $module . '/config/email.php'))->read(),
                'editor-html' => (new EditorHtmlConfigurationStore(
                    $root,
                    EditorHtmlConfigurationStore::packageDefaults($module),
                ))->read(),
                'workspace' => (new WorkspaceConfigurationStore(
                    $root,
                    require $module . '/config/workspace.php',
                ))->read(),
                default => throw new \LogicException('Unknown configuration example.'),
            };
            $this->assertArrayHasKey(match ($name) {
                'auth' => 'providers', 'email' => 'smtp', 'editor-html' => 'storage', default => 'homepage',
            }, $loaded);
            $this->assertFileDoesNotExist($target . '.lock');
            $this->assertDirectoryDoesNotExist($root . '/data');
        } finally {
            foreach (['HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $index => $key) {
                putenv($environment[$index] === false ? $key : $key . '=' . $environment[$index]);
            }

            unlink($root . '/' . $name . '/' . $name . '.json');
            rmdir($root . '/' . $name);
            rmdir($root);
        }
    }
}
