<?php

declare(strict_types=1);

// phpcs:disable Generic.Files.LineLength.TooLong -- Mixed PHP/HTML is clearer without artificial tag wrapping.

/**
 * @var \HeartPhrame\View\View $this
 * @var string $title
 * @var list<array{slug:string,package:string,label_hr:string,label_en:string,optional:bool,recommended:bool,package_installed:bool,enabled:bool,schema_installed:bool,backup_available:bool,state:string}> $modules
 * @var array<string,array{package:string,label_hr:string,label_en:string,optional:bool,recommended:bool,dependencies:list<string>,migrations:list<string>,tables:list<string>}> $definitions
 * @var array{is_fpm:bool,helper_ready:bool,state_changes_allowed:bool,package_changes_allowed:bool,application_updates_allowed:bool,checks:list<array{id:string,label_hr:string,label_en:string,passed:bool,required:bool,detail:string}>} $diagnostics
 * @var list<array{locale:string,native_name:string,keys:int,reference_keys:int,coverage:float,active:bool}> $languages
 * @var list<array{locale:string,native_name:string,version:string,installed:bool,active:bool,update_available:bool}> $repositoryLanguages
 * @var array{components:list<array{package:string,name:string,kind:string,installed_version:string,latest_version:?string,status:string}>,checked_at:?int,failures:int,updates:int} $componentUpdates
 * @var array{state:string,stage:string,progress:?int,started_at:?string,finished_at:?string,pid:?int,message:string,current_version:string} $applicationUpdate
 * @var string $applicationUpdateStatusPath
 * @var string $automaticUpdateCheckPath
 * @var string $actionPath
 * @var string $settingsMenuActiveSection
 * @var object|null $menuRenderer
 */

$localeValue = isset($locale) && is_string($locale) ? strtolower($locale) : 'hr';
$statusLabels = [
    'enabled' => __('Uključen'),
    'disabled' => __('Isključen'),
    'removed' => __('Uklonjen, kopija podataka postoji'),
    'not-installed' => __('Nije instaliran'),
];
$updateStatusLabels = [
    'idle' => __('Nadogradnja još nije pokrenuta'),
    'queued' => __('Nadogradnja čeka pokretanje'),
    'running' => __('Nadogradnja je u tijeku'),
    'success' => __('Posljednja nadogradnja je uspjela'),
    'failed' => __('Posljednja nadogradnja nije uspjela'),
];
$updateRunning = in_array($applicationUpdate['state'], ['queued', 'running'], true);
$componentsByPackage = [];
$applicationComponent = null;
foreach ($componentUpdates['components'] as $component) {
    $package = $component['package'] ?? '';
    if (is_string($package) && $package !== '') {
        $componentsByPackage[$package] = $component;
    }
    if (($component['kind'] ?? null) === 'application') {
        $applicationComponent = $component;
    }
}
$applicationLatestVersion = is_array($applicationComponent)
&& ($applicationComponent['status'] ?? null) === 'update_available'
&& is_string($applicationComponent['latest_version'] ?? null)
? $applicationComponent['latest_version']
: null;
$formatTimestamp = static function (?string $value) use ($localeValue): string {
    if ($value === null || $value === '') {
        return '—';
    }
    try {
        $date = (new DateTimeImmutable($value))->setTimezone(new DateTimeZone(date_default_timezone_get()));
        if (class_exists(IntlDateFormatter::class)) {
            $formatter = new IntlDateFormatter(
                str_replace('-', '_', $localeValue),
                IntlDateFormatter::MEDIUM,
                IntlDateFormatter::SHORT,
                $date->getTimezone(),
            );
            $formatted = $formatter->format($date);
            if (is_string($formatted)) {
                return $formatted;
            }
        }
        return $date->format('Y-m-d H:i:s');
    } catch (Throwable) {
        return $value;
    }
};
$settingsMenu = '';
if (isset($menuRenderer) && is_object($menuRenderer) && is_callable([$menuRenderer, 'renderSettingsMenu'])) {
    $renderedSettingsMenu = $menuRenderer->renderSettingsMenu($settingsMenuActiveSection);
    $settingsMenu = is_string($renderedSettingsMenu) ? $renderedSettingsMenu : '';
}
$repositoryLanguagesByLocale = [];
$availableRepositoryLanguages = [];
foreach ($repositoryLanguages as $repositoryLanguage) {
    $repositoryLanguagesByLocale[$repositoryLanguage['locale']] = $repositoryLanguage;
    if (!$repositoryLanguage['installed']) {
        $availableRepositoryLanguages[] = $repositoryLanguage;
    }
}
usort(
    $availableRepositoryLanguages,
    static fn(array $left, array $right): int => strnatcasecmp($left['native_name'], $right['native_name']),
);
$componentUpdateUi = json_encode(
    [
        'checkingLabel' => __('Provjeravam dostupna izdanja...'),
        'lastCheckedLabel' => __('Zadnja provjera:'),
        'availableUpdatesLabel' => __('Dostupna ažuriranja: %d'),
        'noUpdatesLabel' => __('Nema novih kompatibilnih izdanja.'),
        'partialFailureLabel' => __('Neke komponente nije bilo moguće provjeriti.'),
        'failedLabel' => __('Provjera izdanja nije uspjela.'),
        'locale' => $localeValue,
    ],
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
);
$applicationUpdateUi = json_encode(
    [
        'confirmLabel' => __('Pokrenuti sigurnosnu kopiju i nadogradnju cijele aplikacije?'),
        'titleLabel' => __('Nadogradnja aplikacije je u tijeku'),
        'elapsedLabel' => __('Proteklo vrijeme:'),
        'percentLabel' => __('Napredak:'),
        'retryLabel' => __('Osvježi prikaz'),
        'successLabel' => __('Nadogradnja je uspješno završena. Osvježavam aplikaciju...'),
        'failedLabel' => __('Nadogradnja nije uspjela. Provjerite administratorski zapis prije novog pokušaja.'),
        'waitingLabel' => __('Updater radi u pozadini. Ovaj prikaz možete ostaviti otvoren.'),
        'stages' => [
            'queued' => __('Nadogradnja čeka sigurno pokretanje.'),
            'preparing' => __('Pripremam nadogradnju i provjeravam okruženje.'),
            'download' => __('Dohvaćam označeno izdanje Simbioze.'),
            'backup' => __('Izrađujem sigurnosnu kopiju aplikacijskog koda.'),
            'sync' => __('Ažuriram aplikacijske datoteke i čuvam privatne postavke.'),
            'configuration' => __('Dopunjujem konfiguraciju postojećih tema.'),
            'dependencies' => __('Ažuriram i provjeravam Composer module.'),
            'platform' => __('Provjeravam PHP i platformske preduvjete.'),
            'preflight' => __('Provjeravam pokretanje aplikacije i pristup bazi.'),
            'migrations' => __('Primjenjujem migracije baze.'),
            'verification' => __('Provjeravam da nema migracija na čekanju.'),
            'cache' => __('Čistim aplikacijsku predmemoriju.'),
            'rollback' => __('Vraćam prethodno izdanje nakon prekinute nadogradnje.'),
            'complete' => __('Nadogradnja je uspješno završena.'),
            'failed' => __('Nadogradnja nije uspjela.'),
            'running' => __('Nadogradnja aplikacije je u tijeku.'),
        ],
    ],
    JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR,
);
?>
<style>
    .setup-modules-header,
    .setup-module-row {
        align-items: center;
        display: grid;
        gap: 1rem;
        grid-template-columns:
            minmax(11rem, 1.45fr)
            minmax(6rem, 0.65fr)
            minmax(6rem, 0.65fr)
            minmax(7.5rem, 0.75fr)
            minmax(6rem, 0.65fr)
            minmax(9rem, 1fr);
    }

    .setup-modules-header {
        background: var(--bs-tertiary-bg);
        font-weight: 600;
        padding: 0.5rem 0.75rem;
    }

    .setup-module-row {
        border-bottom: 1px solid var(--bs-border-color);
        padding: 0.75rem;
    }

    .setup-module-row:last-child {
        border-bottom: 0;
    }

    .setup-module-cell {
        min-width: 0;
        overflow-wrap: anywhere;
    }

    .setup-module-status .badge {
        max-width: 100%;
        overflow-wrap: anywhere;
        text-align: start;
        white-space: normal;
    }

    .setup-update-overlay {
        backdrop-filter: blur(0.35rem);
        background: color-mix(in srgb, var(--bs-body-bg) 88%, transparent);
        inset: 0;
        overflow-y: auto;
        padding: 1rem;
        z-index: 2000;
        border: 0;
        max-width: none;
        max-height: none;
        width: 100%;
        height: 100%;
        margin: 0;
        color: inherit;
    }

    .setup-update-overlay[open] { display: flex; }

    .setup-update-dialog {
        max-width: 44rem;
        width: 100%;
    }

    .setup-update-progress {
        height: 1.25rem;
    }

    .setup-language-picker {
        max-width: 44rem;
    }

    .setup-language-picker .dropdown-menu {
        max-width: calc(100vw - 2rem);
        width: 100%;
    }

    .setup-language-options {
        max-height: 20rem;
        overflow-y: auto;
        overscroll-behavior: contain;
    }

    .setup-language-option[hidden] {
        display: none !important;
    }

    @media (max-width: 991.98px) {
        .setup-modules-header {
            display: none;
        }

        .setup-module-row {
            border: 1px solid var(--bs-border-color);
            border-radius: var(--bs-border-radius);
            display: block;
            margin-bottom: 0.75rem;
        }

        .setup-module-row:last-child {
            border-bottom: 1px solid var(--bs-border-color);
            margin-bottom: 0;
        }

        .setup-module-main {
            padding-bottom: 0.625rem;
        }

        .setup-module-cell:not(.setup-module-main) {
            border-top: 1px solid var(--bs-border-color);
            display: grid;
            gap: 0.75rem;
            grid-template-columns: minmax(6.5rem, 38%) minmax(0, 1fr);
            padding: 0.625rem 0;
        }

        .setup-module-cell:not(.setup-module-main)::before {
            color: var(--bs-secondary-color);
            content: attr(data-label);
            font-size: 0.875rem;
            font-weight: 600;
        }

        .setup-module-actions {
            display: block;
            padding-bottom: 0;
        }

        .setup-module-actions::before {
            display: block;
            margin-bottom: 0.5rem;
        }

        .setup-module-action-buttons {
            justify-content: flex-start !important;
        }

        .setup-module-cli {
            overflow-wrap: anywhere;
            text-align: start !important;
        }
    }
