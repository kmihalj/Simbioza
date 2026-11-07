<?php

declare(strict_types=1);

namespace Tests\Guides;

use DOMDocument;
use DOMElement;
use DOMXPath;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * EN: Both public guide renderers produce readable tables while preserving literal source examples.
 *
 * HR: Oba javna renderera uputa stvaraju čitljive tablice i čuvaju doslovne primjere izvornog koda.
 */
#[CoversNothing]
final class GuideTableRenderingTest extends TestCase
{
    /**
     * EN: Markdown tables receive theme classes; existing HTML classes and fenced code survive rendering.
     *
     * HR: Markdown tablice dobivaju klase teme; postojeće HTML klase i izdvojeni kod ostaju sačuvani.
     */
    #[DataProvider('renderers')]
    public function testGeneratedChapterFormatsTablesWithoutChangingCode(string $renderer): void
    {
        require_once dirname(__DIR__, 3) . '/scripts/sync_user_guides.php';
        $root = sys_get_temp_dir() . '/guide-table-render-' . bin2hex(random_bytes(8));
        mkdir($root . '/docs', 0700, true);
        $source = <<<'MD'
            # Example chapter

            | Application mode | Effect |
            | :--- | ---: |
            | Automatic | Follow the system setting |

            <table class="custom table-striped" id="keep-me"><tr><td>Preserved table</td></tr></table>

            <table class=legacy data-caption="A > B"><tr><td>Legacy unquoted class</td></tr></table>

            ```html
            <table class="literal-example"><tr><td>Do not style this code</td></tr></table>
            ```
            MD;
        $path = $root . '/docs/installation_mod_php_hr.md';
        file_put_contents($path, $source);
        try {
            $page = ['slug' => 'example', 'parent' => null, 'title' => ['hr' => 'Primjer', 'en' => 'Example'],
                'sources' => ['hr' => ['docs/installation_mod_php_hr.md'],
                    'en' => ['docs/installation_mod_php_hr.md']]];
            $html = $renderer === 'manual'
            ? \renderManualPage($root, $page, 'hr', [$page], [])
            : \renderInstallationGuide($root, 'apache-mod-php-instalacija', 'hr');
            $document = new DOMDocument();
            $this->assertTrue($document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING));
            $xpath = new DOMXPath($document);
            $tables = $xpath->query('//table');
            $this->assertNotFalse($tables);
            $this->assertCount(3, $tables);
            foreach ($tables as $table) {
                $this->assertInstanceOf(DOMElement::class, $table);
                $classes = explode(' ', $table->getAttribute('class'));
                foreach (['table', 'table-bordered', 'align-middle'] as $class) {
                    $this->assertContains($class, $classes);
                }
            }

            $custom = $xpath->query('//table[@id="keep-me"]')->item(0);
            $this->assertInstanceOf(DOMElement::class, $custom);
            $this->assertStringContainsString('custom table-striped', $custom->getAttribute('class'));
            $this->assertStringContainsString('class="legacy table table-bordered align-middle"', (string) $html);
            $this->assertStringContainsString('data-caption="A > B"', (string) $html);
            $code = $xpath->query('//pre/code')->item(0);
            $this->assertNotNull($code);
            $this->assertSame(
                '<table class="literal-example"><tr><td>Do not style this code</td></tr></table>',
                trim($code->textContent),
            );
            $this->assertSame($html, \styleGuideTables($html));
        } finally {
            unlink($path);
            rmdir($root . '/docs');
            rmdir($root);
        }
    }

    /** @return iterable<string,array{string}> */
    public static function renderers(): iterable
    {
        yield 'regular manual chapter' => ['manual'];
        yield 'installation chapter' => ['installation'];
    }
}
