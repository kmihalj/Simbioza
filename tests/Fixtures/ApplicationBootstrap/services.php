<?php

declare(strict_types=1);

// HR: Provjerava stvarne Simbiozine tvornice, bez sesije i zapisivanja u žive logove.
// EN: Exercises actual Simbioza factories without a session or live log writes.
$services = require dirname(__DIR__, 3) . '/config/services.php';
$services[\Psr\Log\LoggerInterface::class] = static fn(): \Psr\Log\NullLogger => new \Psr\Log\NullLogger();
return $services;
