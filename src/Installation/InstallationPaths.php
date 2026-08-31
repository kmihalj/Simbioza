<?php

declare(strict_types=1);

namespace App\Installation;

/**
 * HR: Određuje sve putanje u vlasništvu instalacijskog čarobnjaka.
 * Osjetljive i promjenjive datoteke uvijek ostaju izvan javnog direktorija.
 *
 * EN: Resolves every path owned by the installation wizard. Sensitive and
 * mutable files always remain outside the public directory.
 */
final readonly class InstallationPaths
{
    private string $appRoot;

    /**
     * HR: Sprema normalizirani korijenski direktorij aplikacije.
     *
     * EN: Stores the normalized application root.
     */
    public function __construct(string $appRoot)
    {
        $normalizedRoot = rtrim($appRoot, DIRECTORY_SEPARATOR);
        if ($normalizedRoot === '' || !is_dir($normalizedRoot)) {
            throw new \InvalidArgumentException('The application root directory does not exist.');
        }

        $this->appRoot = $normalizedRoot;
    }

    /**
     * HR: Vraća neprazan korijen aplikacije.
     * EN: Returns the non-empty application root.
     * @return non-empty-string
     */
    public function appRoot(): string
    {
        return $this->appRoot;
    }

    /**
     * HR: Vraća neprazan konfiguracijski direktorij.
     * EN: Returns the non-empty configuration directory.
     * @return non-empty-string
     */
    public function configDirectory(): string
    {
        return $this->appRoot . DIRECTORY_SEPARATOR . 'config';
    }

    /** HR: Vraća privatni podatkovni direktorij. EN: Returns the private data directory. */
    public function dataDirectory(): string
    {
        return $this->appRoot . DIRECTORY_SEPARATOR . 'data';
    }

    /** HR: Vraća putanju konfiguracije baze. EN: Returns the database configuration path. */
    public function databaseConfig(): string
    {
        return $this->configDirectory() . DIRECTORY_SEPARATOR . 'database.php';
    }

    /** HR: Vraća putanju konfiguracije okruženja. EN: Returns the environment configuration path. */
    public function environmentConfig(): string
    {
        return $this->configDirectory() . DIRECTORY_SEPARATOR . 'env.php';
    }

    /** HR: Vraća putanju instalacijskih postavki. EN: Returns the installation settings path. */
    public function installationConfig(): string
    {
        return $this->configDirectory() . DIRECTORY_SEPARATOR . 'installation.php';
    }

    /** HR: Vraća putanju trajnog instalacijskog locka. EN: Returns the permanent installation lock path. */
    public function lockFile(): string
    {
        return $this->dataDirectory() . DIRECTORY_SEPARATOR . 'installation.lock';
    }

    /** HR: Vraća putanju sažetka jednokratnog tokena. EN: Returns the one-time token hash path. */
    public function tokenFile(): string
    {
        return $this->dataDirectory() . DIRECTORY_SEPARATOR . '.installer-token';
    }

    /** HR: Vraća privatni instalacijski log. EN: Returns the private installation log. */
    public function logFile(): string
    {
        return $this->dataDirectory() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'installer.log';
    }

    /** HR: Vraća direktorij migracija. EN: Returns the application migration directory. */
    public function migrationsDirectory(): string
    {
        return $this->appRoot . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
    }

    /** HR: Vraća instalacijski paket teme Simbioza. EN: Returns the bundled Simbioza theme archive. */
    public function themePackage(): string
    {
        return $this->appRoot
        . DIRECTORY_SEPARATOR . 'resources'
        . DIRECTORY_SEPARATOR . 'installation'
        . DIRECTORY_SEPARATOR . 'theme'
        . DIRECTORY_SEPARATOR . 'simbioza.zip';
    }

    /** HR: Vraća instalacijski backup javnih korisničkih uputa. EN: Returns the bundled public user-guide backup. */
    public function userGuidesPackage(): string
    {
        return $this->appRoot
        . DIRECTORY_SEPARATOR . 'resources'
        . DIRECTORY_SEPARATOR . 'installation'
        . DIRECTORY_SEPARATOR . 'workspace'
        . DIRECTORY_SEPARATOR . 'korisnicke-upute.zip';
    }

    /** HR: Vraća privremeni config sloj za instalacijski import. EN: Returns the temporary install-import config layer. */
    public function importConfigDirectory(): string
    {
        return $this->dataDirectory() . DIRECTORY_SEPARATOR . '.installation-import-config';
    }

    /** HR: Vraća JSON spremište tema. EN: Returns the theme JSON storage directory. */
    public function themeConfigDirectory(): string
    {
        return $this->appRoot
        . DIRECTORY_SEPARATOR . 'resources'
        . DIRECTORY_SEPARATOR . 'config'
        . DIRECTORY_SEPARATOR . 'theme';
    }

    /** HR: Provjerava je li instalacija dovršena. EN: Reports whether installation is complete. */
    public function isInstalled(): bool
    {
        return is_file($this->lockFile());
    }
}
