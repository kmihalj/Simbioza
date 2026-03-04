<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ContactModel;
use HeartPhrame\Database\ModelFactory;
use HeartPhrame\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class HomeController
{
    public function __construct(
        protected readonly ResponseFactory $responseFactory,
        protected readonly LoggerInterface $logger,
        protected readonly ModelFactory $modelFactory,
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

    /**
     * Contact page
     */
    public function contact(): ResponseInterface
    {
        return $this->responseFactory->view('home/contact', [
            'title' => 'Contact Us',
            'content' => 'This is a sample contact page of HeartPhrame framework.',
        ]);
    }

    /**
     * Handle contact form submission
     */
    public function submitContact(ServerRequestInterface $request): ResponseInterface
    {
        // Get form data
        if (!is_array($data = $request->getParsedBody())) {
            throw new \InvalidArgumentException(
                'Invalid form data.',
            );
        }

        // Basic validation
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }

        if (empty($data['email'])) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email is invalid';
        }

        if (empty($data['subject'])) {
            $errors['subject'] = 'Subject is required';
        }

        if (empty($data['message'])) {
            $errors['message'] = 'Message is required';
        }

        /** @var array<string, mixed> $data */

        // If there are errors, return to form with error messages
        if ($errors !== []) {
            return $this->responseFactory->view('home/contact', [
                'title' => 'Contact Us',
                'content' => 'This is a sample contact page of HeartPhrame framework.',
                'errors' => $errors,
                'data' => $data,
            ]);
        }

        $this->logger->info('Contact form submitted', [
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
        ]);

        /** @param array<string, mixed> $data */

        $contact = $this->modelFactory->build(ContactModel::class, $data);
        $contact->save();

        // Redirect to the thank-you page or show a success message
        return $this->responseFactory->view('home/contact_success', [
            'title' => 'Thank You',
            'name' => $data['name'],
        ]);
    }
}
