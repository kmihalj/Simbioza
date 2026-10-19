<?php

declare(strict_types=1);

namespace Tests\Update;

use App\Update\BundledAssetsUpdater;
use App\Update\BundledGuideLinkRepair;
use DOMDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BundledGuideLinkRepair::class)]
#[UsesClass(BundledAssetsUpdater::class)]
final class BundledGuideLinkRepairTest extends TestCase
{
    /**
     * EN: Repairs only known destinations across arbitrary source/target roots and remains idempotent.
     *
     * HR: Popravlja samo poznate ciljeve preko proizvoljnih izvora/odredišta te ostaje idempotentan.
     */
    public function testKnownRoutesRecoverWithoutTouchingTextCodeOrExternalLinks(): void
    {
        $source = '<p>Text /nested/app/nested/app/workspace/guides/install</p>'
        . '<pre><code>&lt;a href="/nested/app/nested/app/workspace/guides/install"&gt;</code></pre>'
        . '<a href="/nested/app/nested/app/workspace/guides/install?lang=hr#part">Repeated</a>'
        . '<a href="https://source.example/previous/workspace/guides/install?lang=en#part">Source</a>'
        . '<a href="https://source.example.evil/previous/workspace/guides/install">External</a>'
        . '<a href="https://source.example/unrelated/workspace/guides/install">Other app</a>'
        . '<a href="/nested/app/nested/app/workspace/guides/missing">Missing</a>'
        . '<a href="../install">Relative</a><a href="#part">Anchor</a>';
        $routes = ['/workspace/guides/install' => '/nested/app/areas/guides/install'];
        $fixed = BundledGuideLinkRepair::rewrite($source, '/nested/app', $routes, 'https://source.example/previous');
        $this->assertStringContainsString('href="/nested/app/areas/guides/install?lang=hr#part"', $fixed);
        $this->assertStringContainsString('href="/nested/app/areas/guides/install?lang=en#part"', $fixed);
        $this->assertStringContainsString('Text /nested/app/nested/app/workspace/guides/install', $fixed);
        $this->assertStringContainsString('&lt;a href="/nested/app/nested/app/workspace/guides/install"&gt;', $fixed);
        $this->assertStringContainsString('https://source.example.evil/previous/workspace/guides/install', $fixed);
        $this->assertStringContainsString('https://source.example/unrelated/workspace/guides/install', $fixed);
        $this->assertStringContainsString('/nested/app/nested/app/workspace/guides/missing', $fixed);
        $this->assertStringContainsString('href="../install"', $fixed);
        $this->assertStringContainsString('href="#part"', $fixed);
        $this->assertSame($fixed, BundledGuideLinkRepair::rewrite(
            $fixed,
            '/nested/app',
            $routes,
            'https://source.example/previous',
        ));
    }

    /**
     * EN: Full standalone files keep their head/resources, and an overlapping base is not over-stripped.
     *
     * HR: Cjelovite samostalne datoteke čuvaju head/resurse, a preklopljena osnova ne uklanja se previše.
     */
    public function testStandaloneAndOverlappingRootsStayValid(): void
    {
        $html = '<!doctype html><html><head><meta charset="UTF-8"><title>Čuvaj</title>'
        . '<link rel="stylesheet" href="../assets/theme.css"></head><body>'
        . '<a href="/workspace/workspace/workspace/guides/install">Fix</a></body></html>';
        $fixed = BundledGuideLinkRepair::rewrite($html, '/workspace', [
            '/workspace/guides/install' => '/workspace/workspace/guides/install',
        ], 'https://source.example');
        $this->assertStringContainsString('<html>', $fixed);
        $document = new DOMDocument();
        $document->loadHTML($fixed, LIBXML_NONET);
        $this->assertSame('Čuvaj', $document->getElementsByTagName('title')->item(0)?->textContent);
        $this->assertStringContainsString('href="../assets/theme.css"', $fixed);
        $this->assertStringContainsString('href="/workspace/workspace/guides/install"', $fixed);
        $this->assertStringNotContainsString('<?xml', $fixed);
        $this->assertSame($fixed, BundledGuideLinkRepair::rewrite($fixed, '/workspace', [
            '/workspace/guides/install' => '/workspace/workspace/guides/install',
        ], 'https://source.example'));
    }
}
