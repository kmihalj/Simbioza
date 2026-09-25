<?php

declare(strict_types=1);

namespace Tests\Setup;

use App\Controllers\SetupController;
use HeartPhrame\CodeBook\HttpMethodsEnum;
use HeartPhrame\Routing\Route;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * HR: Administratorska sklopka ostaje u jezgri i nakon isključivanja modula.
 * EN: The administrator toggle remains in the host after disabling the module.
 */
#[CoversNothing]
final class AccessibilitySettingsContractTest extends TestCase
{
    public function testRoutesRemainInHostAndMenuItemIsLast(): void
    {
        $root = dirname(__DIR__, 3);
        $routes = require $root . '/config/routes.php';
        $matched = [];
        foreach ($routes as $route) {
            if ($route instanceof Route && str_starts_with((string)$route->name, 'accessibility.settings')) {
                $matched[$route->name] = $route;
            }
        }

        $this->assertSame(HttpMethodsEnum::GET, $matched['accessibility.settings']->httpMethod);
        $this->assertSame([SetupController::class, 'accessibility'], $matched['accessibility.settings']->handler);
        $this->assertSame(HttpMethodsEnum::POST, $matched['accessibility.settings.change']->httpMethod);
        $changeHandler = $matched['accessibility.settings.change']->handler;
        $this->assertSame([SetupController::class, 'changeAccessibility'], $changeHandler);

        $menu = json_decode(
            (string)file_get_contents($root . '/resources/config/menu/settings.json'),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $this->assertIsArray($menu);
        $setup = array_values(array_filter($menu, static fn(array $section): bool => $section['id'] === 'setup'))[0];
        $this->assertSame('accessibility.settings', $setup['children'][array_key_last($setup['children'])]['id']);

        $view = (string)file_get_contents($root . '/views/setup/accessibility.php');
        $this->assertStringContainsString('generateCsrfTokenInputField()', $view);
        $this->assertStringContainsString('name="action"', $view);
    }
}
