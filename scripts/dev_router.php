<?php

/**
 * HR: Router za PHP razvojni poslužitelj. Postojeće javne datoteke prepušta
 *     ugrađenom poslužitelju, a ostale zahtjeve šalje kroz aplikacijsku ulaznu
 *     točku. Produkcijski poslužitelj treba koristiti vlastita rewrite pravila.
 *
 * EN: Router for PHP's development server. Existing public files are delegated
 *     to the built-in server, while every other request is sent through the
 *     application entry point. A production server should use its own rewrite
 *     rules.
 */

declare(strict_types=1);

$configuredProject = (string)getenv('HPH_APP_PATH');
$projectDirectory = realpath($configuredProject !== '' ? $configuredProject : dirname(__DIR__));
if (!is_string($projectDirectory)) {
    http_response_code(500);
    fwrite(STDERR, "HPH_APP_PATH does not reference an existing directory.\n");
    return true;
}

$publicDirectory = realpath($projectDirectory . '/public');
if (!is_string($publicDirectory)) {
    http_response_code(500);
    fwrite(STDERR, "The public directory does not exist.\n");
    return true;
}

$requestPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
$requestPath = is_string($requestPath) ? rawurldecode($requestPath) : '/';
$candidate = realpath($publicDirectory . '/' . ltrim($requestPath, '/'));

if (
    is_string($candidate)
    && is_file($candidate)
    && str_starts_with($candidate, $publicDirectory . DIRECTORY_SEPARATOR)
) {
    return false;
}

require $publicDirectory . '/index.php';
return true;
