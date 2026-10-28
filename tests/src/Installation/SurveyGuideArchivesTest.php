<?php

declare(strict_types=1);

namespace Tests\Installation;

use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveReader;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupConfig;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem;
use App\Update\BundledAssetsUpdater;
use HeartPhrame\Bridge\ComposerBridge;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * EN: Public survey guides must work without the surveys used to illustrate them.
 *
 * HR: Javne upute moraju raditi bez anketa kojima su ilustrirane.
 */
#[CoversNothing]
final class SurveyGuideArchivesTest extends TestCase
{
    public function testRoleSeparatedBilingualGuidesContainOnlyPortablePagesAndImages(): void
    {
        $root = dirname(__DIR__, 3);
        $definitions = json_decode(
            (string)file_get_contents($root . '/resources/installation/survey-guides.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->assertCount(21, $definitions);
        $this->assertCount(26, BundledAssetsUpdater::guidePages($root));
        $directory = sys_get_temp_dir() . '/survey-guide-test-' . bin2hex(random_bytes(8));
        $filesystem = new BackupFilesystem();
        $composer = $this->createStub(ComposerBridge::class);
        $composer->method('getRootPackage')->willReturn(['install_path' => $directory]);
        $config = new BackupConfig(
            new Config(new Helper(), ['backup' => ['staging_dir' => $directory . '/staging']], $directory),
            $composer,
            $root . '/vendor/aaieduhr/heartphrame-module-backup',
        );
        $sourceKeys = [];
        $parentSlugs = [];
        try {
            foreach ($definitions as $definition) {
                $slug = $definition['slug'];
                if ($definition['parent'] !== null) {
                    $this->assertContains($definition['parent'], $parentSlugs, $slug);
                }

                $parentSlugs[] = $slug;
                $reader = new BackupArchiveReader(
                    $root . '/resources/installation/workspace/' . $definition['file'],
                    $config,
                    $filesystem,
                    'SimbiozaSeed2026!',
                );
                try {
                    $manifest = $reader->manifest();
                    $this->assertSame(
                        ['page-transfer', 'editor-html-workspace'],
                        array_column($manifest['providers'], 'id'),
                    );
                    $this->assertSame('https://bundled-guides.invalid', $manifest['source']['site_base_url']);
                    $pages = iterator_to_array($reader->records('page-transfer', 'page'));
                    $this->assertCount(1, $pages);
                    $this->assertSame($slug, $pages[0]['slug']);
                    $this->assertFalse($pages[0]['permissions_included']);
                    $this->assertSame('document', $pages[0]['node_type']);
                    $sourceKeys[] = $pages[0]['document_key'];
                    $versions = iterator_to_array($reader->records('editor-html-workspace', 'versions'));
                    $this->assertCount(2, $versions);
                    $languages = array_column($versions, 'language_code');
                    sort($languages);
                    $this->assertSame(['en', 'hr'], $languages);
                    $assets = [];
                    foreach ($reader->records('editor-html-workspace', 'assets') as $asset) {
                        $assets[$asset['uuid']] = true;
                        $this->assertSame('image/png', $asset['mime_type']);
                        $this->assertMatchesRegularExpression('/^(hr|en)-[a-z-]+\.png$/', $asset['original_name']);
                        $this->assertFileExists($reader->blobPath($asset['blob']['sha256']));
                    }

                    $currentVersions = [];
                    foreach ($reader->records('editor-html-workspace', 'asset-versions') as $assetVersion) {
                        $this->assertSame(1, $assetVersion['is_current']);
                        $this->assertArrayHasKey($assetVersion['asset_uuid'], $assets);
                        $this->assertArrayNotHasKey($assetVersion['asset_uuid'], $currentVersions);
                        $currentVersions[$assetVersion['asset_uuid']] = true;
                    }

                    $this->assertSame(array_keys($assets), array_keys($currentVersions));
                    foreach ($versions as $version) {
                        $html = $version['content_html'];
                        if ($slug === 'ankete-autentikacija') {
                            $this->assertStringContainsString('survey-settings-guide', (string) $html);
                            $this->assertStringContainsString('eduPersonAffiliation', (string) $html);
                            $this->assertStringContainsString('identity_source', (string) $html);
                            $this->assertStringContainsString('app.public_url', (string) $html);
                        }

                        $this->assertSame(1, $version['is_published']);
                        $this->assertDoesNotMatchRegularExpression(
                            '~piko\.webhop|/hfc/|/guide-image/|/Volumes/|href="[^"\s]*\.md'
                            . '|/surveys/(?:edit|try|participate|results)/[a-f0-9-]{36}~',
                            $html,
                            $slug,
                        );
                        preg_match_all('~/editor-html/asset/([a-f0-9-]{36})~', $html, $matches);
                        foreach ($matches[1] as $uuid) {
                            $this->assertArrayHasKey($uuid, $assets, $slug);
                        }
                    }
                } finally {
                    $reader->close();
                }
            }

            $seed = new BackupArchiveReader(
                $root . '/resources/installation/workspace/korisnicke-upute.zip',
                $config,
                $filesystem,
                'SimbiozaSeed2026!',
            );
            try {
                $seedKeys = array_column(
                    iterator_to_array($seed->records('editor-html-workspace', 'documents')),
                    'document_key',
                );
                foreach ($sourceKeys as $key) {
                    $this->assertCount(1, array_keys($seedKeys, $key, true), $key);
                }

                foreach ($seed->manifest()['providers'] as $provider) {
                    $this->assertStringNotContainsString('survey', (string) $provider['id']);
                    $this->assertStringNotContainsString('auth', (string) $provider['id']);
                }
            } finally {
                $seed->close();
            }
        } finally {
            $filesystem->removeDirectory($directory);
        }
    }
}
