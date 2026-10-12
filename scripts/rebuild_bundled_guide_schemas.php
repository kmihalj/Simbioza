<?php

/**
 * EN: Development-only rebuild of the six public, encrypted installation guides
 *     for the current provider schemas. This is not an old-backup importer.
 *     All records/blobs are preserved and the finished archives are revalidated.
 *
 * HR: Razvojno ponovno pakiranje šest javnih, šifriranih instalacijskih uputa
 *     za aktualne sheme pružatelja. Ovo nije importer starih backupa.
 *     Čuva sve zapise/datoteke i ponovno provjerava dovršene arhive.
 */

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveReader;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveWriter;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupConfig;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupProviderMetadata;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupScope;
use HeartPhrame\Bridge\ComposerBridge;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;

require dirname(__DIR__) . '/vendor/autoload.php';

$application = dirname(__DIR__);
$temporary = sys_get_temp_dir() . '/simbioza-guide-schema-' . bin2hex(random_bytes(8));
$filesystem = new BackupFilesystem();
$filesystem->ensureDirectory($temporary);
$composer = new class ($temporary) extends ComposerBridge {
    /**
     * EN: Uses only the generated public-guide staging directory.
     *
     * HR: Koristi samo generirani privremeni direktorij javnih uputa.
     */
    public function __construct(private readonly string $root)
    {
    }

    /**
     * EN: Isolates output from the development application's private runtime data.
     *
     * HR: Izolira izlaz od privatnih podataka razvojne aplikacije.
     *
     * @return array<string,mixed>
     */
    public function getRootPackage(): array
    {
        return ['install_path' => $this->root];
    }
};
$config = new BackupConfig(new Config(new Helper(), ['backup' => [
    'staging_dir' => $temporary . '/staging', 'archive_dir' => $temporary . '/archives',
]]), $composer, $application . '/vendor/aaieduhr/heartphrame-module-backup');
try {
    $archives = glob($application . '/resources/installation/workspace/*.zip');
    if (!is_array($archives) || count($archives) !== 6) {
        throw new RuntimeException('Expected exactly six public bundled guides.');
    }
    $prepared = [];
    foreach ($archives as $archive) {
        $reader = new BackupArchiveReader($archive, $config, $filesystem, 'SimbiozaSeed2026!');
        try {
            $manifest = $reader->manifest();
            $writer = new BackupArchiveWriter(
                $config,
                $filesystem,
                new BackupScope($manifest['scope']['type'], $manifest['scope']['identifier']),
                $manifest['source'],
                $manifest['label'],
                'SimbiozaSeed2026!',
            );
            foreach ($manifest['providers'] as $metadata) {
                $schema = match ($metadata['id']) {
                    'editor-html-workspace' => 2, 'editor-html-files' => 3,
                    default => $metadata['schema_version'],
                };
                $writer->beginProvider(new BackupProviderMetadata(
                    $metadata['id'],
                    $metadata['package'],
                    $schema,
                    $metadata['label'],
                    $metadata['dependencies'],
                    $metadata['scopes'],
                    $metadata['required_for_full_restore'],
                    $metadata['contains_sensitive_data'],
                    $metadata['required_enabled_packages'],
                    $metadata['automatic'],
                    $metadata['component_groups'],
                ));
                foreach (array_keys($metadata['datasets']) as $dataset) {
                    foreach ($reader->records($metadata['id'], $dataset) as $record) {
                        $writer->writeRecord($metadata['id'], $dataset, $record);
                    }
                }
            }
            foreach ($manifest['files'] as $file) {
                if (str_starts_with($file['path'], 'blobs/')) {
                    $writer->addBlobFile($reader->blobPath($file['sha256']));
                }
            }
            $rebuilt = $writer->finish();
            $verified = new BackupArchiveReader($rebuilt, $config, $filesystem, 'SimbiozaSeed2026!');
            try {
                $updated = $verified->manifest();
                if ($updated['files'] !== $manifest['files']) {
                    throw new RuntimeException('Guide rebuild changed payload bytes: ' . basename($archive));
                }
            } finally {
                $verified->close();
            }
            $prepared[$archive] = $rebuilt;
        } finally {
            $reader->close();
        }
    }
    foreach ($prepared as $destination => $source) {
        if (!copy($source, $destination . '.partial') || !rename($destination . '.partial', $destination)) {
            throw new RuntimeException('Unable to publish rebuilt guide: ' . basename($destination));
        }
        echo basename($destination) . ": payload preserved; current schemas verified\n";
    }
} finally {
    $filesystem->removeDirectory($temporary);
}
