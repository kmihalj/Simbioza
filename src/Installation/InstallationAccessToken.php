<?php

declare(strict_types=1);

namespace App\Installation;

/**
 * HR: Izrađuje, provjerava i troši jednokratnu tajnu novog instalera.
 * Na disku se sprema samo SHA-256 sažetak, nikada izvorni token.
 *
 * EN: Creates, verifies, and consumes the fresh installer's one-time secret.
 * Only its SHA-256 digest is stored on disk, never the original token.
 */
final readonly class InstallationAccessToken
{
    /** HR: Inicijalizira putanje tokena. EN: Initializes token storage paths. */
    public function __construct(private InstallationPaths $paths)
    {
    }

    /**
     * HR: Generira novi token i izvan javnog direktorija sprema samo njegov sažetak.
     *
     * EN: Generates a new token and stores only its hash outside the public directory.
     */
    public function generate(): string
    {
        if ($this->paths->isInstalled()) {
            throw new \RuntimeException('The application is already installed.');
        }

        $this->ensureDataDirectory();
        $token = bin2hex(random_bytes(32));
        $this->atomicWrite($this->paths->tokenFile(), hash('sha256', $token) . PHP_EOL);

        return $token;
    }

    /** HR: Provjerava token bez vremenskog curenja. EN: Verifies a token without timing leaks. */
    public function verify(string $token): bool
    {
        if ($token === '' || !is_file($this->paths->tokenFile())) {
            return false;
        }

        $storedHash = file_get_contents($this->paths->tokenFile());
        if (!is_string($storedHash)) {
            return false;
        }

        return hash_equals(trim($storedHash), hash('sha256', $token));
    }

    /**
     * HR: Jednokratno zamjenjuje valjani token za autoriziranu instalacijsku sesiju.
     * Token se uklanja prije nastavka kako istu adresu ne bi mogla otvoriti druga sesija.
     *
     * EN: Exchanges a valid token for an authorized installer session exactly once.
     * The token is removed before continuing so a second session cannot reuse its URL.
     */
    public function consume(string $token): bool
    {
        if (!$this->verify($token)) {
            return false;
        }

        $this->remove();

        return true;
    }

    /** HR: Uklanja pohranjeni token. EN: Removes the stored token. */
    public function remove(): void
    {
        $tokenFile = $this->paths->tokenFile();
        if (is_file($tokenFile) && !unlink($tokenFile)) {
            throw new \RuntimeException('The installer access token could not be removed.');
        }
    }

    /** HR: Po potrebi izrađuje privatni podatkovni direktorij. EN: Creates the private data directory when needed. */
    private function ensureDataDirectory(): void
    {
        $directory = $this->paths->dataDirectory();
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException('The application data directory could not be created.');
        }
    }

    /** HR: Atomski zapisuje privatnu datoteku. EN: Atomically writes a private file. */
    private function atomicWrite(string $path, string $contents): void
    {
        $temporaryPath = tempnam(dirname($path), '.simbioza-install-');
        if (!is_string($temporaryPath)) {
            throw new \RuntimeException('A temporary installer file could not be created.');
        }

        try {
            if (file_put_contents($temporaryPath, $contents, LOCK_EX) === false) {
                throw new \RuntimeException('The installer file could not be written.');
            }

            if (!chmod($temporaryPath, 0600)) {
                throw new \RuntimeException('The installer file permissions could not be secured.');
            }

            if (!rename($temporaryPath, $path)) {
                throw new \RuntimeException('The installer file could not be activated.');
            }
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }
}
