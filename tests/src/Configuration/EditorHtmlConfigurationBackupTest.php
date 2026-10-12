<?php

declare(strict_types=1);

namespace Tests\Configuration;

use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveReader;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveWriter;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupConfig;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupExportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupImportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupScope;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlConfig;
use HeartPhrame\Bridge\ComposerBridge;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * EN: Integration uses synthetic policy, not development content.
 *
 * HR: Integracija koristi sintetičku politiku, ne razvojni sadržaj.
 */
#[CoversNothing]
final class EditorHtmlConfigurationBackupTest extends TestCase
{
    /**
     * EN: Restore preserves local paths, hidden options and ownership and can be aborted.
     *
     * HR: Obnova čuva lokalne putanje, skrivene opcije i vlasništvo te se može poništiti.
     */
    public function testPortablePolicyRestoresToHighestLayerAndCanAbort(): void
    {
        $root = sys_get_temp_dir() . '/editor-backup-' . bin2hex(random_bytes(6));
        mkdir($root . '/base/editor-html', 0770, true);
        mkdir($root . '/top/editor-html', 0770, true);
        $saved = [];
        foreach (['HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $name) {
            $saved[$name] = getenv($name);
        }

        putenv('HPH_CONFIG_PATH=' . $root . '/base');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $root . '/top');
        $reader = null;
        try {
            $base = '{"view":{"table_of_contents":false},"uploads":{"max_size_mb":7}}';
            file_put_contents($root . '/base/editor-html/editor-html.json', $base);
            $configuration = new Config(new Helper(), ['backup' => [
                'archive_dir' => $root . '/archives', 'staging_dir' => $root . '/staging',
            ]], $root);
            $module = dirname(__DIR__, 3) . '/vendor/aaieduhr/heartphrame-module-editor-html';
            $editor = new EditorHtmlConfig($configuration, $module);
            $filesystem = new BackupFilesystem();
            $container = $this->createMock(ContainerInterface::class);
            $container->method('get')->willReturnCallback(static fn(string $id): mixed => match ($id) {
                EditorHtmlConfig::class => $editor,
                BackupFilesystem::class => $filesystem,
                default => throw new \RuntimeException('Unexpected fixture service: ' . $id),
            });
            $services = require $module . '/config/services.php';
            $provider = $services['heartphrame.backup.provider.editor-html-configuration']($container);
            $backup = new BackupConfig(
                $configuration,
                new ComposerBridge(),
                dirname(__DIR__, 3) . '/vendor/aaieduhr/heartphrame-module-backup',
            );
            $scope = new BackupScope(BackupScope::SITE);
            $writer = new BackupArchiveWriter($backup, $filesystem, $scope, [], 'Editor fixture');
            $writer->beginProvider($provider->metadata());
            $provider->export(new BackupExportContext($scope, ['editor-html-configuration']), $writer);
            $reader = new BackupArchiveReader($writer->finish(), $backup, $filesystem);
            $records = iterator_to_array($reader->records('editor-html-configuration', 'configuration'));
            $this->assertArrayNotHasKey('storage', $records[0]['values']);
            $this->assertArrayNotHasKey('path', $records[0]['values']['uploads']);

            $path = $root . '/top/editor-html/editor-html.json';
            $target = '{"storage":{"filesystem_path":"local-documents"},'
            . '"uploads":{"path":"local-uploads","max_size_mb":99},'
            . '"images":{"web_variant":{"cache_path":"local-cache"}},'
            . '"view":{"table_of_contents":true},"extension":{"custom":true}}';
            file_put_contents($path, $target);
            chmod($path, 0640);
            $context = new BackupImportContext(
                $scope,
                BackupImportContext::CONFLICT_MERGE,
                ['editor-html-configuration'],
            );
            $this->assertTrue($provider->preflight($context, $reader)->isAllowed());
            $provider->prepareImport($context, $reader);
            $provider->import($context, $reader);
            $provider->finalizeImport($context, $reader);
            $editor->reload();
            $this->assertSame('local-documents', $editor->filesystemDataPath());
            $this->assertSame('local-uploads', $editor->uploadsDataPath());
            $this->assertFalse($editor->tableOfContentsEnabledByDefault());
            $this->assertSame(7, $editor->uploadMaxSizeMb());
            $values = json_decode(file_get_contents($path), true, 64, JSON_THROW_ON_ERROR);
            $this->assertTrue($values['extension']['custom']);
            $this->assertSame('local-cache', $values['images']['web_variant']['cache_path']);
            $this->assertSame(0640, fileperms($path) & 0777);
            $this->assertSame($base, file_get_contents($root . '/base/editor-html/editor-html.json'));
            $provider->abortImport($context);
            $this->assertSame($target, file_get_contents($path));
        } finally {
            $reader?->close();
            foreach ($saved as $name => $value) {
                putenv($value === false ? $name : $name . '=' . $value);
            }

            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(
                $root,
                \FilesystemIterator::SKIP_DOTS,
            ), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($iterator as $entry) {
                $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
            }

            rmdir($root);
        }
    }
}
