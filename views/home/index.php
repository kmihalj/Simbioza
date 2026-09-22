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
           role="button"><?= $this->escape(__('Upoznaj Simbiozu')) ?></a>
    </p>
</div>

<div class="row mt-5">
    <div class="col-md-4">
        <h2><?= $this->escape(__('Znanje na jednom mjestu')) ?></h2>
        <p><?= $this->escape(__('Povežite stranice, područja i zajednički kontekst u jasnu strukturu.')) ?></p>
    </div>
    <div class="col-md-4">
        <h2><?= $this->escape(__('Suradnja bez prepreka')) ?></h2>
        <p><?= $this->escape(
            __('Zajedno stvarajte, pregledavajte i objavljujte uz ovlasti prilagođene vašem timu.'),
        ) ?></p>
    </div>
    <div class="col-md-4">
        <h2><?= $this->escape(__('Prostor koji raste')) ?></h2>
        <p><?= $this->escape(
            __('Dodajte module koje vaša zajednica treba, uz jedno povezano korisničko iskustvo.'),
        ) ?></p>
    </div>
</div>
