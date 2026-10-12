<?php

declare(strict_types=1);

namespace App\Deployment;

use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\MigrationInterface;
use App\Module\ModuleCatalog;
use Composer\InstalledVersions;
use HeartPhrame\Config\Config;
use HeartPhrame\Config\ConfigPathResolver;
use HeartPhrame\Helper\Helper;
use RuntimeException;

/**
 * EN: Initializes only private runtime state and coordinates explicit, locked image migrations.
 * No live code update, automatic database rollback or destructive reinitialization is performed.
 *
 * HR: Inicijalizira samo privatni runtime i koordinira izričite zaključane migracije imagea.
 * Ne nadograđuje živi kod, automatski vraća bazu niti destruktivno ponovno inicijalizira podatke.
 */
final readonly class ContainerRuntime
{
    /**
     * EN: The entry point supplies fixed image/runtime roots; tests use isolated fictional roots.
     *
     * HR: Ulazna točka zadaje fiksne image/runtime korijene; testovi koriste izolirane izmišljene korijene.
     */
    public function __construct(private string $appRoot, private string $runtimeRoot)
    {
    }

    /**
     * EN: The short root-only init job creates owned directories and new private files, never overwriting them.
     * Existing directories must already belong to the documented runtime UID; recursive chown is avoided.
     *
     * HR: Kratki root init stvara direktorije i nove privatne datoteke, bez prepisivanja postojećih.
     * Postojeći direktoriji moraju pripadati dokumentiranom runtime UID-u; nema rekurzivnog chowna.
     */
    public function initialize(): void
    {
        if (posix_geteuid() !== 0) {
            throw new RuntimeException('Run the short init job as root, not the web application.');
        }

        $lockPath = $this->target('.container-init.lock');
        $lock = fopen($lockPath, 'c+b');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('Another runtime initializer is running.');
        }

        try {
            $this->secure($lockPath);
            foreach (
                ['config', 'config/theme', 'config/menu', 'data', 'data/sessions',
                'data/cache', 'data/logs', 'data/themes', 'saml'] as $relative
            ) {
                $this->directory($relative);
            }

            $this->create('config/env.php', [
                'salt' => bin2hex(random_bytes(32)), 'encryption_key' => base64_encode(random_bytes(32)),
                'environment' => 'production', 'debug' => false, 'log_level' => 'info', 'trusted_proxies' => [],
            ]);
            $secure = getenv('SIMBIOZA_COOKIE_SECURE') !== '0';
            $this->create('config/installation.php', ['session_options' => ['cookie_secure' => $secure ? 1 : 0]]);
            if (!is_file($this->target('config/database.php'))) {
                $secretFile = getenv('SIMBIOZA_DB_PASSWORD_FILE');
                if (!is_string($secretFile) || !is_readable($secretFile)) {
                    throw new RuntimeException('A readable database secret file is required for initial setup.');
                }

                $password = file_get_contents($secretFile, false, null, 0, 4097);
                if (!is_string($password) || strlen($password) > 4096 || trim($password) === '') {
                    throw new RuntimeException('Invalid database secret file.');
                }

                $this->create('config/database.php', ['connections' => ['default' => [
                    'driver' => 'pgsql', 'host' => getenv('SIMBIOZA_DB_HOST') ?: 'database', 'port' => 5432,
                    'database' => getenv('SIMBIOZA_DB_NAME') ?: 'simbioza',
                    'username' => getenv('SIMBIOZA_DB_USER') ?: 'simbioza',
                    'password' => rtrim($password, "\r\n"), 'charset' => 'UTF8', 'options' => [],
                ]]]);
            }

            $this->publish('data/.container-initialized', "1\n");
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * EN: Resolves only fixed runtime targets and rejects subordinate symlinks, streams and traversal.
     *
     * HR: Razrješava samo fiksne runtime ciljeve i odbija podređene symlinkove, streamove i traversal.
     */
    private function target(string $relative): string
    {
        if (preg_match('~\A[a-z0-9./_-]+\z~D', $relative) !== 1 || str_contains($relative, '..')) {
            throw new RuntimeException('Invalid runtime path.');
        }

        $path = $this->runtimeRoot;
        foreach (explode('/', $relative) as $part) {
            $path .= '/' . $part;
            if (is_link($path)) {
                throw new RuntimeException('Runtime targets must not be symbolic links.');
            }
        }

        return $path;
    }

    /**
     * EN: Creates a runtime-owned directory or validates existing ownership without resetting data.
     *
     * HR: Stvara runtime direktorij ili provjerava postojeće vlasništvo bez resetiranja podataka.
     */
    private function directory(string $relative): void
    {
        $path = $this->target($relative);
        if (!file_exists($path)) {
            if (!mkdir($path, 0770)) {
                throw new RuntimeException('Unable to create a private runtime directory.');
            }

            if (!chown($path, 33) || !chgrp($path, 33) || !chmod($path, 0770)) {
                throw new RuntimeException('Unable to secure a private runtime directory.');
            }
        } elseif (!is_dir($path) || fileowner($path) !== 33 || filegroup($path) !== 33) {
            throw new RuntimeException('Existing runtime ownership differs from UID/GID 33; resolve it explicitly.');
        }
    }

    /**
     * EN: Publishes a new PHP configuration only; future images never overwrite private settings.
     *
     * HR: Objavljuje samo novu PHP konfiguraciju; budući imagei ne prepisuju privatne postavke.
     *
     * @param array<string,mixed> $value
     */
    private function create(string $relative, array $value): void
    {
        $path = $this->target($relative);
        if (file_exists($path)) {
            if (!is_file($path) || !is_readable($path)) {
                throw new RuntimeException('Existing private configuration is invalid or unreadable.');
            }

            return;
        }

        $this->publish($relative, "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($value, true) . ";\n");
    }

    /**
     * EN: Atomically writes a fixed runtime file and retains no staged file after failure.
     *
     * HR: Atomski zapisuje fiksnu runtime datoteku i nakon greške ne ostavlja pripremnu datoteku.
     */
    private function publish(string $relative, string $bytes): void
    {
        $path = $this->target($relative);
        $temporary = tempnam(dirname($path), '.container-');
        if (!is_string($temporary)) {
            throw new RuntimeException('Unable to stage runtime state.');
        }

        try {
            if (file_put_contents($temporary, $bytes) !== strlen($bytes)) {
                throw new RuntimeException('Unable to write runtime state.');
            }

            if (posix_geteuid() === 0) {
                $this->secure($temporary);
            } elseif (!chmod($temporary, 0660)) {
                throw new RuntimeException('Unable to secure runtime state.');
            }

            if (!rename($temporary, $path)) {
                throw new RuntimeException('Unable to publish runtime state.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    /**
     * EN: Assigns only newly created files to the runtime identity.
     *
     * HR: Runtime identitetu dodjeljuje samo novostvorene datoteke.
     */
    private function secure(string $path): void
    {
        if (!chown($path, 33) || !chgrp($path, 33) || !chmod($path, 0660)) {
            throw new RuntimeException('Unable to secure runtime file.');
        }
    }

    /**
     * EN: Reads the immutable marker written by the sanitized build, not private configuration.
     *
     * HR: Čita nepromjenjivu oznaku očišćene gradnje, ne privatnu konfiguraciju.
     *
     * @return array{format:string,code_id:string,modules:list<string>}
     */
    public function image(): array
    {
        $bytes = file_get_contents($this->appRoot . '/resources/deployment/immutable.json');
        $image = is_string($bytes) ? json_decode($bytes, true, 32, JSON_THROW_ON_ERROR) : null;
        if (
            !is_array($image) || ($image['format'] ?? '') !== 'simbioza-image-v1'
            || !is_string($image['code_id'] ?? null) || preg_match('/\A[a-f0-9]{64}\z/D', $image['code_id']) !== 1
            || !is_array($image['modules'] ?? null) || !array_is_list($image['modules'])
        ) {
            throw new RuntimeException('Invalid image marker.');
        }

        $modules = [];
        foreach ($image['modules'] as $module) {
            if (!is_string($module) || preg_match('~\A[a-z0-9_-]+/[a-z0-9_-]+\z~D', $module) !== 1) {
                throw new RuntimeException('Invalid image module composition.');
            }

            $modules[] = $module;
        }

        return ['format' => 'simbioza-image-v1', 'code_id' => $image['code_id'], 'modules' => $modules];
    }

    /**
     * EN: New sites may open the installer; existing sites must explicitly migrate each new code snapshot first.
     *
     * HR: Novi site može otvoriti installer; postojeći mora prvo izričito migrirati svaku novu snimku koda.
     */
    public function assertReady(): void
    {
        if (!is_file($this->target('data/.container-initialized'))) {
            throw new RuntimeException('Run the init job before starting the application.');
        }

        if (is_file($this->target('data/installation.lock'))) {
            $file = $this->target('data/.image-state.json');
            $state = is_file($file) ? json_decode((string)file_get_contents($file), true, 32, JSON_THROW_ON_ERROR) : [];
            if (!is_array($state) || ($state['code_id'] ?? null) !== $this->image()['code_id']) {
                throw new RuntimeException('Run migrate for this image before serving the installed site.');
            }
        }
    }

    /**
     * EN: Serializes explicit migration, runs only baked module migrations, and marks success last.
     * Operators stop web/workers first and back up database/runtime; failures do not pretend to roll back DDL.
     *
     * HR: Serijalizira izričitu migraciju, izvršava samo migracije ugrađenih modula i tek na kraju označava uspjeh.
     * Održavatelj prvo zaustavlja web/workere i sprema bazu/runtime; greška ne glumi rollback DDL-a.
     */
    public function migrate(): int
    {
        if (!is_file($this->target('data/installation.lock'))) {
            throw new RuntimeException('Complete the new-site installer before migrating an existing installation.');
        }

        $lock = fopen($this->target('data/.deployment.lock'), 'c+b');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('Another image migration is running.');
        }

        try {
            // EN: Migrations load configuration without booting services that may require the new schema.
            //
            // HR: Migracije učitavaju konfiguraciju bez boota servisa koji možda zahtijevaju novu shemu.
            $helper = new Helper();
            $config = new Config($helper, [], $this->appRoot);
            $config->loadLayeredDirectories(ConfigPathResolver::fromEnvironment($this->appRoot));
            $database = new Database($config, $helper);
            $migrations = [];
            $catalog = new ModuleCatalog();
            foreach (glob($this->appRoot . '/database/migrations/*.php') ?: [] as $file) {
                $name = basename($file, '.php');
                $owner = $catalog->slugForMigration($name);
                if ($owner !== null && !InstalledVersions::isInstalled($catalog->definitionFor($owner)['package'])) {
                    continue;
                }

                $migration = require $file;
                if (!$migration instanceof MigrationInterface) {
                    throw new RuntimeException('Invalid baked migration.');
                }

                $migrations[$name] = $migration;
            }

            ksort($migrations);
            if (array_diff($database->migrator()->ran(), array_keys($migrations)) !== []) {
                throw new RuntimeException('This image lacks already-applied migrations; downgrade/removal is unsafe.');
            }

            $applied = $database->migrator()->migrate($migrations);
            $this->clearCache();
            $this->publish('data/.image-state.json', json_encode([
                'code_id' => $this->image()['code_id'], 'migrated_at' => gmdate(DATE_ATOM),
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
            return count($applied);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * EN: Clears only regenerable cache while the operator has stopped all readers; never follows links.
     *
     * HR: Čisti samo obnovljivi cache dok su čitači zaustavljeni; nikada ne prati linkove.
     */
    private function clearCache(): void
    {
        $root = $this->target('data/cache');
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $file) {
            if (!$file instanceof \SplFileInfo) {
                continue;
            }

            $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
    }
}
