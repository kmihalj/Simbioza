<?php

declare(strict_types=1);

namespace App\Installation;

/**
 * HR: Provjerava runtime, ekstenzije i privatne zapisive putanje prije instalacije.
 * EN: Checks the runtime, extensions, and private writable paths before installation.
 */
final readonly class InstallationRequirements
{
    /** @var list<string> */
    private const REQUIRED_EXTENSIONS = [
        'ctype',
        'dom',
        'fileinfo',
        'json',
        'libxml',
        'mbstring',
        'openssl',
        'pdo',
        'session',
        'xmlreader',
        'zip',
    ];

    /** HR: Inicijalizira putanje provjere. EN: Initializes requirement paths. */
    public function __construct(private InstallationPaths $paths)
    {
    }

    /**
     * HR: Vraća sigurne rezultate svih provjera.
     * EN: Returns safe results for every requirement check.
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
        $checks[] = $this->directoryCheck(
            'theme_config_writable',
            $this->paths->themeConfigDirectory(),
            'Spremište konfiguracije teme je zapisivo',
            'Theme configuration storage is writable',
            true,
        );
        $checks[] = $this->directoryCheck(
            'menu_config_writable',
            $this->paths->menuConfigDirectory(),
            'Spremište konfiguracije izbornika je zapisivo',
            'Menu configuration storage is writable',
            true,
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

    /** HR: Provjerava jesu li svi obvezni uvjeti zadovoljeni. EN: Checks whether all required items pass. */
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
     * HR: Gradi provjeru direktorija za čitanje ili zapisivanje.
     * EN: Builds a directory readability or writability check.
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
     * HR: Gradi provjeru čitljive obvezne datoteke.
     * EN: Builds a readable required-file check.
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

    /** HR: Uspoređuje runtime s dokumentiranim minimumom. EN: Compares the runtime with the documented minimum. */
    private function phpVersionSupported(string $version): bool
    {
        return version_compare($version, '8.2.0', '>=');
    }
}
