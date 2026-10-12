<?php

declare(strict_types=1);

/**
 * EN: Loads a generated test fixture only after checking its runtime autoloader.
 *
 * HR: Učitava generirani testni fixture tek nakon provjere runtime autoloadera.
 */
function loadAuditFpmFixture(string $fixtureRoot): void
{
    $autoloader = $fixtureRoot . '/vendor/autoload.php';
    if (!is_file($autoloader) || !is_readable($autoloader)) {
        throw new RuntimeException('Missing or unreadable FPM audit autoloader.');
    }

    require $autoloader;
}
