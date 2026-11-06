<?php

declare(strict_types=1);

namespace Tests\Guides;

use App\Guides\BundledManual;
use App\Update\BundledAssetsUpdater;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * EN: The standalone manual stays complete and reads only the shipped public guide archive.
 *
 * HR: Samostalni priručnik ostaje cjelovit i čita samo isporučeni paket javnih uputa.
 */
#[CoversClass(BundledManual::class)]
#[UsesClass(BundledAssetsUpdater::class)]
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

    /**
     * EN: Every incremental page package must contain the same chapter as the full manual.
     * This catches archive-name collisions, wrong document ownership and missing translations.
     *
     * HR: Svaki pojedinačni paket mora sadržavati isto poglavlje kao puni priručnik.
     * Otkriva kolizije naziva arhiva, pogrešnog vlasnika dokumenta i nedostajuće prijevode.
     */
    public function testIncrementalPackagesMatchPublishedChaptersWithoutImportingPermissions(): void
    {
        $root = dirname(__DIR__, 3);
        $manual = new BundledManual(new Config(new Helper(), [], $root));
        $pages = [];
        foreach (['hr', 'en'] as $locale) {
            $pages[$locale] = array_column($manual->pages($locale), null, 'slug');
            $this->assertArrayHasKey('html-editor-u-obrascu', $pages[$locale]);
            $this->assertSame('razvojne-integracije', $pages[$locale]['html-editor-u-obrascu']['parent']);
        }

        $packages = BundledAssetsUpdater::guidePages($root);
        $this->assertCount(count($pages['hr']), $packages);
        foreach ($packages as $package) {
            $zip = new ZipArchive();
            $this->assertTrue($zip->open($root . '/resources/installation/workspace/' . $package['file']));
            $zip->setPassword('SimbiozaSeed2026!');
            try {
                $transfer = $this->packageRows($zip, 'page-transfer/page');
                $this->assertCount(1, $transfer);
                $this->assertSame($package['slug'], $transfer[0]['slug']);
                $this->assertFalse($transfer[0]['permissions_included']);
                $versions = $this->packageRows($zip, 'editor-html-workspace/versions');
                $this->assertCount(2, $versions);
                $this->assertCount(2, array_unique(array_column($versions, 'version_number')));
                foreach ($versions as $version) {
                    $this->assertSame($transfer[0]['document_key'], $version['document_key']);
                    $this->assertTrue((bool)$version['is_published']);
                    $this->assertSame(
                        $pages[$version['language_code']][$package['slug']]['html'],
                        $version['content_html'],
                        $package['slug'],
                    );
                }
            } finally {
                $zip->close();
            }
        }
    }

    /**
     * EN: Reads only fixed public datasets from the package being verified.
     *
     * HR: Čita samo fiksne javne skupove iz paketa koji se provjerava.
     *
     * @return list<array<string,mixed>>
     */
    private function packageRows(ZipArchive $zip, string $dataset): array
    {
        $text = $zip->getFromName('data/' . $dataset . '.ndjson');
        $this->assertIsString($text);
        return array_map(
            static fn(string $line): array => json_decode($line, true, 512, JSON_THROW_ON_ERROR),
            explode("\n", trim($text)),
        );
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
