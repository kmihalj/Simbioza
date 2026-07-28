<?php

declare(strict_types=1);

namespace App\Controllers;

use HeartPhrame\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

class HomeController
{
    /**
     * HR: Inicijalizira kontroler naslovnice.
     *
     * EN: Initializes the homepage controller.
     */
    public function __construct(
        protected readonly ResponseFactory $responseFactory,
    ) {
    }

    /**
     * HR: Prikazuje naslovnicu i predaje opcionalni hero kontekst theme modulu.
     *
     * EN: Shows the homepage and supplies optional hero context to the theme module.
     */
    public function index(): ResponseInterface
    {
        return $this->responseFactory->view(
            'home/index',
            [
                'title' => 'Welcome to the HeartPhrame Framework',
                'content' => 'This is a sample homepage showcasing our PHP framework.',
                'themeHero' => [
                    'is_home' => true,
                    'title' => __('Welcome to the HeartPhrame Framework'),
                    'subtitle' => __('This is a sample homepage showcasing our PHP framework.'),
                ],
            ],
            true,
        );
    }

    /**
     * HR: Prikazuje informativnu stranicu aplikacije.
     *
     * EN: Shows the application's information page.
     */
    public function about(): ResponseInterface
    {
        return $this->responseFactory->view('home/about', [
            'title' => 'About',
            'themeHero' => [
                'is_home' => false,
                'eyebrow' => __('HeartPhrame'),
                'title' => __('About'),
                'subtitle' => __('A lightweight PHP framework built around PSR standards.'),
            ],
        ]);
    }
}
