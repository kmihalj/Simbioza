<?php

declare(strict_types=1);

namespace App\Setup;

use App\Configuration\ConfigurationPaths;
use App\Deployment\DeploymentPolicy;

/**
 * EN: Checks the runtime and permissions required for safe Setup. It changes
 *     no files except for the helper's short health-check request.
 *
 * HR: Provjerava izvršno okruženje i prava potrebna za siguran Setup. Provjera
 *     ne mijenja datoteke osim kratkog health-check zahtjeva helperu.
 */
final readonly class SetupDiagnostics
{
    /**
     * EN: Receives the application root and privileged gateway.
     *
     * HR: Prima korijen aplikacije i privilegirani gateway.
     */
    public function __construct(
        private string $appRoot,
        private SetupGateway $gateway,
    ) {
    }

    /**
     * EN: Returns individual checks and the final GUI package-change decision.
     *
     * HR: Vraća pojedinačne provjere i završnu odluku za GUI promjene paketa.
     *
     * @return array{
     *     is_fpm:bool,
     *     immutable:bool,
     *     helper_ready:bool,
     *     state_changes_allowed:bool,
     *     language_changes_allowed:bool,
     *     package_changes_allowed:bool,
     *     application_updates_allowed:bool,
     *     checks:list<array{
     *         id:string,
     *         label_hr:string,
     *         label_en:string,
     *         passed:bool,
     *         required:bool,
     *         checked:bool,
     *         detail:string
     *     }>
     * }
     */
    public function report(): array
    {
        $immutable = (new DeploymentPolicy($this->appRoot))->isImmutable();
        $isFpm = PHP_SAPI === 'fpm-fcgi';
        $dedicatedPool = trim((string)getenv('SIMBIOZA_SETUP_POOL')) === '1';
        $helperAvailable = $this->gateway->isAvailable();
        $helperChecked = !$immutable && $isFpm && $dedicatedPool && $helperAvailable;
        $helper = $helperChecked
        ? $this->gateway->probeResult()
        : ['ready' => false, 'detail' => $helperAvailable ? 'Helper nije provjeren u ovom okruženju.' : 'missing'];
        $helperReady = $helper['ready'];
        $applicationUpdateRuntime = false;
        $runtimeDetail = 'CLI provjera nije provedena jer Setup helper nije dostupan.';
        if ($helperReady) {
            try {
                $applicationUpdateRuntime = $this->gateway->execute('application-update-runtime-check') !== '';
                $runtimeDetail = $applicationUpdateRuntime ? 'pcntl + posix' : 'CLI runtime check returned no result.';
            } catch (\Throwable $throwable) {
                $applicationUpdateRuntime = false;
                $runtimeDetail = substr(str_replace(["\r", "\n", "\0"], ' ', $throwable->getMessage()), 0, 1000);
            }
        }

        $checks = [
            $this->check(
                'runtime_fpm',
                'Zahtjev obrađuje zasebni PHP-FPM pool',
                'Request is handled by a dedicated PHP-FPM pool',
                $isFpm,
                true,
                PHP_SAPI,
            ),
            $this->check(
                'dedicated_pool',
                'Aktivan je namjenski Simbioza FPM pool',
                'The dedicated Simbioza FPM pool is active',
                $dedicatedPool,
                true,
                $dedicatedPool ? 'SIMBIOZA_SETUP_POOL=1' : 'SIMBIOZA_SETUP_POOL is missing',
            ),
            $this->check(
                'helper_ready',
                'Ograničeni Setup helper prolazi stvarnu provjeru',
                'Restricted Setup helper passes a real probe',
                $helperReady,
                true,
                $helper['detail'],
                $helperChecked,
            ),
            $this->check(
                'application_update_runtime',
                'CLI podržava sigurno pozadinsko ažuriranje',
                'CLI supports safe background updates',
                $applicationUpdateRuntime,
                false,
                $runtimeDetail,
                $helperReady,
            ),
            $this->pathCheck(
                'code_readable',
                'Kod aplikacije je čitljiv',
                'Application code is readable',
                $this->appRoot,
                true,
                false,
            ),
            $this->pathCheck(
                'code_not_writable',
                'Web proces ne može mijenjati cijeli kod',
                'Web process cannot modify the whole code tree',
                $this->appRoot,
                false,
                true,
            ),
            $this->pathCheck(
                'data_writable',
                'Privatni data direktorij je zapisiv',
                'Private data directory is writable',
                $this->appRoot . '/data',
                true,
                true,
            ),
            $this->pathCheck(
                'cache_writable',
                'Cache direktorij je zapisiv',
                'Cache directory is writable',
                $this->appRoot . '/data/cache',
                true,
                true,
                true,
            ),
            $this->pathCheck(
                'logs_writable',
                'Direktorij dnevnika je zapisiv',
                'Log directory is writable',
                $this->appRoot . '/data/logs',
                true,
                true,
                true,
            ),
            $this->pathCheck(
                'setup_requests_writable',
                'Direktorij Setup zahtjeva je zapisiv',
                'Setup request directory is writable',
                $this->gateway->requestDirectory(),
                true,
                true,
                true,
            ),
            $this->configurationDirectoryCheck(
                'config_writable',
                'Direktorij runtime postavki je zapisiv',
                'Runtime configuration directory is writable',
            ),
            $this->configurationDirectoryCheck(
                'config_sticky',
                'Runtime postavke zaštićene su sticky pravilom',
                'Runtime settings are protected by the sticky rule',
                true,
            ),
            $this->moduleStateCheck(),
            $this->configurationFileCheck(
                'editor_config_writable',
                'Postavke HTML editora su zapisive',
                'HTML Editor configuration is writable',
                'editor-html/editor-html.json',
                true,
                'editor-html/editor-html.json.lock',
            ),
            $this->configurationFileCheck(
                'workspace_config_writable',
                'Postavke područja su zapisive',
                'Workspace configuration is writable',
                'workspace/workspace.json',
                true,
                'workspace/workspace.json.lock',
            ),
            $this->configurationFileCheck(
                'language_config_writable',
                'Registar jezika i lock su zapisivi',
                'Language registry and lock are writable',
                'languages.php',
                true,
                '.languages.lock',
            ),
            $this->configurationFileCheck(
                'installation_config_writable',
                'Instalacijske postavke jezika su zapisive',
                'Installation language settings are writable',
                'installation.php',
                true,
            ),
            $this->configurationFileCheck(
                'language_registry_readable',
                'Efektivni registar jezika je čitljiv',
                'Effective language registry is readable',
                'languages.php',
                false,
            ),
            $this->configurationFileCheck(
                'language_lock_writable',
                'Lock jezičnih postavki je zapisiv',
                'Language-settings lock is writable',
                '.languages.lock',
                true,
            ),
            $this->languageDataCheck(),
            $this->configurationFileCheck(
                'menu_config_writable',
                'Postavke izbornika su zapisive',
                'Menu configuration is writable',
                'menu/settings.json',
                true,
            ),
            $this->configurationFileCheck(
                'theme_config_writable',
                'Postavke teme su zapisive',
                'Theme configuration is writable',
                'theme/themes.json',
                true,
            ),
            $this->configurationFileCheck(
                'database_config_readable',
                'Postavke baze su čitljive',
                'Database configuration is readable',
                'database.php',
                false,
            ),
            $this->configurationFileCheck(
                'environment_config_readable',
                'Postavke okruženja su čitljive',
                'Environment configuration is readable',
                'env.php',
                false,
            ),
        ];

        $permissionsReady = true;
        $stateChangesAllowed = false;
        $languageChangesAllowed = true;
        foreach ($checks as $check) {
            if ($check['id'] === 'module_state_writable') {
                $stateChangesAllowed = $check['passed'];
            }

            if (
                in_array($check['id'], [
                    'installation_config_writable', 'language_config_writable', 'language_registry_readable',
                    'language_lock_writable', 'language_data_writable',
                ], true)
                && !$check['passed']
            ) {
                $languageChangesAllowed = false;
            }

            if ($check['required'] && !$check['passed']) {
                $permissionsReady = false;
            }
        }

        return [
            'immutable' => $immutable,
            'is_fpm' => $isFpm,
            'helper_ready' => $helperReady,
            'state_changes_allowed' => $stateChangesAllowed,
            'language_changes_allowed' => $languageChangesAllowed,
            'package_changes_allowed' => !$immutable && $isFpm && $dedicatedPool && $helperReady && $permissionsReady,
            'application_updates_allowed' => !$immutable && $isFpm
                && $dedicatedPool
                && $helperReady
                && $permissionsReady
                && $applicationUpdateRuntime,
            'checks' => $checks,
        ];
    }

    /**
     * EN: The GUI switch checks the same final target and lock as the module store.
     *
     * HR: GUI prekidač provjerava isti posljednji cilj i lock kao spremište modula.
     *
     * @return array{id:string,label_hr:string,label_en:string,passed:bool,required:bool,detail:string,checked:bool}
     */
    private function moduleStateCheck(): array
    {
        try {
            $paths = ConfigurationPaths::fromEnvironment($this->appRoot);
            $path = $paths->writableFile('modules.php');
            $paths->writableFile('.modules.lock');

            return $this->pathCheck(
                'module_state_writable',
                'Stanje modula je zapisivo',
                'Module state is writable',
                $path,
                true,
                true,
                true,
            );
        } catch (\RuntimeException | \InvalidArgumentException $exception) {
            return $this->check(
                'module_state_writable',
                'Stanje modula je zapisivo',
                'Module state is writable',
                false,
                true,
                $exception->getMessage(),
            );
        }
    }

    /**
     * EN: Runtime languages depend on writable data, not writable application/vendor code.
     * This diagnostic never creates a directory or lock.
     *
     * HR: Runtime jezici trebaju zapisive podatke, ne zapisivi aplikacijski/vendor kod.
     * Ova dijagnostika nikada ne stvara direktorij ili lock.
     *
     * @return array{id:string,label_hr:string,label_en:string,passed:bool,required:bool,detail:string,checked:bool}
     */
    private function languageDataCheck(): array
    {
        try {
            $storage = new \App\Localization\RuntimeLanguageStorage($this->appRoot);
            foreach (['catalogs/en.json', 'flags/en.svg', 'repository-versions.json', '.packs.lock'] as $relative) {
                $storage->assertWritable($relative);
            }

            return $this->check(
                'language_data_writable',
                'Jezični podaci su zapisivi',
                'Language data is writable',
                true,
                true,
                dirname($storage->path('repository-versions.json')),
            );
        } catch (\RuntimeException $runtimeException) {
            return $this->check(
                'language_data_writable',
                'Jezični podaci su zapisivi',
                'Language data is writable',
                false,
                true,
                $runtimeException->getMessage(),
            );
        }
    }

    /**
     * EN: Reports the final configuration directory without creating it or exposing values.
     *
     * HR: Prikazuje zadnji config direktorij bez stvaranja ili otkrivanja vrijednosti.
     *
     * @return array{id:string,label_hr:string,label_en:string,passed:bool,required:bool,detail:string,checked:bool}
     */
    private function configurationDirectoryCheck(
        string $id,
        string $labelHr,
        string $labelEn,
        bool $sticky = false,
    ): array {
        try {
            $paths = ConfigurationPaths::fromEnvironment($this->appRoot);
            $paths->assertReadable();
            $directories = $paths->directories();
            $path = $directories[count($directories) - 1];

            return $sticky
            ? $this->modeCheck($id, $labelHr, $labelEn, $path, 01000)
            : $this->pathCheck($id, $labelHr, $labelEn, $path, true, true);
        } catch (\RuntimeException | \InvalidArgumentException $exception) {
            return $this->check($id, $labelHr, $labelEn, false, true, $exception->getMessage());
        }
    }

    /**
     * EN: Reads use the effective layer; writes and locks use only the final layer.
     *
     * HR: Čitljivost uzima iz efektivnog sloja, zapis i lock samo iz zadnjeg.
     *
     * @return array{id:string,label_hr:string,label_en:string,passed:bool,required:bool,detail:string,checked:bool}
     */
    private function configurationFileCheck(
        string $id,
        string $labelHr,
        string $labelEn,
        string $relativeFile,
        bool $write,
        ?string $lock = null,
    ): array {
        try {
            $paths = ConfigurationPaths::fromEnvironment($this->appRoot);
            $path = $write ? $paths->writableFile($relativeFile) : $paths->effectiveFile($relativeFile);
            if ($lock !== null) {
                $paths->writableFile($lock);
            }

            if ($path === null) {
                return $this->check(
                    $id,
                    $labelHr,
                    $labelEn,
                    false,
                    true,
                    'Configuration file is missing: ' . implode(', ', $paths->candidateFiles($relativeFile)),
                );
            }

            return $this->pathCheck($id, $labelHr, $labelEn, $path, true, $write, $write);
        } catch (\RuntimeException | \InvalidArgumentException $exception) {
            return $this->check($id, $labelHr, $labelEn, false, true, $exception->getMessage());
        }
    }

    /**
     * EN: Checks one path and reports owner, group, and octal mode.
     *
     * HR: Provjerava jednu putanju i prikazuje vlasnika, grupu i oktalna prava.
     *
     * @return array{id:string,label_hr:string,label_en:string,passed:bool,required:bool,detail:string,checked:bool}
     */
    private function pathCheck(
        string $id,
        string $labelHr,
        string $labelEn,
        string $path,
        bool $expected,
        bool $write,
        bool $parentWhenMissing = false,
    ): array {
        $candidate = $path;
        if ($parentWhenMissing && !file_exists($candidate)) {
            $candidate = dirname($candidate);
            // EN: A module may create its subdirectory within the validated configuration layer.
            //
            // HR: Modul smije tek stvoriti svoj poddirektorij unutar provjerenog sloja.
            while (!file_exists($candidate) && !is_link($candidate) && dirname($candidate) !== $candidate) {
                $candidate = dirname($candidate);
            }
        }

        $exists = file_exists($candidate);
        $actual = $exists && ($write ? is_writable($candidate) : is_readable($candidate));
        $passed = $actual === $expected;
        $owner = $exists ? fileowner($candidate) : false;
        $group = $exists ? filegroup($candidate) : false;
        $mode = $exists ? fileperms($candidate) : false;
        $ownerName = is_int($owner) && function_exists('posix_getpwuid')
        ? (posix_getpwuid($owner)['name'] ?? (string)$owner)
        : (is_int($owner) ? (string)$owner : '?');
        $groupName = is_int($group) && function_exists('posix_getgrgid')
        ? (posix_getgrgid($group)['name'] ?? (string)$group)
        : (is_int($group) ? (string)$group : '?');
        $detail = $candidate . ' · ' . $ownerName . ':' . $groupName
        . ' · ' . (is_int($mode) ? substr(sprintf('%o', $mode), -4) : '????');

        return $this->check($id, $labelHr, $labelEn, $passed, true, $detail);
    }

    /**
     * EN: Checks a required Unix mode flag without changing the path.
     *
     * HR: Provjerava obveznu Unix mode zastavicu bez promjene putanje.
     *
     * @return array{id:string,label_hr:string,label_en:string,passed:bool,required:bool,detail:string,checked:bool}
     */
    private function modeCheck(
        string $id,
        string $labelHr,
        string $labelEn,
        string $path,
        int $requiredMode,
    ): array {
        $mode = file_exists($path) ? fileperms($path) : false;
        $passed = is_int($mode) && ($mode & $requiredMode) === $requiredMode;
        $detail = $path . ' · ' . (is_int($mode) ? substr(sprintf('%o', $mode), -4) : '????');

        return $this->check($id, $labelHr, $labelEn, $passed, true, $detail);
    }

    /**
     * EN: Builds a stable record for one diagnostic check.
     *
     * HR: Gradi stabilan zapis jedne dijagnostičke provjere.
     *
     * @return array{id:string,label_hr:string,label_en:string,passed:bool,required:bool,detail:string,checked:bool}
     */
    private function check(
        string $id,
        string $labelHr,
        string $labelEn,
        bool $passed,
        bool $required,
        string $detail,
        bool $checked = true,
    ): array {
        return [
            'id' => $id,
            'label_hr' => $labelHr,
            'label_en' => $labelEn,
            'passed' => $passed,
            'required' => $required,
            'detail' => $detail,
            'checked' => $checked,
        ];
    }
}
