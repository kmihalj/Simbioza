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
    <p class="lead mb-4"><?= $this->escape(__($content)) ?></p>
    <p class="lead">
        <a class="btn btn-primary btn-lg"
           href="<?= $this->urlGenerator->getPathFor('about') ?>"
           role="button"><?= $this->escape(__('Discover Simbioza')) ?></a>
    </p>
</div>

<div class="row mt-5">
    <div class="col-md-4">
        <h2><?= $this->escape(__('Knowledge in one place')) ?></h2>
        <p><?= $this->escape(__('Connect pages, spaces, and shared context in a clear structure.')) ?></p>
    </div>
    <div class="col-md-4">
        <h2><?= $this->escape(__('Collaboration without friction')) ?></h2>
        <p><?= $this->escape(__('Create, review, and publish together with permissions that follow your team.')) ?></p>
    </div>
    <div class="col-md-4">
        <h2><?= $this->escape(__('A space that grows')) ?></h2>
        <p><?= $this->escape(__('Add the modules your community needs while keeping one coherent experience.')) ?></p>
    </div>
</div>