</style>
<div class="row g-4">
    <?php if ($settingsMenu !== '') : ?>
        <aside class="col-12 col-xl-3"><?= $settingsMenu ?></aside>
    <?php endif; ?>

    <div class="col-12 <?= $settingsMenu !== '' ? 'col-xl-9' : '' ?>">
        <section class="card shadow-sm mb-4">
            <div class="card-body">
                <h1 class="h3 mb-2"><?= $this->escape($title) ?></h1>
                <p class="text-body-secondary mb-4">
                    <?= $this->escape(__('Ovdje upravljate opcionalnim modulima i odmah vidite jesu li prava datoteka ispravna.')) ?>
                </p>

                <div class="alert <?= $diagnostics['package_changes_allowed'] ? 'alert-success' : 'alert-info' ?> mb-3" role="status">
                    <strong>
                        <?= $this->escape($diagnostics['package_changes_allowed']
                            ? __('GUI instalacija i uklanjanje modula su dostupni.')
                            : __('GUI instalacija i uklanjanje modula nisu dostupni.')) ?>
                    </strong>
                    <div class="mt-1">
                        <?= $this->escape($diagnostics['package_changes_allowed']
                            ? __('Aktivni su namjenski FPM pool, ograničeni helper i potrebna prava.')
                            : __('Već instalirane module i dalje možete uključiti ili isključiti; za pakete upotrijebite CLI.')) ?>
                    </div>
                </div>

                <details>
                    <summary class="fw-semibold mb-3"><?= $this->escape(__('Provjera okruženja i prava')) ?></summary>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr>
                                <th><?= $this->escape(__('Provjera')) ?></th>
                                <th><?= $this->escape(__('Rezultat')) ?></th>
                                <th><?= $this->escape(__('Detalj')) ?></th>
                            </tr></thead>
                            <tbody>
                            <?php foreach ($diagnostics['checks'] as $check) : ?>
                                <tr>
                                    <td><?= $this->escape(__($check['label_hr'])) ?></td>
                                    <td>
                                        <span class="badge <?= $check['passed'] ? 'text-bg-success' : 'text-bg-danger' ?>">
                                <?= $this->escape($check['passed'] ? __('U redu') : __('Potrebna dorada')) ?>
                                        </span>
                                    </td>
                                    <td><code class="text-break"><?= $this->escape($check['detail']) ?></code></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
        </section>

        <section
            class="card shadow-sm mb-4"
            data-setup-component-updates
            data-update-check-path="<?= $this->escape($automaticUpdateCheckPath) ?>"
            data-application-update-status-path="<?= $this->escape($applicationUpdateStatusPath) ?>"
            data-application-update-state="<?= $this->escape($applicationUpdate['state']) ?>"
        >
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-start">
                    <div>
                        <h2 class="h4 mb-1"><?= $this->escape(__('Nadogradnja aplikacije')) ?></h2>
                        <p class="text-body-secondary mb-2">
                            <?= $this->escape(__('Updater izrađuje sigurnosnu kopiju, uključuje održavanje, nadograđuje kod i module te primjenjuje migracije.')) ?>
                        </p>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <span class="badge text-bg-primary">
                                <?= $this->escape(__('Trenutačna verzija:')) ?>
                                <?= $this->escape($applicationUpdate['current_version']) ?>
                            </span>
                            <span class="badge <?= $applicationUpdate['state'] === 'failed' ? 'text-bg-danger' : ($updateRunning ? 'text-bg-warning' : 'text-bg-secondary') ?>">
                                <?= $this->escape($updateStatusLabels[$applicationUpdate['state']] ?? $applicationUpdate['state']) ?>
                            </span>
                            <span
                                class="badge text-bg-warning <?= $applicationLatestVersion === null ? 'd-none' : '' ?>"
                                data-setup-application-update
                            >
                                <?= $this->escape(__('Dostupna verzija:')) ?>
                                <span data-setup-application-latest><?= $this->escape($applicationLatestVersion ?? '') ?></span>
                            </span>
                        </div>
                        <div class="small text-body-secondary mt-2" data-setup-update-summary aria-live="polite">
                            <?php if ($componentUpdates['checked_at'] !== null) : ?>
                                <?= $this->escape(__('Zadnja provjera:')) ?>
                                <?= $this->escape($formatTimestamp(date(DATE_ATOM, $componentUpdates['checked_at']))) ?>
                            <?php else : ?>
                                <?= $this->escape(__('Provjeravam dostupna izdanja...')) ?>
                            <?php endif; ?>
                        </div>
                        <button class="btn btn-outline-primary mt-3" type="button" data-setup-update-open hidden>
                            <?= $this->escape(__('Napredak nadogradnje')) ?>
                        </button>
                        <?php if ($applicationUpdate['started_at'] !== null) : ?>
                            <div class="small text-body-secondary mt-2">
                            <?= $this->escape(__('Pokrenuto:')) ?> <?= $this->escape($formatTimestamp($applicationUpdate['started_at'])) ?>
                            <?php if ($applicationUpdate['finished_at'] !== null) : ?>
                                    · <?= $this->escape(__('Završeno:')) ?> <?= $this->escape($formatTimestamp($applicationUpdate['finished_at'])) ?>
                            <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($diagnostics['application_updates_allowed']) : ?>
                        <div class="d-flex flex-column gap-2 align-items-stretch align-items-lg-end">
                            <form method="post" action="<?= $this->escape($actionPath) ?>">
                        <?= $this->csrfHandler->generateCsrfTokenInputField() ?>
                                <input type="hidden" name="action" value="application-update-check">
                                <button class="btn btn-outline-primary w-100" <?= $updateRunning ? 'disabled' : '' ?>>
                        <?= $this->escape(__('Provjeri novo izdanje')) ?>
                                </button>
                            </form>
                            <form
                                method="post"
                                action="<?= $this->escape($actionPath) ?>"
                                class="d-flex flex-column flex-sm-row gap-2"
                                data-setup-application-update-form
                            >
                        <?= $this->csrfHandler->generateCsrfTokenInputField() ?>
                                <input type="hidden" name="action" value="application-update-start">
                                <input class="form-control" name="tag" inputmode="numeric" pattern="v?[0-9]+\.[0-9]+\.[0-9]+" placeholder="<?= $this->escape(__('Zadnje izdanje ili tag 1.2.3')) ?>" aria-label="<?= $this->escape(__('Ciljno izdanje')) ?>">
                                <button class="btn btn-primary text-nowrap" <?= $updateRunning ? 'disabled' : '' ?>>
                        <?= $this->escape(__('Pokreni nadogradnju')) ?>
                                </button>
                            </form>
                        </div>
                    <?php else : ?>
                        <div class="rounded bg-body-tertiary p-3">
                            <div class="fw-semibold mb-2"><?= $this->escape(__('Nadogradnja kroz CLI')) ?></div>
                            <code class="d-block user-select-all">php update.php --check</code>
                            <code class="d-block user-select-all">php update.php</code>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="card shadow-sm">
            <div class="card-body">
                <header class="mb-4">
                    <h2 class="h4 mb-1"><?= $this->escape(__('Moduli')) ?></h2>
                    <p class="text-body-secondary mb-0">
                        <?= $this->escape(__('Obvezni moduli čine minimalnu instalaciju. Opcionalne dodajte samo kada su potrebni.')) ?>
                    </p>
                </header>

                <div class="setup-modules-grid" role="table" aria-label="<?= $this->escape(__('Moduli')) ?>">
                    <div class="setup-modules-header" role="row">
                        <div role="columnheader"><?= $this->escape(__('Modul')) ?></div>
                        <div role="columnheader"><?= $this->escape(__('Vrsta')) ?></div>
                        <div role="columnheader"><?= $this->escape(__('Ovisnosti')) ?></div>
                        <div role="columnheader"><?= $this->escape(__('Verzija')) ?></div>
                        <div role="columnheader"><?= $this->escape(__('Stanje')) ?></div>
                        <div role="columnheader" class="text-end"><?= $this->escape(__('Radnje')) ?></div>
                    </div>
                    <div role="rowgroup">
                        <?php foreach ($modules as $module) :
                            $definition = $definitions[$module['slug']] ?? ['dependencies' => []];
                            $label = __($module['label_hr']);
                            $command = 'vendor/bin/hph modules add ' . $module['slug'];
                            $component = $componentsByPackage[$module['package']] ?? null;
                            $installedVersion = $module['package_installed']
                            && is_array($component)
                            && is_string($component['installed_version'] ?? null)
                            ? $component['installed_version']
                            : null;
                            $latestVersion = is_array($component)
                            && ($component['status'] ?? null) === 'update_available'
                            && is_string($component['latest_version'] ?? null)
                            ? $component['latest_version']
                            : null;
                            ?>
                        <div
                            class="setup-module-row"
                            role="row"
                            data-component-package="<?= $this->escape($module['package']) ?>"
                        >
                                <div class="setup-module-cell setup-module-main" role="rowheader">
                                    <div class="fw-semibold"><?= $this->escape($label) ?></div>
                                    <div class="small fw-normal text-body-secondary text-break">
                            <?= $this->escape($module['package']) ?>
                                    </div>
                                </div>
                                <div class="setup-module-cell" role="cell" data-label="<?= $this->escape(__('Vrsta')) ?>">
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge <?= $module['optional'] ? 'text-bg-secondary' : 'text-bg-primary' ?>">
                            <?= $this->escape($module['optional'] ? __('Opcionalan') : __('Obvezan')) ?>
                                        </span>
                            <?php if ($module['recommended']) : ?>
                                        <span class="badge text-bg-info"><?= $this->escape(__('Preporučen')) ?></span>
                            <?php endif; ?>
                                    </div>
                                </div>
                                <div class="setup-module-cell small" role="cell" data-label="<?= $this->escape(__('Ovisnosti')) ?>">
                            <?= ($definition['dependencies'] ?? []) === []
                            ? '—'
                            : $this->escape(implode(', ', $definition['dependencies'])) ?>
                                </div>
                                <div class="setup-module-cell" role="cell" data-label="<?= $this->escape(__('Verzija')) ?>">
                                    <code data-component-installed-version><?= $this->escape($installedVersion ?? '—') ?></code>
                                    <div
                                        class="small text-warning-emphasis <?= $latestVersion === null ? 'd-none' : '' ?>"
                                        data-component-update-version
                                    >
                            <?= $this->escape(__('Dostupno:')) ?>
                                        <code data-component-latest-version><?= $this->escape($latestVersion ?? '') ?></code>
                                    </div>
                                </div>
                                <div class="setup-module-cell setup-module-status" role="cell" data-label="<?= $this->escape(__('Stanje')) ?>">
                                    <span class="badge <?= $module['enabled'] ? 'text-bg-success' : 'text-bg-light' ?>">
                            <?= $this->escape($statusLabels[$module['state']] ?? $module['state']) ?>
                                    </span>
                                </div>
                                <div class="setup-module-cell setup-module-actions" role="cell" data-label="<?= $this->escape(__('Radnje')) ?>">
                                    <div class="d-flex flex-wrap gap-1 justify-content-end setup-module-action-buttons">
                            <?php if (!$module['optional']) : ?>
                                        <span class="text-body-secondary">—</span>
                            <?php elseif ($module['enabled']) : ?>
                                        <form method="post" action="<?= $this->escape($actionPath) ?>">
                                <?= $this->csrfHandler->generateCsrfTokenInputField() ?>
                                            <input type="hidden" name="action" value="disable">
                                            <input type="hidden" name="module" value="<?= $this->escape($module['slug']) ?>">
                                            <button class="btn btn-sm btn-outline-secondary" <?= !$diagnostics['state_changes_allowed'] ? 'disabled' : '' ?>>
                                <?= $this->escape(__('Isključi')) ?>
                                            </button>
                                        </form>
                            <?php elseif ($module['package_installed'] && $module['schema_installed']) : ?>
                                        <form method="post" action="<?= $this->escape($actionPath) ?>">
                                <?= $this->csrfHandler->generateCsrfTokenInputField() ?>
                                            <input type="hidden" name="action" value="enable">
                                            <input type="hidden" name="module" value="<?= $this->escape($module['slug']) ?>">
                                            <button class="btn btn-sm btn-primary" <?= !$diagnostics['state_changes_allowed'] ? 'disabled' : '' ?>>
                                <?= $this->escape(__('Uključi')) ?>
                                            </button>
                                        </form>
                            <?php elseif ($diagnostics['package_changes_allowed']) : ?>
                                        <form method="post" action="<?= $this->escape($actionPath) ?>" class="d-flex flex-wrap gap-1 justify-content-end">
                                <?= $this->csrfHandler->generateCsrfTokenInputField() ?>
                                            <input type="hidden" name="action" value="add">
                                            <input type="hidden" name="module" value="<?= $this->escape($module['slug']) ?>">
                                <?php if ($module['backup_available']) : ?>
                                            <button class="btn btn-sm btn-primary" name="restore" value="1"><?= $this->escape(__('Vrati iz kopije')) ?></button>
                                            <button class="btn btn-sm btn-outline-primary" name="restore" value="0"><?= $this->escape(__('Nova instalacija')) ?></button>
                                <?php else : ?>
                                            <button class="btn btn-sm btn-primary"><?= $this->escape(__('Instaliraj')) ?></button>
                                <?php endif; ?>
                                        </form>
                            <?php endif; ?>

                            <?php if ($module['package_installed'] && $module['schema_installed'] && $diagnostics['package_changes_allowed']) : ?>
                                        <form method="post" action="<?= $this->escape($actionPath) ?>" onsubmit="return confirm('<?= $this->escape(__('Modul će se sigurnosno kopirati, a njegove tablice i paket ukloniti. Nastaviti?')) ?>')">
                                <?= $this->csrfHandler->generateCsrfTokenInputField() ?>
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="module" value="<?= $this->escape($module['slug']) ?>">
                                            <button class="btn btn-sm btn-outline-danger"><?= $this->escape(__('Deinstaliraj')) ?></button>
                                        </form>
                            <?php endif; ?>
                                    </div>

                            <?php if ($module['optional'] && !$diagnostics['package_changes_allowed'] && !$module['schema_installed']) : ?>
                                    <div class="small text-body-secondary text-end mt-1 setup-module-cli">
                                <?= $this->escape(__('Instalacija kroz CLI')) ?>:
                                        <code class="user-select-all text-break"><?= $this->escape($command . ($module['backup_available'] ? ' --restore' : ' --fresh')) ?></code>
                                    </div>
                            <?php elseif ($module['optional'] && !$diagnostics['package_changes_allowed'] && $module['schema_installed']) : ?>
                                    <div class="small text-body-secondary text-end mt-1 setup-module-cli">
                                <?= $this->escape(__('Za potpuno uklanjanje pokrenite:')) ?>
                                        <code class="user-select-all text-break">vendor/bin/hph modules remove <?= $this->escape($module['slug']) ?> --yes</code>
                                    </div>
                            <?php endif; ?>
                                </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <section class="card shadow-sm mt-4">
            <div class="card-body">
                <h2 class="h4 mb-1"><?= $this->escape(__('Jezici')) ?></h2>
                <p class="text-body-secondary">
                    <?= $this->escape(__('Jedan jezični paket sadrži prijevode, višejezične nazive jezika i sigurnu SVG zastavicu.')) ?>
                </p>

                <h3 class="h6 mt-4 mb-2"><?= $this->escape(__('Instalirani jezici')) ?></h3>
                <div class="table-responsive mb-4" data-setup-installed-languages>
                    <table class="table table-sm align-middle">
                        <thead><tr>
                            <th><?= $this->escape(__('Jezik')) ?></th>
                            <th><?= $this->escape(__('Naziv')) ?></th>
                            <th><?= $this->escape(__('Revizija')) ?></th>
                            <th><?= $this->escape(__('Pokrivenost')) ?></th>
                            <th><?= $this->escape(__('Stanje')) ?></th>
                            <th class="text-end"><?= $this->escape(__('Radnje')) ?></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($languages as $language) :
                            $remote = $repositoryLanguagesByLocale[$language['locale']] ?? null;
                            ?>
                            <tr>
                                <td><code><?= $this->escape($language['locale']) ?></code></td>
                                <td><?= $this->escape($language['native_name']) ?></td>
                                <td><?= is_array($remote) && $remote['version'] !== '' ? '<code>' . $this->escape($remote['version']) . '</code>' : '—' ?></td>
                                <td><?= $this->escape(number_format($language['coverage'], 2)) ?>%</td>
                                <td>
                            <?= $this->escape($language['active'] ? __('Uključen') : __('Isključen')) ?>
                            <?php if (is_array($remote) && $remote['update_available']) :
                                ?><span class="badge text-bg-warning"><?= $this->escape(__('Dostupna nadogradnja')) ?></span><?php
                            endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex flex-wrap justify-content-end gap-1">
                            <?php if (is_array($remote) && $remote['update_available'] && $diagnostics['package_changes_allowed']) : ?>
                                                <form method="post" action="<?= $this->escape($actionPath) ?>">
                                <?= $this->csrfHandler->generateCsrfTokenInputField() ?>
                                                    <input type="hidden" name="action" value="language-install"><input type="hidden" name="locale" value="<?= $this->escape($language['locale']) ?>"><input type="hidden" name="replace" value="1">
                                                    <button class="btn btn-sm btn-outline-primary"><?= $this->escape(__('Ažuriraj')) ?></button>
                                                </form>
                            <?php endif; ?>
                            <?php if ($diagnostics['state_changes_allowed']) : ?>
                                                <form method="post" action="<?= $this->escape($actionPath) ?>">
                                <?= $this->csrfHandler->generateCsrfTokenInputField() ?>
                                                    <input type="hidden" name="action" value="<?= $language['active'] ? 'language-disable' : 'language-enable' ?>"><input type="hidden" name="locale" value="<?= $this->escape($language['locale']) ?>">
                                                    <button class="btn btn-sm btn-outline-secondary"><?= $this->escape($language['active'] ? __('Isključi') : __('Uključi')) ?></button>
                                                </form>
                            <?php endif; ?>
                            <?php if ($diagnostics['package_changes_allowed']) : ?>
                                                <form method="post" action="<?= $this->escape($actionPath) ?>" onsubmit="return confirm(<?= $this->escape(json_encode(__('Deinstalirati jezik?'), JSON_THROW_ON_ERROR)) ?>)">
                                <?= $this->csrfHandler->generateCsrfTokenInputField() ?>
                                                    <input type="hidden" name="action" value="language-remove"><input type="hidden" name="locale" value="<?= $this->escape($language['locale']) ?>">
                                                    <button class="btn btn-sm btn-outline-danger"><?= $this->escape(__('Deinstaliraj')) ?></button>
                                                </form>
                            <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($languages === []) : ?>
                            <tr><td colspan="6" class="text-body-secondary"><?= $this->escape(__('Nema instaliranih jezika.')) ?></td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <h3 class="h6 mb-2"><?= $this->escape(__('Dostupni jezici')) ?></h3>
                <p class="small text-body-secondary mb-3">
                    <?= $this->escape(__('Objavljeni jezici iz javnog repozitorija Simbioze. Odaberite jedan ili više jezika za zajedničku instalaciju.')) ?>
                </p>

                <?php if ($availableRepositoryLanguages !== [] && $diagnostics['package_changes_allowed']) : ?>
                    <form method="post" action="<?= $this->escape($actionPath) ?>" class="d-flex flex-column flex-lg-row align-items-lg-end gap-3 mb-4" data-setup-language-install-form>
                    <?= $this->csrfHandler->generateCsrfTokenInputField() ?>
                        <input type="hidden" name="action" value="language-install-selected">
                        <div
                            class="dropdown setup-language-picker flex-grow-1"
                            data-setup-language-picker
                            data-default-label="<?= $this->escape(__('Odaberite jezike')) ?>"
                            data-selected-label="<?= $this->escape(__('Odabrano jezika: %d')) ?>"
                        >
                            <label class="form-label" for="setup-language-picker-toggle"><?= $this->escape(__('Jezici za instalaciju')) ?></label>
                            <button
                                id="setup-language-picker-toggle"
                                class="form-select text-start d-flex align-items-center justify-content-between"
                                type="button"
                                data-bs-toggle="dropdown"
                                data-bs-auto-close="outside"
                                aria-expanded="false"
                            >
                                <span data-setup-language-picker-label><?= $this->escape(__('Odaberite jezike')) ?></span>
                                <span class="badge text-bg-primary ms-2" data-setup-language-picker-count>0</span>
                            </button>
                            <div class="dropdown-menu p-3 shadow" data-setup-language-picker-menu>
                                <input
                                    class="form-control form-control-sm mb-2"
                                    id="setup-language-picker-search"
                                    type="search"
                                    placeholder="<?= $this->escape(__('Pretraži dostupne jezike')) ?>"
                                    aria-label="<?= $this->escape(__('Pretraži dostupne jezike')) ?>"
                                    autocomplete="off"
                                    data-setup-language-search
                                >
                                <div class="d-flex flex-wrap gap-2 mb-2">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-setup-language-select-visible><?= $this->escape(__('Odaberi prikazane')) ?></button>
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-setup-language-clear><?= $this->escape(__('Poništi odabir')) ?></button>
                                </div>
                                <div class="setup-language-options border rounded" data-setup-language-options>
                    <?php foreach ($availableRepositoryLanguages as $remote) :
                        $searchText = mb_strtolower($remote['locale'] . ' ' . $remote['native_name']);
                        ?>
                                    <div class="form-check px-5 py-2 border-bottom setup-language-option" data-setup-language-option data-search-text="<?= $this->escape($searchText) ?>">
                                        <input class="form-check-input" id="setup-language-<?= $this->escape($remote['locale']) ?>" type="checkbox" name="locales[]" value="<?= $this->escape($remote['locale']) ?>">
                                        <label class="form-check-label d-flex justify-content-between gap-3 w-100" for="setup-language-<?= $this->escape($remote['locale']) ?>">
                                            <span><span class="fw-semibold"><?= $this->escape($remote['native_name']) ?></span> <code><?= $this->escape($remote['locale']) ?></code></span>
                                            <code class="text-nowrap"><?= $this->escape($remote['version']) ?></code>
                                        </label>
                                    </div>
                    <?php endforeach; ?>
                                </div>
                                <div class="small text-body-secondary py-2 d-none" data-setup-language-empty><?= $this->escape(__('Nema jezika koji odgovaraju pretrazi.')) ?></div>
                            </div>
                        </div>
                        <button class="btn btn-primary text-nowrap" type="submit" disabled data-setup-language-install-selected><?= $this->escape(__('Instaliraj odabrane jezike')) ?></button>
                    </form>
                <?php elseif ($availableRepositoryLanguages === []) : ?>
                    <div class="text-body-secondary mb-4">
                    <?= $this->escape($repositoryLanguages === [] ? __('Katalog jezika trenutačno nije dostupan; instalirani jezici ostaju aktivni.') : __('Svi dostupni jezici već su instalirani.')) ?>
                    </div>
                <?php else : ?>
                    <div class="rounded bg-body-tertiary p-3 mb-4">
                        <div class="fw-semibold mb-2"><?= $this->escape(__('Instalacija jezika kroz CLI')) ?></div>
                        <code class="d-block user-select-all text-break">vendor/bin/hph languages available</code>
                        <code class="d-block user-select-all text-break">vendor/bin/hph languages install &lt;locale&gt;</code>
                    </div>
                <?php endif; ?>

                <?php if ($diagnostics['package_changes_allowed']) : ?>
                    <form method="post" action="<?= $this->escape($actionPath) ?>" enctype="multipart/form-data" class="row g-3 align-items-end">
                    <?= $this->csrfHandler->generateCsrfTokenInputField() ?>
                        <input type="hidden" name="action" value="language-add">
                        <div class="col-12 col-lg-7">
                            <label class="form-label" for="setup-language-pack"><?= $this->escape(__('JSON jezični paket')) ?></label>
                            <div class="input-group">
                                <input class="visually-hidden" id="setup-language-pack" type="file" name="language_pack" accept="application/json,.json" required data-localized-file-input>
                                <label class="btn btn-outline-secondary" for="setup-language-pack"><?= $this->escape(__('Odaberi datoteku')) ?></label>
                                <span
                                    class="form-control text-truncate"
                                    data-localized-file-name
                                    data-empty-label="<?= $this->escape(__('Nijedna datoteka nije odabrana')) ?>"
                                    aria-live="polite"
                                ><?= $this->escape(__('Nijedna datoteka nije odabrana')) ?></span>
                            </div>
                        </div>
                        <div class="col-12 col-sm-auto">
                            <div class="form-check">
                                <input class="form-check-input" id="setup-language-replace" type="checkbox" name="replace" value="1">
                                <label class="form-check-label" for="setup-language-replace"><?= $this->escape(__('Zamijeni postojeći jezik')) ?></label>
                            </div>
                        </div>
                        <div class="col-12 col-sm-auto">
                            <button class="btn btn-primary"><?= $this->escape(__('Dodaj jezik')) ?></button>
                        </div>
                    </form>
                <?php else : ?>
                    <div class="rounded bg-body-tertiary p-3">
                        <div class="fw-semibold mb-2"><?= $this->escape(__('Dodavanje kroz CLI')) ?></div>
                        <code class="d-block user-select-all text-break">vendor/bin/hph languages add &lt;language-pack.json&gt;</code>
                        <div class="small text-body-secondary mt-2">
                    <?= $this->escape(__('Predložak za prijevod izradite naredbom:')) ?>
                            <code class="user-select-all">vendor/bin/hph languages template de --source=en</code>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
