<?php

declare(strict_types=1);

namespace App\Installation;

use Throwable;

/**
 * EN: Writes installation diagnostics to a private log without exposing them to the user.
 *
 * HR: Zapisuje tehničke detalje instalacije u privatni log bez korisničkog ispisa.
 */
final readonly class InstallationLogger
{
    /**
     * EN: Initializes the private log location.
     *
     * HR: Inicijalizira privatnu lokaciju loga.
     */
    public function __construct(private InstallationPaths $paths)
    {
    }

    /**
     * EN: Records safe context and the complete exception for the system administrator.
     *
     * HR: Zapisuje sigurni kontekst i puni izuzetak za administratora sustava.
     */
    public function error(string $context, Throwable $throwable): void
    {
        $message = sprintf(
            "[%s] ERROR %s: %s\n%s\n",
            gmdate(DATE_ATOM),
            $context,
            $throwable->getMessage(),
            $throwable->getTraceAsString(),
        );

        $this->write($message);
    }

    /**
     * EN: Records a message without sensitive context.
     *
     * HR: Zapisuje poruku bez osjetljivog konteksta.
     */
    public function info(string $message): void
    {
        $this->write(sprintf("[%s] INFO %s\n", gmdate(DATE_ATOM), $message));
    }

    /**
     * EN: Safely appends a record to the private file.
     *
     * HR: Sigurno dodaje zapis privatnoj datoteci.
     */
    private function write(string $message): void
    {
        $directory = dirname($this->paths->logFile());
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            error_log('Simbioza installer could not create its private log directory.');
            return;
        }

        if (file_put_contents($this->paths->logFile(), $message, FILE_APPEND | LOCK_EX) === false) {
            error_log('Simbioza installer could not write its private log.');
            return;
        }

        chmod($this->paths->logFile(), 0600);
    }
}
