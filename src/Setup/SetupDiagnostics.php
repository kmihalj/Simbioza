<?php

declare(strict_types=1);

namespace App\Setup;

/**
 * HR: Provjerava izvršno okruženje i prava potrebna za siguran Setup. Provjera
 *     ne mijenja datoteke osim kratkog health-check zahtjeva helperu.
 * EN: Checks the runtime and permissions required for safe Setup. It changes
 *     no files except for the helper's short health-check request.
 */
final readonly class SetupDiagnostics
{
    /**
     * HR: Prima korijen aplikacije i privilegirani gateway.
     * EN: Receives the application root and privileged gateway.
     */
    public function __construct(
        private string $appRoot,
        private SetupGateway $gateway,
    ) {
    }

    /**
     * HR: Vraća pojedinačne provjere i završnu odluku za GUI promjene paketa.
     * EN: Returns individual checks and the final GUI package-change decision.
     *
     * @return array{
     *     is_fpm:bool,
     *     helper_ready:bool,
     *     state_changes_allowed:bool,
     *     package_changes_allowed:bool,
     *     application_updates_allowed:bool,
     *     checks:list<array{
     *         id:string,
     *         label_hr:string,
     *         label_en:string,
     *         passed:bool,
     *         required:bool,
     *         detail:string
     *     }>
     * }
     */
    public function report(): array
    {
        $isFpm = PHP_SAPI === 'fpm-fcgi';
        $dedicatedPool = trim((string)getenv('SIMBIOZA_SETUP_POOL')) === '1';
        $helperAvailable = $this->gateway->isAvailable();
        $helperReady = $isFpm && $dedicatedPool && $helperAvailable && $this->gateway->probe();
        $applicationUpdateRuntime = false;
        if ($helperReady) {
            try {
                $applicationUpdateRuntime = $this->gateway->execute('application-update-runtime-check') !== '';
            } catch (\Throwable) {
                $applicationUpdateRuntime = false;
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
                $helperAvailable ? 'configured and probed' : 'missing',
            ),
            $this->check(
                'application_update_runtime',
                'CLI podržava sigurno pozadinsko ažuriranje',
                'CLI supports safe background updates',
                $applicationUpdateRuntime,
                false,
                $applicationUpdateRuntime ? 'pcntl + posix' : 'pcntl/posix unavailable',
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
                $this->appRoot . '/data/setup-requests',
                true,
                true,
                true,
            ),
            $this->pathCheck(
                'config_writable',
                'Direktorij runtime postavki je zapisiv',
                'Runtime configuration directory is writable',
                $this->appRoot . '/config',
                true,
                true,
            ),
            $this->modeCheck(
                'config_sticky',
                'Runtime postavke zaštićene su sticky pravilom',
                'Runtime settings are protected by the sticky rule',
                $this->appRoot . '/config',
                01000,
            ),
            $this->pathCheck(
                'module_state_writable',
                'Stanje modula je zapisivo',
                'Module state is writable',
                $this->appRoot . '/data/config/modules.php',
                true,
                true,
                true,
            ),
            $this->pathCheck(
                'editor_config_writable',
                'Postavke HTML editora su zapisive',
                'HTML Editor configuration is writable',
                $this->appRoot . '/config/editor-html.php',
                true,
                true,
                true,
            ),
            $this->pathCheck(
                'menu_config_writable',
                'Postavke izbornika su zapisive',
                'Menu configuration is writable',
                $this->appRoot . '/resources/config/menu',
                true,
                true,
            ),
            $this->pathCheck(
                'theme_config_writable',
                'Postavke teme su zapisive',
                'Theme configuration is writable',
                $this->appRoot . '/resources/config/theme',
                true,
                true,
            ),
            $this->pathCheck(
                'database_config_readable',
                'Postavke baze su čitljive',
                'Database configuration is readable',
                $this->appRoot . '/config/database.php',
                true,
                false,
            ),
            $this->pathCheck(
                'environment_config_readable',
                'Postavke okruženja su čitljive',
                'Environment configuration is readable',
                $this->appRoot . '/config/env.php',
                true,
                false,
            ),
        ];

        $permissionsReady = true;
        $stateChangesAllowed = false;
        foreach ($checks as $check) {
            if ($check['id'] === 'module_state_writable') {
                $stateChangesAllowed = $check['passed'];
            }

            if ($check['required'] && !$check['passed']) {
                $permissionsReady = false;
            }
        }

        return [
            'is_fpm' => $isFpm,
            'helper_ready' => $helperReady,
            'state_changes_allowed' => $stateChangesAllowed,
            'package_changes_allowed' => $isFpm && $dedicatedPool && $helperReady && $permissionsReady,
            'application_updates_allowed' => $isFpm
                && $dedicatedPool
                && $helperReady
                && $permissionsReady
                && $applicationUpdateRuntime,
            'checks' => $checks,
        ];
    }

    /**
     * HR: Provjerava jednu putanju i prikazuje vlasnika, grupu i oktalna prava.
     * EN: Checks one path and reports owner, group, and octal mode.
     * @return array{id:string,label_hr:string,label_en:string,passed:bool,required:bool,detail:string}
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
     * HR: Provjerava obveznu Unix mode zastavicu bez promjene putanje.
     * EN: Checks a required Unix mode flag without changing the path.
     * @return array{id:string,label_hr:string,label_en:string,passed:bool,required:bool,detail:string}
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
     * HR: Gradi stabilan zapis jedne dijagnostičke provjere.
     * EN: Builds a stable record for one diagnostic check.
     *
     * @return array{id:string,label_hr:string,label_en:string,passed:bool,required:bool,detail:string}
     */
    private function check(
        string $id,
        string $labelHr,
        string $labelEn,
        bool $passed,
        bool $required,
        string $detail,
    ): array {
        return [
            'id' => $id,
            'label_hr' => $labelHr,
            'label_en' => $labelEn,
            'passed' => $passed,
            'required' => $required,
            'detail' => $detail,
        ];
    }
}
