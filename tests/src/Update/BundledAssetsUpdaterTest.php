<?php

declare(strict_types=1);

namespace Tests\Update;

use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlConfig;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlDocumentFormatter;
use App\Update\BundledAssetsUpdater;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversNothing]
final class BundledAssetsUpdaterTest extends TestCase
{
    /**
     * EN: Fresh-install normalization never adds a prefix already supplied by restore.
     *
     * HR: Normalizacija nove instalacije nikada ne dodaje prefiks koji je restore već upisao.
     */
    public function testGuidePathsAreNormalizedExactlyOnce(): void
    {
        $source = '<a href="/nested/app/workspace/docs/page?lang=hr#part">Already resolved</a>'
        . '<a href="/workspace/docs/page?lang=en#part">Canonical</a>'
        . '<a href="/nested/app?lang=hr#part">Application root</a>'
        . '<a href="/nested/application/page">Different boundary</a>'
        . '<img src="/editor-html/asset/file" data-editor-html-web-src="/nested/app/editor-html/asset/file">'
        . '<a href="https://other.example/workspace/docs">External</a>'
        . '<a href="//other.example/path">Network</a><a href="../page">Relative</a><a href="#part">Anchor</a>';
        $once = BundledAssetsUpdater::normalizeGuideHtmlPaths($source, '/nested/app/');
        $this->assertSame($once, BundledAssetsUpdater::normalizeGuideHtmlPaths($once, '/nested/app'));
        $this->assertSame($source, BundledAssetsUpdater::normalizeGuideHtmlPaths($source, ''));
        $this->assertStringContainsString('href="/nested/app/workspace/docs/page?lang=hr#part"', $once);
        $this->assertStringContainsString('href="/nested/app/workspace/docs/page?lang=en#part"', $once);
        $this->assertStringContainsString('href="/nested/app?lang=hr#part"', $once);
        $this->assertStringContainsString('href="/nested/app/nested/application/page"', $once);
        $this->assertStringContainsString('src="/nested/app/editor-html/asset/file"', $once);
        $this->assertStringContainsString('href="https://other.example/workspace/docs"', $once);
        $this->assertStringContainsString('href="//other.example/path"', $once);
        $this->assertStringContainsString('href="../page"', $once);
        $this->assertStringContainsString('href="#part"', $once);
    }

    /**
     * EN: Release finalization applies shared FPM-readable manifest permissions.
     *
     * HR: Završni korak izdanja primjenjuje zajednička, FPM-u čitljiva prava manifesta.
     */
    public function testFpmManifestIsWebReadable(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Windows uses inherited NTFS ACLs instead of POSIX modes.');
        }

