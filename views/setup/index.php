<?php

declare(strict_types=1);

// phpcs:disable Generic.Files.LineLength.TooLong -- Mixed PHP/HTML is clearer without artificial tag wrapping.

/**
 * @var \HeartPhrame\View\View $this
 * @var string $title
 * @var list<array{slug:string,package:string,label_hr:string,label_en:string,optional:bool,recommended:bool,package_installed:bool,enabled:bool,schema_installed:bool,backup_available:bool,state:string}> $modules
 * @var array<string,array{package:string,label_hr:string,label_en:string,optional:bool,recommended:bool,dependencies:list<string>,migrations:list<string>,tables:list<string>}> $definitions
 * @var array{is_fpm:bool,helper_ready:bool,state_changes_allowed:bool,package_changes_allowed:bool,application_updates_allowed:bool,checks:list<array{id:string,label_hr:string,label_en:string,passed:bool,required:bool,detail:string}>} $diagnostics
 * @var list<array{locale:string,native_name:string,keys:int,reference_keys:int,coverage:float}> $languages
 * @var array{components:list<array{package:string,name:string,kind:string,installed_version:string,latest_version:?string,status:string}>,checked_at:?int,failures:int,updates:int} $componentUpdates
 * @var array{state:string,stage:string,progress:?int,started_at:?string,finished_at:?string,pid:?int,message:string,current_version:string} $applicationUpdate
 * @var string $applicationUpdateStatusPath
 * @var string $automaticUpdateCheckPath
 * @var string $actionPath
 * @var string $settingsMenuActiveSection
 * @var object|null $menuRenderer
 */

