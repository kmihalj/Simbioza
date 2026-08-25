<?php

declare(strict_types=1);

namespace App\Installation;

use InvalidArgumentException;

/**
 * HR: Priprema jednokratni URL kojim lokalni administrator otvara web-installer.
 * EN: Prepares the one-time URL used by a local administrator to open the web installer.
 */
final readonly class InstallationPrepareCommand
{
    /** HR: Inicijalizira generator tokena. EN: Initializes the token generator. */
    public function __construct(private InstallationAccessToken $accessToken)
    {
    }

    /**
     * HR: Generira novu tajnu i vraća strogo provjereni HTTPS/HTTP URL.
     * EN: Generates a new secret and returns a strictly validated HTTPS/HTTP URL.
     */
    public function prepare(string $baseUrl): string
    {
        $baseUrl = rtrim(trim($baseUrl), '/');
        $parts = parse_url($baseUrl);
        if (
            !is_array($parts)
            || !in_array(strtolower((string)($parts['scheme'] ?? '')), ['https', 'http'], true)
            || trim((string)($parts['host'] ?? '')) === ''
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
        ) {
            throw new InvalidArgumentException('The installer base URL is invalid.');
        }

        return $baseUrl . '/install?token=' . rawurlencode($this->accessToken->generate());
    }
}
