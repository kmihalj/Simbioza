<?php

declare(strict_types=1);

// phpcs:disable Generic.Files.LineLength.TooLong -- Keep readable template markup.

/**
 * @var \HeartPhrame\View\View $this
 * @var array{slug:string,title:string,parent:?string,html:string,order:int} $page
 * @var list<array{slug:string,title:string,parent:?string,html:string,order:int}> $pages
 * @var string $locale
 * @var string $manualHtml
 */

$byParent = [];
$bySlug = array_column($pages, null, 'slug');
foreach ($pages as $chapter) {
    $parent = $chapter['parent'];
    $byParent[$parent !== null && isset($bySlug[$parent]) ? $parent : ''][] = $chapter;
}
$ordered = [];
$renderChapters = function (string $parent, array $visited = []) use (&$renderChapters, &$ordered, $byParent, $page, $locale): string {
    $html = '<ul class="list-unstyled ps-3">';
    foreach ($byParent[$parent] ?? [] as $chapter) {
        $slug = $chapter['slug'];
        if (isset($visited[$slug])) {
            continue;
        }
        $visited[$slug] = true;
        $ordered[] = $chapter;
        $path = $this->urlGenerator->getPathFor('guides.page', ['slug' => $slug], ['lang' => $locale]);
        $html .= '<li class="my-2"><a href="' . $this->escape($path) . '"'
        . ($page['slug'] === $slug ? ' aria-current="page" class="fw-bold"' : '') . '>'
        . $this->escape($chapter['title']) . '</a>' . $renderChapters($slug, $visited) . '</li>';
    }

    return $html . '</ul>';
};
$navigation = $renderChapters('');
$position = array_search($page['slug'], array_column($ordered, 'slug'), true);
$previous = $position !== false && $position > 0 ? $ordered[$position - 1] : null;
$next = $position !== false ? $ordered[$position + 1] ?? null : null;
$supported = $this->config->get('app.supported_locales', ['hr', 'en']);
$supported = is_array($supported) ? array_filter($supported, is_string(...)) : [];
$languages = array_intersect(['hr', 'en'], $supported);
?>
<link rel="stylesheet" href="<?= $this->escape($this->urlGenerator->getBasePath() . '/guide-assets/manual.css') ?>">
<div class="row g-4">
    <aside class="col-lg-3 d-none d-lg-block">
        <nav class="card card-body" aria-label="<?= $this->escape(__('Sadržaj uputa')) ?>">
            <details open>
                <summary class="fw-bold mb-3"><?= $this->escape(__('Sadržaj uputa')) ?></summary>
                <?= $navigation ?>
            </details>
        </nav>
    </aside>
    <article class="col-lg-9">
        <div class="card card-body">
            <details class="d-lg-none mb-4">
                <summary class="fw-bold"><?= $this->escape(__('Sadržaj uputa')) ?></summary>
                <nav class="mt-3" aria-label="<?= $this->escape(__('Sadržaj uputa')) ?>"><?= $navigation ?></nav>
            </details>
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <a href="<?= $this->escape($this->urlGenerator->getPathFor('guides.index')) ?>"><?= $this->escape(__('Korisničke upute')) ?></a>
                <?php if (count($languages) > 1) : ?>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary dropdown-toggle d-inline-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="<?= $this->escape(__('Jezik')) ?>">
                        <img class="simbioza-manual-flag" src="<?= $this->escape($this->urlGenerator->getPathFor('menu.assets.flag', ['file' => $locale . '.svg'])) ?>" alt="">
                        <span><?= $this->escape(strtoupper($locale)) ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                    <?php foreach ($languages as $language) : ?>
                        <li><a class="dropdown-item d-flex align-items-center gap-2<?= $language === $locale ? ' active' : '' ?>" hreflang="<?= $this->escape($language) ?>" href="<?= $this->escape($this->urlGenerator->getPathFor('guides.page', ['slug' => $page['slug']], ['lang' => $language])) ?>">
                            <img class="simbioza-manual-flag" src="<?= $this->escape($this->urlGenerator->getPathFor('menu.assets.flag', ['file' => $language . '.svg'])) ?>" alt="">
                            <span><?= $language === 'hr' ? 'Hrvatski' : 'English' ?></span>
                        </a></li>
                    <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            <div class="simbioza-manual-content" lang="<?= $this->escape($locale) ?>"><?= $manualHtml ?></div>
            <nav class="d-flex justify-content-between flex-wrap gap-3 border-top pt-3 mt-4" aria-label="<?= $this->escape(__('Navigacija po poglavljima')) ?>">
                <?php foreach (['previous' => $previous, 'next' => $next] as $direction => $chapter) : ?>
                    <?php if ($chapter !== null) : ?>
                    <a class="btn btn-outline-secondary" rel="<?= $direction === 'previous' ? 'prev' : 'next' ?>" href="<?= $this->escape($this->urlGenerator->getPathFor('guides.page', ['slug' => $chapter['slug']], ['lang' => $locale])) ?>">
                        <?= $this->escape(__($direction === 'previous' ? 'Prethodno poglavlje' : 'Sljedeće poglavlje')) ?>: <?= $this->escape($chapter['title']) ?>
                    </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </div>
    </article>
</div>
