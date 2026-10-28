<?php

declare(strict_types=1);

// EN: Checks the actual homepage response and a bounded internal redirect chain in a disposable installation.
//
// HR: Provjerava stvarni odgovor naslovnice i ograničeni niz internih preusmjerenja privremene instalacije.
$root = realpath($argv[1] ?? '');
if (!is_string($root) || !is_file($root . '/data/installation.lock')) {
    throw new RuntimeException('An installed matrix fixture is required.');
}

require $root . '/bootstrap.php';
require $root . '/vendor/autoload.php';
$path = '/';
for ($attempt = 0; $attempt < 5; $attempt++) {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
    $_SERVER['REQUEST_URI'] = $path;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $query = parse_url($path, PHP_URL_QUERY);
    $_GET = [];
    parse_str(is_string($query) ? $query : '', $_GET);
    $app = new HeartPhrame\App([$root . '/config'], $root);
    $response = $app->handle();
    if (in_array($response->getStatusCode(), [301, 302, 303, 307, 308], true)) {
        $path = $response->getHeaderLine('Location');
        if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
            throw new RuntimeException('The fixture homepage redirected outside the application.');
        }

        continue;
    }

    $html = (string)$response->getBody();
    if (
        $response->getStatusCode() !== 200 || !str_contains($html, '<html lang="hr"')
        || !str_contains($html, '<main')
    ) {
        throw new RuntimeException('Homepage destination failed: HTTP ' . $response->getStatusCode() . ' at ' . $path);
    }

    echo '[OK] HTTP homepage and internal destination' . PHP_EOL;
    exit(0);
}

throw new RuntimeException('The fixture homepage has a redirect loop.');
