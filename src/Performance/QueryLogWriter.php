<?php

declare(strict_types=1);

namespace App\Performance;

use AaiEduHr\HeartPhrameModuleOrm\Database\QueryExecuted;
use InvalidArgumentException;
use RuntimeException;

/**
 * HR: Zapisuje neosjetljive ORM query događaje kao JSONL za ponovljivu analizu
 *     broja i trajanja upita tijekom performance testova.
 *
 * EN: Writes non-sensitive ORM query events as JSONL for repeatable query-count
 *     and duration analysis during performance tests.
 */
final class QueryLogWriter
{
    /** @var list<string> */
    private array $records = [];

    /**
     * HR: Provjerava ciljnu datoteku; direktorij mora unaprijed postojati kako
     *     pogrešna varijabla okruženja ne bi stvarala proizvoljne putanje.
     *
     * EN: Validates the target file; its directory must already exist so a bad
     *     environment variable cannot create arbitrary paths.
     */
    public function __construct(private readonly string $path)
    {
        if (trim($path) === '' || !is_dir(dirname($path))) {
            throw new InvalidArgumentException('Query log target directory does not exist.');
        }

        register_shutdown_function([$this, 'flush']);
    }

    /**
     * HR: Dodaje jedan JSON red pod file lockom. SQL bind vrijednosti nisu dio
     *     QueryExecuted događaja i zato se ne mogu slučajno zapisati.
     *
     * EN: Appends one JSON line under a file lock. SQL bindings are absent from
     *     QueryExecuted and therefore cannot be written accidentally.
     *
     * @throws \JsonException
     */
    public function __invoke(QueryExecuted $event): void
    {
        $requestId = $this->requestId();
        $uri = is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : 'cli';
        $path = parse_url($uri, PHP_URL_PATH);
        $record = [
            'request_id' => $requestId,
            'method' => is_string($_SERVER['REQUEST_METHOD'] ?? null) ? $_SERVER['REQUEST_METHOD'] : 'CLI',
            'path' => is_string($path) && $path !== '' ? $path : '/',
            'connection' => $event->connectionName,
            'duration_ms' => round($event->durationMilliseconds, 6),
            'sql' => preg_replace('/\s+/', ' ', trim($event->sql)) ?? trim($event->sql),
        ];

        $this->records[] = json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * HR: Zapisuje cijeli request u jednom zaključanom diskovnom pozivu kako
     *     profiler ne bi sam dominirao mjerenim vremenom velikih zahtjeva.
     *
     * EN: Writes the complete request in one locked disk operation so the
     *     profiler itself does not dominate large-request measurements.
     */
    public function flush(): void
    {
        if ($this->records === []) {
            return;
        }

        $payload = implode(PHP_EOL, $this->records) . PHP_EOL;
        if (file_put_contents($this->path, $payload, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('Unable to append the performance query log.');
        }

        $this->records = [];
    }

    /**
     * HR: Koristi oznaku konkretnog mjerenja iz zaglavlja ili stabilnu oznaku
     *     trenutačnog PHP zahtjeva kada zaglavlje nije poslano.
     *
     * EN: Uses the measurement marker from the request header, or a stable
     *     marker for the current PHP request when no header was sent.
     */
    private function requestId(): string
    {
        $provided = is_string($_SERVER['HTTP_X_HPH_PERFORMANCE_RUN'] ?? null)
        ? trim($_SERVER['HTTP_X_HPH_PERFORMANCE_RUN'])
        : '';
        if ($provided !== '' && preg_match('/\A[a-zA-Z0-9._:-]{1,100}\z/D', $provided) === 1) {
            return $provided;
        }

        $requestStartedAt = is_float($_SERVER['REQUEST_TIME_FLOAT'] ?? null)
        ? (string)$_SERVER['REQUEST_TIME_FLOAT']
        : 'cli';

        return hash('sha256', getmypid() . '|' . $requestStartedAt);
    }
}
