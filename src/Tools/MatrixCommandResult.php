<?php

declare(strict_types=1);

namespace App\Tools;

/**
 * EN: Result of one external command with its exit code and combined output.
 *
 * HR: Rezultat jedne vanjske naredbe s izlaznim kodom i spojenim izlazom.
 */
final readonly class MatrixCommandResult
{
    /**
     * EN: Stores the exit code, combined output, and command duration.
     *
     * HR: Sprema izlazni kod, spojeni izlaz i trajanje pokrenute naredbe.
     */
    public function __construct(
        public int $exitCode,
        public string $output,
        public float $durationSeconds,
    ) {
    }
}
