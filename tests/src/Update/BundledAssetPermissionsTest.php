<?php

declare(strict_types=1);

namespace Tests\Update;

use App\Update\BundledAssetPermissions;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversNothing]
final class BundledAssetPermissionsTest extends TestCase
{
    private string $directory;

    /** HR: Priprema zasebno runtime i privatno spremište. EN: Prepares separate runtime and private storage. */
    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/simbioza-asset-permissions-' . bin2hex(random_bytes(8));
        mkdir($this->directory . '/uploads/guide/nested', 0750, true);
        file_put_contents($this->directory . '/uploads/guide/nested/image.png', 'image bytes');
        chmod($this->directory . '/uploads/guide/nested/image.png', 0640);
        file_put_contents($this->directory . '/private-backup.zip', 'private bytes');
        chmod($this->directory . '/private-backup.zip', 0600);
    }

    /** HR: Uklanja samo imenovane testne datoteke i direktorije. EN: Removes only named test files and directories. */
    protected function tearDown(): void
    {
        foreach (['uploads/link', 'uploads/guide/nested/image.png', 'private-backup.zip'] as $path) {
            $path = $this->directory . '/' . $path;
            if (is_file($path) || is_link($path)) {
                unlink($path);
            }
        }

        foreach (['uploads/guide/nested', 'uploads/guide', 'uploads', ''] as $path) {
            rmdir($this->directory . ($path === '' ? '' : '/' . $path));
        }
    }

    /** HR: Ispravan postojeći import ostaje netaknut, uključujući privatne artefakte. EN: A correct existing import remains unchanged, including private artifacts. */
    public function testCorrectOwnershipAndModesRemainUnchanged(): void
    {
        $asset = $this->directory . '/uploads/guide/nested/image.png';
        $before = stat($asset);
        $this->assertFalse(BundledAssetPermissions::inheritOwnership(
            $this->directory . '/uploads',
            ['guide/nested/image.png', 'guide/nested/image.png'],
        ));
        clearstatcache();
        $this->assertSame($before, stat($asset));
        $this->assertSame('image bytes', file_get_contents($asset));
        $this->assertSame(0600, fileperms($this->directory . '/private-backup.zip') & 0777);
        $this->assertSame('private bytes', file_get_contents($this->directory . '/private-backup.zip'));
    }

    /** HR: Sudo import nasljeđuje UID/GID runtime korijena i ostaje idempotentan. EN: A sudo import inherits the runtime root UID/GID and remains idempotent. */
    public function testRootImportsInheritAnotherRuntimeIdentity(): void
    {
        if (PHP_OS_FAMILY === 'Windows' || !function_exists('posix_geteuid') || posix_geteuid() !== 0) {
            $this->markTestSkipped('Cross-identity ownership requires a root POSIX test process.');
        }

        $root = $this->directory . '/uploads';
        $this->assertTrue(chown($root, 65534));
        $this->assertTrue(chgrp($root, 65534));
        clearstatcache();
        if (fileowner($root) !== 65534) {
            $this->markTestSkipped('This filesystem does not implement POSIX ownership.');
        }

        $this->assertTrue(BundledAssetPermissions::inheritOwnership($root, ['guide/nested/image.png']));
        clearstatcache();
        foreach (['guide', 'guide/nested', 'guide/nested/image.png'] as $path) {
            $this->assertSame(65534, fileowner($root . '/' . $path));
            $this->assertSame(65534, filegroup($root . '/' . $path));
        }

        $this->assertSame(0640, fileperms($root . '/guide/nested/image.png') & 0777);
        $this->assertSame(0, fileowner($this->directory . '/private-backup.zip'));
        $this->assertFalse(BundledAssetPermissions::inheritOwnership($root, ['guide/nested/image.png']));
    }

    /** HR: Prazan popis privitaka ne zahtijeva filesystem spremište. EN: An empty attachment list needs no filesystem storage. */
    public function testEmptyAssetListIsANoOp(): void
    {
        $this->assertFalse(BundledAssetPermissions::inheritOwnership($this->directory . '/missing', []));
    }

    /** HR: Putanje ne smiju izaći iz odabranog spremišta. EN: Paths must not escape the selected storage. */
    public function testTraversalIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        BundledAssetPermissions::inheritOwnership($this->directory . '/uploads', ['../private-backup.zip']);
    }

    /** HR: Apsolutna putanja ne smije zamijeniti relativnu. EN: An absolute path must not replace a relative path. */
    public function testAbsolutePathIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        BundledAssetPermissions::inheritOwnership($this->directory . '/uploads', ['/etc/passwd']);
    }

    /** HR: Windows putanje ne smiju biti prihvaćene kao relativne. EN: Windows paths must not be accepted as relative paths. */
    public function testDrivePathIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        BundledAssetPermissions::inheritOwnership($this->directory . '/uploads', ['C:\\outside.png']);
    }

    /** HR: Nedostajući korijen mora zaustaviti provjeru. EN: A missing root must stop validation. */
    public function testMissingRootIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        BundledAssetPermissions::inheritOwnership($this->directory . '/missing', ['image.png']);
    }

    /** HR: Nedostajući privitak ne smije uzrokovati djelomičan popravak ranijih putanja. EN: A missing attachment must not partially repair preceding paths. */
    public function testAllPathsAreValidatedBeforeRepair(): void
    {
        $asset = $this->directory . '/uploads/guide/nested/image.png';
        $before = stat($asset);
        try {
            BundledAssetPermissions::inheritOwnership($this->directory . '/uploads', [
                'guide/nested/image.png', 'guide/missing.png',
            ]);
            $this->fail('A missing attachment was accepted.');
        } catch (RuntimeException $runtimeException) {
            $this->assertStringContainsString('missing', $runtimeException->getMessage());
        }

        clearstatcache();
        $this->assertSame($before, stat($asset));
    }

    /** HR: Simboličke veze prema drugim datotekama nisu dopuštene. EN: Symbolic links to other files are not allowed. */
    public function testSymbolicLinkIsRejected(): void
    {
        $this->assertTrue(symlink($this->directory, $this->directory . '/uploads/link'));
        $this->expectException(RuntimeException::class);
        BundledAssetPermissions::inheritOwnership($this->directory . '/uploads', ['link/private-backup.zip']);
    }

    /** HR: Direktorij nije binarni privitak. EN: A directory is not an attachment binary. */
    public function testDirectoryIsRejected(): void
    {
        $this->expectException(RuntimeException::class);
        BundledAssetPermissions::inheritOwnership($this->directory . '/uploads', ['guide/nested']);
    }
}
