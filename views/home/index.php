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

<section class="card hph-content-card" data-simbioza-home>
<div class="card-body">
    <?php if (!$themeShowsHomeHero) : ?>
    <h1 class="display-4"><?= $this->escape(__($title)) ?></h1>
    <p class="lead"><?= $this->escape(__($content)) ?></p>
    <?php endif; ?>
    <h2><?= $this->escape(__('Simbioza — znanje, sadržaj i suradnja na jednom mjestu.')) ?></h2>
    <p class="lead mb-4"><?= $this->escape(__(
        'Simbioza povezuje stranice i Wiki, kalendare i sastanke, ankete, zadatke, komentare i obavijesti. '
        . 'Sve što vašoj zajednici treba za zajednički rad dostupno je u jednoj aplikaciji.',
    )) ?></p>
    <?= $this->forPartial('home/profiles') ?>
    <h2 class="mt-4"><?= $this->escape(__('Trebate samo dio mogućnosti?')) ?></h2>
    <p><?= $this->escape(__(
        'Modularnost HeartPhrame frameworka omogućuje i užu instalaciju Simbioze: samo HTML web, '
        . 'kalendar, Wiki ili ankete. To je dodatna mogućnost za one kojima nije potrebna cijela Simbioza.',
    )) ?></p>
    <p class="lead">
        <a class="btn btn-primary btn-lg"
           href="<?= $this->urlGenerator->getPathFor('about') ?>"
           role="button"><?= $this->escape(__('Upoznaj Simbiozu')) ?></a>
    </p>
</div>
</section>
