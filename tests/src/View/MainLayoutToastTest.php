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
}
