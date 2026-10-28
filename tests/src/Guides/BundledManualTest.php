<?php

declare(strict_types=1);

namespace Tests\Guides;

use App\Guides\BundledManual;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * EN: The standalone manual stays complete and reads only the shipped public guide archive.
 *
 * HR: Samostalni priručnik ostaje cjelovit i čita samo isporučeni paket javnih uputa.
 */
#[CoversClass(BundledManual::class)]
final class BundledManualTest extends TestCase
{
    public function testPublishedChaptersAndScreenshotsAreAvailableInBothGuideLanguages(): void
    {
        $manual = new BundledManual(new Config(new Helper(), [], dirname(__DIR__, 3)));
        foreach (['hr', 'en'] as $locale) {
            $pages = $manual->pages($locale);
            $slugs = array_column($pages, 'slug');
            $this->assertGreaterThanOrEqual(30, count($pages));
            $this->assertSame($slugs, array_values(array_unique($slugs)));
            foreach (['simbioza', 'instalacija', 'ankete', 'ankete-api-php', 'ankete-ispunjavanje'] as $slug) {
                $this->assertContains($slug, $slugs, $locale);
            }

            $assetUuid = null;
            foreach ($pages as $page) {
                $this->assertNotSame('', $page['title']);
                $this->assertNotSame('', $page['html']);
                if ($page['parent'] !== null) {
                    $this->assertContains($page['parent'], $slugs);
                }

                if (preg_match('~/editor-html/asset/([a-f0-9-]{36})~', $page['html'], $match) === 1) {
                    $assetUuid = $match[1];
                }
            }

            $this->assertIsString($assetUuid);
            $asset = $manual->asset($assetUuid, $locale);
            $this->assertIsArray($asset);
            $this->assertStringStartsWith('image/', $asset['mime']);
            $this->assertNotSame('', $asset['content']);
        }
    }

    public function testOnlyPublicGuideLinksAreRewrittenAndUnknownAssetsAreRejected(): void
    {
        $html = '<a href="/workspace/korisnicke-upute/ankete?lang=en#start">Guide</a>'
        . '<img src="/editor-html/asset/c346cf06-9762-49b3-9113-a5bbb540a78a">'
        . '<a href="https://example.test/external">External</a>';
        $rewritten = BundledManual::html($html, '/PHPAnkete', 'en');
        $this->assertStringContainsString('/PHPAnkete/guides/ankete?lang=en#start', $rewritten);
        $this->assertStringContainsString(
            '/PHPAnkete/guides/assets/c346cf06-9762-49b3-9113-a5bbb540a78a?lang=en',
            $rewritten,
        );
        $this->assertStringContainsString('https://example.test/external', $rewritten);
        $manual = new BundledManual(new Config(new Helper(), [], dirname(__DIR__, 3)));
        $this->assertNull($manual->asset('../../config/env.php', 'hr'));
        $this->assertNull($manual->asset('00000000-0000-0000-0000-000000000000', 'hr'));
        $this->assertSame([], $manual->pages('not-a-guide-language'));
    }
}
