<?php

declare(strict_types=1);

namespace App\Installation;

use Throwable;

/**
 * HR: Zapisuje tehničke detalje instalacije u privatni log bez korisničkog ispisa.
 * EN: Writes installation diagnostics to a private log without exposing them to the user.
 */
final readonly class InstallationLogger
{
    /** HR: Inicijalizira privatnu lokaciju loga. EN: Initializes the private log location. */
    public function __construct(private InstallationPaths $paths)
    {
    }

    /**
     * HR: Zapisuje sigurni kontekst i puni izuzetak za administratora sustava.
     * EN: Records safe context and the complete exception for the system administrator.
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

    /** HR: Zapisuje poruku bez osjetljivog konteksta. EN: Records a message without sensitive context. */
    public function info(string $message): void
    {
        $this->write(sprintf("[%s] INFO %s\n", gmdate(DATE_ATOM), $message));
    }

    /** HR: Sigurno dodaje zapis privatnoj datoteci. EN: Safely appends a record to the private file. */
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
