<?php

declare(strict_types=1);

namespace App\Setup;

/**
 * HR: Čita javno siguran sažetak pozadinske nadogradnje iz privatnog data
 *     direktorija. Izlaz naredbi ostaje u administratorskom logu.
 * EN: Reads a disclosure-safe background-update summary from the private data
 *     directory. Command output remains in the administrator log.
 */
final readonly class ApplicationUpdateStatusStore
{
    /** HR: Prima korijen jedne instalacije. EN: Receives one installation root. */
    public function __construct(private string $appRoot)
    {
    }

    /**
     * HR: Vraća normalizirano stanje i trenutačnu verziju aplikacije.
     * EN: Returns normalized state and the current application version.
     *
     * @return array{state:string,started_at:?string,finished_at:?string,pid:?int,message:string,current_version:string}
     */
    public function status(): array
    {
        $payload = [];
        $path = $this->path();
        if (is_file($path)) {
            $decoded = json_decode((string)file_get_contents($path), true);
            $payload = is_array($decoded) ? $decoded : [];
        }

        $state = is_string($payload['state'] ?? null) ? $payload['state'] : 'idle';
        if (!in_array($state, ['idle', 'queued', 'running', 'success', 'failed'], true)) {
            $state = 'idle';
        }

        $pid = is_int($payload['pid'] ?? null) && $payload['pid'] > 0 ? $payload['pid'] : null;

        return [
            'state' => $state,
            'started_at' => is_string($payload['started_at'] ?? null) ? $payload['started_at'] : null,
            'finished_at' => is_string($payload['finished_at'] ?? null) ? $payload['finished_at'] : null,
            'pid' => $pid,
            'message' => is_string($payload['message'] ?? null) ? $payload['message'] : '',
            'current_version' => $this->currentVersion(),
        ];
    }

    /** HR: Vraća privatnu statusnu putanju. EN: Returns the private status path. */
    public function path(): string
    {
        return rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/data/application-update-status.json';
    }

    /** HR: Čita samo valjani stabilni VERSION. EN: Reads only a valid stable VERSION. */
    private function currentVersion(): string
    {
        $path = rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/VERSION';
        $version = is_file($path) ? trim((string)file_get_contents($path)) : '';

        return preg_match('/\A(?:v)?\d+\.\d+\.\d+\z/D', $version) === 1 ? $version : '?';
    }
}
