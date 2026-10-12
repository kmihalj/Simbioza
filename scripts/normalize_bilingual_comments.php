<?php

declare(strict_types=1);

// EN: Explicit maintenance tool; only Git-visible code in the application and public modules is eligible.
// --write is a mechanical comment formatter, guarded by identical executable-token hashes.
// Framework, vendor links, private runtime configuration and data are never modified.
//
// HR: Izričiti alat održavanja; obrađuje samo Git-visible kod aplikacije i javnih modula.
// --write mehanički uređuje komentare, uz jednak sažetak izvršivih tokena.
// Framework, vendor linkovi, privatna runtime konfiguracija i podaci nikada se ne mijenjaju.
require dirname(__DIR__) . '/vendor/autoload.php';
require_once __DIR__ . '/audit_bilingual_phpdoc.php';

$rules = new \App\Tools\BilingualCommentRules();
$write = in_array('--write', $argv, true);
$report = ['format' => 'simbioza-comment-normalization-v1', 'files' => []];
$checked = 0;
$base = dirname(__DIR__);
foreach (AUDITED_PROJECTS as $project) {
    $root = auditedProjectDirectory($base, $project);
    if (str_contains($root, '/vendor/')) {
        throw new RuntimeException('Normalization requires a source checkout, never a vendor directory.');
    }
    $result = (new \App\Module\NativeProcessRunner())->run(
        ['git', 'ls-files', '--cached', '--others', '--exclude-standard', '-z'],
        $root,
    );
    if ($result->exitCode !== 0) {
        throw new RuntimeException('Unable to enumerate source files.');
    }
    foreach (array_unique(explode("\0", $result->stdout)) as $relative) {
        if (
            !preg_match('~\.(?:php|php\.dist)$~', $relative)
            || preg_match('~\A(?:vendor|data|build|node_modules|\.git)/~', $relative)
            || !is_file($root . '/' . $relative) || is_link($root . '/' . $relative)
        ) {
            continue;
        }
        $file = $root . '/' . $relative;
        $source = file_get_contents($file);
        if (!is_string($source)) {
            throw new RuntimeException('Unable to read source file.');
        }
        $checked++;
        $updated = $rules->normalizeSource($source);
        if ($updated === $source) {
            continue;
        }
        $report['files'][] = ['path' => $file, 'semantic_sha256' => $rules->semanticHash($source)];
        if ($write && file_put_contents($file, $updated) !== strlen($updated)) {
            throw new RuntimeException('Unable to publish formatted comments.');
        }
    }
}
printf("Checked %d code files; %d require comment normalization.\n", $checked, count($report['files']));
if ($write) {
    $directory = $base . '/build/a5';
    if (!is_dir($directory) && !mkdir($directory, 0700, true)) {
        throw new RuntimeException('Unable to create the verification report directory.');
    }
    $file = $directory . '/comment-normalization.json';
    file_put_contents($file, json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    echo "Executable-token equality report saved.\n";
}
