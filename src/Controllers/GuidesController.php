<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Guides\BundledManual;
use HeartPhrame\Config\ConfigInterface;
use HeartPhrame\Http\ResponseFactory;
use HeartPhrame\Localization\TranslatorInterface;
use HeartPhrame\Routing\UrlGenerator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * EN: Public read-only guides without optional content modules; Wiki installations keep their native pages.
 *
 * HR: Javne upute samo za čitanje bez opcionalnih modula sadržaja; Wiki instalacije zadržavaju izvorne stranice.
 */
final readonly class GuidesController
{
    /**
     * EN: Requires only framework services and the release's public manual reader.
     *
     * HR: Zahtijeva samo servise frameworka i čitač javnog priručnika izdanja.
     */
    public function __construct(
        private BundledManual $manual,
        private ConfigInterface $config,
        private ResponseFactory $responses,
        private UrlGenerator $urls,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * EN: Opens the manual's existing Simbioza introduction.
     *
     * HR: Otvara postojeću uvodnu stranicu priručnika Simbioze.
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responses->redirect($this->urls->getPathFor(
            'guides.page',
            ['slug' => 'simbioza'],
            ['lang' => $this->locale($request)],
        ));
    }

    /**
     * EN: Renders a published chapter, or redirects to the unchanged native guide area when Wiki is enabled.
     *
     * HR: Prikazuje objavljeno poglavlje ili vodi na nepromijenjeno izvorno područje uputa kada je Wiki uključen.
     */
    public function page(ServerRequestInterface $request, string $slug): ResponseInterface
    {
        $locale = $this->locale($request);
        $enabled = $this->config->get('app.modules.enabled', []);
        if (is_array($enabled) && in_array('aaieduhr/simbioza-module-workspace', $enabled, true)) {
            return $this->responses->redirect(
                $this->urls->getBasePath() . '/workspace/korisnicke-upute/' . rawurlencode($slug)
                . '?lang=' . rawurlencode($locale),
            );
        }

        $pages = $this->manual->pages($locale);
        $current = null;
        foreach ($pages as $page) {
            if ($page['slug'] === $slug) {
                $current = $page;
                break;
            }
        }

        if ($current === null) {
            return $this->responses->html(__('Stranica nije pronađena.'), 404);
        }

        return $this->responses->view('guides/page', [
            'title' => $current['title'], 'page' => $current, 'pages' => $pages, 'locale' => $locale,
            'manualHtml' => BundledManual::html($current['html'], $this->urls->getBasePath(), $locale),
            'themeHero' => ['title' => $current['title'], 'subtitle' => __('Korisničke upute'), 'is_home' => false],
        ]);
    }

    /**
     * EN: Serves only digest-verified release screenshots and videos, never live content attachments.
     *
     * HR: Poslužuje samo provjerene snimke i videozapise izdanja, nikada privitke živog sadržaja.
     */
    public function asset(ServerRequestInterface $request, string $uuid): ResponseInterface
    {
        $asset = $this->manual->asset($uuid, $this->locale($request));
        if ($asset === null) {
            return $this->responses->html('', 404);
        }

        $headers = ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public, max-age=3600'];
        $safe = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'video/mp4', 'video/webm', 'application/pdf'];
        if (!in_array($asset['mime'], $safe, true)) {
            return $this->responses->download($asset['content'], $asset['name'], headers: $headers);
        }

        return $this->responses->html($asset['content'], headers: $headers, contentType: $asset['mime']);
    }

    /**
     * EN: Offers only guide translations available among the installation's languages.
     *
     * HR: Nudi samo prijevode uputa dostupne među jezicima instalacije.
     */
    private function locale(ServerRequestInterface $request): string
    {
        $supported = $this->config->get('app.supported_locales', ['hr', 'en']);
        $supported = is_array($supported) ? array_filter($supported, is_string(...)) : [];

        $available = array_values(array_intersect($supported, ['hr', 'en']));
        $requested = $request->getQueryParams()['lang'] ?? $this->translator->getLocale();

        return is_string($requested) && in_array($requested, $available, true)
        ? $requested
        : (is_string($available[0] ?? null) ? $available[0] : 'en');
    }
}
