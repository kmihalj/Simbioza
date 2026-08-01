<?php

/**
 * HR: Provjerava da README datoteke rano navode Composer ovisnosti te da svaki
 *     dvojezični modul ima potpun par EN/HR dokumenata.
 *
 * EN: Verifies that README files list Composer dependencies early and that
 *     every bilingual module has complete EN/HR documentation pairs.
 */

declare(strict_types=1);

const DOCUMENTED_MODULES = [
    'heartphrame-module-api',
    'heartphrame-module-auth',
    'heartphrame-module-calendar',
    'heartphrame-module-comment',
    'heartphrame-module-editor-html',
    'heartphrame-module-email',
    'heartphrame-module-menu',
    'heartphrame-module-notification',
    'heartphrame-module-orm',
    'heartphrame-module-task',
    'heartphrame-module-theme',
    'heartphrame-module-workspace',
];

/**
 * HR: Vraća tekstualni sadržaj datoteke ili prekida audit jasnom greškom.
 * EN: Returns file text or stops the audit with a clear error.
 */
function documentationText(string $path): string
{
    $contents = file_get_contents($path);
    if (!is_string($contents)) {
        throw new RuntimeException('Unable to read documentation: ' . $path);
    }

    return $contents;
}

/**
 * HR: Razrješava lokalni sibling repozitorij ili instalirani vendor paket.
 * EN: Resolves a local sibling repository or the installed vendor package.
 */
function documentedModuleRoot(string $applicationRoot, string $module): string
{
    $sibling = dirname($applicationRoot) . '/' . $module;
    if (is_dir($sibling)) {
        return $sibling;
    }
    $vendor = $applicationRoot . '/vendor/aaieduhr/' . $module;
    if (is_dir($vendor)) {
        return $vendor;
    }

    throw new RuntimeException('Unable to resolve documented module: ' . $module);
}

/**
 * HR: Pokreće provjeru ovisnosti i parova dokumenata za dopuštene module.
 * EN: Runs dependency and document-pair checks for the allowed modules.
 */
function runModuleDocumentationAudit(): int
{
    $applicationRoot = dirname(__DIR__);
    $issues = [];
    foreach (DOCUMENTED_MODULES as $module) {
        $root = documentedModuleRoot($applicationRoot, $module);
        $composer = json_decode(
            documentationText($root . '/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        if (!is_array($composer) || !is_array($composer['require'] ?? null)) {
            $issues[] = $module . ': invalid Composer requirements';
            continue;
        }

        foreach (['README.md', 'README_hr.md'] as $readme) {
            $readmeText = documentationText($root . '/' . $readme);
            $firstSection = implode("\n", array_slice(explode("\n", $readmeText), 0, 90));
            foreach ($composer['require'] as $package => $constraint) {
                if (!is_string($package) || !str_starts_with($package, 'aaieduhr/')) {
                    continue;
                }
                if (!str_contains($firstSection, $package)) {
                    $issues[] = $module . '/' . $readme . ': missing early dependency ' . $package;
                }
            }
        }

        $english = glob($root . '/docs/*_en.md') ?: [];
        $croatian = glob($root . '/docs/*_hr.md') ?: [];
        foreach ($english as $englishPath) {
            $expected = substr($englishPath, 0, -6) . '_hr.md';
            if (!is_file($expected)) {
                $issues[] = $module . ': missing ' . basename($expected);
            }
        }
        foreach ($croatian as $croatianPath) {
            $expected = substr($croatianPath, 0, -6) . '_en.md';
            if (!is_file($expected)) {
                $issues[] = $module . ': missing ' . basename($expected);
            }
        }
        if (count($english) !== count($croatian)) {
            $issues[] = sprintf(
                '%s: EN/HR document count differs (%d/%d)',
                $module,
                count($english),
                count($croatian),
            );
        }
    }

    $apiRoot = documentedModuleRoot($applicationRoot, 'heartphrame-module-api');
    $quickStart = documentationText($apiRoot . '/docs/quickstart_en.md');
    foreach (['curl ', '```php', '```json', 'Idempotency-Key', 'application/problem+json'] as $needle) {
        if (!str_contains($quickStart, $needle)) {
            $issues[] = 'API quick start is missing: ' . $needle;
        }
    }

    foreach ($issues as $issue) {
        fwrite(STDERR, '[FAIL] ' . $issue . PHP_EOL);
    }
    printf('Documentation issues / Problemi dokumentacije: %d%s', count($issues), PHP_EOL);

    return $issues === [] ? 0 : 1;
}

exit(runModuleDocumentationAudit());
