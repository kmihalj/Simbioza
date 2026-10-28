<?php

declare(strict_types=1);

namespace App\Controllers;

use AaiEduHr\HeartPhrameModuleMenu\Service\ComponentUpdateService;
use App\Localization\LanguagePackManager;
use App\Localization\RepositoryLanguageManager;
use App\Module\ModuleCatalog;
use App\Module\ModuleLifecycleManager;
use App\Setup\ApplicationUpdateStatusStore;
use App\Setup\SetupDiagnostics;
use App\Setup\SetupGateway;
use App\Setup\SetupRequestStore;
use HeartPhrame\Alert\Alert;
use HeartPhrame\Alert\AlertHandler;
use HeartPhrame\CodeBook\AlertLevelEnum;
use HeartPhrame\Http\ResponseFactory;
use HeartPhrame\Localization\TranslatorInterface;
use HeartPhrame\Routing\UrlGenerator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

/**
 * EN: The administrator Setup screen shows module state and separates ordinary
 *     state changes and migrations from privileged Composer operations.
 *
 * HR: Administratorski Setup prikazuje stanje modula i razdvaja obične
 *     promjene stanja i migracije od privilegiranih Composer radnji.
 */
final readonly class SetupController
{
    /**
     * EN: Receives only services required by the Setup screen.
     *
     * HR: Prima isključivo servise potrebne Setup ekranu.
     */
    public function __construct(
        private ResponseFactory $responses,
        private ModuleLifecycleManager $modules,
        private ModuleCatalog $catalog,
        private SetupDiagnostics $diagnostics,
        private SetupGateway $gateway,
        private SetupRequestStore $requests,
        private LanguagePackManager $languages,
        private RepositoryLanguageManager $repositoryLanguages,
        private ComponentUpdateService $componentUpdates,
        private ApplicationUpdateStatusStore $updates,
        private AlertHandler $alerts,
        private UrlGenerator $urls,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * EN: Shows modules, permission diagnostics, and CLI fallback commands.
     *
     * HR: Prikazuje module, dijagnostiku prava i CLI zamjenske naredbe.
     */
    public function index(): ResponseInterface
    {
        return $this->responses->view('setup/index', [
            'title' => __('Setup i moduli'),
            'modules' => $this->modules->status(),
            'definitions' => $this->catalog->definitions(),
            'diagnostics' => $this->diagnostics->report(),
            'languages' => $this->languages->status(),
            'repositoryLanguages' => $this->repositoryLanguages->status(),
            'componentUpdates' => $this->componentUpdates->status(),
            'applicationUpdate' => $this->updates->status(),
            'applicationUpdateStatusPath' => $this->applicationUpdateStatusPath(),
            'automaticUpdateCheckPath' => $this->automaticUpdateCheckPath(),
            'actionPath' => $this->path(),
            'locale' => $this->translator->getLocale(),
            'settingsMenuActiveSection' => 'setup',
            'themeHero' => [
                'is_home' => false,
                'title' => __('Setup i moduli'),
                'subtitle' => __('Upravljanje sastavom instalacije i provjera prava datoteka.'),
            ],
        ]);
    }

    /**
     * EN: The admin page remains available even when the optional module is not loaded.
     *
     * HR: Administratorska stranica ostaje dostupna i kad opcionalni modul nije učitan.
     */
    public function accessibility(): ResponseInterface
    {
        $module = null;
        foreach ($this->modules->status() as $candidate) {
            if ($candidate['slug'] === 'accessibility') {
                $module = $candidate;
                break;
            }
        }

        return $this->responses->view('setup/accessibility', [
            'title' => __('Pristupačnost'),
            'module' => $module,
            'stateChangesAllowed' => $this->diagnostics->report()['state_changes_allowed'],
            'actionPath' => $this->accessibilityPath(),
            'settingsMenuActiveSection' => 'setup',
            'themeHero' => [
                'is_home' => false,
                'title' => __('Pristupačnost'),
            ],
        ]);
    }

    /**
     * EN: Changes only accessibility module state under the existing CSRF and admin guards.
     *
     * HR: Mijenja isključivo stanje modula pristupačnosti, uz postojeću CSRF i admin zaštitu.
     */
    public function changeAccessibility(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $action = $this->body($request)['action'] ?? null;
            if (!is_string($action) || !in_array($action, ['enable', 'disable'], true)) {
                throw new RuntimeException(__('Odabrana Setup radnja nije dopuštena.'));
            }

            $message = $this->changeState(
                'accessibility',
                $action === 'enable',
                $this->diagnostics->report()['state_changes_allowed'],
            );
            $this->alerts->add(new Alert($message, AlertLevelEnum::Success));
        } catch (Throwable $throwable) {
            $this->alerts->add(new Alert($throwable->getMessage(), AlertLevelEnum::Danger));
        }

        return $this->responses->redirect($this->accessibilityPath());
    }

    /**
     * EN: Executes only an allowed action; package changes use the helper, while
     *     enable/disable remain available without FPM when state is writable.
     *
     * HR: Izvršava samo dopuštenu radnju; paketne promjene prolaze kroz helper,
     *     a enable/disable ostaju dostupni bez FPM-a ako je stanje zapisivo.
     */
    public function change(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $body = $this->body($request);
            $actionValue = $body['action'] ?? '';
            $action = is_string($actionValue) ? strtolower(trim($actionValue)) : '';
            $diagnostics = $this->diagnostics->report();
            if ($action === 'language-update-check') {
                try {
                    $status = $this->repositoryLanguages->status(true);
                } catch (RuntimeException $error) {
                    throw new RuntimeException(
                        __('Katalog jezika nije moguće osvježiti; dostupnost nadogradnji nije potvrđena.'),
                        0,
                        $error,
                    );
                }

                $updates = count(array_filter(
                    $status,
                    static fn(array $row): bool => $row['installed'] && $row['update_available'],
                ));
                $unknown = count(array_filter(
                    $status,
                    static fn(array $row): bool => $row['installed'] && !$row['revision_known'],
                ));
                $message = sprintf(
                    __('Provjera jezika je dovršena. Dostupne nadogradnje: %d. Neevidentirane lokalne revizije: %d.'),
                    $updates,
                    $unknown,
                );
            } elseif (in_array($action, ['application-update-check', 'application-update-start'], true)) {
                $message = $this->applicationUpdate(
                    $action,
                    $diagnostics['application_updates_allowed'],
                    $body,
                );
            } elseif ($action === 'language-add') {
                $message = $this->addLanguage(
                    $request,
                    $diagnostics['language_changes_allowed'],
                    !empty($body['replace']),
                );
            } elseif (
                in_array($action, [
                'language-install', 'language-enable', 'language-disable', 'language-remove',
                ], true)
            ) {
                $message = $this->changeLanguage($action, $body, $diagnostics);
            } elseif ($action === 'language-install-selected') {
                $message = $this->installLanguages($body, $diagnostics);
            } else {
                $slugValue = $body['module'] ?? '';
                $slug = is_string($slugValue) ? strtolower(trim($slugValue)) : '';
                if (!in_array($slug, $this->catalog->optionalSlugs(), true)) {
                    throw new RuntimeException(__('Odabran je nepoznat ili obvezan modul.'));
                }

                $message = match ($action) {
                    'enable' => $this->changeState($slug, true, $diagnostics['state_changes_allowed']),
                    'disable' => $this->changeState($slug, false, $diagnostics['state_changes_allowed']),
                    'add' => $this->addModule(
                        $slug,
                        $diagnostics['package_changes_allowed'],
                        !empty($body['restore']),
                    ),
                    'remove' => $this->removeModule($slug, $diagnostics['package_changes_allowed']),
                    default => throw new RuntimeException(__('Odabrana Setup radnja nije dopuštena.')),
                };
            }

            $this->alerts->add(new Alert($message, AlertLevelEnum::Success));
        } catch (Throwable $throwable) {
            $this->alerts->add(new Alert($throwable->getMessage(), AlertLevelEnum::Danger));
        }

        return $this->responses->redirect($this->path());
    }

    /**
     * EN: Returns only the disclosure-safe background-update status for the administrator progress screen.
     *
     * HR: Vraća samo javno siguran status pozadinske nadogradnje za administratorski prikaz napretka.
     */
    public function applicationUpdateStatus(): ResponseInterface
    {
        return $this->responses->json(
            ['ok' => true, 'update' => $this->updates->status()],
            headers: ['Cache-Control' => 'no-store, max-age=0'],
        );
    }

    /**
     * EN: Changes an installed module state without a Composer operation.
     *
     * HR: Mijenja stanje već instaliranog modula bez Composer radnje.
     */
    private function changeState(string $slug, bool $enable, bool $allowed): string
    {
        if (!$allowed) {
            throw new RuntimeException(__('Datoteka stanja modula nije zapisiva za web proces.'));
        }

        if ($enable) {
            $this->modules->enable($slug);
            return __('Modul je uključen.');
        }

        $this->modules->disable($slug);
        return __('Modul je isključen; podaci su sačuvani.');
    }

    /**
     * EN: Rejects a package change unless the complete diagnostic has passed.
     *
     * HR: Odbija paketnu promjenu kada potpuna dijagnostika nije prošla.
     */
    private function assertPackageChangesAllowed(bool $allowed): void
    {
        if (!$allowed) {
            throw new RuntimeException(__(
                'Instalacija i uklanjanje iz GUI-ja nisu dostupni. Upotrijebite prikazanu CLI naredbu.',
            ));
        }
    }

    /**
     * EN: First adds only the tagged package through the privileged helper,
     *     then installs migrations and optionally restores data as the limited web user.
     *
     * HR: Najprije privilegirano dodaje samo tagirani paket, zatim kao
     *     ograničeni web korisnik instalira migracije i po izboru vraća podatke.
     */
    private function addModule(string $slug, bool $allowed, bool $restore): string
    {
        $this->assertPackageChangesAllowed($allowed);
        $this->gateway->execute('package-install', ['module' => $slug]);
        $archive = $this->modules->addPrepared($slug, $restore);

        return $archive === null
        ? __('Modul je instaliran i uključen.')
        : __('Modul je instaliran, podaci su vraćeni i modul je uključen.');
    }

    /**
     * EN: As the limited web user, first backs up data and removes schema,
     *     then asks the helper to remove the package from the application set.
     *
     * HR: Kao ograničeni web korisnik prvo sprema podatke i uklanja shemu,
     *     a tek zatim helper uklanja paket iz aplikacijskog skupa.
     */
    private function removeModule(string $slug, bool $allowed): string
    {
        $this->assertPackageChangesAllowed($allowed);
        $this->modules->removePrepared($slug);
        try {
            $this->gateway->execute('package-uninstall', ['module' => $slug]);
        } catch (Throwable $throwable) {
            // EN: Restores the complete module if the privileged package action
            //     fails after the web layer already backed up and removed schema.
            //
            // HR: Vraća cijeli modul ako privilegirana paketna radnja zakaže
            //     nakon što je web sloj već izradio kopiju i uklonio shemu.
            $this->gateway->execute('package-install', ['module' => $slug]);
            $this->modules->addPrepared($slug, true);
            throw new RuntimeException(
                __('Uklanjanje paketa nije uspjelo; modul i podaci su vraćeni.'),
                0,
                $throwable,
            );
        }

        return __('Modul je sigurnosno kopiran i deinstaliran.');
    }

    /**
     * EN: Validates a data-only upload before local atomic installation.
     *
     * HR: Provjerava podatkovni upload prije lokalne atomske instalacije.
     */
    private function addLanguage(ServerRequestInterface $request, bool $allowed, bool $replace): string
    {
        if (!$allowed) {
            throw new RuntimeException(__(
                'Dodavanje jezika iz GUI-ja nije dostupno. Upotrijebite prikazanu CLI naredbu.',
            ));
        }

        $upload = $request->getUploadedFiles()['language_pack'] ?? null;
        if (!$upload instanceof \Psr\Http\Message\UploadedFileInterface) {
            throw new RuntimeException(__('Odaberite JSON jezični paket.'));
        }

        $file = $this->requests->storeLanguagePack($upload);
        try {
            $this->languages->validate($this->requests->languagePackPath($file));
            $this->languages->install($this->requests->languagePackPath($file), $replace);
            return __('Jezični paket je instaliran.');
        } finally {
            $this->requests->discardLanguagePack($file);
        }
    }

    /**
     * EN: Restricts language changes to the catalogue and appropriate installation mode.
     *
     * HR: Ograničava promjene jezika na katalog i potreban instalacijski način.
     *
     * @param array<string,mixed> $body
     * @param array<string,mixed> $diagnostics
     */
    private function changeLanguage(string $action, array $body, array $diagnostics): string
    {
        $locale = $body['locale'] ?? null;
        if (
            !is_string($locale) || preg_match('/\A[a-z0-9]+(?:[-_][a-z0-9]+)*\z/D', $locale) !== 1
            || strlen($locale) > 32
        ) {
            throw new RuntimeException(__('Odabran je nepoznat jezik.'));
        }

        if (empty($diagnostics['language_changes_allowed'])) {
            throw new RuntimeException(__('Promjena jezika nije dostupna zbog prava pisanja.'));
        }

        // EN: JSON-only language changes need no privileged code-changing worker.
        //
        // HR: Jezične promjene samo u JSON-u ne trebaju privilegirani worker za promjenu koda.
        match ($action) {
            'language-install' => $this->repositoryLanguages->install($locale, !empty($body['replace'])),
            'language-remove' => $this->repositoryLanguages->uninstall($locale),
            'language-enable', 'language-disable' => $this->languages->setActive(
                $locale,
                $action === 'language-enable',
            ),
            default => throw new RuntimeException(__('Odabrana Setup radnja nije dopuštena.')),
        };

        return match ($action) {
            'language-install' => __('Jezični paket je instaliran.'),
            'language-enable' => __('Jezik je uključen.'),
            'language-disable' => __('Jezik je isključen.'),
            default => __('Jezični paket je uklonjen.'),
        };
    }

    /**
     * EN: Validates a multi-selection and installs it through one Setup request.
     *
     * HR: Provjerava višestruki odabir i instalira ga jednim Setup zahtjevom.
     *
     * @param array<string,mixed> $body
     * @param array<string,mixed> $diagnostics
     */
    private function installLanguages(array $body, array $diagnostics): string
    {
        if (empty($diagnostics['language_changes_allowed'])) {
            throw new RuntimeException(__('Promjena jezika nije dostupna zbog prava pisanja.'));
        }

        $requested = $body['locales'] ?? null;
        if (!is_array($requested) || $requested === [] || count($requested) > 512) {
            throw new RuntimeException(__('Odaberite barem jedan dostupan jezik.'));
        }

        $locales = [];
        foreach ($requested as $locale) {
            if (
                !is_string($locale)
                || preg_match('/\A[a-z0-9]+(?:[-_][a-z0-9]+)*\z/D', $locale) !== 1
                || strlen($locale) > 32
            ) {
                throw new RuntimeException(__('Odabran je nepoznat jezik.'));
            }

            $locales[$locale] = true;
        }

        $installed = array_keys($locales);
        $this->repositoryLanguages->installMany($installed);

        return sprintf(__('Instalirano je %d jezičnih paketa.'), count($installed));
    }

    /**
     * EN: Submits an update check or background update start only to the
     *     restricted helper after complete FPM diagnostics.
     *
     * HR: Provjeru ili pozadinsko pokretanje updatera predaje isključivo
     *     ograničenom helperu nakon potpune FPM dijagnostike.
     *
     * @param array<string,mixed> $body
     */
    private function applicationUpdate(string $action, bool $allowed, array $body): string
    {
        if (!$allowed) {
            throw new RuntimeException(__(
                'Nadogradnja iz GUI-ja nije dostupna. Upotrijebite prikazanu CLI naredbu.',
            ));
        }

        $tag = is_string($body['tag'] ?? null) ? trim($body['tag']) : '';
        if ($tag !== '' && preg_match('/\A(?:v)?\d+\.\d+\.\d+\z/D', $tag) !== 1) {
            throw new RuntimeException(__('Ciljna verzija mora biti stabilni tag oblika 1.2.3.'));
        }

        $result = $this->gateway->execute($action, [
            'locale' => str_starts_with(strtolower($this->translator->getLocale()), 'en') ? 'en' : 'hr',
            'tag' => $tag,
        ]);

        return $action === 'application-update-start'
        ? __('Nadogradnja aplikacije pokrenuta je u pozadini.')
        : $result;
    }

    /**
     * EN: Normalizes a PSR-7 form body.
     *
     * HR: Normalizira PSR-7 tijelo forme.
     *
     * @return array<string,mixed>
     */
    private function body(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        if (!is_array($body)) {
            return [];
        }

        $normalized = [];
        foreach ($body as $key => $value) {
            if (is_string($key)) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * EN: Returns the named Setup path with a safe fallback.
     *
     * HR: Vraća named Setup putanju uz siguran fallback.
     */
    private function path(): string
    {
        return $this->urls->namedRouteExists('setup.index')
        ? $this->urls->getPathFor('setup.index')
        : '/settings/setup';
    }

    /**
     * EN: Returns the permanent admin toggle path.
     *
     * HR: Vraća putanju stalne administracijske sklopke.
     */
    private function accessibilityPath(): string
    {
        return $this->urls->namedRouteExists('accessibility.settings')
        ? $this->urls->getPathFor('accessibility.settings')
        : '/settings/accessibility';
    }

    /**
     * EN: Returns the existing administrator endpoint for automatic release checks.
     *
     * HR: Vraća postojeći administratorski endpoint za automatsku provjeru izdanja.
     */
    private function automaticUpdateCheckPath(): string
    {
        return $this->urls->namedRouteExists('settings.check-updates.status')
        ? $this->urls->getPathFor('settings.check-updates.status')
        : '/settings/check-updates/status';
    }

    /**
     * EN: Returns the safe updater-status path.
     *
     * HR: Vraća putanju sigurnog statusa updatera.
     */
    private function applicationUpdateStatusPath(): string
    {
        return $this->urls->namedRouteExists('setup.application-update-status')
        ? $this->urls->getPathFor('setup.application-update-status')
        : '/settings/setup/application-update-status';
    }
}
