<?php

declare(strict_types=1);

namespace App\Module;

/**
 * HR: Neizmjenjivi rezultat jedne strogo ograničene sistemske naredbe.
 * EN: Immutable result of one strictly allowlisted system command.
 */
final readonly class CommandResult
{
    /** HR: Sprema izlazni kod i odvojeni standardni izlaz/pogrešku. EN: Stores exit code and separate stdout/stderr. */
    public function __construct(
        public int $exitCode,
        public string $stdout,
        public string $stderr,
    ) {
    }
}
