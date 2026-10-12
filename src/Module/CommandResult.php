<?php

declare(strict_types=1);

namespace App\Module;

/**
 * EN: Immutable result of one strictly allowlisted system command.
 *
 * HR: Neizmjenjivi rezultat jedne strogo ograničene sistemske naredbe.
 */
final readonly class CommandResult
{
    /**
     * EN: Stores exit code and separate stdout/stderr.
     *
     * HR: Sprema izlazni kod i odvojeni standardni izlaz/pogrešku.
     */
    public function __construct(
        public int $exitCode,
        public string $stdout,
        public string $stderr,
    ) {
    }
}
