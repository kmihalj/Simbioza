<?php

declare(strict_types=1);

namespace App\Installation;

/**
 * HR: Normalizira i provjerava sve podatke web-čarobnjaka prije zapisivanja.
 * EN: Normalizes and validates all wizard data before anything is written.
 */
final readonly class InstallationInputValidator
{
    private const SUPPORTED_DRIVERS = ['sqlite', 'mysql', 'pgsql'];

    private const SUPPORTED_LOCALES = ['hr', 'en'];

    /**
     * HR: Provjerava i normalizira postavke baze.
     * EN: Validates and normalizes database settings.
     *
     * @param array<array-key, mixed> $input
     * @return array{driver:'sqlite'}|array{driver:'mysql'|'pgsql',host:string,port:int,database:string,username:string,password:string}
     */
    public function database(array $input): array
    {
        $driver = strtolower(trim($this->scalarString($input['driver'] ?? '')));
        $errors = [];
        if (!in_array($driver, self::SUPPORTED_DRIVERS, true)) {
            $errors[] = 'database_driver';
        }

        if ($errors !== []) {
            throw new InstallationValidationException($errors);
        }

        if ($driver === 'sqlite') {
            return ['driver' => 'sqlite'];
        }

        $host = trim($this->scalarString($input['host'] ?? ''));
        $database = trim($this->scalarString($input['database'] ?? ''));
        $username = trim($this->scalarString($input['username'] ?? ''));
        $password = $this->scalarString($input['password'] ?? '');
        $defaultPort = $driver === 'pgsql' ? 5432 : 3306;
        $port = filter_var($input['port'] ?? $defaultPort, FILTER_VALIDATE_INT);

        if ($host === '' || mb_strlen($host) > 255 || preg_match('/^[A-Za-z0-9._:-]+$/', $host) !== 1) {
            $errors[] = 'database_host';
        }

        if ($database === '' || mb_strlen($database) > 128 || preg_match('/^[A-Za-z0-9_$-]+$/', $database) !== 1) {
            $errors[] = 'database_name';
        }

        if ($username === '' || mb_strlen($username) > 128 || preg_match('/[\x00-\x1F\x7F]/', $username) === 1) {
            $errors[] = 'database_username';
        }

        if ($port === false || $port < 1 || $port > 65535) {
            $errors[] = 'database_port';
        }

        if (strlen($password) > 1024) {
            $errors[] = 'database_password';
        }

        if ($errors !== []) {
            throw new InstallationValidationException(array_values(array_unique($errors)));
        }

        if (($driver !== 'mysql' && $driver !== 'pgsql') || !is_int($port)) {
            throw new InstallationValidationException(['database_driver']);
        }

        return [
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $username,
            'password' => $password,
        ];
    }

    /**
     * HR: Provjerava i normalizira identitet, jezike i vremensku zonu aplikacije.
     * EN: Validates and normalizes application identity, locales, and timezone.
     *
     * @param array<array-key, mixed> $input
     * @return array{name:string,primary_locale:string,supported_locales:list<string>,timezone:string}
     */
    public function application(array $input): array
    {
        $name = trim($this->scalarString($input['name'] ?? ''));
        $primaryLocale = strtolower(trim($this->scalarString($input['primary_locale'] ?? '')));
        $requestedLocales = is_array($input['supported_locales'] ?? null)
        ? $input['supported_locales']
        : [];
        $supportedLocales = [];
        foreach ($requestedLocales as $locale) {
            $locale = strtolower(trim($this->scalarString($locale)));
            if (in_array($locale, self::SUPPORTED_LOCALES, true) && !in_array($locale, $supportedLocales, true)) {
                $supportedLocales[] = $locale;
            }
        }

        $timezone = trim($this->scalarString($input['timezone'] ?? 'Europe/Zagreb'));
        $errors = [];
        if ($name === '' || mb_strlen($name) > 100) {
            $errors[] = 'application_name';
        }

        if ($supportedLocales === []) {
            $errors[] = 'supported_locales';
        }

        if (!in_array($primaryLocale, $supportedLocales, true)) {
            $errors[] = 'primary_locale';
        }

        if (!in_array($timezone, timezone_identifiers_list(), true)) {
            $errors[] = 'timezone';
        }

        if ($errors !== []) {
            throw new InstallationValidationException($errors);
        }

        return [
            'name' => $name,
            'primary_locale' => $primaryLocale,
            'supported_locales' => $supportedLocales,
            'timezone' => $timezone,
        ];
    }

    /**
     * HR: Provjerava podatke i čvrstoću lozinke prvog administratora.
     * EN: Validates the first administrator and password strength.
     *
     * @param array<array-key, mixed> $input
     * @return array{login:string,display_name:string,first_name:string,last_name:string,email:string,password:string}
     */
    public function administrator(array $input): array
    {
        $login = trim($this->scalarString($input['login'] ?? ''));
        $displayName = trim($this->scalarString($input['display_name'] ?? ''));
        $firstName = trim($this->scalarString($input['first_name'] ?? ''));
        $lastName = trim($this->scalarString($input['last_name'] ?? ''));
        $email = trim($this->scalarString($input['email'] ?? ''));
        $password = $this->scalarString($input['password'] ?? '');
        $passwordConfirmation = $this->scalarString($input['password_confirmation'] ?? '');
        $errors = [];

        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._@-]{2,127}$/', $login) !== 1) {
            $errors[] = 'administrator_login';
        }

        if ($displayName === '' || mb_strlen($displayName) > 150) {
            $errors[] = 'administrator_display_name';
        }

        if (mb_strlen($firstName) > 100 || mb_strlen($lastName) > 100) {
            $errors[] = 'administrator_name';
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 254) {
            $errors[] = 'administrator_email';
        }

        if (!$this->isStrongPassword($password, $login, $email)) {
            $errors[] = 'administrator_password';
        }

        if (!hash_equals($password, $passwordConfirmation)) {
            $errors[] = 'administrator_password_confirmation';
        }

        if ($errors !== []) {
            throw new InstallationValidationException($errors);
        }

        return [
            'login' => $login,
            'display_name' => $displayName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'password' => $password,
        ];
    }

    /**
     * HR: Traži dugu lozinku s najmanje tri skupine znakova koja ne sadrži identitet računa.
     * EN: Requires a long password with three character groups that does not contain account identity.
     */
    private function isStrongPassword(string $password, string $login, string $email): bool
    {
        $length = mb_strlen($password);
        if ($length < 12 || $length > 128) {
            return false;
        }

        $groups = 0;
        foreach (['/[a-z]/u', '/[A-Z]/u', '/\d/u', '/[^A-Za-z0-9]/u'] as $pattern) {
            $groups += preg_match($pattern, $password) === 1 ? 1 : 0;
        }

        if ($groups < 3) {
            return false;
        }

        $normalizedPassword = mb_strtolower($password);
        $identityParts = [$login, strstr($email, '@', true) ?: ''];
        foreach ($identityParts as $identityPart) {
            $identityPart = mb_strtolower(trim($identityPart));
            if (mb_strlen($identityPart) >= 3 && str_contains($normalizedPassword, $identityPart)) {
                return false;
            }
        }

        return true;
    }

    /** HR: Sigurno pretvara samo skalaran HTTP unos. EN: Safely converts scalar HTTP input only. */
    private function scalarString(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : '';
    }
}
