<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleAccessibility\Service\AccessibilityRenderer;
use HeartPhrame\Localization\TranslatorInterface;
use HeartPhrame\Routing\UrlGenerator;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$translations = require dirname(__DIR__, 3) . '/heartphrame-module-accessibility/lang/en.php';
/* HR: Testni prevoditelj koristi stvarni engleski paket bez ostalih modula.
 * EN: The fixture translator uses the real English pack without other modules. */
$translator = new class ($translations) implements TranslatorInterface {
    /**
     * HR: Primamo samo lokalne prijevode testnog modula.
     * EN: Only the test module's local translations are accepted.
     *
     * @param array<string, string> $translations
     */
    public function __construct(private readonly array $translations)
    {
    }

    /**
     * HR: Vraća prijevod ili izvorni ključ kada prijevod nedostaje.
     * EN: Returns the translation or original key when none exists.
     *
     * @param array<string, string|int|float> $replace
     */
    public function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        return strtr($this->translations[$key] ?? $key, $replace);
    }

    public function getLocale(): string
    {
        return 'en';
    }

    public function setLocale(string $locale): void
    {
    }
};
/* HR: Prazna osnovna putanja odgovara ruti lokalnog testnog poslužitelja.
 * EN: An empty base path matches the local test server's asset routes. */
$url = new class extends UrlGenerator {
    public function __construct()
    {
    }

    public function getBasePath(): string
    {
        return '';
    }
};
$renderer = new AccessibilityRenderer($translator, $url);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accessibility without Theme</title>
    <link rel="icon" href="data:,">
    <?php if (!isset($_GET['plain'])) : ?>
        <link rel="stylesheet" href="/HFClean/public/http_cdn.jsdelivr.net_npm_bootstrap@5.2.3_dist_css_bootstrap.css">
    <?php endif; ?>
    <?= $renderer->renderHead() ?>
</head>
<body>
    <nav class="navbar navbar-light bg-light p-3" aria-label="Main navigation">
        <a class="navbar-brand" href="#content">Example app</a>
        <a class="nav-link" href="#content">Content</a>
    </nav>
    <main id="content" class="container py-4">
        <h1>No Theme module</h1>
        <p>The accessibility widget must work with or without Bootstrap.</p>
        <p><a id="ordinary-link" href="#status">Ordinary link</a></p>
        <button class="btn btn-primary" type="button">Primary action</button>
        <button class="btn btn-outline-success" type="button">Outline action</button>
        <div id="status" class="alert alert-warning mt-3">Warning with text</div>
    </main>
    <?= $renderer->renderPanel() ?>
</body>
</html>
