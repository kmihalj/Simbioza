<?php

declare(strict_types=1);

namespace Tests\Framework;

use AaiEduHr\HeartPhrameModuleMenu\Middleware\RequireSettingsAdminWhenAuthEnabledMiddleware;
use App\Module\ModuleStateStore;
use App\Update\BundledUpgradeCommandManager;
use HeartPhrame\App;
use HeartPhrame\Command\CommandManager;
use HeartPhrame\Config\ConfigInterface;
use HeartPhrame\Middleware\DeferredModuleLoaderMiddleware;
use HeartPhrame\Module\ModuleManager;
use HeartPhrame\Routing\Routes;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class AppTest extends TestCase
{
    /**
     * HR: Stvarno konstruira aplikaciju s našim servisima i rutama u zasebnom
     *     procesu, bez privatnih postavki, baze i promjena frameworkova izvora.
     * EN: Actually constructs the app with our services and routes in a separate
     *     process, without private settings, a database, or framework source changes.
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testCanConstruct(): void
    {
        $root = sys_get_temp_dir() . '/simbioza-bootstrap-' . bin2hex(random_bytes(8));
        $this->assertTrue(mkdir($root, 0700));
        try {
            $app = new App(dirname(__DIR__, 2) . '/Fixtures/ApplicationBootstrap', $root);
            $this->assertSame($app, App::getInstance());
            $container = $app->getContainer();
            $config = $container->get(ConfigInterface::class);
            $this->assertInstanceOf(ConfigInterface::class, $config);
            $this->assertSame($root, $config->getAppRootDir());
            $this->assertSame('Simbioza bootstrap test', $config->get('app.name'));
            $modules = $container->get(ModuleManager::class);
            $this->assertInstanceOf(ModuleManager::class, $modules);
            $this->assertSame([], $modules->getLoadedModules());
            $this->assertInstanceOf(ModuleStateStore::class, $container->get(ModuleStateStore::class));
            $this->assertInstanceOf(BundledUpgradeCommandManager::class, $container->get(CommandManager::class));
            $routes = $container->get(Routes::class);
            $this->assertInstanceOf(Routes::class, $routes);
            $this->assertNotEmpty($routes->getRoutes());
            $this->assertSame(['.', '..'], scandir($root));
        } finally {
            rmdir($root);
        }
    }

    /**
     * HR: Settings zaštita mora se ponoviti nakon učitavanja Simbioza dekoratora prava.
     * EN: Settings protection must run again after the Simbioza rights decorator is loaded.
     */
    public function testSettingsAuthorizationRunsAfterDeferredModules(): void
    {
        $middleware = require dirname(__DIR__, 3) . '/config/middleware.php';

        $this->assertIsArray($middleware);
        $deferredIndex = array_search(DeferredModuleLoaderMiddleware::class, $middleware, true);
        $settingsIndex = array_search(RequireSettingsAdminWhenAuthEnabledMiddleware::class, $middleware, true);

        $this->assertIsInt($deferredIndex);
        $this->assertIsInt($settingsIndex);
        $this->assertGreaterThan($deferredIndex, $settingsIndex);
    }

    /**
     * HR: Lokalna zaštita ulazi prije postavki, a pogrešna klasa mora srušiti zatvoren bootstrap.
     * EN: Local protection precedes the settings gate, while a missing class must fail boot closed.
     */
    public function testLocalMiddlewareOrderAndFailClosedConfiguration(): void
    {
        $directory = sys_get_temp_dir() . '/simbioza-local-middleware-' . bin2hex(random_bytes(6));
        $this->assertTrue(mkdir($directory, 0700));
        try {
            $this->assertTrue(copy(dirname(__DIR__, 3) . '/config/middleware.php', $directory . '/middleware.php'));
            file_put_contents(
                $directory . '/middleware.local.php',
                '<?php return [' . \HeartPhrame\Middleware\StartSessionMiddleware::class . '::class];',
            );
            $middleware = require $directory . '/middleware.php';
            $this->assertCount(
                2,
                array_filter(
                    $middleware,
                    static fn(mixed $item): bool => $item === \HeartPhrame\Middleware\StartSessionMiddleware::class,
                ),
            );
            $this->assertLessThan(
                array_search(RequireSettingsAdminWhenAuthEnabledMiddleware::class, $middleware, true),
                array_search(\HeartPhrame\Middleware\StartSessionMiddleware::class, $middleware, true),
            );

            file_put_contents($directory . '/middleware.local.php', '<?php return ["Missing\\DemoGuard"];');
            $this->expectException(\RuntimeException::class);
            require $directory . '/middleware.php';
        } finally {
            @unlink($directory . '/middleware.local.php');
            @unlink($directory . '/middleware.php');
            rmdir($directory);
        }
    }

    /**
     * HR: Integrirani testovi ne smiju ovisiti o brzini baze i pogoditi
     *     produkcijski API limit dok dijele jedan administratorski ključ.
     * EN: Integrated tests must not depend on database speed and hit the
     *     production API limit while sharing one administrator key.
     */
    public function testEndToEndSuiteUsesDedicatedApiRateLimit(): void
    {
        $runner = file_get_contents(dirname(__DIR__, 3) . '/scripts/run_e2e.php');

        $this->assertIsString($runner);
        $this->assertStringContainsString(
            "\$apiConfig['rate_limit_per_minute'] = 10_000;",
            $runner,
        );
    }
}
