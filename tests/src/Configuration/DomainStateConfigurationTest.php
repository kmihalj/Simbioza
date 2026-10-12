<?php

declare(strict_types=1);

namespace Tests\Configuration;

use AaiEduHr\HeartPhrameModuleComment\Service\CommentSettingsService;
use AaiEduHr\HeartPhrameModuleNotification\Service\NotificationEmailBridge;
use AaiEduHr\HeartPhrameModuleNotification\Service\NotificationPreferenceService;
use AaiEduHr\HeartPhrameModuleNotification\Service\NotificationService;
use AaiEduHr\HeartPhrameModuleNotification\Service\NotificationVisibilityRegistry;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\Migration\ReversibleMigrationInterface;
use AaiEduHr\HeartPhrameModuleTask\Service\TaskDefinition;
use AaiEduHr\HeartPhrameModuleTask\Service\TaskStateService;
use App\Configuration\ConfigurationPaths;
use App\Module\ModuleCatalog;
use App\Module\ModuleStateStore;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * EN: Domain services use shared host configuration without a settings GUI or private module files.
 *
 * HR: Domenski servisi koriste zajedničku konfiguraciju bez GUI-ja postavki ili zasebnih datoteka modula.
 */
#[CoversNothing]
final class DomainStateConfigurationTest extends TestCase
{
    private string $root;

    /** @var array<string,string|false> */
    private array $environment = [];

    /**
     * EN: Creates only fictional host configuration and restores environment values afterwards.
     *
     * HR: Stvara samo izmišljenu konfiguraciju domaćina i poslije vraća vrijednosti okruženja.
     */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/simbioza-domain-config-' . bin2hex(random_bytes(8));
        foreach (['config', 'external', 'override'] as $directory) {
            $this->assertTrue(mkdir($this->root . '/' . $directory, 0700, true));
        }

        foreach (['HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $key) {
            $this->environment[$key] = getenv($key);
            putenv($key);
        }
    }

    /**
     * EN: Removes only owned fixtures, including their deliberately read-only configuration.
     *
     * HR: Briše samo vlastite fixture, uključujući namjerno read-only konfiguraciju.
     */
    protected function tearDown(): void
    {
        foreach (['config', 'external', 'override'] as $directory) {
            $path = $this->root . '/' . $directory;
            chmod($path, 0700);
            foreach (new \DirectoryIterator($path) as $file) {
                if (!$file->isDot()) {
                    unlink($file->getPathname());
                }
            }

            rmdir($path);
        }

        rmdir($this->root);
        foreach ($this->environment as $key => $value) {
            putenv($value === false ? $key : $key . '=' . $value);
        }
    }

