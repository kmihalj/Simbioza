<?php

declare(strict_types=1);

// EN: A test-only endpoint copied into a disposable public directory by the fixture verifier.
// It never runs outside that fixture and returns no secrets or real user data.
//
// HR: Testna krajnja točka koju provjera kopira u javni direktorij privremene instalacije.
// Izvan tog testa ne radi te ne vraća tajne ni stvarne korisničke podatke.
if (!is_file('/.dockerenv') || !is_file('/usr/local/share/simbioza-fixture/secured-fpm')) {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
if (!is_file($root . '/bootstrap.php') || !is_file($root . '/vendor/autoload.php')) {
    throw new RuntimeException('Fixture application files are unavailable.');
}

require $root . '/bootstrap.php';
require $root . '/vendor/autoload.php';
$gateway = new App\Setup\SetupGateway(
    new App\Setup\SetupRequestStore($root . '/data/setup-requests'),
    new App\Module\NativeProcessRunner(),
    $root,
    '/usr/local/sbin/simbioza-setup',
    false,
);
$report = (new App\Setup\SetupDiagnostics($root, $gateway))->report();
header('Content-Type: application/json');
echo json_encode($report, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