        $directory = sys_get_temp_dir() . '/simbioza-manifest-permissions-' . bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        $manifest = $directory . '/composer.json';
        try {
            file_put_contents($manifest, "{}\n");
            chmod($manifest, 0660);

            BundledAssetsUpdater::normalizeComposerManifestMetadata($directory);

            $this->assertSame(0664, fileperms($manifest) & 07777);
        } finally {
            if (is_file($manifest)) {
                unlink($manifest);
            }

            rmdir($directory);
        }
    }

    /**
     * EN: Release finalization repairs new code created by the updater
     *     under umask 0007 without changing private settings or data.
     *
     * HR: Završni korak izdanja popravlja novi kod koji je updater
     *     stvorio uz umask 0007, ali ne mijenja privatne postavke ni podatke.
     */
    public function testReleaseTreeBecomesReadableWhilePrivatePathsRemainPrivate(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('Windows uses inherited NTFS ACLs instead of POSIX modes.');
        }

        $directory = sys_get_temp_dir() . '/simbioza-release-permissions-' . bin2hex(random_bytes(8));
        $this->assertTrue(mkdir($directory . '/src/Localization', 0770, true));
        $this->assertTrue(mkdir($directory . '/config', 0770, true));
        $this->assertTrue(mkdir($directory . '/data/private', 0770, true));
        $this->assertTrue(mkdir($directory . '/config/theme', 0770, true));
        file_put_contents($directory . '/composer.json', "{}\n");
        file_put_contents($directory . '/src/Localization/NewService.php', "<?php\n");
        file_put_contents($directory . '/config/database.php', "<?php return [];\n");
        file_put_contents($directory . '/data/private/secret', "secret\n");
        file_put_contents($directory . '/config/theme/settings.json', "{}\n");
        $privateRootFiles = ['framework.php', 'composer.local.json', 'composer.local.lock'];
        $privateSnapshots = [];
        foreach ($privateRootFiles as $privateFile) {
            $path = $directory . '/' . $privateFile;
            file_put_contents($path, "Private administrator configuration: " . $privateFile . "\n");
            chmod($path, 0600);
            $privateSnapshots[$privateFile] = [hash_file('sha256', $path), fileowner($path), filegroup($path)];
        }

        chmod($directory . '/composer.json', 0660);
        chmod($directory . '/src/Localization/NewService.php', 0660);
        chmod($directory . '/config/database.php', 0600);
        chmod($directory . '/data/private/secret', 0600);
        chmod($directory . '/config/theme/settings.json', 0600);

        try {
            BundledAssetsUpdater::normalizeReleaseTreeMetadata($directory);

            $this->assertSame(0664, fileperms($directory . '/composer.json') & 07777);
            $this->assertSame(0664, fileperms($directory . '/src/Localization/NewService.php') & 07777);
            $this->assertSame(0600, fileperms($directory . '/config/database.php') & 07777);
            $this->assertSame(0600, fileperms($directory . '/data/private/secret') & 07777);
            $this->assertSame(
                0600,
                fileperms($directory . '/config/theme/settings.json') & 07777,
            );
            // EN: The central path configuration is private even though it lives outside config/.
            //
            // HR: Središnja konfiguracija putanja je privatna i kada je izvan direktorija config/.
            foreach ($privateRootFiles as $privateFile) {
                $path = $directory . '/' . $privateFile;
                $this->assertSame(0600, fileperms($path) & 07777, $privateFile);
                $this->assertSame(
                    $privateSnapshots[$privateFile],
                    [hash_file('sha256', $path), fileowner($path), filegroup($path)],
                    $privateFile,
                );
            }
        } finally {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($iterator as $item) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }

            rmdir($directory);
        }
    }

    /**
     * EN: Bundled guides use a virtual, non-fetchable origin and an explicitly configured base path.
     *
     * HR: Ugrađene upute koriste virtualni izvor koji se ne dohvaća i izričito podešenu osnovnu putanju.
     */
    public function testBundledGuideDestinationUsesExplicitBasePath(): void
    {
        $this->assertSame('https://bundled-guides.invalid', BundledAssetsUpdater::guideSiteUrl(''));
        $this->assertSame('https://bundled-guides.invalid/simbioza', BundledAssetsUpdater::guideSiteUrl('/simbioza'));
        $this->assertSame(
            'https://bundled-guides.invalid/nested/app',
            BundledAssetsUpdater::guideSiteUrl('/nested/app/'),
        );
    }

    /**
     * EN: The updater must not accept external URLs as base paths.
     *
     * HR: Updater ne smije prihvatiti vanjske URL-ove kao base path.
     */
    public function testExternalBasePathIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        BundledAssetsUpdater::validatedBasePath('https://example.test/app');
    }

    /**
     * EN: A public URL provides an explicit application base path.
     *
     * HR: Javni URL daje izričitu osnovnu putanju aplikacije.
     */
    public function testApplicationUrlDeterminesTheBasePath(): void
    {
        $this->assertSame('/hfc', BundledAssetsUpdater::basePathFromApplicationUrl(
            'https://piko.webhop.me/hfc/',
        ));
        $this->assertSame('', BundledAssetsUpdater::basePathFromApplicationUrl('https://example.test/'));
    }

    /**
     * EN: Reads both current storage drivers without guessing the installation URL from page content.
     *
     * HR: Čita oba aktualna načina pohrane bez nagađanja URL-a instalacije iz sadržaja stranice.
     */
    public function testCurrentFilesystemAndDatabaseGuidePayloads(): void
    {
        $directory = sys_get_temp_dir() . '/simbioza-guide-reader-' . bin2hex(random_bytes(8));
        mkdir($directory . '/data/editor-html', 0700, true);
        $config = new EditorHtmlConfig(
            new Config(new Helper(), [], $directory),
            dirname(__DIR__, 3) . '/vendor/aaieduhr/heartphrame-module-editor-html',
        );
        $path = $config->documentsRoot() . '/guide.html';
        $formatter = new EditorHtmlDocumentFormatter();
        try {
            $fragment = '<img src="/simbioza/editor-html/asset/abc">';
            file_put_contents($path, $formatter->standaloneDocument('Current guide', 'en', $fragment, []));
            $this->assertSame($fragment, BundledAssetsUpdater::guideVersionHtml([
                'storage_driver' => 'filesystem', 'content_path' => 'guide.html', 'content_html' => null,
            ], $config, $formatter));
            $this->assertSame('<p>Database guide</p>', BundledAssetsUpdater::guideVersionHtml([
                'storage_driver' => 'database', 'content_html' => '<p>Database guide</p>',
            ], $config, $formatter));
        } finally {
            unlink($path);
            rmdir($directory . '/data/editor-html');
            rmdir($directory . '/data');
            rmdir($directory);
        }
    }

    /**
     * EN: Guide reads reject traversal before resolving a payload.
     *
     * HR: Čitanje uputa odbija traversal prije određivanja putanje sadržaja.
     */
    public function testFilesystemTraversalIsRejected(): void
    {
        $config = new EditorHtmlConfig(
            new Config(new Helper(), [], sys_get_temp_dir()),
            dirname(__DIR__, 3) . '/vendor/aaieduhr/heartphrame-module-editor-html',
        );
        $this->expectException(RuntimeException::class);
        BundledAssetsUpdater::guideVersionHtml([
            'storage_driver' => 'filesystem', 'content_path' => '../outside.html',
        ], $config, new EditorHtmlDocumentFormatter());
    }
}
