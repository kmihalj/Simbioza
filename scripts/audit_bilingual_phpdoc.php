<?php

/**
 * EN: Finds named PHP functions and methods without an adjacent bilingual
 * PHPDoc block and rejects mixed/reversed marked comments throughout source code.
 * Anonymous functions do not require method documentation; their comments are checked.
 *
 * HR: Pronalazi imenovane PHP funkcije/metode bez neposrednog dvojezičnog
 * PHPDoc bloka te odbija pomiješane/obrnute označene komentare u izvornom kodu.
 * Anonimne funkcije ne trebaju dokumentaciju metode; njihovi komentari provjeravaju se.
 *
 * Usage:
 *   php scripts/audit_bilingual_phpdoc.php
 */

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects -- Importable CLI maintenance entry point.

require_once dirname(__DIR__) . '/vendor/autoload.php';

const AUDITED_PROJECTS = [
    'HFClean',
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
 * EN: Recursively returns production PHP files for one project.
 *
 * HR: Rekurzivno vraća produkcijske PHP datoteke jednog projekta.
 *
 * @return list<string>
 */
function auditedPhpFiles(string $projectDirectory): array
{
    $result = (new \App\Module\NativeProcessRunner())->run(
        ['git', 'ls-files', '--cached', '--others', '--exclude-standard', '-z'],
        $projectDirectory,
    );
    if ($result->exitCode === 0 && !str_contains($projectDirectory, '/vendor/')) {
        $files = [];
        foreach (array_unique(explode("\0", $result->stdout)) as $relative) {
            $path = $projectDirectory . '/' . $relative;
            if (
                preg_match('~\.(?:php|php\.dist)$~', $relative)
                && !preg_match('~\A(?:vendor|data|build|node_modules|\.git)/~', $relative)
                && is_file($path) && !is_link($path)
            ) {
                $files[] = $path;
            }
        }
        sort($files);
        return $files;
    }
    $files = [];
    foreach (['src', 'config', 'resources', 'views', 'tests', 'scripts', 'database', 'bin'] as $sourceDirectory) {
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
 * EN: Resolves a sibling checkout locally or a Composer vendor package in CI.
 *
 * HR: Razrješava sibling checkout lokalno ili Composer vendor paket u CI-ju.
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
 * EN: Extracts named functions/methods and the status of their PHPDoc block.
 *
 * HR: Izvlači imenovane funkcije/metode i status njihova PHPDoc bloka.
 *
 * @phpstan-impure
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
    $issues = [];
    $count = count($tokens);
    $rules = new \App\Tools\BilingualCommentRules();
    foreach ($rules->comments($source) as $comment) {
        if (!$rules->isOrdered($comment['text'])) {
            $issues[] = ['line' => $comment['line'], 'name' => 'comment', 'status' => 'language-order'];
        }
    }
    // EN: Preserve the method-doc scope; tests and templates still receive marked-comment checks.
    //
    // HR: Čuva opseg dokumentacije metoda; testovi i predlošci i dalje imaju provjeru označenih komentara.
    if (str_contains($path, '/tests/') || !preg_match('~/(?:src|config|resources/migrations)/~', $path)) {
        return $issues;
    }

    for ($index = 0; $index < $count; $index++) {
        $token = $tokens[$index];
        if (is_array($token) && $token[0] === T_DOC_COMMENT) {
            $lastDoc = $token[1];
            continue;
        }
        if (is_string($token) && in_array($token, [';', '{', '}'], true)) {
            $lastDoc = null;
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
        if ($lastDoc === null) {
            $status = 'missing';
        } elseif (!str_contains($lastDoc, 'HR:') || !str_contains($lastDoc, 'EN:')) {
            $status = 'not-bilingual';
        }
        if ($status !== null) {
            $issues[] = ['line' => $line, 'name' => $name, 'status' => $status];
        }
        $lastDoc = null;
    }

    return $issues;
}

/**
 * EN: Runs the audit only on repositories that are allowed to be edited.
 *
 * HR: Pokreće audit samo nad repozitorijima koje je dopušteno uređivati.
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

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    exit(runBilingualPhpDocAudit());
}
