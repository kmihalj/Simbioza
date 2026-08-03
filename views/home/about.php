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
    'Structured knowledge without losing human context.',
    'Collaboration supported by clear roles and permissions.',
    'A modular foundation that adapts to each community.',
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
            'Simbioza is a shared knowledge environment for pages, spaces, collaboration, and publication.',
        )) ?></p>
        <p><?= $this->escape(__(
            'It is powered by HeartPhrame and grows through focused modules '
            . 'without locking the application to one workflow.',
        )) ?></p>
        <ul>
            <?php foreach ($principles as $principle) : ?>
                <li><?= $this->escape(__($principle)) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
