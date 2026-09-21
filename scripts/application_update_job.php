<?php

declare(strict_types=1);

// HR: Pozadinski omotač bilježi samo sažetak ishoda; puni izlaz preusmjerava
//     pokretač u privatni log. Smije ga pokrenuti samo Setup worker/CLI.
// EN: The background wrapper records only an outcome summary; its launcher
//     redirects full output to a private log. Only Setup worker/CLI may run it.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

$root = dirname(__DIR__);
$statusPath = $root . '/data/application-update-status.json';
$arguments = [];
foreach (array_slice($argv, 1) as $argument) {
    if (!is_string($argument)) {
        continue;
    }
    if (
        preg_match('/\A--tag=(?:v)?\d+\.\d+\.\d+\z/D', $argument) !== 1
        && preg_match('/\A--lang=(?:hr|en)\z/D', $argument) !== 1
    ) {
        fwrite(STDERR, "Invalid application-update argument.\n");
        exit(2);
    }
    $arguments[] = $argument;
}

writeApplicationUpdateStatus($statusPath, [
    'state' => 'running',
    'stage' => 'preparing',
    'progress' => 5,
    'started_at' => gmdate(DATE_ATOM),
    'finished_at' => null,
    'pid' => getmypid(),
    'message' => 'Application update is running.',
]);

require $root . '/update.php';
$reportProgress = static function (string $stage, int $progress, string $message) use ($statusPath): void {
    writeApplicationUpdateStatus($statusPath, [
        'state' => 'running',
        'stage' => $stage,
        'progress' => $progress,
        'started_at' => readApplicationUpdateStartedAt($statusPath),
        'finished_at' => null,
        'pid' => getmypid(),
        'message' => $message,
    ]);
};
$exitCode = (new \Simbioza\Update\ApplicationUpdateCommand($root, $arguments, $reportProgress))->run();
$lastProgress = readApplicationUpdateProgress($statusPath);
writeApplicationUpdateStatus($statusPath, [
    'state' => $exitCode === 0 ? 'success' : 'failed',
    'stage' => $exitCode === 0 ? 'complete' : 'failed',
    'progress' => $exitCode === 0 ? 100 : $lastProgress,
    'started_at' => readApplicationUpdateStartedAt($statusPath),
    'finished_at' => gmdate(DATE_ATOM),
    'pid' => null,
    'message' => $exitCode === 0 ? 'Application update completed.' : 'Application update failed. Check the private log.',
]);
exit($exitCode);

/** HR: Atomski zapisuje mali status izvan web korijena. EN: Atomically writes a small status outside the web root. */
function writeApplicationUpdateStatus(string $path, array $status): void
{
    $directory = dirname($path);
    if (!is_dir($directory)) {
        throw new RuntimeException('Application data directory is unavailable.');
    }
    $temporary = tempnam($directory, '.application-update-status-');
    if (!is_string($temporary)) {
        throw new RuntimeException('Application update status could not be prepared.');
    }
    try {
        $json = json_encode($status, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        if (file_put_contents($temporary, $json . "\n", LOCK_EX) === false || !chmod($temporary, 0640)) {
            throw new RuntimeException('Application update status could not be written.');
        }
        if (!rename($temporary, $path)) {
            throw new RuntimeException('Application update status could not be activated.');
        }
    } finally {
        if (is_file($temporary)) {
            unlink($temporary);
        }
    }
}

/** HR: Čuva početno vrijeme iz zapisa koji je izradio launcher. EN: Preserves the start time written by the launcher. */
function readApplicationUpdateStartedAt(string $path): string
{
    $payload = is_file($path) ? json_decode((string)file_get_contents($path), true) : null;

    return is_array($payload) && is_string($payload['started_at'] ?? null)
        ? $payload['started_at']
        : gmdate(DATE_ATOM);
}

/** HR: Čuva zadnji potvrđeni postotak za prikaz neuspjele faze. EN: Preserves the last confirmed percentage for a failed-stage display. */
function readApplicationUpdateProgress(string $path): ?int
{
    $payload = is_file($path) ? json_decode((string)file_get_contents($path), true) : null;
    $progress = is_array($payload) && is_int($payload['progress'] ?? null)
        ? $payload['progress']
        : null;

    return $progress === null ? null : max(0, min(100, $progress));
}
