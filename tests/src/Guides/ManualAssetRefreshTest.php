<?php

declare(strict_types=1);

namespace Tests\Guides;

use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveReader;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupProviderMetadata;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupScope;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * EN: Rebuilding a retaken screenshot refreshes bytes and metadata without breaking stable attachment links.
 *
 * HR: Ponovna izrada zamijenjene snimke osvježava bajtove i metapodatke bez kvarenja stalnih poveznica.
 */
#[CoversNothing]
final class ManualAssetRefreshTest extends TestCase
{
    /**
     * EN: The second archive uses the new hash and size; the current binary revision and UUID stay consistent.
     *
     * HR: Druga arhiva koristi novi hash i veličinu; trenutačna binarna revizija i UUID ostaju usklađeni.
     */
    public function testSecondBuildAfterReplacingImageRetainsIdentityAndRefreshesPayload(): void
    {
        require_once dirname(__DIR__, 3) . '/scripts/sync_user_guides.php';
        $root = sys_get_temp_dir() . '/manual-asset-refresh-' . bin2hex(random_bytes(8));
        $filesystem = new BackupFilesystem();
        $filesystem->ensureDirectory($root);
        try {
            $path = $root . '/screenshot.png';
            $bytes = base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6m9sAAAAASUVORK5CYII=',
                true,
            );
            file_put_contents($path, $bytes);
            $uuid = '12345678-1234-4123-a123-123456789abc';
            $asset = ['file' => 'screenshot.png', 'sha256' => hash('sha256', $bytes)];
            $row = ['uuid' => $uuid, 'document_key' => 'guide', 'caption' => 'Keep this caption'];
            $versions = [['uuid' => '87654321-4321-4321-a123-123456789abc', 'asset_uuid' => $uuid,
                'document_key' => 'guide', 'version_number' => 1, 'is_current' => 1]];
            $first = \refreshManualAssetPayload($row, $versions, $asset, $root);
            $metadata = new BackupProviderMetadata(
                'editor-html-workspace',
                'aaieduhr/heartphrame-module-editor-html',
                1,
                ['hr' => 'Upute', 'en' => 'Guides'],
                scopes: [BackupScope::PAGE],
            );
            $manifest = ['scope' => ['type' => BackupScope::PAGE, 'identifier' => 'guide'],
                'source' => ['app' => 'Manual regression fixture'], 'label' => 'Guide image regression',
                'providers' => [$metadata->toArray()]];
            $config = \manualArchiveConfig($root, dirname(__DIR__, 3));
            $firstArchive = \writeManualArchive($config, $filesystem, $manifest, [
                'editor-html-workspace' => ['assets' => [$first['asset']], 'asset-versions' => $first['versions']],
            ], [$asset['sha256'] => $path]);
            $reader = new BackupArchiveReader($firstArchive, $config, $filesystem, 'SimbiozaSeed2026!');
            try {
                $records = \manualRecords($reader)['editor-html-workspace'];
            } finally {
                $reader->close();
            }

            // EN: Retake the same file at the same path; no special original_name override is supplied.
            //
            // HR: Zamijenimo istu datoteku na istoj putanji, bez posebnog original_name nadjačavanja.
            $replacement = $bytes . "\nNew screenshot payload\n";
            file_put_contents($path, $replacement);
            $asset['sha256'] = hash('sha256', $replacement);
            $second = \refreshManualAssetPayload($records['assets'][0], $records['asset-versions'], $asset, $root);
            $this->assertNotSame($first['asset']['blob']['sha256'], $second['asset']['blob']['sha256']);
            $this->assertSame($uuid, $second['asset']['uuid']);
            $this->assertSame('guide', $second['asset']['document_key']);
            $this->assertSame('Keep this caption', $second['asset']['caption']);
            $this->assertSame($first['versions'][0]['uuid'], $second['versions'][0]['uuid']);
            $this->assertSame(strlen($replacement), $second['asset']['file_size']);
            $this->assertSame(strlen($replacement), $second['versions'][0]['file_size']);
            $this->assertSame('image/png', $second['asset']['mime_type']);
            $this->assertSame('image/png', $second['versions'][0]['mime_type']);
            $this->assertNull($second['versions'][0]['blob']);
            $archive = \writeManualArchive($config, $filesystem, $manifest, [
                'editor-html-workspace' => ['assets' => [$second['asset']], 'asset-versions' => $second['versions']],
            ], [$asset['sha256'] => $path]);
            $reader = new BackupArchiveReader($archive, $config, $filesystem, 'SimbiozaSeed2026!');
            try {
                $restored = \manualRecords($reader)['editor-html-workspace'];
                $this->assertSame($second['asset'], $restored['assets'][0]);
                $this->assertSame($second['versions'], $restored['asset-versions']);
            } finally {
                $reader->close();
            }
        } finally {
            $filesystem->removeDirectory($root);
        }
    }
}
