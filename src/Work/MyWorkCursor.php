<?php

declare(strict_types=1);

namespace App\Work;

use Closure;
use HeartPhrame\Session\SessionInterface;

/**
 * EN: Keeps source-row positions in the session; the browser receives only an opaque random token.
 *
 * HR: Čuva položaje izvornih redaka u sjednici; preglednik prima samo neproziran slučajni token.
 */
final readonly class MyWorkCursor
{
    private const SESSION_KEY = 'simbioza_my_work_cursors';

    private const LIFETIME = 3600;

    private const MAX_TOKENS = 50;

    /**
     * EN: Uses native session storage and an optional clock for deterministic expiry tests.
     *
     * HR: Koristi izvornu pohranu sjednice i neobvezni sat za determinističke testove isteka.
     *
     * @param (Closure():int)|null $clock
     */
    public function __construct(private SessionInterface $session, private ?Closure $clock = null)
    {
    }

    /**
     * EN: Invalid, expired, cross-account or cross-list tokens restart at zero without exposing stored positions.
     *
     * HR: Nevaljani, istekli ili tuđem računu/popisu pripadajući tokeni vraćaju početak bez otkrivanja položaja.
     */
    public function resolve(mixed $token, int $user, string $kind, string $section, int $size): int
    {
        $records = $this->records();
        $this->session->set(self::SESSION_KEY, $records);
        if (!is_string($token) || preg_match('/^[a-f0-9]{64}$/D', $token) !== 1) {
            return 0;
        }

        $record = $records[$token] ?? null;
        if (
            $record === null || !$this->validBinding($user, $kind, $section, $size)
            || $record['user'] !== $user || $record['kind'] !== $kind
            || $record['section'] !== $section || $record['size'] !== $size
        ) {
            return 0;
        }

        return $record['offset'];
    }

    /**
     * EN: Issues one hour of session-local navigation and evicts the oldest tokens beyond fifty.
     *
     * HR: Izdaje sat vremena navigacije unutar sjednice i uklanja najstarije tokene iznad pedeset.
     */
    public function issue(int $offset, int $user, string $kind, string $section, int $size): string
    {
        $records = $this->records();
        if (!$this->validBinding($user, $kind, $section, $size)) {
            $this->session->set(self::SESSION_KEY, $records);
            return '';
        }

        $token = bin2hex(random_bytes(32));
        $records[$token] = ['offset' => max(0, min(1000000, $offset)), 'user' => $user,
            'kind' => $kind, 'section' => $section, 'size' => $size, 'expires' => $this->now() + self::LIFETIME];
        $this->session->set(self::SESSION_KEY, array_slice($records, -self::MAX_TOKENS, null, true));
        return $token;
    }

    /**
     * EN: Discards expired or malformed server records before any lookup or insertion.
     *
     * HR: Uklanja istekle ili nevaljane poslužiteljske zapise prije dohvata ili umetanja.
     *
     * @return array<string,array{offset:int,user:int,kind:string,section:string,size:int,expires:int}>
     */
    private function records(): array
    {
        $stored = $this->session->get(self::SESSION_KEY, []);
        if (!is_array($stored)) {
            return [];
        }

        $records = [];
        $now = $this->now();
        foreach ($stored as $token => $record) {
            if (
                !is_string($token) || preg_match('/^[a-f0-9]{64}$/D', $token) !== 1 || !is_array($record)
                || !is_int($record['offset'] ?? null) || $record['offset'] < 0 || $record['offset'] > 1000000
                || !is_int($record['user'] ?? null) || !is_string($record['kind'] ?? null)
                || !is_string($record['section'] ?? null) || !is_int($record['size'] ?? null)
                || !is_int($record['expires'] ?? null) || $record['expires'] <= $now
                || !$this->validBinding($record['user'], $record['kind'], $record['section'], $record['size'])
            ) {
                continue;
            }

            $records[$token] = ['offset' => $record['offset'], 'user' => $record['user'], 'kind' => $record['kind'],
                'section' => $record['section'], 'size' => $record['size'], 'expires' => $record['expires']];
        }

        return array_slice($records, -self::MAX_TOKENS, null, true);
    }

    /**
     * EN: Navigation tokens never confer identity or permit an unknown list or page size.
     *
     * HR: Navigacijski tokeni ne dodjeljuju identitet ni pristup nepoznatom popisu ili veličini stranice.
     */
    private function validBinding(int $user, string $kind, string $section, int $size): bool
    {
        return $user > 0 && in_array($kind, ['page', 'html', 'survey'], true)
        && in_array($section, ['recent', 'drafts', 'review'], true) && in_array($size, [10, 25, 50], true);
    }

    /**
     * EN: Reads the injected clock or the normal Unix timestamp.
     *
     * HR: Čita zadani sat ili uobičajenu Unix vremensku oznaku.
     */
    private function now(): int
    {
        return $this->clock instanceof \Closure ? ($this->clock)() : time();
    }
}
