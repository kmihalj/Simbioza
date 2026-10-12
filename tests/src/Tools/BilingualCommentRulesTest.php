<?php

declare(strict_types=1);

namespace Tests\Tools;

use App\Tools\BilingualCommentRules;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BilingualCommentRules::class)]
final class BilingualCommentRulesTest extends TestCase
{
    /**
     * EN: Inline, reversed and alternating labels must become exactly two ordered blocks.
     *
     * HR: Oznake u istom retku, obrnute i izmjenične oznake moraju postati točno dva uređena bloka.
     */
    public function testRejectsAndNormalizesIncorrectLanguageOrder(): void
    {
        $rules = new BilingualCommentRules();
        foreach (
            [
            '/** HR: Hrvatski. EN: English. */',
            "/**\n * HR: Hrvatski.\n *\n * EN: English.\n */",
            "// EN: First English.\n// HR: Prvi hrvatski.\n// EN: Second English.\n// HR: Drugi hrvatski.",
            "/**\n * EN: English.\n * HR: Hrvatski.\n */",
            ] as $comment
        ) {
            self::assertFalse($rules->isOrdered($comment));
            $normalized = $rules->normalizeComment($comment);
            self::assertTrue($rules->isOrdered($normalized), $normalized);
            self::assertSame(['EN', 'HR'], $rules->markers($normalized));
            self::assertSame($normalized, $rules->normalizeComment($normalized));
        }
    }

    /**
     * EN: Code strings, annotations, nested indentation and comment-independent semantics stay intact.
     *
     * HR: Stringovi koda, anotacije, unutarnje uvlačenje i semantika neovisna o komentarima ostaju isti.
     */
    public function testPreservesCodeStringsAnnotationsAndIndentation(): void
    {
        $rules = new BilingualCommentRules();
        $source = <<<'PHP'
<?php
/**
 * HR: Hrvatski opis.
 *
 * EN: English description.
 *
 * @param array<string,int> $items
 * @return string
 */
function example(array $items): string
{
    // HR: Prvi hrvatski redak.
    // EN: First English line.
    // HR: Drugi hrvatski redak.
    // EN: Second English line.
    return 'HR: literal EN: not a comment';
}
PHP;
        $normalized = $rules->normalizeSource($source);
        self::assertSame($rules->semanticHash($source), $rules->semanticHash($normalized));
        self::assertStringContainsString("return 'HR: literal EN: not a comment';", $normalized);
        self::assertStringContainsString(' * @param array<string,int> $items', $normalized);
        self::assertStringContainsString("    // EN: First English line.\n", $normalized);
        self::assertStringContainsString("\n    //\n    // HR:", $normalized);
        foreach ($rules->comments($normalized) as $comment) {
            self::assertTrue($rules->isOrdered($comment['text']));
        }
        self::assertSame($normalized, $rules->normalizeSource($normalized));
    }

    /**
     * EN: Technical directives and already ordered blocks remain byte-for-byte unchanged.
     *
     * HR: Tehničke direktive i već uređeni blokovi ostaju nepromijenjeni bajt za bajt.
     */
    public function testLeavesAlreadyCorrectAndUnmarkedCommentsUntouched(): void
    {
        $rules = new BilingualCommentRules();
        foreach (
            [
            '/** @var string $value */',
            '// phpcs:ignore Some.Rule -- technical instruction',
            "/**\n * EN: English.\n *\n * HR: Hrvatski.\n */",
            "// EN: English.\n//\n// HR: Hrvatski.",
            ] as $comment
        ) {
            self::assertTrue($rules->isOrdered($comment));
            self::assertSame($comment, $rules->normalizeComment($comment));
        }
    }

    /**
     * EN: A tag sharing the closing delimiter retains its value, not the delimiter bytes.
     *
     * HR: Tag u retku sa završnim delimiterom čuva vrijednost, ne bajtove delimitera.
     */
    public function testRetainsTagOnClosingLine(): void
    {
        $rules = new BilingualCommentRules();
        $source = "<?php\n/** HR: Opis. EN: Description.\n * @see Example */\nfunction example(): void {}";
        $result = $rules->normalizeSource($source);
        self::assertStringContainsString(" * @see Example\n */", $result);
        self::assertSame($rules->semanticHash($source), $rules->semanticHash($result));
    }

    /**
     * EN: Inline bilingual parameter prose moves to language blocks without changing its type or name.
     *
     * HR: Dvojezični opis parametra premješta se u jezične blokove bez promjene tipa ili naziva.
     */
    public function testMovesBilingualParameterDescriptionAndRetainsSignature(): void
    {
        $rules = new BilingualCommentRules();
        $source = <<<'PHP'
<?php
/**
 * HR: Poveznice.
 * EN: Links.
 * @param array<string,string> $links HR: Relativne putanje. EN: Relative paths.
 */
function links(array $links): void {}
PHP;
        $result = $rules->normalizeSource($source);
        self::assertSame($rules->semanticHash($source), $rules->semanticHash($result));
        self::assertStringContainsString(' * @param array<string,string> $links', $result);
        self::assertStringContainsString('Relative paths.', $result);
        self::assertStringContainsString('Relativne putanje.', $result);
        self::assertTrue($rules->isOrdered($rules->comments($result)[0]['text']));
    }
}
