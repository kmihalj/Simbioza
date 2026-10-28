<?php

declare(strict_types=1);

/** @var \HeartPhrame\View\View $this */
$profiles = [
    [__('Stranice i objava sadržaja'), __(
        'Oblikujte i objavite stranice s tekstom, slikama i videom te ih povežite s ostalim sadržajem.',
    )],
    [__('Kalendar i sastanci'), __(
        'Planirajte događaje i sastanke te povežite kalendarske klijente putem CalDAV-a.',
    )],
    [__('Wiki i timsko znanje'), __(
        'Organizirajte stranice po područjima, povežite ih i pretražujte uz ovlasti korisnika i grupa.',
    )],
    [__('Ankete i odgovori'), __(
        'Izradite višejezične ankete s uvjetnim pitanjima, odabranom publikom i pregledom rezultata.',
    )],
];
?>
<div class="row g-4" role="list">
    <?php foreach ($profiles as [$heading, $description]) : ?>
    <div class="col-md-6" role="listitem">
        <section class="card h-100">
            <div class="card-body">
                <h3 class="h4"><?= $this->escape($heading) ?></h3>
                <p class="mb-0"><?= $this->escape($description) ?></p>
            </div>
        </section>
    </div>
    <?php endforeach; ?>
</div>
