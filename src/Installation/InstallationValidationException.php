<?php

declare(strict_types=1);

namespace App\Installation;

/**
 * HR: Predstavlja očekivane i korisniku sigurno prikazive pogreške unosa.
 * EN: Represents expected input errors that are safe to show to the user.
 */
final class InstallationValidationException extends \InvalidArgumentException
{
    /**
     * HR: Sprema stabilne kodove validacijskih pogrešaka.
     * EN: Stores stable validation error codes.
     *
     * @param list<string> $errorCodes
     */
    public function __construct(private readonly array $errorCodes)
    {
        parent::__construct('Installation input is invalid.');
    }

    /**
     * HR: Vraća kodove za lokalizirani prikaz.
     * EN: Returns codes for localized presentation.
     *
     * @return list<string>
     */
    public function errorCodes(): array
    {
        return $this->errorCodes;
    }
}
