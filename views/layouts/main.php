<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var ?string $title
 * @var string $content
 */

?>

<!DOCTYPE html>
<html lang="<?= $this->translator->getLocale() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'HeartPhrame' ?></title>

    <?php // phpcs:ignore ?>
    <link rel="stylesheet" href="<?= $this->urlGenerator->getBasePath() ?>/http_cdn.jsdelivr.net_npm_bootstrap@5.2.3_dist_css_bootstrap.css">

    <style>
        body {
            padding-top: 2rem;
            padding-bottom: 2rem;
        }
        .navbar {
            margin-bottom: 2rem;
        }
    </style>

    <?php // Render additional head content, if any. ?>
    <?= $this->renderPlaceholder('head') ?>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">HeartPhrame</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $this->urlGenerator->getPathFor('home') ?>">
                            Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $this->urlGenerator->getPathFor('about') ?>">
                            About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $this->urlGenerator->getPathFor('contact.index') ?>">
                            Contact</a>
                    </li>
                    <?php if ($this->urlGenerator->namedRouteExists('heartframe-module-demo.home')) : ?>
                    <li class="nav-item">
                        <a class="nav-link"
                           href="<?= $this->urlGenerator->getPathFor('heartframe-module-demo.home') ?>">
                            Demo Module
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($messages = $this->alertHandler->getAllAndForget()) : ?>
            <?php foreach ($messages as $message) : ?>
                <div class="alert alert-<?= $message->level->value ?>" role="alert">
                <?= $message->message ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?= $content ?>
    </div>

    <footer class="mt-5 pt-3 border-top text-center text-muted">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= __('HeartPhrame. All rights reserved.') ?></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

    <?php // Render additional HTML content at the end, if any. ?>
    <?= $this->renderPlaceholder('tail') ?>
</body>
</html>
