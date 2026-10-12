<?php

/**
 * EN: Verifies that README files list Composer dependencies early and that
 *     every bilingual module has complete EN/HR documentation pairs.
 *
 * HR: Provjerava da README datoteke rano navode Composer ovisnosti te da svaki
 *     dvojezični modul ima potpun par EN/HR dokumenata.
 */

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects -- Importable CLI maintenance entry point.

const DOCUMENTED_MODULES = [
    'heartphrame-module-accessibility',
    'heartphrame-module-api',
    'heartphrame-module-audit',
    'heartphrame-module-auth',
    'heartphrame-module-backup',
    'heartphrame-module-calendar',
    'heartphrame-module-comment',
    'heartphrame-module-editor-html',
    'heartphrame-module-email',
    'heartphrame-module-menu',
    'heartphrame-module-notification',
    'heartphrame-module-orm',
    'heartphrame-module-task',
    'heartphrame-module-theme',
    'simbioza-module-workspace',
    'simbioza-module-workspace-search',
    'simbioza-module-confluence-import',
    'simbioza-module-user',
];

/**
 * EN: Returns file text or stops the audit with a clear error.
 *
 * HR: Vraća tekstualni sadržaj datoteke ili prekida audit jasnom greškom.
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
 * EN: Resolves a local sibling repository or the installed vendor package.
 *
 * HR: Razrješava lokalni sibling repozitorij ili instalirani vendor paket.
 */
function documentedModuleRoot(string $applicationRoot, string $module): string
{
    $sibling = dirname($applicationRoot) . '/' . $module;
    if (is_dir($sibling)) {
        return $sibling;
    }
    foreach (['aaieduhr', 'heartphrame'] as $vendorNamespace) {
        $vendor = $applicationRoot . '/vendor/' . $vendorNamespace . '/' . $module;
        if (is_dir($vendor)) {
            return $vendor;
        }
    }

    throw new RuntimeException('Unable to resolve documented module: ' . $module);
}

/**
 * EN: Verifies complete language pairs, rejects legacy mixed-language names,
 *     and confirms that relative Markdown links target existing files.
 *
 * HR: Provjerava potpune jezične parove, uklanja stare miješane nazive i
 *     potvrđuje da relativne Markdown poveznice vode na postojeće datoteke.
 *
 * @param list<string> $issues
 */
function auditDocumentationPairs(string $root, string $label, array &$issues): void
{
    $documentationRoot = $root . '/docs';
    $english = glob($documentationRoot . '/*_en.md') ?: [];
    $croatian = glob($documentationRoot . '/*_hr.md') ?: [];

    foreach ($english as $englishPath) {
        $expected = substr($englishPath, 0, -6) . '_hr.md';
        if (!is_file($expected)) {
            $issues[] = $label . ': missing ' . basename($expected);
        }
    }
    foreach ($croatian as $croatianPath) {
        $expected = substr($croatianPath, 0, -6) . '_en.md';
        if (!is_file($expected)) {
            $issues[] = $label . ': missing ' . basename($expected);
        }
    }
    if (count($english) !== count($croatian)) {
        $issues[] = sprintf(
            '%s: EN/HR document count differs (%d/%d)',
            $label,
            count($english),
            count($croatian),
        );
    }

    $documents = array_merge(glob($documentationRoot . '/*.md') ?: [], [$root . '/README.md', $root . '/README_hr.md']);
    foreach ($documents as $document) {
        $basename = basename($document);
        if (!in_array($basename, ['README.md', 'README_hr.md'], true) && !preg_match('/_(?:en|hr)\.md$/', $basename)) {
            $issues[] = $label . ': legacy mixed or unpaired document ' . $basename;
        }

        $text = documentationText($document);
        auditDocumentationExamples($text, $label . '/' . $basename, $issues);
        auditDocumentationAnchors($document, $label . '/' . $basename, $issues);
        if (
            preg_match_all(
                '/\]\((?![a-z][a-z0-9+.-]*:|\/|#)([^)#]+\.(?:md|php(?:\.dist)?|json(?:\.dist)?'
                . '|png|svg|jpe?g|webp|zip|html|js))(?:#[^)]*)?\)/i',
                $text,
                $matches,
            )
        ) {
            foreach ($matches[1] as $target) {
                if (!is_string($target)) {
                    continue;
                }
                $resolved = dirname($document) . '/' . rawurldecode($target);
                if (!is_file($resolved)) {
                    $issues[] = sprintf('%s/%s: broken link %s', $label, $basename, $target);
                }
            }
        }
    }
}

/**
 * EN: Verifies local Markdown heading links, including README links and Unicode headings.
 * This is a source check, not a promise that a remote website remains available.
 *
 * HR: Provjerava lokalne Markdown poveznice na naslove, uključujući README i Unicode naslove.
 * Ovo provjerava izvor, ne jamči dostupnost udaljene web stranice.
 *
 * @param list<string> $issues
 */
