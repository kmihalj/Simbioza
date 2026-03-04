<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var string $title
 * @var string $name
 */

// Add custom CSS to the head
$this->addToPlaceholder('head', '<style>
    .alert-success {
        border-left: 5px solid #28a745;
        padding-left: 15px;
    }
</style>');

?>

<div class="card mb-4">
    <div class="card-header">
        <h1><?= $this->escape($title) ?></h1>
    </div>
    <div class="card-body">
        <div class="alert alert-success" role="alert">
            <h4 class="alert-heading">Message Sent Successfully!</h4>
            <p>Thank you, <?= $this->escape($name) ?>! Your message has been received.</p>
            <hr>
            <p class="mb-0">Our team will review your message and get back to you as soon as possible.</p>
        </div>

        <p>
            We appreciate you taking the time to contact us. If your matter is urgent, please feel free to call us
            directly at (123) 456-7890.
        </p>

        <div class="mt-4">
            <a href="<?= $this->urlGenerator->getPathFor('home') ?>" class="btn btn-primary">Back to Home</a>
            <a href="<?= $this->urlGenerator->getPathFor('contact.index') ?>"
               class="btn btn-outline-secondary ms-2">
                Send Another Message
            </a>
        </div>
    </div>
</div>
