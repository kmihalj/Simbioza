<?php

/**
 * HR: Pronalazi imenovane PHP funkcije i metode bez neposrednog dvojezičnog
 *     PHPDoc bloka. Provjera namjerno ne obuhvaća anonimne funkcije.
 *
 * EN: Finds named PHP functions and methods without an adjacent bilingual
 *     PHPDoc block. Anonymous functions are intentionally excluded.
 *
 * Usage / Uporaba:
 *   php scripts/audit_bilingual_phpdoc.php
 */

declare(strict_types=1);

const AUDITED_PROJECTS = [
    'HFClean',
    'heartphrame-module-api',
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
    'heartphrame-module-workspace',
    'heartphrame-module-workspace-search',
];

/**
 * HR: Rekurzivno vraća produkcijske PHP datoteke jednog projekta.
 * EN: Recursively returns production PHP files for one project.
 *
 * @return list<string>
 */
function auditedPhpFiles(string $projectDirectory): array
{
    $files = [];
    foreach (['src', 'config', 'resources/migrations'] as $sourceDirectory) {
        $root = $projectDirectory . '/' . $sourceDirectory;
        if (!is_dir($root)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
    }
    sort($files);

    return $files;
}

/**
 * HR: Razrješava sibling checkout lokalno ili Composer vendor paket u CI-ju.
 * EN: Resolves a sibling checkout locally or a Composer vendor package in CI.
 */
function auditedProjectDirectory(string $applicationRoot, string $project): string
{
    if ($project === 'HFClean') {
        return $applicationRoot;
    }
    $sibling = dirname($applicationRoot) . '/' . $project;
    if (is_dir($sibling)) {
        return $sibling;
    }
    foreach (['aaieduhr', 'heartphrame'] as $vendorNamespace) {
        $vendor = $applicationRoot . '/vendor/' . $vendorNamespace . '/' . $project;
        if (is_dir($vendor)) {
            return $vendor;
        }
    }

    throw new RuntimeException('Unable to resolve audited project: ' . $project);
}

/**
 * HR: Izvlači imenovane funkcije/metode i status njihova PHPDoc bloka.
 * EN: Extracts named functions/methods and the status of their PHPDoc block.
 *
 * @return list<array{line:int,name:string,status:string}>
 */
function auditPhpFile(string $path): array
{
    $source = file_get_contents($path);
    if (!is_string($source)) {
        throw new RuntimeException('Unable to read PHP source: ' . $path);
    }
    $tokens = token_get_all($source);
    $lastDoc = null;
    $lastDocLine = 0;
    $issues = [];
    $count = count($tokens);

    for ($index = 0; $index < $count; $index++) {
        $token = $tokens[$index];
        if (is_array($token) && $token[0] === T_DOC_COMMENT) {
            $lastDoc = $token[1];
            $lastDocLine = $token[2];
            continue;
        }
        if (is_string($token) && in_array($token, [';', '{', '}'], true)) {
            $lastDoc = null;
            $lastDocLine = 0;
            continue;
        }
        if (!is_array($token) || $token[0] !== T_FUNCTION) {
            continue;
        }

        $previous = null;
        for ($lookbehind = $index - 1; $lookbehind >= 0; $lookbehind--) {
            $candidate = $tokens[$lookbehind];
            if (is_array($candidate) && in_array($candidate[0], [T_WHITESPACE, T_COMMENT], true)) {
                continue;
            }
            $previous = $candidate;
            break;
        }
        if (is_array($previous) && $previous[0] === T_USE) {
            continue;
        }

        $name = null;
        for ($lookahead = $index + 1; $lookahead < $count; $lookahead++) {
            $candidate = $tokens[$lookahead];
            if (is_string($candidate) && $candidate === '(') {
                break;
            }
            if (is_array($candidate) && $candidate[0] === T_STRING) {
                $name = $candidate[1];
                break;
            }
        }
        if ($name === null) {
            continue;
        }

        $line = $token[2];
        $status = null;
        if ($lastDoc === null || $line - $lastDocLine > 40) {
            $status = 'missing';
        } elseif (!str_contains($lastDoc, 'HR:') || !str_contains($lastDoc, 'EN:')) {
            $status = 'not-bilingual';
        }
        if ($status !== null) {
            $issues[] = ['line' => $line, 'name' => $name, 'status' => $status];
        }
        $lastDoc = null;
        $lastDocLine = 0;
    }

    return $issues;
}

/**
 * HR: Pokreće audit samo nad repozitorijima koje je dopušteno uređivati.
 * EN: Runs the audit only on repositories that are allowed to be edited.
 */
function runBilingualPhpDocAudit(): int
{
    $applicationRoot = dirname(__DIR__);
    $totalIssues = 0;
    foreach (AUDITED_PROJECTS as $project) {
        $projectIssues = 0;
        $projectDirectory = auditedProjectDirectory($applicationRoot, $project);
        foreach (auditedPhpFiles($projectDirectory) as $file) {
            foreach (auditPhpFile($file) as $issue) {
                $relative = $project . '/' . substr($file, strlen($projectDirectory) + 1);
                printf(
                    "%s:%d %s %s()%s",
                    $relative,
                    $issue['line'],
                    $issue['status'],
                    $issue['name'],
                    PHP_EOL,
                );
                $projectIssues++;
            }
        }
        printf('[%s] issues / problemi: %d%s', $project, $projectIssues, PHP_EOL);
        $totalIssues += $projectIssues;
    }
    printf('Total issues / Ukupno problema: %d%s', $totalIssues, PHP_EOL);

    return $totalIssues === 0 ? 0 : 1;
}

exit(runBilingualPhpDocAudit());
