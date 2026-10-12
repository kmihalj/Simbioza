<?php

declare(strict_types=1);

namespace App\Controllers;

use HeartPhrame\Http\ResponseFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

use function is_object;
use function is_string;
use function method_exists;
use function trim;

class HomeController
{
    private const HOMEPAGE_RESOLVER_SERVICE = 'heartphrame.application_homepage_resolver';

    /**
     * EN: Initializes the homepage controller.
     *
     * HR: Inicijalizira kontroler naslovnice.
     */
    public function __construct(
        protected readonly ResponseFactory $responseFactory,
        protected readonly ContainerInterface $container,
    ) {
    }

    /**
     * EN: Shows the homepage and supplies optional hero context to the theme module.
     *
     * HR: Prikazuje naslovnicu i predaje opcionalni hero kontekst theme modulu.
     */
    public function index(): ResponseInterface
    {
        $workspaceHomepage = $this->workspaceHomepagePath();
        if ($workspaceHomepage !== null) {
            return $this->responseFactory->redirect($workspaceHomepage, 302, [
                'Cache-Control' => 'private, no-store, max-age=0',
                'Vary' => 'Cookie, Accept-Language',
            ]);
        }

        return $this->responseFactory->view(
            'home/index',
            [
                'title' => 'Simbioza',
                'content' => 'Zajednički prostor za znanje, suradnju i sadržaj koji raste s vašom zajednicom.',
                'themeHero' => [
                    'is_home' => true,
                    'eyebrow' => __('Simbioza by HeartPhrame'),
                    'title' => __('Simbioza'),
                    'subtitle' => __('Znanje koje živi zajedno.'),
                ],
            ],
            true,
        );
    }

    /**
     * EN: Calls the optional neutral resolver registered by the Workspace module.
     * When the module is absent, disabled, or not ready, the built-in homepage remains.
     *
     * HR: Poziva opcionalni neutralni resolver koji registrira Workspace modul.
     * Kada modul nije instaliran, uključen ili spreman, ostaje ugrađena naslovnica.
     */
    private function workspaceHomepagePath(): ?string
    {
        if (!$this->container->has(self::HOMEPAGE_RESOLVER_SERVICE)) {
            return null;
        }

        try {
            $resolver = $this->container->get(self::HOMEPAGE_RESOLVER_SERVICE);
            if (!is_object($resolver) || !method_exists($resolver, 'resolvePath')) {
                return null;
            }

            $path = $resolver->resolvePath();
            if (!is_string($path)) {
                return null;
            }

            $path = trim($path);

            return $path !== '' && str_starts_with($path, '/') ? $path : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * EN: Shows the application's information page.
     *
     * HR: Prikazuje informativnu stranicu aplikacije.
     */
    public function about(): ResponseInterface
    {
        return $this->responseFactory->view('home/about', [
            'title' => 'O aplikaciji',
            'themeHero' => [
                'is_home' => false,
                'eyebrow' => __('Simbioza by HeartPhrame'),
                'title' => __('O Simbiozi'),
                'subtitle' => __('Znanje koje živi zajedno.'),
            ],
        ]);
    }
}
