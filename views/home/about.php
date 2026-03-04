<?php

declare(strict_types=1);

/**
 * @var \HeartPhrame\View\View $this
 * @var string $title
 */

?>

<div class="card mb-4">
    <div class="card-header">
        <h1><?= $this->escape($title) ?></h1>
    </div>
    <div class="card-body">
        <p>Follows Model-View-Controller architecture.</p>

        <p>Custom implementation for several PSR recommendations</p>
        <ul>
            <li>PSR-3 Logger Interface</li>
            <li>PSR-4 Autoloading</li>
            <li>PSR-7 HTTP Message Interface</li>
            <li>PSR-11 Container Interface</li>
            <li>PSR-12 Extended Coding Style Guide</li>
            <li>PSR-14 Event Dispatcher</li>
            <li>PSR-15 HTTP Server Request Handlers</li>
            <li>PSR-16 Caching Interface</li>
            <li>PSR-17 HTTP Factories</li>
        </ul>

        <p>Other notable functionalities:</p>
        <ul>
            <li>Routing</li>
            <li>Templating</li>
            <li>Localization</li>
            <li>Configuration</li>
            <li>Sessions</li>
            <li>Authentication</li>
            <li>Encryption</li>
            <li>Database abstraction</li>
        </ul>
    </div>
</div>