<dialog
    class="setup-update-overlay position-fixed align-items-center justify-content-center"
    data-setup-update-overlay
    data-initial-progress="<?= $applicationUpdate['progress'] === null ? '' : $this->escape((string)$applicationUpdate['progress']) ?>"
    data-initial-stage="<?= $this->escape($applicationUpdate['stage']) ?>"
    data-started-at="<?= $this->escape($applicationUpdate['started_at'] ?? '') ?>"
    aria-labelledby="setup-update-title"
    aria-describedby="setup-update-stage"
>
    <div class="setup-update-dialog card border-primary shadow-lg">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="spinner-border text-primary" data-setup-update-spinner aria-hidden="true"></div>
                <div>
                    <h2 class="h3 mb-1" id="setup-update-title" tabindex="-1" autofocus><?= $this->escape(__('Nadogradnja aplikacije je u tijeku')) ?></h2>
                    <div class="text-body-secondary"><?= $this->escape(__('Updater radi u pozadini. Ovaj prikaz možete ostaviti otvoren.')) ?></div>
                </div>
            </div>
            <p class="lead mb-3" id="setup-update-stage" data-setup-update-stage aria-live="polite"></p>
            <div
                class="progress setup-update-progress mb-2"
                role="progressbar"
                aria-label="<?= $this->escape(__('Napredak nadogradnje')) ?>"
                aria-valuemin="0"
                aria-valuemax="100"
                data-setup-update-progress
            >
                <div class="progress-bar progress-bar-striped progress-bar-animated" data-setup-update-progress-bar></div>
            </div>
            <div class="d-flex justify-content-between gap-3 small text-body-secondary">
                <span><span><?= $this->escape(__('Napredak:')) ?></span> <strong data-setup-update-percent>—</strong></span>
                <span><span><?= $this->escape(__('Proteklo vrijeme:')) ?></span> <strong data-setup-update-elapsed>00:00</strong></span>
            </div>
            <div class="alert alert-danger mt-4 mb-0 d-none" data-setup-update-error role="alert"></div>
            <div class="text-end mt-4 d-none" data-setup-update-actions>
                <button class="btn btn-primary" type="button" data-setup-update-reload><?= $this->escape(__('Osvježi prikaz')) ?></button>
            </div>
            <div class="text-end mt-3"><button class="btn btn-outline-secondary" type="button" data-setup-update-close><?= $this->escape(__('Zatvori')) ?></button></div>
        </div>
    </div>
