<?php

declare(strict_types=1);

namespace App\Installation;

use PDO;
use RuntimeException;

/**
 * HR: Otvara stvarnu PDO vezu odabrane vrste baze i izvršava probni upit.
 * EN: Opens a real PDO connection for the selected database and executes a probe query.
 */
final readonly class InstallationDatabaseTester
{
    /** HR: Inicijalizira normalizaciju veze. EN: Initializes connection normalization. */
    public function __construct(private InstallationConfigWriter $configWriter)
    {
    }

    /**
     * HR: Provjerava stvarnu vezu bez vraćanja ili zapisivanja vjerodajnica.
     * EN: Verifies the real connection without returning or logging credentials.
     *
     * @param array<array-key, mixed> $database
     */
    public function test(array $database): void
    {
        $connection = $this->configWriter->buildDatabaseConnection($database);
        $driver = $connection['driver'];
        $requiredExtension = 'pdo_' . $driver;
        if (!extension_loaded($requiredExtension)) {
            throw new RuntimeException('The selected PDO driver is unavailable.');
        }

        $dsn = $this->dsn($connection);
        if ($connection['driver'] === 'sqlite') {
            $username = null;
            $password = null;
        } else {
            $username = $connection['username'];
            $password = $connection['password'];
        }

        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        $statement = $pdo->query('SELECT 1');
        if ($statement === false || $statement->fetchColumn() === false) {
            throw new RuntimeException('The database probe query failed.');
        }

        $this->assertEmptyDatabase($pdo, $driver);
    }

    /**
     * HR: Gradi PDO DSN iz već provjerenih vrijednosti.
     * EN: Builds a PDO DSN from already validated values.
     *
     * @param array{driver:'sqlite',database:string}|array{
     *     driver:'mysql'|'pgsql',
     *     host:string,
     *     port:int,
     *     database:string,
     *     username:string,
     *     password:string,
     *     charset:string,
     *     options:array<mixed>
     * } $connection
     */
    private function dsn(array $connection): string
    {
        $driver = $connection['driver'];
        if ($driver === 'sqlite') {
            return 'sqlite:' . $connection['database'];
        }

        if ($connection['driver'] !== 'mysql' && $connection['driver'] !== 'pgsql') {
            throw new RuntimeException('The selected database driver is invalid.');
        }

        if ($connection['driver'] === 'pgsql') {
            return sprintf(
                'pgsql:host=%s;port=%d;dbname=%s',
                $connection['host'],
                $connection['port'],
                $connection['database'],
            );
        }

        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $connection['host'],
            $connection['port'],
            $connection['database'],
        );
    }

    /**
     * HR: Odbija bazu koja već sadrži korisničke tablice kako migracije ne bi pregazile podatke.
     * EN: Rejects a database with existing user tables so migrations cannot overwrite data.
     */
    private function assertEmptyDatabase(PDO $pdo, string $driver): void
    {
        $query = match ($driver) {
            'sqlite' => "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'",
            'pgsql' => "SELECT COUNT(*) FROM pg_catalog.pg_tables WHERE schemaname = current_schema()",
            'mysql' => 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()',
            default => throw new RuntimeException('The selected database driver is invalid.'),
        };
        $statement = $pdo->query($query);
        $tableCount = $statement === false ? false : $statement->fetchColumn();
        if (!is_numeric($tableCount) || (int)$tableCount !== 0) {
            throw new RuntimeException('The selected database is not empty.');
        }
    }
}
