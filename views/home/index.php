<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var string $title
 * @var string $content
 * @var ?\AaiEduHr\HeartPhrameModuleTheme\Service\ThemeLayoutRenderer $themeLayoutRenderer
 */

$themeShowsHomeHero = isset($themeLayoutRenderer)
&& is_object($themeLayoutRenderer)
&& method_exists($themeLayoutRenderer, 'heroEnabled')
&& $themeLayoutRenderer->heroEnabled(true);
?>

<div class="jumbotron">
    <?php if (!$themeShowsHomeHero) : ?>
    <h1 class="display-4"><?= $this->escape(__($title)) ?></h1>
    <p class="lead"><?= $this->escape(__($content)) ?></p>
    <?php endif; ?>
    <hr class="my-4">
    <p><?= $this->escape(__('This is a lightweight PHP framework that implements PSR standards.')) ?></p>
    <p class="lead">
        <a class="btn btn-primary btn-lg"
           href="<?= $this->urlGenerator->getPathFor('about') ?>"
           role="button"><?= $this->escape(__('Learn more')) ?></a>
    </p>
</div>

<div class="row mt-5">
    <div class="col-md-4">
        <h2><?= $this->escape(__('PSR Compliant')) ?></h2>
        <p>
            <?= $this->escape(__(
                'This framework adheres to PHP-FIG PSR standards, making it interoperable with other libraries.',
            )) ?>
        </p>
    </div>
    <div class="col-md-4">
        <h2><?= $this->escape(__('Simple Design')) ?></h2>
        <p><?= $this->escape(__("Lightweight and minimalistic design that's easy to understand and extend.")) ?></p>
    </div>
    <div class="col-md-4">
        <h2><?= $this->escape(__('Modern PHP')) ?></h2>
        <p><?= $this->escape(__('Built for PHP 8.2+ with modern features and best practices.')) ?></p>
    </div>
</div>