$localeValue = isset($locale) && is_string($locale) ? strtolower($locale) : 'hr';
$english = str_starts_with($localeValue, 'en');
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
$formatTimestamp = static function (?string $value) use ($english): string {
    if ($value === null || $value === '') {
        return '—';
    }
    try {
        return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone(date_default_timezone_get()))
            ->format($english ? 'Y-m-d H:i:s' : 'd.m.Y. H:i:s');
    } catch (Throwable) {
        return $value;
    }
};
$settingsMenu = '';
if (isset($menuRenderer) && is_object($menuRenderer) && is_callable([$menuRenderer, 'renderSettingsMenu'])) {
    $renderedSettingsMenu = $menuRenderer->renderSettingsMenu($settingsMenuActiveSection);
    $settingsMenu = is_string($renderedSettingsMenu) ? $renderedSettingsMenu : '';
}
$componentUpdateUi = json_encode(
    [
        'checkingLabel' => __('Provjeravam dostupna izdanja...'),
        'lastCheckedLabel' => __('Zadnja provjera:'),
        'availableUpdatesLabel' => __('Dostupna ažuriranja: %d'),
        'noUpdatesLabel' => __('Nema novih kompatibilnih izdanja.'),
        'partialFailureLabel' => __('Neke komponente nije bilo moguće provjeriti.'),
        'failedLabel' => __('Provjera izdanja nije uspjela.'),
        'locale' => $english ? 'en' : 'hr',
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

    .setup-update-overlay {
        backdrop-filter: blur(0.35rem);
        background: color-mix(in srgb, var(--bs-body-bg) 88%, transparent);
        inset: 0;
        overflow-y: auto;
        padding: 1rem;
        z-index: 2000;
    }

    .setup-update-dialog {
        max-width: 44rem;
        width: 100%;
    }

    .setup-update-progress {
        height: 1.25rem;
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

    <main class="col-12 <?= $settingsMenu !== '' ? 'col-xl-9' : '' ?>">
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
                                    <td><?= $this->escape($english ? $check['label_en'] : $check['label_hr']) ?></td>
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
                                <?= $this->escape(date($english ? 'Y-m-d H:i:s' : 'd.m.Y. H:i:s', $componentUpdates['checked_at'])) ?>
                            <?php else : ?>
                                <?= $this->escape(__('Provjeravam dostupna izdanja...')) ?>
                            <?php endif; ?>
                        </div>
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
                            $label = $english ? $module['label_en'] : $module['label_hr'];
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
                        <article
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
                                <div class="setup-module-cell" role="cell" data-label="<?= $this->escape(__('Stanje')) ?>">
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
                        </article>
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

                <div class="table-responsive mb-4">
                    <table class="table table-sm align-middle">
                        <thead><tr>
                            <th><?= $this->escape(__('Jezik')) ?></th>
                            <th><?= $this->escape(__('Naziv')) ?></th>
                            <th><?= $this->escape(__('Pokrivenost')) ?></th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($languages as $language) : ?>
                            <tr>
                                <td><code><?= $this->escape($language['locale']) ?></code></td>
                                <td><?= $this->escape($language['native_name']) ?></td>
                                <td><?= $this->escape(number_format($language['coverage'], 2)) ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($diagnostics['package_changes_allowed']) : ?>
                    <form method="post" action="<?= $this->escape($actionPath) ?>" enctype="multipart/form-data" class="row g-3 align-items-end">
                    <?= $this->csrfHandler->generateCsrfTokenInputField() ?>
                        <input type="hidden" name="action" value="language-add">
                        <div class="col-12 col-lg-7">
                            <label class="form-label" for="setup-language-pack"><?= $this->escape(__('JSON jezični paket')) ?></label>
                            <input class="form-control" id="setup-language-pack" type="file" name="language_pack" accept="application/json,.json" required>
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
                        <code class="d-block user-select-all text-break">vendor/bin/hph languages add /putanja/jezik.json</code>
                        <div class="small text-body-secondary mt-2">
                    <?= $this->escape(__('Predložak za prijevod izradite naredbom:')) ?>
                            <code class="user-select-all">vendor/bin/hph languages template de --source=en</code>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</div>
<div
    class="setup-update-overlay position-fixed align-items-center justify-content-center <?= $updateRunning ? 'd-flex' : 'd-none' ?>"
    data-setup-update-overlay
    data-initial-progress="<?= $applicationUpdate['progress'] === null ? '' : $this->escape((string)$applicationUpdate['progress']) ?>"
    data-initial-stage="<?= $this->escape($applicationUpdate['stage']) ?>"
    data-started-at="<?= $this->escape($applicationUpdate['started_at'] ?? '') ?>"
    role="dialog"
    aria-modal="true"
    aria-labelledby="setup-update-title"
    aria-describedby="setup-update-stage"
>
    <div class="setup-update-dialog card border-primary shadow-lg">
        <div class="card-body p-4 p-lg-5">
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="spinner-border text-primary" data-setup-update-spinner aria-hidden="true"></div>
                <div>
                    <h2 class="h3 mb-1" id="setup-update-title"><?= $this->escape(__('Nadogradnja aplikacije je u tijeku')) ?></h2>
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
        </div>
    </div>
</div>
<script>
(function () {
    'use strict';

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
    const updateProgress = document.querySelector('[data-setup-update-progress]');
    const updateProgressBar = document.querySelector('[data-setup-update-progress-bar]');
    const updatePercent = document.querySelector('[data-setup-update-percent]');
    const updateElapsed = document.querySelector('[data-setup-update-elapsed]');
    const updateSpinner = document.querySelector('[data-setup-update-spinner]');
    const updateError = document.querySelector('[data-setup-update-error]');
    const updateActions = document.querySelector('[data-setup-update-actions]');
    const updateReload = document.querySelector('[data-setup-update-reload]');
    const initialStartedAt = updateOverlay instanceof HTMLElement && updateOverlay.dataset.startedAt !== ''
        ? Date.parse(updateOverlay.dataset.startedAt || '')
        : Date.now();
    let updateStartedAt = Number.isFinite(initialStartedAt) ? initialStartedAt : Date.now();
    let updatePolling = false;
    let updateElapsedTimer = null;

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

        updateOverlay.classList.remove('d-none');
        updateOverlay.classList.add('d-flex');
        document.body.classList.add('overflow-hidden');
        if (updateStage instanceof HTMLElement) {
            updateStage.textContent = state === 'success' ? updateUi.successLabel : stageLabel(stage);
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
