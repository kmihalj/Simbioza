<?php

/**
 * Event listeners
 */

declare(strict_types=1);

use HeartPhrame\Event\EventListener;

return [
    new EventListener(
        \HeartPhrame\Event\SampleEvent::class,
        \HeartPhrame\Event\SampleEventListener::class,
    ),
];
