<?php

declare(strict_types=1);

namespace App\Installation;

/**
 * EN: Framework-independent installer HTTP response that can be tested without a web server.
 *
 * HR: Neovisan HTTP odgovor instalera koji se može provjeriti bez web-poslužitelja.
 */
final readonly class InstallationResponse
{
    /**
     * EN: Stores response status, headers, and body.
     *
     * HR: Sprema status, zaglavlja i tijelo odgovora.
     *
     * @param array<string, string> $headers
     */
    public function __construct(
        public int $status,
        public array $headers,
        public string $body,
    ) {
    }
}
