<?php

declare(strict_types=1);

namespace Tests\Installation;

use AaiEduHr\HeartPhrameModuleBackup\ModuleBackup;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveReader;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupConfig;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem;
use HeartPhrame\Bridge\ComposerBridge;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * EN: Validates every shipped guide with the current reader, including encrypted payload hashes.
 *
 * HR: Provjerava sve isporučene upute aktualnim čitačem, uključujući hash šifriranog sadržaja.
 */
#[CoversNothing]
final class BundledGuideArchivesTest extends TestCase
{
    public function testEveryGuideUsesTheCurrentPortableFormat(): void
    {
        $applicationRoot = dirname(__DIR__, 3);
        $archives = glob($applicationRoot . '/resources/installation/workspace/*.zip');
        $this->assertIsArray($archives);
        $this->assertCount(6, $archives);
        $temporary = sys_get_temp_dir() . '/simbioza-bundled-guide-test-' . bin2hex(random_bytes(8));
        $filesystem = new BackupFilesystem();
        $filesystem->ensureDirectory($temporary);

        $composer = $this->createStub(ComposerBridge::class);
        $composer->method('getRootPackage')->willReturn(['install_path' => $temporary]);
        $config = new BackupConfig(new Config(new Helper(), ['backup' => [
            'staging_dir' => $temporary . '/staging',
        ]], $temporary), $composer, $applicationRoot . '/vendor/aaieduhr/heartphrame-module-backup');
        try {
            foreach ($archives as $archive) {
                $reader = new BackupArchiveReader($archive, $config, $filesystem, 'SimbiozaSeed2026!');
                try {
                    $manifest = $reader->manifest();
                    $this->assertSame(ModuleBackup::ARCHIVE_VERSION, $manifest['format_version'], basename($archive));
                    $this->assertSame('0.3.0', $manifest['source']['root_version']);
                    $this->assertTrue($manifest['source']['canonical_link_paths']);
                    $this->assertSame('https://piko.webhop.me/hfc', $manifest['source']['site_base_url']);
                    $this->assertSame('/editor-html', $manifest['source']['link_route_roots']['editor']);
                    $this->assertSame('/workspace', $manifest['source']['link_route_roots']['workspace']);
                    $this->assertSame(
                        basename($archive) === 'korisnicke-upute.zip' ? 'workspace' : 'page',
                        $manifest['scope']['type'],
                    );
                    $this->assertNotEmpty($manifest['providers']);
                    foreach ($manifest['providers'] as $provider) {
                        if ($provider['id'] === 'editor-html-workspace') {
                            $this->assertSame(2, $provider['schema_version']);
                        }

                        if ($provider['id'] === 'editor-html-files') {
                            $this->assertSame(3, $provider['schema_version']);
                        }
                    }

                    $this->assertNotEmpty($manifest['files']);
                } finally {
                    $reader->close();
                }
            }
        } finally {
            $filesystem->removeDirectory($temporary);
        }
    }
}
