<?php

declare(strict_types=1);

namespace App\Installation;

/**
 * HR: Neovisan HTTP odgovor instalera koji se može provjeriti bez web-poslužitelja.
 * EN: Framework-independent installer HTTP response that can be tested without a web server.
 */
final readonly class InstallationResponse
{
    /**
     * HR: Sprema status, zaglavlja i tijelo odgovora.
     * EN: Stores response status, headers, and body.
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
