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
 * HR: Administratorski Setup prikazuje stanje modula i razdvaja obične
 *     promjene stanja i migracije od privilegiranih Composer radnji.
 * EN: The administrator Setup screen shows module state and separates ordinary
 *     state changes and migrations from privileged Composer operations.
 */
final readonly class SetupController
{
    /** HR: Prima isključivo servise potrebne Setup ekranu. EN: Receives only services required by the Setup screen. */
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
     * HR: Prikazuje module, dijagnostiku prava i CLI zamjenske naredbe.
     * EN: Shows modules, permission diagnostics, and CLI fallback commands.
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
     * HR: Izvršava samo dopuštenu radnju; paketne promjene prolaze kroz helper,
     *     a enable/disable ostaju dostupni bez FPM-a ako je stanje zapisivo.
     * EN: Executes only an allowed action; package changes use the helper, while
     *     enable/disable remain available without FPM when state is writable.
     */
    public function change(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $body = $this->body($request);
            $actionValue = $body['action'] ?? '';
            $action = is_string($actionValue) ? strtolower(trim($actionValue)) : '';
            $diagnostics = $this->diagnostics->report();
            if (in_array($action, ['application-update-check', 'application-update-start'], true)) {
                $message = $this->applicationUpdate(
                    $action,
                    $diagnostics['application_updates_allowed'],
                    $body,
                );
            } elseif ($action === 'language-add') {
                $message = $this->addLanguage(
                    $request,
                    $diagnostics['package_changes_allowed'],
                    !empty($body['replace']),
                );
            } elseif (
                in_array($action, [
                'language-install', 'language-enable', 'language-disable', 'language-remove',
                ], true)
            ) {
                $message = $this->changeLanguage($action, $body, $diagnostics);
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
     * HR: Vraća samo javno siguran status pozadinske nadogradnje za administratorski prikaz napretka.
     * EN: Returns only the disclosure-safe background-update status for the administrator progress screen.
     */
    public function applicationUpdateStatus(): ResponseInterface
    {
        return $this->responses->json(
            ['ok' => true, 'update' => $this->updates->status()],
            headers: ['Cache-Control' => 'no-store, max-age=0'],
        );
    }

    /**
     * HR: Mijenja stanje već instaliranog modula bez Composer radnje.
     * EN: Changes an installed module state without a Composer operation.
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
     * HR: Odbija paketnu promjenu kada potpuna dijagnostika nije prošla.
     * EN: Rejects a package change unless the complete diagnostic has passed.
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
     * HR: Najprije privilegirano dodaje samo tagirani paket, zatim kao
     *     ograničeni web korisnik instalira migracije i po izboru vraća podatke.
     * EN: First adds only the tagged package through the privileged helper,
     *     then installs migrations and optionally restores data as the limited web user.
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
     * HR: Kao ograničeni web korisnik prvo sprema podatke i uklanja shemu,
     *     a tek zatim helper uklanja paket iz aplikacijskog skupa.
     * EN: As the limited web user, first backs up data and removes schema,
     *     then asks the helper to remove the package from the application set.
     */
    private function removeModule(string $slug, bool $allowed): string
    {
        $this->assertPackageChangesAllowed($allowed);
        $this->modules->removePrepared($slug);
        try {
            $this->gateway->execute('package-uninstall', ['module' => $slug]);
        } catch (Throwable $throwable) {
            // HR: Vraća cijeli modul ako privilegirana paketna radnja zakaže
            //     nakon što je web sloj već izradio kopiju i uklonio shemu.
            // EN: Restores the complete module if the privileged package action
            //     fails after the web layer already backed up and removed schema.
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
     * HR: Provjerava upload prije nego ga preda privilegiranom workeru.
     * EN: Validates an upload before handing it to the privileged worker.
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
            return $this->gateway->execute('language-add', ['file' => $file, 'replace' => $replace]);
        } catch (Throwable $throwable) {
            $this->requests->discardLanguagePack($file);
            throw $throwable;
        }
    }

    /**
     * HR: Ograničava promjene jezika na katalog i potreban instalacijski način.
     * EN: Restricts language changes to the catalogue and appropriate installation mode.
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

        if (
            in_array($action, ['language-install', 'language-remove'], true)
            && empty($diagnostics['package_changes_allowed'])
        ) {
            throw new RuntimeException(__('Instaliranje i uklanjanje jezika zahtijeva namjenski FPM ili CLI.'));
        }

        if (
            in_array($action, ['language-enable', 'language-disable'], true)
            && empty($diagnostics['state_changes_allowed'])
        ) {
            throw new RuntimeException(__('Promjena jezika nije dostupna zbog prava pisanja.'));
        }

        if (!empty($diagnostics['package_changes_allowed'])) {
            return $this->gateway->execute($action, [
                'locale' => $locale,
                'replace' => !empty($body['replace']),
            ]);
        }

        $this->languages->setActive($locale, $action === 'language-enable');
        return $action === 'language-enable'
        ? __('Jezik je uključen.')
        : __('Jezik je isključen.');
    }

    /**
     * HR: Provjeru ili pozadinsko pokretanje updatera predaje isključivo
     *     ograničenom helperu nakon potpune FPM dijagnostike.
     * EN: Submits an update check or background update start only to the
     *     restricted helper after complete FPM diagnostics.
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

        return $this->gateway->execute($action, [
            'locale' => str_starts_with(strtolower($this->translator->getLocale()), 'en') ? 'en' : 'hr',
            'tag' => $tag,
        ]);
    }

    /**
     * HR: Normalizira PSR-7 tijelo forme.
     * EN: Normalizes a PSR-7 form body.
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

    /** HR: Vraća named Setup putanju uz siguran fallback. EN: Returns the named Setup path with a safe fallback. */
    private function path(): string
    {
        return $this->urls->namedRouteExists('setup.index')
        ? $this->urls->getPathFor('setup.index')
        : '/settings/setup';
    }

    /**
     * HR: Vraća postojeći administratorski endpoint za automatsku provjeru izdanja.
     * EN: Returns the existing administrator endpoint for automatic release checks.
     */
    private function automaticUpdateCheckPath(): string
    {
        return $this->urls->namedRouteExists('settings.check-updates.status')
        ? $this->urls->getPathFor('settings.check-updates.status')
        : '/settings/check-updates/status';
    }

    /** HR: Vraća putanju sigurnog statusa updatera. EN: Returns the safe updater-status path. */
    private function applicationUpdateStatusPath(): string
    {
        return $this->urls->namedRouteExists('setup.application-update-status')
        ? $this->urls->getPathFor('setup.application-update-status')
        : '/settings/setup/application-update-status';
    }
}
