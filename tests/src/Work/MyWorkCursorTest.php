<?php

declare(strict_types=1);

namespace Tests\Work;

use App\Work\MyWorkCursor;
use HeartPhrame\Session\SessionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * EN: Verifies private cursor navigation independently of content permissions, which remain checked on every page.
 *
 * HR: Provjerava privatne navigacijske tokene neovisno o ovlastima sadržaja koje se provjeravaju na svakoj stranici.
 */
#[CoversClass(MyWorkCursor::class)]
final class MyWorkCursorTest extends TestCase
{
    /** @var array<string,mixed> */
    private array $storage = [];

    private int $now = 1700000000;

    private MyWorkCursor $cursor;

    /**
     * EN: Models the native session get/set contract and advances time without sleeping.
     *
     * HR: Modelira izvorni ugovor get/set sjednice i pomiče vrijeme bez čekanja.
     */
    protected function setUp(): void
    {
        $session = $this->createStub(SessionInterface::class);
        $session->method('get')->willReturnCallback(
            fn(string $key, mixed $default = null): mixed => $this->storage[$key] ?? $default,
        );
        $session->method('set')->willReturnCallback(function (string $key, mixed $value): void {
            $this->storage[$key] = $value;
        });
        $this->cursor = new MyWorkCursor($session, fn(): int => $this->now);
    }