</dialog>
<script>
(function () {
    'use strict';

    document.querySelectorAll('[data-localized-file-input]').forEach(function (input) {
        if (!(input instanceof HTMLInputElement)) {
            return;
        }
        const container = input.closest('.input-group');
        const filename = container instanceof HTMLElement
            ? container.querySelector('[data-localized-file-name]')
            : null;
        if (!(filename instanceof HTMLElement)) {
            return;
        }
        input.addEventListener('change', function () {
            const selected = Array.from(input.files || []).map((file) => file.name);
            filename.textContent = selected.length > 0
                ? selected.join(', ')
                : filename.dataset.emptyLabel || '';
        });
    });

    const languagePicker = document.querySelector('[data-setup-language-picker]');
    if (languagePicker instanceof HTMLElement) {
        const search = languagePicker.querySelector('[data-setup-language-search]');
        const options = Array.from(languagePicker.querySelectorAll('[data-setup-language-option]'));
        const checkboxes = options
            .map((option) => option.querySelector('input[type="checkbox"]'))
            .filter((checkbox) => checkbox instanceof HTMLInputElement);
        const count = languagePicker.querySelector('[data-setup-language-picker-count]');
        const label = languagePicker.querySelector('[data-setup-language-picker-label]');
        const empty = languagePicker.querySelector('[data-setup-language-empty]');
        const selectVisible = languagePicker.querySelector('[data-setup-language-select-visible]');
        const clear = languagePicker.querySelector('[data-setup-language-clear]');
        const form = languagePicker.closest('form');
        const submit = form instanceof HTMLFormElement
            ? form.querySelector('[data-setup-language-install-selected]')
            : null;

        const updateSelection = function () {
            const selected = checkboxes.filter((checkbox) => checkbox.checked).length;
            if (count instanceof HTMLElement) {
                count.textContent = String(selected);
            }
            if (label instanceof HTMLElement) {
                const template = selected === 0
                    ? languagePicker.dataset.defaultLabel || ''
                    : languagePicker.dataset.selectedLabel || '%d';
                label.textContent = template.replace('%d', String(selected));
            }
            if (submit instanceof HTMLButtonElement) {
                submit.disabled = selected === 0;
            }
        };

        const filterOptions = function () {
            const query = search instanceof HTMLInputElement
                ? search.value.trim().toLocaleLowerCase()
                : '';
            let visible = 0;
            options.forEach(function (option) {
                const matches = query === '' || (option.dataset.searchText || '').includes(query);
                option.hidden = !matches;
                if (matches) {
                    visible += 1;
                }
            });
            if (empty instanceof HTMLElement) {
                empty.classList.toggle('d-none', visible !== 0);
            }
        };

        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', updateSelection);
        });
        if (search instanceof HTMLInputElement) {
            search.addEventListener('input', filterOptions);
        }
        if (selectVisible instanceof HTMLButtonElement) {
            selectVisible.addEventListener('click', function () {
                options.forEach(function (option) {
                    const checkbox = option.querySelector('input[type="checkbox"]');
                    if (!option.hidden && checkbox instanceof HTMLInputElement) {
                        checkbox.checked = true;
                    }
                });
                updateSelection();
            });
        }
        if (clear instanceof HTMLButtonElement) {
            clear.addEventListener('click', function () {
                checkboxes.forEach(function (checkbox) { checkbox.checked = false; });
                updateSelection();
            });
        }
        updateSelection();
        filterOptions();
    }

    const panel = document.querySelector('[data-setup-component-updates]');
    if (!(panel instanceof HTMLElement)) {
        return;
    }

    const checkPath = panel.dataset.updateCheckPath || '';
    const summary = panel.querySelector('[data-setup-update-summary]');
    const applicationUpdate = panel.querySelector('[data-setup-application-update]');
    const applicationLatest = panel.querySelector('[data-setup-application-latest]');
    const moduleRows = new Map();
    document.querySelectorAll('.setup-module-row[data-component-package]').forEach(function (row) {
        if (row instanceof HTMLElement) {
            moduleRows.set(row.dataset.componentPackage || '', row);
        }
    });
    const ui = <?= $componentUpdateUi ?>;
    const updateUi = <?= $applicationUpdateUi ?>;
    const updateStatusPath = panel.dataset.applicationUpdateStatusPath || '';
    const initialUpdateState = panel.dataset.applicationUpdateState || 'idle';
    const updateForm = document.querySelector('[data-setup-application-update-form]');
    const updateOverlay = document.querySelector('[data-setup-update-overlay]');
    const updateStage = document.querySelector('[data-setup-update-stage]');
    const updateTitle = document.querySelector('#setup-update-title');
    const updateProgress = document.querySelector('[data-setup-update-progress]');
    const updateProgressBar = document.querySelector('[data-setup-update-progress-bar]');
    const updatePercent = document.querySelector('[data-setup-update-percent]');
    const updateElapsed = document.querySelector('[data-setup-update-elapsed]');
    const updateSpinner = document.querySelector('[data-setup-update-spinner]');
    const updateError = document.querySelector('[data-setup-update-error]');
    const updateActions = document.querySelector('[data-setup-update-actions]');
    const updateReload = document.querySelector('[data-setup-update-reload]');
    const updateOpen = document.querySelector('[data-setup-update-open]');
    const updateClose = document.querySelector('[data-setup-update-close]');
    const initialStartedAt = updateOverlay instanceof HTMLElement && updateOverlay.dataset.startedAt !== ''
        ? Date.parse(updateOverlay.dataset.startedAt || '')
        : Date.now();
    let updateStartedAt = Number.isFinite(initialStartedAt) ? initialStartedAt : Date.now();
    let updatePolling = false;
    let updateElapsedTimer = null;
    let updateDialogShown = false;

    // HR: Nativni dijalog upravlja fokusom i neaktivnom pozadinom; zatvaranje ne prekida updater.
    // EN: The native dialog manages focus and inert background; closing never cancels the updater.
    const openUpdateDialog = function () {
        if (updateOverlay instanceof HTMLDialogElement && !updateOverlay.open) {
            updateOverlay.showModal();
            document.body.classList.add('overflow-hidden');
        }
    };
    if (updateOpen instanceof HTMLButtonElement) updateOpen.addEventListener('click', openUpdateDialog);
    if (updateClose instanceof HTMLButtonElement) {
        updateClose.addEventListener('click', () => updateOverlay.close());
    }
    if (updateOverlay instanceof HTMLDialogElement) {
        // HR: Zadržavamo Tab u dijalogu i kada preglednik na kraju nudi adresnu traku.
        // EN: Keep Tab inside the dialog even when the browser offers its address bar at the end.
        updateOverlay.addEventListener('keydown', (event) => {
            if (event.key !== 'Tab') return;
            const buttons = Array.from(updateOverlay.querySelectorAll('button:not([disabled])'))
                .filter((button) => button.getClientRects().length > 0);
            const index = buttons.indexOf(document.activeElement);
            if (buttons.length > 0 && ((event.shiftKey && index <= 0) || (!event.shiftKey && index === buttons.length - 1))) {
                event.preventDefault();
                buttons[event.shiftKey ? buttons.length - 1 : 0].focus();
            }
        });
        updateOverlay.addEventListener('close', () => {
            document.body.classList.remove('overflow-hidden');
            if (updateOpen instanceof HTMLButtonElement) updateOpen.focus();
        });
    }

    const formatElapsed = function () {
        if (!(updateElapsed instanceof HTMLElement)) {
            return;
        }
        const seconds = Math.max(0, Math.floor((Date.now() - updateStartedAt) / 1000));
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const remainder = seconds % 60;
        updateElapsed.textContent = hours > 0
            ? [hours, minutes, remainder].map((value) => String(value).padStart(2, '0')).join(':')
            : [minutes, remainder].map((value) => String(value).padStart(2, '0')).join(':');
    };

    const stageLabel = function (stage) {
        return typeof updateUi.stages[stage] === 'string'
            ? updateUi.stages[stage]
            : updateUi.stages.running;
    };

    const renderUpdate = function (update) {
        if (!(updateOverlay instanceof HTMLElement)) {
            return;
        }
        const state = typeof update.state === 'string' ? update.state : 'running';
        const stage = typeof update.stage === 'string' ? update.stage : state;
        const numericProgress = Number.isInteger(update.progress)
            ? Math.max(0, Math.min(100, Number(update.progress)))
            : null;
        if (typeof update.started_at === 'string' && update.started_at !== '') {
            const parsed = Date.parse(update.started_at);
            if (Number.isFinite(parsed)) {
                updateStartedAt = parsed;
            }
        }

        if (updateOpen instanceof HTMLButtonElement) updateOpen.hidden = false;
        if (!updateDialogShown) {
            updateDialogShown = true;
            openUpdateDialog();
        }
        document.body.classList.toggle('overflow-hidden', updateOverlay.open);
        if (updateStage instanceof HTMLElement) {
            updateStage.textContent = state === 'success' ? updateUi.successLabel : stageLabel(stage);
        }
        if (updateTitle instanceof HTMLElement && (state === 'failed' || state === 'success')) {
            updateTitle.textContent = stageLabel(state === 'success' ? 'complete' : 'failed');
        }
        if (updateProgressBar instanceof HTMLElement) {
            const displayProgress = numericProgress === null ? 12 : numericProgress;
            updateProgressBar.style.width = String(displayProgress) + '%';
            updateProgressBar.classList.toggle('progress-bar-animated', state === 'queued' || state === 'running');
            updateProgressBar.classList.toggle('progress-bar-striped', state === 'queued' || state === 'running');
            updateProgressBar.classList.toggle('bg-danger', state === 'failed');
            updateProgressBar.classList.toggle('bg-success', state === 'success');
        }
        if (updateProgress instanceof HTMLElement) {
            if (numericProgress === null) {
                updateProgress.removeAttribute('aria-valuenow');
                updateProgress.setAttribute('aria-valuetext', stageLabel(stage));
            } else {
                updateProgress.setAttribute('aria-valuenow', String(numericProgress));
                updateProgress.removeAttribute('aria-valuetext');
            }
        }
        if (updatePercent instanceof HTMLElement) {
            updatePercent.textContent = numericProgress === null ? '—' : String(numericProgress) + '%';
        }
        if (updateSpinner instanceof HTMLElement) {
            updateSpinner.classList.toggle('d-none', state === 'success' || state === 'failed');
        }
        if (updateError instanceof HTMLElement) {
            updateError.textContent = state === 'failed' ? updateUi.failedLabel : '';
            updateError.classList.toggle('d-none', state !== 'failed');
        }
        if (updateActions instanceof HTMLElement) {
            updateActions.classList.toggle('d-none', state !== 'failed');
        }
        formatElapsed();
    };

    const pollApplicationUpdate = async function () {
        if (!updatePolling || updateStatusPath === '') {
            return;
        }
        try {
            const response = await fetch(updateStatusPath, {
                headers: {Accept: 'application/json'},
                credentials: 'same-origin',
                cache: 'no-store'
            });
            const contentType = response.headers.get('content-type') || '';
            if (!response.ok || !contentType.includes('application/json')) {
                renderUpdate({state: 'running', stage: 'running', progress: null});
            } else {
                const payload = await response.json();
                if (payload.ok !== true || typeof payload.update !== 'object' || payload.update === null) {
                    throw new Error('Invalid update status.');
                }
                renderUpdate(payload.update);
                if (payload.update.state === 'success') {
                    updatePolling = false;
                    window.setTimeout(function () { window.location.reload(); }, 1500);
                    return;
                }
                if (payload.update.state === 'failed') {
                    updatePolling = false;
                    return;
                }
            }
        } catch (_error) {
            renderUpdate({state: 'running', stage: 'running', progress: null});
        }
        window.setTimeout(pollApplicationUpdate, 750);
    };

    const startUpdateProgress = function (update) {
        updatePolling = true;
        renderUpdate(update);
        if (updateElapsedTimer === null) {
            updateElapsedTimer = window.setInterval(formatElapsed, 1000);
        }
        window.setTimeout(pollApplicationUpdate, 500);
    };

    if (updateForm instanceof HTMLFormElement) {
        updateForm.addEventListener('submit', function (event) {
            if (!window.confirm(updateUi.confirmLabel)) {
                event.preventDefault();
                return;
            }
            updateStartedAt = Date.now();
            startUpdateProgress({state: 'queued', stage: 'queued', progress: 0});
        });
    }

    if (updateReload instanceof HTMLButtonElement) {
        updateReload.addEventListener('click', function () { window.location.reload(); });
    }

    if (initialUpdateState === 'queued' || initialUpdateState === 'running') {
        const initialProgress = updateOverlay instanceof HTMLElement
            && /^\d+$/.test(updateOverlay.dataset.initialProgress || '')
            ? Number(updateOverlay.dataset.initialProgress)
            : null;
        startUpdateProgress({
            state: initialUpdateState,
            stage: updateOverlay instanceof HTMLElement ? updateOverlay.dataset.initialStage || 'running' : 'running',
            progress: initialProgress,
            started_at: updateOverlay instanceof HTMLElement ? updateOverlay.dataset.startedAt || '' : ''
        });
    }

    const setSummary = function (payload) {
        if (!(summary instanceof HTMLElement)) {
            return;
        }

        summary.replaceChildren();
        summary.classList.remove('text-danger');
        summary.classList.add('text-body-secondary');
        const checkedAt = Number(payload.checked_at || 0);
        if (checkedAt <= 0) {
            summary.textContent = String(payload.message || ui.failedLabel);
            return;
        }

        const checkedDate = new Date(checkedAt * 1000);
        const time = document.createElement('time');
        time.dateTime = String(payload.checked_at_iso || checkedDate.toISOString());
        time.textContent = checkedDate.toLocaleString(ui.locale);
        summary.append(document.createTextNode(ui.lastCheckedLabel + ' '), time);

        const updates = Number(payload.updates || 0);
        const result = document.createElement('span');
        result.className = updates > 0 ? 'text-warning-emphasis' : 'text-success';
        result.textContent = updates > 0
            ? ui.availableUpdatesLabel.replace('%d', String(updates))
            : ui.noUpdatesLabel;
        summary.append(document.createTextNode(' · '), result);

        if (Number(payload.failures || 0) > 0) {
            const warning = document.createElement('span');
            warning.className = 'text-danger';
            warning.textContent = ui.partialFailureLabel;
            summary.append(document.createTextNode(' · '), warning);
        }
    };

    const updateVersions = function (components) {
        let latestApplication = '';
        components.forEach(function (component) {
            const updateAvailable = component.status === 'update_available'
                && typeof component.latest_version === 'string'
                && component.latest_version !== '';
            if (component.kind === 'application' && updateAvailable) {
                latestApplication = component.latest_version;
            }

            const row = moduleRows.get(String(component.package || ''));
            if (!(row instanceof HTMLElement)) {
                return;
            }

            const installed = row.querySelector('[data-component-installed-version]');
            if (installed instanceof HTMLElement && typeof component.installed_version === 'string') {
                installed.textContent = component.installed_version;
            }

            const update = row.querySelector('[data-component-update-version]');
            const latest = row.querySelector('[data-component-latest-version]');
            if (latest instanceof HTMLElement) {
                latest.textContent = updateAvailable ? component.latest_version : '';
            }
            if (update instanceof HTMLElement) {
                update.classList.toggle('d-none', !updateAvailable);
            }
        });

        if (applicationLatest instanceof HTMLElement) {
            applicationLatest.textContent = latestApplication;
        }
        if (applicationUpdate instanceof HTMLElement) {
            applicationUpdate.classList.toggle('d-none', latestApplication === '');
        }
    };

    const checkForUpdates = async function () {
        if (checkPath === '') {
            return;
        }

        panel.setAttribute('aria-busy', 'true');
        if (summary instanceof HTMLElement) {
            summary.textContent = ui.checkingLabel;
            summary.classList.remove('text-danger');
            summary.classList.add('text-body-secondary');
        }

        try {
            const response = await fetch(checkPath, {
                headers: {Accept: 'application/json'},
                credentials: 'same-origin',
                cache: 'no-store'
            });
            let payload;
            try {
                payload = await response.json();
            } catch (_error) {
                throw new Error(ui.failedLabel);
            }
            if (!response.ok || payload.ok !== true || !Array.isArray(payload.components)) {
                throw new Error(String(payload.message || ui.failedLabel));
            }

            updateVersions(payload.components);
            setSummary(payload);
        } catch (error) {
            if (summary instanceof HTMLElement) {
                summary.textContent = error instanceof Error ? error.message : ui.failedLabel;
                summary.classList.remove('text-body-secondary');
                summary.classList.add('text-danger');
            }
        } finally {
            panel.removeAttribute('aria-busy');
        }
    };

    checkForUpdates();
}());
</script>
