<?php

declare(strict_types=1);

namespace App\Controllers;

use HeartPhrame\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;

class HomeController
{
    public function __construct(
        protected readonly ResponseFactory $responseFactory,
    ) {
    }

    /**
     * Homepage
     */
    public function index(): ResponseInterface
    {
        return $this->responseFactory->view(
            'home/index',
            [
                'title' => 'Welcome to the HeartPhrame Framework',
                'content' => 'This is a sample homepage showcasing our PHP framework.',
            ],
            true,
        );
    }

    /**
     * About page
     */
    public function about(): ResponseInterface
    {
        return $this->responseFactory->view('home/about', [
            'title' => 'About',
        ]);
    }
}
