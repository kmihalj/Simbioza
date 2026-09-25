<?php

declare(strict_types=1);

namespace Tests\Setup;

use App\Controllers\SetupController;
use App\Module\OptionalModuleRoutes;
use HeartPhrame\CodeBook\HttpMethodsEnum;
use HeartPhrame\Routing\Route;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * HR: Postavke su u aplikaciji dok je paket instaliran, čak i ako je isključen.
 * EN: Settings remain in the host while the package is installed, even if disabled.
 */
#[CoversNothing]
final class AccessibilitySettingsContractTest extends TestCase
{
    public function testHostRoutesFollowPackageInstallation(): void
    {
        $root = dirname(__DIR__, 3);
        $routes = require $root . '/config/routes.php';
        $matched = [];
        foreach ($routes as $route) {
            if ($route instanceof Route && str_starts_with((string)$route->name, 'accessibility.settings')) {
                $matched[$route->name] = $route;
            }
        }

        $installed = is_dir($root . '/vendor/aaieduhr/heartphrame-module-accessibility');
        $this->assertSame($installed, isset($matched['accessibility.settings']));
        $this->assertSame($installed, isset($matched['accessibility.settings.change']));
        if ($installed) {
            $this->assertSame(HttpMethodsEnum::GET, $matched['accessibility.settings']->httpMethod);
            $this->assertSame([SetupController::class, 'accessibility'], $matched['accessibility.settings']->handler);
            $this->assertSame(HttpMethodsEnum::POST, $matched['accessibility.settings.change']->httpMethod);
            $this->assertSame(
                [SetupController::class, 'changeAccessibility'],
                $matched['accessibility.settings.change']->handler,
            );
        }

        $this->assertSame([], OptionalModuleRoutes::whenInstalled(
            'aaieduhr/heartphrame-module-accessibility',
            ['settings route'],
            static fn(string $package): bool => false,
        ));
        $this->assertSame(['settings route'], OptionalModuleRoutes::whenInstalled(
            'aaieduhr/heartphrame-module-accessibility',
            ['settings route'],
            static fn(string $package): bool => true,
        ));

        $view = (string)file_get_contents($root . '/views/setup/accessibility.php');
        $this->assertStringContainsString('generateCsrfTokenInputField()', $view);
        $this->assertStringContainsString('name="action"', $view);
    }
}
