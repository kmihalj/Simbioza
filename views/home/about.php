<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var string $title
 */

$psrItems = [
    'PSR-3 Logger Interface',
    'PSR-4 Autoloading',
    'PSR-7 HTTP Message Interface',
    'PSR-11 Container Interface',
    'PSR-12 Extended Coding Style Guide',
    'PSR-14 Event Dispatcher',
    'PSR-15 HTTP Server Request Handlers',
    'PSR-16 Caching Interface',
    'PSR-17 HTTP Factories',
];

$functionalityItems = [
    'Routing',
    'Templating',
    'Localization',
    'Configuration',
    'Sessions',
    'Authentication',
    'Encryption',
    'Database abstraction',
];
?>

<div class="card mb-4">
    <div class="card-header">
        <h1><?= $this->escape(__($title)) ?></h1>
    </div>
    <div class="card-body">
        <p><?= $this->escape(__('Follows Model-View-Controller architecture.')) ?></p>

        <p><?= $this->escape(__('Custom implementation for several PSR recommendations')) ?></p>
        <ul>
            <?php foreach ($psrItems as $item) : ?>
                <li><?= $this->escape(__($item)) ?></li>
            <?php endforeach; ?>
        </ul>

        <p><?= $this->escape(__('Other notable functionalities:')) ?></p>
        <ul>
            <?php foreach ($functionalityItems as $item) : ?>
                <li><?= $this->escape(__($item)) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
