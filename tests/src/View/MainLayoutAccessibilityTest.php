<?php

declare(strict_types=1);

namespace Tests\View;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * HR: Čuva osnovnu navigaciju tipkovnicom neovisno o opcionalnoj temi.
 * EN: Protects baseline keyboard navigation independently of the optional theme.
 */
#[CoversNothing]
final class MainLayoutAccessibilityTest extends TestCase
{
    /**
     * HR: Setup koristi glavni orijentir rasporeda i nativni dijalog napretka.
     * EN: Setup uses the layout's main landmark and a native progress dialog.
     */
    public function testSetupUsesSupportedRowAndDialogSemantics(): void
    {
        $view = file_get_contents(dirname(__DIR__, 3) . '/views/setup/index.php');

        $this->assertIsString($view);
        $this->assertStringNotContainsString('<main', $view);
        $this->assertStringNotContainsString('<article', $view);
        $this->assertStringContainsString('<dialog', $view);
        $this->assertStringContainsString('updateOverlay.showModal()', $view);
        $this->assertStringContainsString('data-setup-update-close', $view);
        $this->assertStringContainsString('data-setup-update-open', $view);
        $this->assertStringContainsString("event.key !== 'Tab'", $view);
    }

    /**
     * HR: Preskakanje navigacije postoji i kada renderer teme ne vrati poveznicu.
     * EN: Navigation can be bypassed even when the theme renderer returns no link.
     */
    public function testLayoutProvidesAnIndependentSkipLinkFallback(): void
    {
        $layout = file_get_contents(dirname(__DIR__, 3) . '/views/layouts/main.php');

        $this->assertIsString($layout);
        $this->assertStringContainsString("if (\$layoutSkipLinkHtml === '')", $layout);
        $this->assertStringContainsString('class="simbioza-skip-link" href="#main-content"', $layout);
        $this->assertStringContainsString('.simbioza-skip-link:focus', $layout);
        $this->assertMatchesRegularExpression('/<main\s+id="main-content"\s+tabindex="-1"/', $layout);
    }

    /**
     * HR: Bez modula se ne ispisuju njegove skripte ni panel.
     * EN: Its scripts and panel are not rendered when the module is absent.
     */
    public function testAccessibilityRendererIsOptional(): void
    {
        $layout = (string)file_get_contents(dirname(__DIR__, 3) . '/views/layouts/main.php');

        $this->assertStringContainsString('if (isset($accessibilityRenderer))', $layout);
        $this->assertStringContainsString('$accessibilityRenderer->renderHead()', $layout);
        $this->assertStringContainsString('$accessibilityRenderer->renderPanel()', $layout);
    }
}
