<?php

declare(strict_types=1);

namespace Tests\Update;

use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlDocumentFormatter;
use App\Update\BundledAssetsUpdater;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversNothing]
final class BundledAssetsUpdaterTest extends TestCase
{
    /**
     * HR: Završni korak novog izdanja popravlja manifest koji je stariji FPM updater ostavio privatnim.
     * EN: New-release finalization repairs a manifest left private by an older FPM updater.
     */
    public function testLegacyFpmUpdateLeavesComposerManifestWebReadable(): void
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

    /** HR: Upute otkrivaju stvarni root ili poddirektorij. EN: Guides identify the actual root or subdirectory. */
    public function testOldGuideLinksDetermineTheBasePath(): void
    {
        $this->assertSame('/simbioza', BundledAssetsUpdater::basePathFromGuideHtml([
            '<img src="/simbioza/editor-html/asset/abc">',
            '<img src="/simbioza/editor-html/asset/def">',
        ]));
        $this->assertSame('', BundledAssetsUpdater::basePathFromGuideHtml(['<img src="/editor-html/asset/abc">']));
        $this->assertSame('/nested/app', BundledAssetsUpdater::validatedBasePath('/nested/app/'));
    }

    /** HR: Dvosmislene stare putanje zahtijevaju eksplicitni unos. EN: Ambiguous old paths require an explicit value. */
    public function testAmbiguousBasePathsAreRejected(): void
    {
        $this->expectException(RuntimeException::class);
        BundledAssetsUpdater::basePathFromGuideHtml([
            '<img src="/a/editor-html/asset/a"><img src="/b/editor-html/asset/b">',
        ]);
    }

    /** HR: Updater ne smije prihvatiti vanjske URL-ove kao base path. EN: The updater must not accept external URLs as base paths. */
    public function testExternalBasePathIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        BundledAssetsUpdater::validatedBasePath('https://example.test/app');
    }

    /** HR: Stara instalacija može preuzeti base path iz javnog URL-a. EN: A legacy installation can derive its base path from the public URL. */
    public function testApplicationUrlDeterminesTheBasePath(): void
    {
        $this->assertSame('/hfc', BundledAssetsUpdater::basePathFromApplicationUrl(
            'https://piko.webhop.me/hfc/',
        ));
        $this->assertSame('', BundledAssetsUpdater::basePathFromApplicationUrl('https://example.test/'));
    }

    /** HR: Putanja se može otkriti i iz stare HTML datoteke, ne samo SQL stupca. EN: The path can also be detected from a legacy HTML file, not just a SQL column. */
    public function testLegacyFilesystemGuideDeterminesBasePath(): void
    {
        $directory = sys_get_temp_dir() . '/simbioza-guide-reader-' . bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        $path = $directory . '/guide.html';
        $formatter = new EditorHtmlDocumentFormatter();
        try {
            file_put_contents($path, $formatter->standaloneDocument(
                'Legacy guide',
                'en',
                '<img src="/simbioza/editor-html/asset/abc">',
                [],
            ));
            $html = BundledAssetsUpdater::guideVersionHtml([
                'storage_driver' => 'filesystem', 'content_path' => 'guide.html', 'content_html' => null,
            ], $directory, $formatter);
            $this->assertSame('/simbioza', BundledAssetsUpdater::basePathFromGuideHtml([$html]));
            $this->assertSame('<p>Database guide</p>', BundledAssetsUpdater::guideVersionHtml([
                'storage_driver' => 'database', 'content_html' => '<p>Database guide</p>',
            ], $directory, $formatter));
        } finally {
            unlink($path);
            rmdir($directory);
        }
    }

    /** HR: Čitanje uputa ne smije izaći iz editor spremišta. EN: Guide reading must not escape editor storage. */
    public function testLegacyFilesystemTraversalIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        BundledAssetsUpdater::guideVersionHtml([
            'storage_driver' => 'filesystem', 'content_path' => '../outside.html',
        ], sys_get_temp_dir(), new EditorHtmlDocumentFormatter());
    }
}
