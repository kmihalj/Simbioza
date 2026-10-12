<?php

declare(strict_types=1);

namespace App\Installation;

use App\Configuration\ConfigurationPaths;

/**
 * EN: Resolves every path owned by the installation wizard. Sensitive and
 * mutable files always remain outside the public directory.
 *
 * HR: Određuje sve putanje u vlasništvu instalacijskog čarobnjaka.
 * Osjetljive i promjenjive datoteke uvijek ostaju izvan javnog direktorija.
 */
final readonly class InstallationPaths
{
    private string $appRoot;

    private ConfigurationPaths $configuration;

    /**
     * EN: Stores the normalized application root.
     *
     * HR: Sprema normalizirani korijenski direktorij aplikacije.
     */
    public function __construct(string $appRoot)
    {
        $normalizedRoot = rtrim($appRoot, DIRECTORY_SEPARATOR);
        if ($normalizedRoot === '' || !is_dir($normalizedRoot)) {
            throw new \InvalidArgumentException('The application root directory does not exist.');
        }

        $this->appRoot = $normalizedRoot;
        $this->configuration = ConfigurationPaths::fromEnvironment($normalizedRoot);
    }

    /**
     * EN: Returns the non-empty application root.
     *
     * HR: Vraća neprazan korijen aplikacije.
     *
     * @return non-empty-string
     */
    public function appRoot(): string
    {
        return $this->appRoot;
    }

    /**
     * EN: Returns the last declared configuration layer for installer writes.
     *
     * HR: Vraća zadnji deklarirani konfiguracijski sloj za instalacijski zapis.
     *
     * @return non-empty-string
     */
    public function configDirectory(): string
    {
        $directories = $this->configuration->directories();

        return $directories[count($directories) - 1];
    }

    /**
     * EN: Returns the shared path contract.
     *
     * HR: Vraća zajednički dogovor o putanjama.
     */
    public function configuration(): ConfigurationPaths
    {
        return $this->configuration;
    }

    /**
     * EN: Returns the private data directory.
     *
     * HR: Vraća privatni podatkovni direktorij.
     */
    public function dataDirectory(): string
    {
        return $this->appRoot . DIRECTORY_SEPARATOR . 'data';
    }

    /**
     * EN: Returns the database configuration path.
     *
     * HR: Vraća putanju konfiguracije baze.
     */
    public function databaseConfig(): string
    {
        return $this->configuration->writableFile('database.php');
    }

    /**
     * EN: Returns the environment configuration path.
     *
     * HR: Vraća putanju konfiguracije okruženja.
     */
    public function environmentConfig(): string
    {
        return $this->configuration->writableFile('env.php');
    }

    /**
     * EN: Returns the installation settings path.
     *
     * HR: Vraća putanju instalacijskih postavki.
     */
    public function installationConfig(): string
    {
        return $this->configuration->writableFile('installation.php');
    }

    /**
     * EN: Returns GUI- and CLI-writable module configuration.
     *
     * HR: Vraća konfiguracijski odabir modula zapisiv iz GUI-ja i CLI-ja.
     */
    public function moduleStateConfig(): string
    {
        return $this->configuration->writableFile('modules.php');
    }

    /**
     * EN: Returns the permanent installation lock path.
     *
     * HR: Vraća putanju trajnog instalacijskog locka.
     */
    public function lockFile(): string
    {
        return $this->dataDirectory() . DIRECTORY_SEPARATOR . 'installation.lock';
    }

    /**
     * EN: Returns the one-time token hash path.
     *
     * HR: Vraća putanju sažetka jednokratnog tokena.
     */
    public function tokenFile(): string
    {
        return $this->dataDirectory() . DIRECTORY_SEPARATOR . '.installer-token';
    }

    /**
     * EN: Returns the private installation log.
     *
     * HR: Vraća privatni instalacijski log.
     */
    public function logFile(): string
    {
        return $this->dataDirectory() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'installer.log';
    }

    /**
     * EN: Returns the application migration directory.
     *
     * HR: Vraća direktorij migracija.
     */
    public function migrationsDirectory(): string
    {
        return $this->appRoot . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'migrations';
    }

    /**
     * EN: Returns the bundled Simbioza theme archive.
     *
     * HR: Vraća instalacijski paket teme Simbioza.
     */
    public function themePackage(): string
    {
        return $this->appRoot
        . DIRECTORY_SEPARATOR . 'resources'
        . DIRECTORY_SEPARATOR . 'installation'
        . DIRECTORY_SEPARATOR . 'theme'
        . DIRECTORY_SEPARATOR . 'simbioza.zip';
    }

    /**
     * EN: Returns the bundled public user-guide backup.
     *
     * HR: Vraća instalacijski backup javnih korisničkih uputa.
     */
    public function userGuidesPackage(): string
    {
        return $this->appRoot
        . DIRECTORY_SEPARATOR . 'resources'
        . DIRECTORY_SEPARATOR . 'installation'
        . DIRECTORY_SEPARATOR . 'workspace'
        . DIRECTORY_SEPARATOR . 'korisnicke-upute.zip';
    }

    /**
     * EN: Returns the temporary install-import config layer.
     *
     * HR: Vraća privremeni config sloj za instalacijski import.
     */
    public function importConfigDirectory(): string
    {
        return $this->dataDirectory() . DIRECTORY_SEPARATOR . '.installation-import-config';
    }

    /**
     * EN: Returns the theme JSON storage directory.
     *
     * HR: Vraća JSON spremište tema.
     */
    public function themeConfigDirectory(): string
    {
        return $this->configDirectory() . '/theme';
    }

    /**
     * EN: Returns the menu configuration JSON storage directory.
     *
     * HR: Vraća JSON spremište konfiguracije izbornika.
     */
    public function menuConfigDirectory(): string
    {
        return $this->configDirectory() . '/menu';
    }

    /**
     * EN: Reports whether installation is complete.
     *
     * HR: Provjerava je li instalacija dovršena.
     */
    public function isInstalled(): bool
    {
        return is_file($this->lockFile());
    }
}
