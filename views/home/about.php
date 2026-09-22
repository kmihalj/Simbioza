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

$principles = [
    'Strukturirano znanje koje ne gubi ljudski kontekst.',
    'Suradnja podržana jasnim ulogama i ovlastima.',
    'Modularna osnova koja se prilagođava svakoj zajednici.',
];
?>

<div class="card mb-4">
    <?php if (!$themeShowsInnerHero) : ?>
    <div class="card-header">
        <h1><?= $this->escape(__($title)) ?></h1>
    </div>
    <?php endif; ?>
    <div class="card-body">
        <p class="lead"><?= $this->escape(__(
            'Simbioza je zajedničko okruženje za stranice, područja, suradnju i objavu znanja.',
        )) ?></p>
        <p><?= $this->escape(__(
            'Pokreće je HeartPhrame, a raste kroz ciljane module bez vezivanja aplikacije '
            . 'uz samo jedan način rada.',
        )) ?></p>
        <ul>
            <?php foreach ($principles as $principle) : ?>
                <li><?= $this->escape(__($principle)) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