function auditDocumentationAnchors(string $document, string $label, array &$issues): void
{
    $text = documentationText($document);
    preg_match_all('/\]\((?![a-z][a-z0-9+.-]*:|\/)([^)\s]+)\)/i', $text, $links);
    foreach ($links[1] as $link) {
        $parts = explode('#', rawurldecode($link), 2);
        if (count($parts) !== 2 || $parts[1] === '' || ($parts[0] !== '' && !str_ends_with($parts[0], '.md'))) {
            continue;
        }
        $target = $parts[0] === '' ? $document : dirname($document) . '/' . $parts[0];
        if (!is_file($target)) {
            continue;
        }
        $targetText = preg_replace('/^```[^\n]*\R.*?^```[^\n]*$/ms', '', documentationText($target)) ?? '';
        preg_match_all('/^#{1,6}\h+(.+?)\h*#*\h*\r?$/m', $targetText, $headings);
        $anchors = [];
        foreach ($headings[1] as $heading) {
            $heading = preg_replace('/\[([^]]+)\]\([^)]*\)/', '$1', $heading) ?? $heading;
            $heading = html_entity_decode(strip_tags($heading), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $slug = mb_strtolower(trim($heading), 'UTF-8');
            $slug = preg_replace('/[^\p{L}\p{N}\s_-]/u', '', $slug) ?? $slug;
            $slug = preg_replace('/\s/u', '-', $slug) ?? $slug;
            $suffix = 0;
            $candidate = $slug;
            while (isset($anchors[$candidate])) {
                $candidate = $slug . '-' . ++$suffix;
            }
            $anchors[$candidate] = true;
        }
        preg_match_all('/\b(?:id|name)=["\x27]([^"\x27]+)["\x27]/', $targetText, $explicit);
        if (!isset($anchors[$parts[1]]) && !in_array($parts[1], $explicit[1], true)) {
            $issues[] = $label . ': missing heading target ' . $link;
        }
    }
}

/**
 * EN: Checks complete PHP and JSON examples without executing code or contacting services.
 * Historical release notes retain their original examples and are not installation instructions.
 *
 * HR: Provjerava cjelovite PHP i JSON primjere bez izvršavanja koda ili povezivanja sa servisima.
 * Povijesne bilješke izdanja čuvaju izvorne primjere i nisu upute za instalaciju.
 *
 * @param list<string> $issues
 */
function auditDocumentationExamples(string $text, string $label, array &$issues): void
{
    if (preg_match('~/(?:release-|release_)[0-9]~', $label)) {
        if (!str_contains($text, 'Historical release note') && !str_contains($text, 'Povijesna bilješka izdanja')) {
            $issues[] = $label . ': historical release note lacks an explicit current-guide notice';
        }
        return;
    }
    preg_match_all('/^```(php|json)\h*\R(.*?)^```\h*\r?$/ms', $text, $blocks, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    foreach ($blocks as $block) {
        $type = $block[1][0];
        $source = $block[2][0];
        $line = 1 + substr_count(substr($text, 0, $block[0][1]), "\n");
        try {
            if ($type === 'json') {
                json_decode($source, true, 512, JSON_THROW_ON_ERROR);
            } elseif (str_starts_with(ltrim($source), '<?php')) {
                token_get_all($source, TOKEN_PARSE);
            }
        } catch (ParseError | JsonException $error) {
            $issues[] = sprintf('%s:%d: invalid %s example: %s', $label, $line, $type, $error->getMessage());
        }
    }
}

/**
 * EN: JSON starting examples must be valid complete documents with separate EN then HR explanations.
 * The descriptions are inert data, never JSON comments or real connection secrets.
 *
 * HR: Početni JSON primjeri moraju biti valjani cjeloviti dokumenti s odvojenim EN pa HR objašnjenjima.
 * Opisi su neaktivni podatci, ne JSON komentari niti stvarne tajne veza.
 *
 * @param list<string> $issues
 */
function auditJsonStartingExample(string $text, string $label, array &$issues): void
{
    try {
        $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        $issues[] = $label . ': invalid starting example: ' . $error->getMessage();
        return;
    }
    $documentation = is_array($decoded) ? ($decoded['_documentation'] ?? null) : null;
    if (is_array($decoded) && !array_key_exists('_documentation', $decoded)) {
        $keys = array_keys($decoded);
        $englishPosition = array_search('_description_en', $keys, true);
        $croatianPosition = array_search('_description_hr', $keys, true);
        if ($englishPosition !== false && $croatianPosition !== false && $englishPosition < $croatianPosition) {
            $documentation = ['en' => $decoded['_description_en'], 'hr' => $decoded['_description_hr']];
        }
    }
    if (
        !is_array($documentation) || array_keys($documentation) !== ['en', 'hr']
        || empty($documentation['en']) || empty($documentation['hr'])
    ) {
        $issues[] = $label . ': starting example needs separate non-empty English then Croatian descriptions';
    }
}

/**
 * EN: Runs dependency and document-pair checks for the allowed modules.
 *
 * HR: Pokreće provjeru ovisnosti i parova dokumenata za dopuštene module.
 */
function runModuleDocumentationAudit(): int
{
    $applicationRoot = dirname(__DIR__);
    $issues = [];
    auditDocumentationPairs($applicationRoot, 'HFClean', $issues);
    foreach (glob($applicationRoot . '/config/*.json.dist') ?: [] as $example) {
        auditJsonStartingExample(documentationText($example), 'HFClean/config/' . basename($example), $issues);
    }
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
                if (
                    !is_string($package)
                    || (!str_starts_with($package, 'aaieduhr/') && !str_starts_with($package, 'heartphrame/'))
                ) {
                    continue;
                }
                if (!str_contains($firstSection, $package)) {
                    $issues[] = $module . '/' . $readme . ': missing early dependency ' . $package;
                }
            }
        }

        auditDocumentationPairs($root, $module, $issues);
        foreach (glob($root . '/config/*.json.dist') ?: [] as $example) {
            auditJsonStartingExample(documentationText($example), $module . '/config/' . basename($example), $issues);
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

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    exit(runModuleDocumentationAudit());
}
