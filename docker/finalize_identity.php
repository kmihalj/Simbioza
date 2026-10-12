<?php

declare(strict_types=1);

// EN: Build-only step; the runtime never rewrites its read-only identity or dependencies.
//
// HR: Korak samo za build; runtime ne prepisuje read-only identitet niti ovisnosti.
require dirname(__DIR__) . '/vendor/autoload.php';
echo \App\Deployment\ImageContextBuilder::sealDependencies(dirname(__DIR__)) . "\n";
