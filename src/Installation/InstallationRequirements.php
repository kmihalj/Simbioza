<?php

declare(strict_types=1);

namespace App\Installation;

/**
 * EN: Checks the runtime, extensions, and private writable paths before installation.
 *
 * HR: Provjerava runtime, ekstenzije i privatne zapisive putanje prije instalacije.
 */
final readonly class InstallationRequirements
{
    /** @var list<string> */
    private const REQUIRED_EXTENSIONS = [
        'ctype',
        'dom',
        'fileinfo',
        'intl',
        'json',
        'libxml',
        'mbstring',
        'openssl',
        'pdo',
        'session',
        'xmlreader',
        'zip',
    ];

    /**
     * EN: Initializes requirement paths.
     *
     * HR: Inicijalizira putanje provjere.
     */
    public function __construct(private InstallationPaths $paths)
    {
    }

    /**
     * EN: Returns safe results for every requirement check.
     *
     * HR: Vraća sigurne rezultate svih provjera.
     *
     * @return list<array{id:string,label_hr:string,label_en:string,passed:bool,required:bool}>
     */
    public function checks(?string $selectedDriver = null): array
    {
        $checks = [[
            'id' => 'php_version',
            'label_hr' => 'PHP 8.2 ili noviji (otkriven ' . PHP_VERSION . ')',
            'label_en' => 'PHP 8.2 or newer (detected ' . PHP_VERSION . ')',
            'passed' => $this->phpVersionSupported(PHP_VERSION),
            'required' => true,
        ]];

        foreach (self::REQUIRED_EXTENSIONS as $extension) {
            $checks[] = [
                'id' => 'extension_' . $extension,
                'label_hr' => 'PHP ekstenzija: ' . $extension,
                'label_en' => 'PHP extension: ' . $extension,
                'passed' => extension_loaded($extension),
                'required' => true,
            ];
        }

        foreach (['sqlite' => 'pdo_sqlite', 'mysql' => 'pdo_mysql', 'pgsql' => 'pdo_pgsql'] as $driver => $extension) {
            $required = $selectedDriver === $driver;
            $checks[] = [
                'id' => 'database_' . $driver,
                'label_hr' => sprintf('Podrška za %s bazu (%s)', strtoupper($driver), $extension),
                'label_en' => sprintf('%s database support (%s)', strtoupper($driver), $extension),
                'passed' => extension_loaded($extension),
                'required' => $required,
            ];
        }

        $checks[] = $this->directoryCheck(
            'config_writable',
            $this->paths->configDirectory(),
            'Konfiguracijski direktorij je zapisiv',
            'Configuration directory is writable',
            true,
        );
        $checks[] = $this->directoryCheck(
            'data_writable',
            $this->paths->dataDirectory(),
            'Privatni podatkovni direktorij je zapisiv',
            'Private data directory is writable',
            true,
        );
        $configurationReady = true;
        try {
            $configuration = $this->paths->configuration();
            $configuration->assertReadable();
            foreach (
                [
                'app.php', 'bootstrap.php', 'commands.php', 'listeners.php',
                'middleware.php', 'routes.php', 'services.php',
                ] as $file
            ) {
                $configurationReady = $configurationReady && $configuration->effectiveFile($file) !== null;
            }

            foreach (['database.php', 'env.php', 'installation.php', 'modules.php'] as $file) {
                $configuration->writableFile($file);
            }
        } catch (\RuntimeException | \InvalidArgumentException) {
            $configurationReady = false;
        }

        $checks[] = [
            'id' => 'configuration_ready',
            'label_hr' => 'Svi konfiguracijski slojevi su pripremljeni, a instalacijski ciljevi zapisivi',
            'label_en' => 'All configuration layers are prepared and installer targets are writable',
            'passed' => $configurationReady,
            'required' => true,
        ];
        $checks[] = $this->moduleConfigurationCheck(
            'theme_config_writable',
            'theme/themes.json',
            'Spremište konfiguracije teme je zapisivo',
            'Theme configuration storage is writable',
        );
        $checks[] = $this->moduleConfigurationCheck(
            'menu_config_writable',
            'menu/settings.json',
            'Spremište konfiguracije izbornika je zapisivo',
            'Menu configuration storage is writable',
        );
        $checks[] = $this->directoryCheck(
            'migrations_readable',
            $this->paths->migrationsDirectory(),
            'Direktorij migracija je čitljiv',
            'Migration directory is readable',
            false,
        );
        $checks[] = $this->fileCheck(
            'autoload_readable',
            $this->paths->appRoot() . '/vendor/autoload.php',
            'Composer ovisnosti su instalirane',
            'Composer dependencies are installed',
        );
        $checks[] = $this->fileCheck(
            'theme_package_readable',
            $this->paths->themePackage(),
            'Instalacijski paket teme Simbioza je dostupan',
            'Bundled Simbioza theme package is available',
        );
        $checks[] = $this->fileCheck(
            'user_guides_package_readable',
            $this->paths->userGuidesPackage(),
            'Instalacijski paket javnih korisničkih uputa je dostupan',
            'Bundled public user-guide package is available',
        );

        return $checks;
    }

    /**
     * EN: A missing module folder may be created in the highest writable layer; read-only targets fail safely.
     *
     * HR: Nedostajući folder modula može se stvoriti u najvišem zapisivom sloju; read-only cilj sigurno pada.
     *
     * @return array{id:string,label_hr:string,label_en:string,passed:bool,required:bool}
     */
    private function moduleConfigurationCheck(string $id, string $file, string $labelHr, string $labelEn): array
    {
        $passed = true;
        try {
            $this->paths->configuration()->writableFile($file);
        } catch (\RuntimeException | \InvalidArgumentException) {
            $passed = false;
        }

        return ['id' => $id, 'label_hr' => $labelHr, 'label_en' => $labelEn,
            'passed' => $passed, 'required' => true];
    }

    /**
     * EN: Checks whether all required items pass.
     *
     * HR: Provjerava jesu li svi obvezni uvjeti zadovoljeni.
     */
    public function passes(?string $selectedDriver = null): bool
    {
        foreach ($this->checks($selectedDriver) as $check) {
            if ($check['required'] && !$check['passed']) {
                return false;
            }
        }

        return true;
    }

    /**
     * EN: Builds a directory readability or writability check.
     *
     * HR: Gradi provjeru direktorija za čitanje ili zapisivanje.
     *
     * @return array{id:string,label_hr:string,label_en:string,passed:bool,required:bool}
     */
    private function directoryCheck(
        string $id,
        string $directory,
        string $labelHr,
        string $labelEn,
        bool $writable,
    ): array {
        $passed = is_dir($directory) && is_readable($directory);
        if ($writable) {
            $passed = $passed && is_writable($directory);
        }

        return [
            'id' => $id,
            'label_hr' => $labelHr,
            'label_en' => $labelEn,
            'passed' => $passed,
            'required' => true,
        ];
    }

    /**
     * EN: Builds a readable required-file check.
     *
     * HR: Gradi provjeru čitljive obvezne datoteke.
     *
     * @return array{id:string,label_hr:string,label_en:string,passed:bool,required:bool}
     */
    private function fileCheck(string $id, string $file, string $labelHr, string $labelEn): array
    {
        return [
            'id' => $id,
            'label_hr' => $labelHr,
            'label_en' => $labelEn,
            'passed' => is_file($file) && is_readable($file),
            'required' => true,
        ];
    }

    /**
     * EN: Compares the runtime with the documented minimum.
     *
     * HR: Uspoređuje runtime s dokumentiranim minimumom.
     */
    private function phpVersionSupported(string $version): bool
    {
        return version_compare($version, '8.2.0', '>=');
    }
}