    /**
     * EN: Two opaque random tokens resume their own positions without encoding a readable source offset.
     *
     * HR: Dva neprozirna slučajna tokena vraćaju vlastite položaje bez čitljivog izvornog pomaka.
     */
    public function testIssuesDistinctOpaqueTokensAndResolvesTheirPositions(): void
    {
        $first = $this->cursor->issue(120, 7, 'page', 'recent', 10);
        $second = $this->cursor->issue(913, 7, 'page', 'recent', 10);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $first);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $second);
        $this->assertNotSame($first, $second);
        $this->assertSame(120, $this->cursor->resolve($first, 7, 'page', 'recent', 10));
        $this->assertSame(913, $this->cursor->resolve($second, 7, 'page', 'recent', 10));
    }

    /**
     * EN: An authenticated account and every list dimension must match before a position can be used.
     *
     * HR: Prijavljeni račun i svaka dimenzija popisa moraju odgovarati prije uporabe položaja.
     */
    public function testRejectsCrossAccountCrossListAndRevokedAuthentication(): void
    {
        $token = $this->cursor->issue(120, 7, 'page', 'recent', 10);
        $this->assertSame(0, $this->cursor->resolve($token, 8, 'page', 'recent', 10));
        $this->assertSame(0, $this->cursor->resolve($token, 0, 'page', 'recent', 10));
        $this->assertSame(0, $this->cursor->resolve($token, 7, 'survey', 'recent', 10));
        $this->assertSame(0, $this->cursor->resolve($token, 7, 'page', 'drafts', 10));
        $this->assertSame(0, $this->cursor->resolve($token, 7, 'page', 'review', 10));
        $this->assertSame(0, $this->cursor->resolve($token, 7, 'page', 'recent', 25));
        $this->assertSame(0, $this->cursor->resolve($token, 7, 'page', 'recent', 50));
        $this->assertSame(120, $this->cursor->resolve($token, 7, 'page', 'recent', 10));
    }

    /**
     * EN: Expiry is exactly one hour from issue; navigation does not extend the lifetime.
     *
     * HR: Istek je točno sat vremena od izdavanja; navigacija ne produljuje trajanje.
     */
    public function testExpiresAtOneHourAndRemovesExpiredRecords(): void
    {
        $token = $this->cursor->issue(120, 7, 'html', 'drafts', 25);
        $this->now += 3599;
        $this->assertSame(120, $this->cursor->resolve($token, 7, 'html', 'drafts', 25));
        ++$this->now;
        $this->assertSame(0, $this->cursor->resolve($token, 7, 'html', 'drafts', 25));
        $this->assertSame([[]], array_values($this->storage));
        $fresh = $this->cursor->issue(40, 7, 'html', 'drafts', 25);
        $this->assertSame(40, $this->cursor->resolve($fresh, 7, 'html', 'drafts', 25));
    }

    /**
     * EN: Fifty tokens are retained in issue order even when they share the same timestamp.
     *
     * HR: Pedeset tokena zadržava se redoslijedom izdavanja i kada dijele istu vremensku oznaku.
     */
    public function testEvictsOldestTokensAtTheSessionBound(): void
    {
        $tokens = [];
        for ($offset = 1; $offset <= 51; ++$offset) {
            $tokens[] = $this->cursor->issue($offset, 7, 'survey', 'review', 50);
        }

        $this->assertSame(0, $this->cursor->resolve($tokens[0], 7, 'survey', 'review', 50));
        $this->assertSame(2, $this->cursor->resolve($tokens[1], 7, 'survey', 'review', 50));
        $this->assertSame(51, $this->cursor->resolve($tokens[50], 7, 'survey', 'review', 50));
        $this->assertCount(50, array_values($this->storage)[0]);
    }

    /**
     * EN: Source offsets are bounded and malformed input cannot become a numeric cursor.
     *
     * HR: Izvorni pomaci su ograničeni, a nevaljani ulaz ne postaje brojčani token.
     */
    public function testClampsOffsetsAndRejectsMalformedTokens(): void
    {
        $large = $this->cursor->issue(PHP_INT_MAX, 7, 'page', 'recent', 10);
        $negative = $this->cursor->issue(-100, 7, 'page', 'recent', 10);
        $this->assertSame(1000000, $this->cursor->resolve($large, 7, 'page', 'recent', 10));
        $this->assertSame(0, $this->cursor->resolve($negative, 7, 'page', 'recent', 10));
        foreach ([null, [], 120, '120', '', str_repeat('g', 64), str_repeat('a', 63)] as $invalid) {
            $this->assertSame(0, $this->cursor->resolve($invalid, 7, 'page', 'recent', 10));
        }

        $this->assertSame('', $this->cursor->issue(10, 0, 'page', 'recent', 10));
        $this->assertSame('', $this->cursor->issue(10, 7, 'unknown', 'recent', 10));
        $this->assertSame('', $this->cursor->issue(10, 7, 'page', 'unknown', 10));
        $this->assertSame('', $this->cursor->issue(10, 7, 'page', 'recent', 1000));
    }

    /**
     * EN: A token copied to another session or retained after session destruction grants no resume position.
     *
     * HR: Token kopiran u drugu sjednicu ili zadržan nakon njezina uništenja ne vraća položaj nastavka.
     */
    public function testTokensStayWithinTheirSessionAndDisappearOnLogout(): void
    {
        $token = $this->cursor->issue(120, 7, 'page', 'recent', 10);
        $otherSession = $this->createStub(SessionInterface::class);
        $otherSession->method('get')->willReturn([]);
        $other = new MyWorkCursor($otherSession, fn(): int => $this->now);
        $this->assertSame(0, $other->resolve($token, 7, 'page', 'recent', 10));
        $this->storage = [];
        $this->assertSame(0, $this->cursor->resolve($token, 7, 'page', 'recent', 10));
    }

    /**
     * EN: Corrupt server storage is cleaned and never coerced into an authorization binding.
     *
     * HR: Oštećena poslužiteljska pohrana čisti se i nikada se ne pretvara u vezu ovlaštenja.
     */
    public function testMalformedServerRecordsFailClosedAndAreCleaned(): void
    {
        $token = $this->cursor->issue(120, 7, 'page', 'recent', 10);
        $key = array_key_first($this->storage);
        $this->assertIsString($key);
        $this->storage[$key] = [$token => ['offset' => '120', 'user' => 7, 'kind' => 'page',
            'section' => 'recent', 'size' => 10, 'expires' => $this->now + 3600]];
        $this->assertSame(0, $this->cursor->resolve($token, 7, 'page', 'recent', 10));
        $this->assertSame([[]], array_values($this->storage));
        $this->storage[$key] = 'invalid';
        $this->assertSame(0, $this->cursor->resolve($token, 7, 'page', 'recent', 10));
        $this->assertSame([[]], array_values($this->storage));
    }
}
