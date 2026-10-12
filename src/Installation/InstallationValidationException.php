<?php

declare(strict_types=1);

namespace App\Installation;

/**
 * EN: Represents expected input errors that are safe to show to the user.
 *
 * HR: Predstavlja očekivane i korisniku sigurno prikazive pogreške unosa.
 */
final class InstallationValidationException extends \InvalidArgumentException
{
    /**
     * EN: Stores stable validation error codes.
     *
     * HR: Sprema stabilne kodove validacijskih pogrešaka.
     *
     * @param list<string> $errorCodes
     */
    public function __construct(private readonly array $errorCodes)
    {
        parent::__construct('Installation input is invalid.');
    }

    /**
     * EN: Returns codes for localized presentation.
     *
     * HR: Vraća kodove za lokalizirani prikaz.
     *
     * @return list<string>
     */
    public function errorCodes(): array
    {
        return $this->errorCodes;
    }
}
