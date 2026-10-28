<?php

declare(strict_types=1);

namespace App\Controllers;

use AaiEduHr\HeartPhrameModuleEditorHtml\ModuleEditorHtml;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorPagePermissions;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorService;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleSurvey\Contract\SurveyActorProviderInterface;
use AaiEduHr\HeartPhrameModuleSurvey\Service\SurveyAdmission;
use AaiEduHr\HeartPhrameModuleSurvey\Service\SurveyAuthorization;
use AaiEduHr\HeartPhrameModuleSurvey\Service\SurveyError;
use HeartPhrame\Authn\AuthnHandlerInterface;
use HeartPhrame\Config\ConfigInterface;
use HeartPhrame\Http\ResponseFactory;
use HeartPhrame\Localization\TranslatorInterface;
use HeartPhrame\Routing\UrlGenerator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * EN: Public entry points use published content and owning-module access checks, never author drafts.
 *
 * HR: Javni ulazi koriste objavljeni sadržaj i provjere matičnih modula, nikada autorske nacrte.
 */
final readonly class ContentHubController
{
    /**
     * EN: Optional domain services stay lazy so minimal installations need no absent packages.
     *
     * HR: Opcionalni domenski servisi ostaju odgođeni pa minimalne instalacije ne trebaju odsutne pakete.
     */
    public function __construct(
        private ContainerInterface $container,
        private ConfigInterface $config,
        private ResponseFactory $responses,
        private UrlGenerator $urls,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * EN: Selects a useful focused-product start page without changing full Wiki installations.
     *
     * HR: Bira korisnu početnu stranicu samostalnog alata bez izmjene cjelovitih Wiki instalacija.
     *
     * @param list<string> $enabled
     */
    public static function homeRoute(array $enabled): ?string
    {
        if (in_array('aaieduhr/simbioza-module-workspace', $enabled, true)) {
            return null;
        }

        foreach (
            [
            'aaieduhr/heartphrame-module-survey' => 'content.surveys',
            'aaieduhr/heartphrame-module-calendar' => 'calendar.index',
            'aaieduhr/heartphrame-module-editor-html' => 'content.pages',
            ] as $package => $route
        ) {
            if (in_array($package, $enabled, true)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * EN: Lists standalone published HTML through the same public reader used by direct document links.
     *
     * HR: Popisuje samostalni objavljeni HTML istim javnim čitačem koji koriste izravne poveznice dokumenata.
     */
    public function pages(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->enabled(ModuleEditorHtml::PACKAGE_NAME)) {
            return $this->responses->text(__('Stranica nije pronađena.'), 404);
        }

        $page = $this->pageNumber($request);
        $editor = $this->service(EditorService::class);
        $rows = $this->service(EditorPagePermissions::class)->publishedPageKeys(($page - 1) * 24, 25);
        $items = [];
        foreach (array_slice($rows, 0, 24) as $key) {
            $document = $editor->loadPublic($key, $this->translator->getLocale());
            if ($document !== null) {
                $items[] = ['title' => $document->title, 'url' => $this->urls->getPathFor(
                    'editor-html.view',
                    [],
                    ['document' => $document->id, 'lang' => $this->translator->getLocale()],
                )];
            }
        }

        $user = $this->service(AuthnHandlerInterface::class)->userData();
        return $this->render(
            'HTML stranice',
            'Odaberite stranicu koju želite pročitati.',
            $items,
            (is_array($user) && (bool)($user['is_admin'] ?? false)) || $editor->canCreatePage()
                ? 'editor-html.index' : null,
            $page,
            count($rows) > 24,
            'content.pages',
        );
    }

    /**
     * EN: Lists published standalone surveys only after admission checks; login does not expose other audiences.
     *
     * HR: Popisuje objavljene samostalne ankete tek nakon provjere pristupa; prijava ne otkriva druge publike.
     */
    public function surveys(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->enabled('aaieduhr/heartphrame-module-survey')) {
            return $this->responses->text(__('Stranica nije pronađena.'), 404);
        }

        $page = $this->pageNumber($request);
        try {
            $actor = $this->service(SurveyActorProviderInterface::class)->resolve();
        } catch (SurveyError) {
            $actor = null;
        }

        $admission = $this->service(SurveyAdmission::class);
        $db = $this->service(Database::class);
        $rows = $db->fetchAll('SELECT s.uuid FROM survey_surveys s WHERE s.published_revision_uuid IS NOT NULL '
            . "AND s.state IN ('published','closed') AND NOT EXISTS "
            . '(SELECT 1 FROM survey_placements p WHERE p.survey_uuid=s.uuid) ORDER BY s.uuid LIMIT 25 OFFSET '
            . (($page - 1) * 24));
        $items = [];
        foreach (array_slice($rows, 0, 24) as $row) {
            try {
                if (!is_string($row['uuid'] ?? null)) {
                    continue;
                }

                $uuid = $row['uuid'];
                $snapshot = $admission->snapshot($uuid, $actor);
                if (!$admission->eligible($snapshot, $actor)) {
                    continue;
                }

                $locale = $this->translator->getLocale();
                $available = $this->value($snapshot, ['definition', 'settings', 'enabled_languages']);
                if (!is_array($available) || !in_array($locale, $available, true)) {
                    $primary = $this->value($snapshot, ['survey', 'primary_language']);
                    if (!is_string($primary)) {
                        continue;
                    }

                    $locale = $primary;
                }

                $title = $this->value($snapshot, ['definition', 'texts', $locale, $uuid, 'title']);
                if (!is_string($title)) {
                    continue;
                }

                $items[] = ['title' => $title,
                    'url' => $this->urls->getPathFor('survey.participate', ['survey' => $uuid], ['lang' => $locale])];
            } catch (SurveyError $error) {
                if ($error->status !== 404) {
                    throw $error;
                }
            }
        }

        $access = $this->service(SurveyAuthorization::class);
        $manage = $actor !== null && ($access->canCreate($actor) || $access->grants($actor) !== []);
        return $this->render(
            'Ankete',
            'Odaberite anketu koju želite ispuniti.',
            $items,
            $manage ? 'survey.index' : null,
            $page,
            count($rows) > 24,
            'content.surveys',
        );
    }

    /**
     * EN: Checks the active installation selection before requesting any optional service.
     *
     * HR: Provjerava aktivni odabir instalacije prije dohvata opcionalnog servisa.
     */
    private function enabled(string $package): bool
    {
        return in_array(
            $package,
            $this->config->getAsArrayWithValuesAsNonEmptyStrings('app.modules.enabled') ?? [],
            true,
        );
    }

    /**
     * EN: Resolves an optional service only when used and validates its registered implementation.
     *
     * HR: Dohvaća opcionalni servis tek kada se koristi i provjerava registriranu implementaciju.
     *
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    private function service(string $class): object
    {
        $service = $this->container->get($class);
        if (!$service instanceof $class) {
            throw new \LogicException('Invalid content catalogue service: ' . $class);
        }

        return $service;
    }

    /**
     * EN: Reads versioned snapshot data without coercing malformed fields into public titles or URLs.
     *
     * HR: Čita podatke verzionirane snimke bez pretvaranja nevaljanih polja u javne naslove ili URL-ove.
     *
     * @param array<string,mixed> $snapshot
     * @param list<string> $path
     */
    private function value(array $snapshot, array $path): mixed
    {
        $value = $snapshot;
        foreach ($path as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }

            $value = $value[$key];
        }

        return $value;
    }

    /**
     * EN: Bounds public pagination and never accepts unbounded offsets.
     *
     * HR: Ograničava javnu paginaciju i ne prihvaća neograničene pomake.
     */
    private function pageNumber(ServerRequestInterface $request): int
    {
        $value = $request->getQueryParams()['page'] ?? '1';
        return is_scalar($value) && ctype_digit((string)$value) ? max(1, min(10000, (int)$value)) : 1;
    }

    /**
     * EN: Uses the normal theme and consistent cards, navigation and permission-aware editing entry.
     *
     * HR: Koristi uobičajenu temu i ujednačene kartice, navigaciju te ulaz u uređivanje prema ovlastima.
     *
     * @param list<array{title:string,url:string}> $items
     */
    private function render(
        string $title,
        string $description,
        array $items,
        ?string $manage,
        int $page,
        bool $next,
        string $route,
    ): ResponseInterface {
        return $this->responses->view('home/content', [
            'title' => $title, 'description' => $description, 'items' => $items,
            'manage' => $manage === null ? null : $this->urls->getPathFor($manage),
            'page' => $page, 'hasNext' => $next, 'route' => $route,
            'themeHero' => ['title' => __($title), 'subtitle' => __($description), 'is_home' => false],
        ]);
    }
}
