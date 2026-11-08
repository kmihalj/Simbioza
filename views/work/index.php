<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var string $section
 * @var int $pageSize
 * @var list<array{kind:string,items:list<array<string,string>>,start:int,next:string,more:bool,
 *     unavailable:bool}> $lists
 * @var array<string,mixed> $query
 */
$e = $this->escape(...);
$sections = ['recent' => __('Nedavno sam radio'), 'drafts' => __('Moji nacrti'), 'review' => __('Čeka moj pregled')];
$kinds = ['page' => __('Stranice u područjima'), 'html' => __('Samostalne HTML stranice'), 'survey' => __('Ankete')];
$states = [
    'draft' => __('Nacrt'), 'in_review' => __('Na pregledu'),
    'published' => __('Objavljeno'), 'closed' => __('Zatvoreno'),
];
$description = __(
    'Ovdje su aktivni sadržaji koje ste stvarali ili uređivali te sadržaji koje možete pregledati prije objave.',
);
$permissionHint = __('Popisi prikazuju samo sadržaj za koji trenutačno imate potrebne ovlasti.');
$unavailableHint = __(
    'Ovaj popis trenutačno nije moguće dovršiti. Pokušajte kasnije ili upotrijebite pretraživanje sadržaja.',
);
$path = $this->urlGenerator->getPathFor('work.index');
$pageQuery = ['section' => $section, 'size' => $pageSize];
foreach (['page', 'html', 'survey'] as $kind) {
    $cursor = $query[$kind . '_cursor'] ?? null;
    if (is_string($cursor) && preg_match('/^[a-f0-9]{64}$/D', $cursor) === 1) {
        $pageQuery[$kind . '_cursor'] = $cursor;
    }
}
?>
<section class="card hph-content-card">
    <div class="card-body" data-hph-content-title-target>
        <p><?= $e($description) ?></p>
        <p class="text-body-secondary"><?= $e($permissionHint) ?></p>
        <nav class="nav nav-tabs flex-wrap mb-4" aria-label="<?= $e(__('Pregledi mojeg rada')) ?>">
            <?php foreach ($sections as $key => $label) :
                $sectionUrl = $path . '?' . http_build_query(['section' => $key, 'size' => $pageSize]);
                ?>
                <a class="nav-link<?= $section === $key ? ' active' : '' ?>"
                <?= $section === $key ? 'aria-current="page"' : '' ?>
                    href="<?= $e($sectionUrl) ?>"><?= $e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <form method="get" action="<?= $e($path) ?>" class="d-flex flex-wrap align-items-end gap-2 mb-4">
            <input type="hidden" name="section" value="<?= $e($section) ?>">
            <div><label for="my-work-size" class="form-label"><?= $e(__('Broj stavki po popisu')) ?></label>
                <select name="size" id="my-work-size" class="form-select">
                    <?php foreach ([10, 25, 50] as $size) : ?>
                        <option value="<?= $size ?>"<?= $pageSize === $size ? ' selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn btn-outline-secondary" type="submit"><?= $e(__('Primijeni')) ?></button>
        </form>
        <?php if ($lists === []) : ?>
            <p role="status"><?= $e(__('U ovoj instalaciji nema dostupnih popisa za ovaj pregled.')) ?></p>
        <?php endif; ?>
        <div class="d-grid gap-4">
            <?php foreach ($lists as $list) :
                $kind = $list['kind'];
                ?>
                <section class="card" aria-labelledby="my-work-<?= $e($kind) ?>">
                    <div class="card-body">
                        <h2 class="h4" id="my-work-<?= $e($kind) ?>"><?= $e($kinds[$kind]) ?></h2>
                <?php if ($list['unavailable']) : ?>
                            <p class="text-body-secondary mb-0" role="status"><?= $e($unavailableHint) ?></p>
                <?php elseif ($list['items'] === []) : ?>
                            <p class="text-body-secondary mb-0" role="status">
                    <?= $e(__('Nema sadržaja za ovaj pregled.')) ?>
                            </p>
                <?php else : ?>
                            <ul class="list-group list-group-flush">
                    <?php foreach ($list['items'] as $item) : ?>
                                    <li class="list-group-item px-0 d-flex flex-wrap gap-3
                                        align-items-start justify-content-between">
                                        <div class="flex-grow-1 text-break">
                                            <a href="<?= $e($item['url']) ?>" class="fw-semibold">
                        <?= $e($item['title']) ?>
                                            </a>
                        <?php if ($item['workspace'] !== '') : ?>
                                                <div class="small text-body-secondary">
                            <?= $e($item['workspace']) ?>
                                                </div>
                        <?php endif; ?>
                                            <div class="d-flex flex-wrap gap-2 small mt-1">
                                                <span class="badge text-bg-secondary">
                        <?= $e($states[$item['state']] ?? $item['state']) ?>
                                                </span>
                                                <span lang="en" class="text-body-secondary">
                        <?= $e(strtoupper($item['language'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <time class="small text-body-secondary text-nowrap"
                                            datetime="<?= $e($item['datetime']) ?>"><?= $e($item['time']) ?></time>
                                    </li>
                    <?php endforeach; ?>
                            </ul>
                <?php endif; ?>
                <?php if (!$list['unavailable'] && ($list['start'] > 0 || $list['more'])) :
                    $baseUrl = $path . '?' . http_build_query([...$pageQuery, $kind . '_cursor' => '']);
                    $nextUrl = $path . '?' . http_build_query(
                        [
                        ...$pageQuery, $kind . '_cursor' => $list['next'],
                        ],
                    );
                    $anchor = '#my-work-' . $kind;
                    $pagingLabel = __('Straničenje') . ' · ' . $kinds[$kind];
                    $nextLabel = __('Sljedeća stranica') . ' · ' . $kinds[$kind];
                    ?>
                            <nav class="d-flex flex-wrap gap-2 border-top pt-3 mt-3
                                justify-content-between align-items-center" aria-label="<?= $e($pagingLabel) ?>">
                    <?php if ($list['start'] > 0) : ?>
                                    <a class="btn btn-outline-secondary" href="<?= $e($baseUrl . $anchor) ?>">
                        <?= $e(__('Na početak popisa')) ?>
                                    </a>
                    <?php endif; ?>
                    <?php if ($list['more']) : ?>
                                    <a class="btn btn-outline-secondary ms-auto" href="<?= $e($nextUrl . $anchor) ?>"
                                        aria-label="<?= $e($nextLabel) ?>">
                        <?= $e(__('Sljedeća stranica')) ?> <span aria-hidden="true">›</span>
                                    </a>
                    <?php endif; ?>
                            </nav>
                <?php endif; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        </div>
    </div>
</section>
