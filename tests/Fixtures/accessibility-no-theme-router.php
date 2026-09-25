<?php

declare(strict_types=1);

/*
 * HR: Samo lokalni testni poslužitelj preslikava stvarne rute resursa modula.
 * EN: Only the local test server maps the module's real asset routes.
 */
$root = dirname(__DIR__, 3) . '/heartphrame-module-accessibility/resources';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$asset = match ($path) {
    '/accessibility.css' => [$root . '/accessibility.css', 'text/css; charset=utf-8'],
    '/accessibility.js' => [$root . '/accessibility.js', 'application/javascript; charset=utf-8'],
    default => null,
};
if (
    $asset === null && is_string($path)
    && preg_match('~^/accessibility/fonts/([a-z0-9-]+\.woff2)$~', $path, $matches) === 1
) {
    $asset = [$root . '/fonts/' . $matches[1], 'font/woff2'];
}

if ($asset === null) {
    return false;
}

if (!is_file($asset[0])) {
    http_response_code(404);
    return true;
}

header('Content-Type: ' . $asset[1]);
readfile($asset[0]);
return true;
