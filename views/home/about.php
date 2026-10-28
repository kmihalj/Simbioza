<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var string $title
 * @var ?\AaiEduHr\HeartPhrameModuleTheme\Service\ThemeLayoutRenderer $themeLayoutRenderer
 */

$themeShowsInnerHero = isset($themeLayoutRenderer)
&& is_object($themeLayoutRenderer)
&& method_exists($themeLayoutRenderer, 'heroEnabled')
&& $themeLayoutRenderer->heroEnabled(false);

?>

<div class="card mb-4 hph-content-card">
    <?php if (!$themeShowsInnerHero) : ?>
    <div class="card-header">
        <h1><?= $this->escape(__($title)) ?></h1>
    </div>
    <?php endif; ?>
    <div class="card-body">
        <p class="lead"><?= $this->escape(__(
            'Simbioza povezuje stranice i Wiki, kalendare i sastanke, ankete, zadatke, komentare i obavijesti. '
            . 'Sve što vašoj zajednici treba za zajednički rad dostupno je u jednoj aplikaciji.',
        )) ?></p>
        <p><?= $this->escape(__(
            'Simbioza znači zajednički život različitih organizama. Naziv aplikacije prenosi tu ideju '
            . 'na alate: svaki modul ima svoju ulogu, a povezani moduli međusobno se nadopunjuju.',
        )) ?></p>
        <h2><?= $this->escape(__('Jedna aplikacija, povezane mogućnosti')) ?></h2>
        <?= $this->forPartial('home/profiles') ?>
        <p class="mt-4"><?= $this->escape(__(
            'Cjelovita Simbioza povezuje stranice i Wiki, kalendare i sastanke, ankete, zadatke, '
            . 'komentare i obavijesti. Alati dijele korisničko iskustvo, a pristup se provjerava prema ovlastima.',
        )) ?></p>
        <h2 class="mt-4"><?= $this->escape(__('Trebate samo dio mogućnosti?')) ?></h2>
        <p><?= $this->escape(__(
            'Modularnost HeartPhrame frameworka omogućuje i užu instalaciju Simbioze: samo HTML web, '
            . 'kalendar, Wiki ili ankete. To je dodatna mogućnost za one kojima nije potrebna cijela Simbioza.',
        )) ?></p>
        <p><?= $this->escape(__(
            'Moguće su i kombinacije poput weba s kalendarom ili Wikija s anketama. '
            . 'Užu instalaciju možete kasnije proširiti dodatnim modulima, uz zadržavanje postojećeg sadržaja.',
        )) ?></p>
        <h2><?= $this->escape(__('Zajednički temelj, slobodan izbor funkcionalnosti')) ?></h2>
        <p><?= $this->escape(__(
            'Svaka Simbioza uključuje Auth, ORM, Menu, Theme, Backup i Audit: prijavu i ovlasti, '
            . 'rad s bazom, izbornike, izgled, sigurnosne kopije i dnevnike aktivnosti. '
            . 'API i pristupačnost mogu se dodati prema potrebi.',
        )) ?></p>
        <p><?= $this->escape(__(
            'HeartPhrame i njegovi moduli služe i razvoju prilagođenih aplikacija. '
            . 'Iskoristite gotove temelje za podatke, prijavu, navigaciju i izgled, '
            . 'a API modulom povežite aplikaciju s drugim sustavima.',
        )) ?></p>
    </div>
</div>
