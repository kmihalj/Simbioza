<?php

declare(strict_types=1);

namespace Tests\Tools;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class BilingualPhpDocAuditTest extends TestCase
{
    /**
     * EN: A long adjacent ordered PHPDoc is valid; reversed language blocks are not.
     * Temporary files contain synthetic examples, never application configuration.
     *
     * HR: Dug neposredni uređeni PHPDoc je valjan; obrnuti jezični blokovi nisu.
     * Privremene datoteke sadrže ogledne primjere, ne konfiguraciju aplikacije.
     */
    public function testRecognizesOrderAndDoesNotImposeAnArbitraryDocLengthLimit(): void
    {
        require_once dirname(__DIR__, 3) . '/scripts/audit_bilingual_phpdoc.php';
        $directory = sys_get_temp_dir() . '/simbioza-a5-phpdoc-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($directory . '/src', 0700, true));
        self::assertTrue(mkdir($directory . '/tests/src', 0700, true));
        try {
            $path = $directory . '/src/example.php';
            file_put_contents($path, "<?php\n/**\n * EN: English.\n" . str_repeat(" * Extra detail.\n", 50)
                . " *\n * HR: Hrvatski.\n */\nfunction example(): void {}\n");
            self::assertSame([], \auditPhpFile($path));
            file_put_contents($path, "<?php\n/**\n * HR: Hrvatski.\n *\n * EN: English.\n */\n"
                . "function example(): void {}\n");
            $issues = \auditPhpFile($path);
            self::assertCount(1, $issues);
            self::assertSame('language-order', $issues[0]['status']);
            file_put_contents($path, "<?php\nfunction example(): void {}\n");
            self::assertSame('missing', \auditPhpFile($path)[0]['status']);
            $testPath = $directory . '/tests/src/example.php';
            file_put_contents($testPath, "<?php\nfunction example(): void {}\n");
            self::assertSame([], \auditPhpFile($testPath));
        } finally {
            unlink($directory . '/src/example.php');
            unlink($directory . '/tests/src/example.php');
            rmdir($directory . '/src');
            rmdir($directory . '/tests/src');
            rmdir($directory . '/tests');
            rmdir($directory);
        }
    }
}
