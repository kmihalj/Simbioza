<?php

declare(strict_types=1);

namespace App\Tools;

/**
 * HR: Rezultat jedne vanjske naredbe s izlaznim kodom i spojenim izlazom.
 * EN: Result of one external command with its exit code and combined output.
 */
final readonly class MatrixCommandResult
{
    /**
     * HR: Sprema izlazni kod, spojeni izlaz i trajanje pokrenute naredbe.
     * EN: Stores the exit code, combined output, and command duration.
     */
    public function __construct(
        public int $exitCode,
        public string $output,
        public float $durationSeconds,
    ) {
    }
}
