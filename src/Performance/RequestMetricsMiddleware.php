<?php

declare(strict_types=1);

namespace App\Performance;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * HR: Bilježi vrijeme aplikacijskog requesta, vršnu memoriju i veličinu
 *     odgovora samo kada performance alat postavi sigurnu ciljnu datoteku.
 *
 * EN: Records application request time, peak memory, and response size only
 *     when the performance tool provides a safe target file.
 */
final readonly class RequestMetricsMiddleware implements MiddlewareInterface
{
    /**
     * HR: Propušta normalne zahtjeve bez profiliranja, a označene zapisuje kao
     *     neosjetljivi JSONL zapis bez query stringa i tijela odgovora.
     *
     * EN: Passes normal requests without profiling and writes marked requests
     *     as non-sensitive JSONL without a query string or response body.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $logPath = trim((string)getenv('HPH_REQUEST_LOG'));
        if ($logPath === '') {
            return $handler->handle($request);
        }

        if (!is_dir(dirname($logPath))) {
            throw new RuntimeException('Request metrics target directory does not exist.');
        }

        $startedAt = hrtime(true);
        $startingMemory = memory_get_usage(true);
        $response = $handler->handle($request);
        $streamSize = $response->getBody()->getSize();
        $currentMemory = memory_get_usage(true);
        $record = [
            'request_id' => $this->requestId($request, $startedAt),
            'method' => strtoupper($request->getMethod()),
            'path' => $request->getUri()->getPath() !== '' ? $request->getUri()->getPath() : '/',
            'status' => $response->getStatusCode(),
            'duration_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 6),
            'memory_bytes' => $currentMemory,
            'memory_delta_bytes' => max(0, $currentMemory - $startingMemory),
            'peak_memory_bytes' => memory_get_peak_usage(true),
            'response_bytes' => is_int($streamSize) ? $streamSize : null,
            'content_type' => $response->getHeaderLine('Content-Type'),
        ];
        $json = json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        if (file_put_contents($logPath, $json . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException('Unable to append the request metrics log.');
        }

        return $response;
    }

    /**
     * HR: Koristi sigurnu oznaku mjerenja ili generira lokalni identifikator.
     * EN: Uses a safe measurement marker or generates a local identifier.
     */
    private function requestId(ServerRequestInterface $request, int $startedAt): string
    {
        $provided = trim($request->getHeaderLine('X-HPH-Performance-Run'));
        if ($provided !== '' && preg_match('/\A[a-zA-Z0-9._:-]{1,100}\z/D', $provided) === 1) {
            return $provided;
        }

        return hash(
            'sha256',
            getmypid() . '|' . $startedAt . '|' . $request->getMethod() . '|' . $request->getUri()->getPath(),
        );
    }
}
