<?php

declare(strict_types=1);

namespace Tests\View;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class MainLayoutToastTest extends TestCase
{
    /**
     * HR: Globalni toast mora se pripremiti prije HTML zaglavlja kako bi njegov
     *     partial stigao registrirati CSS, a prikazati tek uz sadržaj stranice.
     *
     * EN: The global toast must be prepared before the HTML head so its partial
     *     can register CSS, while rendering only alongside the page content.
     */
    public function testGlobalToastIsPreparedBeforeHeadAndRenderedInBody(): void
    {
        $layout = file_get_contents(dirname(__DIR__, 3) . '/views/layouts/main.php');

        $this->assertIsString($layout);
        $preparationPosition = strpos($layout, '$layoutToastHtml =');
        $headPosition = strpos($layout, '<!DOCTYPE html>');
        $renderPosition = strrpos($layout, '<?= $layoutToastHtml ?>');

        $this->assertIsInt($preparationPosition);
        $this->assertIsInt($headPosition);
        $this->assertIsInt($renderPosition);
        $this->assertLessThan($headPosition, $preparationPosition);
        $this->assertGreaterThan($headPosition, $renderPosition);
    }

    /**
     * HR: Layout mora iz naslova moći izraditi zadani unutarnji hero, dok se
     *     samostalna navigacija ispisuje prije njega, a ne ispod njega.
     *
     * EN: The layout must be able to derive a default inner hero from the title,
     *     while standalone navigation renders before it rather than below it.
     */
    public function testInnerHeroFallbackAndStandaloneNavigationOrderAreExplicit(): void
    {
        $layout = file_get_contents(dirname(__DIR__, 3) . '/views/layouts/main.php');

        $this->assertIsString($layout);
        $this->assertStringContainsString("'title' => __((string)\$title)", $layout);
        $this->assertStringContainsString('$themeHero === false', $layout);
        $this->assertStringContainsString('data-hph-duplicate-hero-title', $layout);

        $navigationPosition = strrpos($layout, '<?= $layoutStandaloneNavigationHtml ?>');
        $heroPosition = strrpos($layout, '<?= $layoutHeroHtml ?>');

        $this->assertIsInt($navigationPosition);
        $this->assertIsInt($heroPosition);
        $this->assertLessThan($heroPosition, $navigationPosition);
    }

    /**
     * HR: Isključena ili neinstalirana tema mora ostaviti razmak između navigacije i sadržaja.
     *
     * EN: A disabled or missing theme must leave spacing between navigation and page content.
     */
    public function testLayoutAddsFallbackContentSpacingWhenRuntimeThemeIsDisabled(): void
    {
        $layout = file_get_contents(dirname(__DIR__, 3) . '/views/layouts/main.php');

        $this->assertIsString($layout);
        $this->assertStringContainsString(
            "\$layoutMainClasses = 'container-fluid px-4' . (\$layoutThemeEnabled ? '' : ' pt-3');",
            $layout,
        );
        $this->assertStringContainsString(
            'class="<?= $this->escape($layoutMainClasses) ?>"',
            $layout,
        );
    }

    /**
     * HR: Svaki Bootstrap modal iz modula prije otvaranja mora prijeći iz
     *     tematskog stacking contexta izravno pod `body`, gdje Bootstrap
     *     umeće i njegov backdrop.
     *
     * EN: Every module Bootstrap modal must move out of the Theme stacking
     *     context directly under `body` before opening, alongside the backdrop
     *     that Bootstrap inserts there.
     */
    public function testNestedBootstrapModalsArePortaledToBodyBeforeShowing(): void
    {
        $layout = file_get_contents(dirname(__DIR__, 3) . '/views/layouts/main.php');

        $this->assertIsString($layout);
        $this->assertStringContainsString(
            "document.addEventListener('show.bs.modal', (event) => {",
            $layout,
        );
        $this->assertStringContainsString(
            'modal.parentElement !== document.body',
            $layout,
        );
        $this->assertStringContainsString(
            'document.body.appendChild(modal);',
            $layout,
        );

        $portalPosition = strpos($layout, "document.addEventListener('show.bs.modal'");
        $bootstrapPosition = strpos(
            $layout,
            'bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js',
        );

        $this->assertIsInt($portalPosition);
        $this->assertIsInt($bootstrapPosition);
        $this->assertLessThan($bootstrapPosition, $portalPosition);
    }

    /**
     * HR: Posebni lijevi meni mora imati sadržajno prilagodljiv stupac, vlastitu
     *     SVG sklopku i početno sklopiti Workspace stablo.
     *
     * EN: The special left menu must have a content-sized column, its own SVG
     *     toggle, and initially collapse the Workspace tree.
     */
    public function testSpecialLeftMenuLayoutIsResizableAndControlsWorkspaceTree(): void
    {
        $layout = file_get_contents(dirname(__DIR__, 3) . '/views/layouts/main.php');

        $this->assertIsString($layout);
        $this->assertStringContainsString('grid-template-columns: fit-content(18rem)', $layout);
        $this->assertStringContainsString('data-hph-route-left-toggle', $layout);
        $this->assertStringContainsString('data-hph-route-left-panel', $layout);
        $this->assertStringContainsString(
            'window.bootstrap.Collapse.getOrCreateInstance',
            $layout,
        );
        $this->assertStringContainsString("'hidden.bs.collapse'", $layout);
        $this->assertStringContainsString("queryTree = new URLSearchParams", $layout);
        $this->assertStringContainsString(
            "workspaceTree.classList.toggle('show', explicitlyShownTree)",
            $layout,
        );
        $this->assertStringContainsString('<rect x="3" y="4" width="18" height="16" rx="2"/>', $layout);
        $this->assertStringContainsString('document.body.appendChild(toggle);', $layout);
        $this->assertStringContainsString('height: calc(100dvh - 1.5rem);', $layout);
        $this->assertStringContainsString('top: 43%;', $layout);
    }
}
