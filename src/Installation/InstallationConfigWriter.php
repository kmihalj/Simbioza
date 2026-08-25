<?php

declare(strict_types=1);

namespace App\Installation;

use Psr\Log\LogLevel;

/**
 * HR: Provjerava i atomski zapisuje privatnu instalacijsku konfiguraciju.
 * Datoteke s vjerodajnicama dobivaju prava 0600 i nikada se ne ispisuju.
 *
 * EN: Validates and atomically writes private installation configuration.
 * Credential-bearing files receive mode 0600 and are never printed.
 */
final readonly class InstallationConfigWriter
{
    private const SUPPORTED_DRIVERS = ['sqlite', 'mysql', 'pgsql'];

    private const SUPPORTED_LOCALES = ['hr', 'en'];

    /** HR: Inicijalizira konfiguracijske putanje. EN: Initializes configuration paths. */
    public function __construct(private InstallationPaths $paths)
    {
    }

    /**
     * HR: Zapisuje postavke baze, okruženja i aplikacije.
     *
     * EN: Writes database, environment, and application settings.
     *
     * @param array<array-key, mixed> $database
     * @param array<array-key, mixed> $application
     */
    public function write(array $database, array $application): void
    {
        $this->ensureDirectories();

        $databaseConfig = $this->buildDatabaseConfig($database);
        $installationConfig = $this->buildInstallationConfig($application);
        $environmentConfig = [
            'salt' => bin2hex(random_bytes(32)),
            'log_level' => LogLevel::INFO,
            'environment' => 'production',
            'debug' => false,
            'encryption_key' => base64_encode(random_bytes(32)),
            'trusted_proxies' => ['127.0.0.1', '::1'],
        ];

        $this->atomicWritePhpConfig($this->paths->databaseConfig(), $databaseConfig);
        $this->atomicWritePhpConfig($this->paths->environmentConfig(), $environmentConfig);
        $this->atomicWritePhpConfig($this->paths->installationConfig(), $installationConfig);
    }

    /**
     * HR: Vraća normaliziranu vezu prema bazi podataka.
     *
     * EN: Returns a normalized database connection.
     *
     * @param array<array-key, mixed> $database
     * @return array{driver:'sqlite',database:string}|array{driver:'mysql'|'pgsql',host:string,port:int,database:string,username:string,password:string,charset:string,options:array<mixed>}
     */
    public function buildDatabaseConnection(array $database): array
    {
        $driver = strtolower(trim($this->scalarString($database['driver'] ?? '')));
        if (!in_array($driver, self::SUPPORTED_DRIVERS, true)) {
            throw new \InvalidArgumentException('Unsupported database driver.');
        }

        if ($driver === 'sqlite') {
            return [
                'driver' => 'sqlite',
                'database' => $this->paths->dataDirectory() . DIRECTORY_SEPARATOR . 'simbioza.sqlite',
            ];
        }

        $host = trim($this->scalarString($database['host'] ?? ''));
        $name = trim($this->scalarString($database['database'] ?? ''));
        $username = trim($this->scalarString($database['username'] ?? ''));
        $port = filter_var($database['port'] ?? null, FILTER_VALIDATE_INT);
        $defaultPort = $driver === 'pgsql' ? 5432 : 3306;

        if ($host === '' || $name === '' || $username === '') {
            throw new \InvalidArgumentException('Database host, name, and username are required.');
        }

        if ($port === false || $port < 1 || $port > 65535) {
            $port = $defaultPort;
        }

        return [
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'database' => $name,
            'username' => $username,
            'password' => $this->scalarString($database['password'] ?? ''),
            'charset' => $driver === 'pgsql' ? 'UTF8' : 'utf8mb4',
            'options' => [],
        ];
    }

    /**
     * HR: Omata normaliziranu vezu u ORM format konfiguracije.
     *
     * EN: Wraps a normalized connection in the ORM configuration format.
     *
     * @param array<array-key, mixed> $database
     * @return array<string, mixed>
     */
    private function buildDatabaseConfig(array $database): array
    {
        return ['connections' => ['default' => $this->buildDatabaseConnection($database)]];
    }

    /**
     * HR: Provjerava postavke jezika i identiteta web-mjesta.
     *
     * EN: Validates site language and identity settings.
     *
     * @param array<array-key, mixed> $application
     * @return array<string, mixed>
     */
    private function buildInstallationConfig(array $application): array
    {
        $name = trim($this->scalarString($application['name'] ?? ''));
        $primaryLocale = strtolower(trim($this->scalarString($application['primary_locale'] ?? '')));
        $requestedLocales = is_array($application['supported_locales'] ?? null)
        ? $application['supported_locales']
        : [];
        $supportedLocales = array_values(array_unique(array_filter(
            array_map(fn (mixed $locale): string => strtolower(trim($this->scalarString($locale))), $requestedLocales),
            static fn (string $locale): bool => in_array($locale, self::SUPPORTED_LOCALES, true),
        )));
        $timezone = trim($this->scalarString($application['timezone'] ?? 'Europe/Zagreb'));

        if ($name === '' || mb_strlen($name) > 100) {
            throw new \InvalidArgumentException('The application name is required and must be at most 100 characters.');
        }

        if ($supportedLocales === [] || !in_array($primaryLocale, $supportedLocales, true)) {
            throw new \InvalidArgumentException('The primary language must be one of the enabled languages.');
        }

        if (!in_array($timezone, timezone_identifiers_list(), true)) {
            throw new \InvalidArgumentException('The selected timezone is invalid.');
        }

        return [
            'name' => $name,
            'primary_locale' => $primaryLocale,
            'supported_locales' => $supportedLocales,
            'timezone' => $timezone,
            'installed_at' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(DATE_ATOM),
        ];
    }

    /** HR: Izrađuje privatne zapisive direktorije. EN: Creates private writable directories. */
    private function ensureDirectories(): void
    {
        foreach ([$this->paths->configDirectory(), $this->paths->dataDirectory()] as $directory) {
            if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
                throw new \RuntimeException('An installation directory could not be created.');
            }
        }
    }

    /**
     * HR: Atomski zapisuje privatnu PHP konfiguracijsku datoteku.
     *
     * EN: Atomically writes a private PHP configuration file.
     *
     * @param array<string, mixed> $configuration
     */
    private function atomicWritePhpConfig(string $path, array $configuration): void
    {
        $contents = "<?php\n\ndeclare(strict_types=1);\n\nreturn "
        . var_export($configuration, true)
        . ";\n";
        $temporaryPath = tempnam(dirname($path), '.simbioza-config-');
        if (!is_string($temporaryPath)) {
            throw new \RuntimeException('A temporary configuration file could not be created.');
        }

        try {
            if (file_put_contents($temporaryPath, $contents, LOCK_EX) === false) {
                throw new \RuntimeException('An installation configuration file could not be written.');
            }

            if (!chmod($temporaryPath, 0600)) {
                throw new \RuntimeException('The installation configuration permissions could not be secured.');
            }

            if (!rename($temporaryPath, $path)) {
                throw new \RuntimeException('An installation configuration file could not be activated.');
            }
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    /** HR: Sigurno pretvara samo skalaran instalacijski unos. EN: Safely converts scalar installer input only. */
    private function scalarString(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : '';
    }
}