    /**
     * EN: Business writes and personal choices stay outside default/external/layered read-only config.
     * The real ORM reads only the effective database.php; no configuration GUI is instantiated.
     *
     * HR: Poslovni zapisi i osobni odabiri ostaju izvan zadane/vanjske/slojevite read-only konfiguracije.
     * Stvarni ORM čita samo efektivni database.php; GUI konfiguracije se ne stvara.
     */
    #[DataProvider('configurationModes')]
    public function testDomainStateDoesNotBecomeInstallationConfiguration(string $mode): void
    {
        $packages = array_map(
            static fn(string $slug): string => 'aaieduhr/heartphrame-module-' . $slug,
            ['comment', 'task', 'notification', 'accessibility'],
        );
        $sqlite = ['connections' => ['default' => ['driver' => 'sqlite', 'database' => ':memory:']]];
        $this->writeConfiguration('config', 'database', $sqlite);
        $this->writeConfiguration('config', 'modules', ['enabled' => []]);
        if ($mode !== 'default') {
            // EN: The excluded default directory would fail if a consumer silently used it.
            //
            // HR: Izuzeti zadani direktorij izazvao bi grešku ako ga servis tiho upotrijebi.
            $this->writeConfiguration('config', 'database', [
                'connections' => ['default' => ['driver' => 'invalid-default-driver']],
            ]);
            $this->writeConfiguration('external', 'database', $sqlite);
            putenv('HPH_CONFIG_PATH=' . $this->root . '/external');
        }

        $selected = $mode === 'default' ? 'config' : 'external';
        $this->writeConfiguration($selected, 'modules', ['enabled' => $packages]);
        if ($mode === 'layered') {
            $this->writeConfiguration('external', 'database', [
                'connections' => ['default' => ['driver' => 'invalid-lower-driver']],
            ]);
            $this->writeConfiguration('override', 'database', $sqlite);
            $packages = ['aaieduhr/heartphrame-module-notification'];
            $this->writeConfiguration('override', 'modules', ['enabled' => $packages]);
            putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/override');
        }

        $before = $this->configurationSnapshot();
        foreach (['config', 'external', 'override'] as $directory) {
            foreach (new \DirectoryIterator($this->root . '/' . $directory) as $file) {
                if (!$file->isDot()) {
                    chmod($file->getPathname(), 0400);
                }
            }

            chmod($this->root . '/' . $directory, 0500);
        }

        $paths = ConfigurationPaths::fromEnvironment($this->root);
        $helper = new Helper();
        $config = new Config($helper, [], $this->root);
        $config->loadLayeredDirectories($paths->directories());

        $database = new Database($config, $helper);
        foreach (['comment', 'task', 'notification'] as $module) {
            $migration = require dirname(__DIR__, 3) . '/vendor/aaieduhr/heartphrame-module-'
            . $module . '/resources/migrations/initial_' . $module . '_schema.php';
            $this->assertInstanceOf(ReversibleMigrationInterface::class, $migration);
            $migration->up($database);
        }

        $comments = new CommentSettingsService($database);
        $comments->savePublished('page-a', 'en', false, 7);
        $comments->saveDraft('page-a', 'en', true, 7);
        $this->assertFalse($comments->publishedEnabled('page-a', 'en'));
        $this->assertTrue($comments->editorEnabled('page-a', 'en'));
        $this->assertTrue($comments->publishedEnabled('page-a', 'hr'));
        $this->assertTrue($comments->publishedEnabled('page-b', 'en'));
        $comments->publishDraft('page-a', 'en', 7);
        $this->assertTrue((new CommentSettingsService($database))->publishedEnabled('page-a', 'en'));

        $task = new TaskDefinition(
            'e3d1be37-04fd-4f69-ad6e-39fa9f085223',
            '5adf2862-a532-4d66-b916-b977284fc159',
            'Check document',
            'editors',
            'Publication',
        );
        $tasks = new TaskStateService($database);
        $tasks->setState($task, 'page-a', true, 7, 'Test user');
        $tasks->setState($task, 'page-a', true, 7, 'Test user');
        $tasks->setState($task, 'page-b', false, 8, 'Other user');

        $reloadedTasks = new TaskStateService($database);
        $this->assertCount(1, $reloadedTasks->history('page-a', $task->uuid));
        $this->assertTrue((bool)$reloadedTasks->statesFor('page-a', [$task->uuid])[$task->uuid]['is_completed']);
        $this->assertFalse((bool)$reloadedTasks->statesFor('page-b', [$task->uuid])[$task->uuid]['is_completed']);

        $preferences = new NotificationPreferenceService($database);
        $preferences->saveEmailEnabled(7, true);
        $this->assertTrue((new NotificationPreferenceService($database))->emailEnabled(7));
        $this->assertFalse($preferences->emailEnabled(8));
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(false);
        $container->method('get')->willThrowException(new \RuntimeException('Optional Email is absent.'));
        $notifications = new NotificationService(
            $database,
            new NotificationEmailBridge($container, $preferences),
            new NotificationVisibilityRegistry(),
        );
        $message = $notifications->notifyUser(7, 'test.event', 'Test', 'Local message');
        $this->assertSame(1, $notifications->unreadCount(7));
        $this->assertSame(0, $notifications->unreadCount(8));
        $this->assertNull($notifications->markRead(8, (string)$message['uuid']));
        $this->assertIsArray($notifications->markRead(7, (string)$message['uuid']));
        $this->assertSame(0, $notifications->unreadCount(7));

        $state = new ModuleStateStore($this->root, new ModuleCatalog());
        $this->assertSame($packages, $state->enabledPackages());
        $this->assertSame($before, $this->configurationSnapshot());
    }

    /**
     * EN: Exercises the same services in all supported configuration-root shapes.
     *
     * HR: Provjerava iste servise uz sve podržane oblike konfiguracijskog korijena.
     *
     * @return \Iterator<string, array{string}>
     */
    public static function configurationModes(): \Iterator
    {
        yield 'default' => ['default'];
        yield 'external' => ['external'];
        yield 'layered' => ['layered'];
    }

    /**
     * EN: Writes test-only shared PHP configuration, never an active application setting.
     *
     * HR: Piše samo testnu zajedničku PHP konfiguraciju, nikada aktivnu postavku aplikacije.
     *
     * @param array<string,mixed> $values
     */
    private function writeConfiguration(string $directory, string $name, array $values): void
    {
        $this->assertNotFalse(file_put_contents(
            $this->root . '/' . $directory . '/' . $name . '.php',
            '<?php return ' . var_export($values, true) . ';',
        ));
    }

    /**
     * EN: Detects changed files, unexpected module directories, or configuration locks.
     *
     * HR: Otkriva izmijenjene datoteke, neočekivane direktorije modula ili konfiguracijske lockove.
     *
     * @return array<string,string>
     */
    private function configurationSnapshot(): array
    {
        $snapshot = [];
        foreach (['config', 'external', 'override'] as $directory) {
            foreach (new \DirectoryIterator($this->root . '/' . $directory) as $file) {
                if (!$file->isDot()) {
                    $this->assertTrue($file->isFile());
                    $content = file_get_contents($file->getPathname());
                    $this->assertIsString($content);
                    $snapshot[$directory . '/' . $file->getFilename()] = $content;
                }
            }
        }

        ksort($snapshot);

        return $snapshot;
    }
}
