<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var string $title
 * @var string $description
 * @var list<array{title:string,url:string}> $items
 * @var ?string $manage
 * @var int $page
 * @var bool $hasNext
 * @var string $route
 */
$previousUrl = $page > 1 ? $this->urlGenerator->getPathFor($route, [], ['page' => $page - 1]) : '';
$nextUrl = $hasNext ? $this->urlGenerator->getPathFor($route, [], ['page' => $page + 1]) : '';
?>
<section class="card hph-content-card" aria-label="<?= $this->escape(__($title)) ?>">
    <div class="card-body" data-hph-content-title-target>
    <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap mb-4">
        <p class="lead mb-0"><?= $this->escape(__($description)) ?></p>
        <?php if ($manage !== null) : ?>
        <a class="btn btn-primary" href="<?= $this->escape($manage) ?>">
            <?= $this->escape(__('Upravljanje sadržajem')) ?>
        </a>
        <?php endif; ?>
    </div>
    <?php if ($items === []) : ?>
    <p role="status"><?= $this->escape(__('Trenutačno nema dostupnog objavljenog sadržaja.')) ?></p>
    <?php else : ?>
    <ul class="row g-3 list-unstyled">
        <?php foreach ($items as $item) : ?>
        <li class="col-md-6 col-xl-4">
            <div class="card card-body h-100 d-flex flex-column align-items-start">
                <h2 class="h5 mb-3"><?= $this->escape($item['title']) ?></h2>
                <a class="btn btn-outline-secondary align-self-start mt-auto" href="<?= $this->escape($item['url']) ?>">
            <?= $this->escape(__('Otvori')) ?>
            <span class="visually-hidden"><?= $this->escape(': ' . $item['title']) ?></span>
                </a>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
    <?php if ($page > 1 || $hasNext) : ?>
    <nav class="d-flex gap-3 flex-wrap mt-4" aria-label="<?= $this->escape(__('Navigacija po stranicama')) ?>">
        <?php if ($page > 1) : ?>
        <a class="btn btn-outline-secondary" rel="prev" href="<?= $this->escape($previousUrl) ?>">
            <?= $this->escape(__('Prethodna stranica')) ?>
        </a>
        <?php endif; ?>
        <?php if ($hasNext) : ?>
        <a class="btn btn-outline-secondary" rel="next" href="<?= $this->escape($nextUrl) ?>">
            <?= $this->escape(__('Sljedeća stranica')) ?>
        </a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>
    </div>
</section>
