<?php

declare(strict_types=1);

namespace Tests\Framework;

use AaiEduHr\HeartPhrameModuleMenu\Middleware\RequireSettingsAdminWhenAuthEnabledMiddleware;
use HeartPhrame\Middleware\DeferredModuleLoaderMiddleware;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class AppTest extends TestCase
{
    public function testCanConstruct(): void
    {
        $this->markTestIncomplete();
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
