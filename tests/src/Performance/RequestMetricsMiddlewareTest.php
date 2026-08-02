<?php

declare(strict_types=1);

namespace Tests\Performance;

use App\Performance\RequestMetricsMiddleware;
use HeartPhrame\Http\Request;
use HeartPhrame\Http\ResponseFactory;
use HeartPhrame\Http\StreamFactory;
use HeartPhrame\View\View;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(RequestMetricsMiddleware::class)]
final class RequestMetricsMiddlewareTest extends TestCase
{
    private string $logPath;

    /**
     * HR: Priprema praznu ciljnu datoteku za jedan izolirani request.
     * EN: Prepares an empty target file for one isolated request.
     */
    protected function setUp(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'hph_request_log_');
        $this->assertIsString($path);
        $this->logPath = $path;
        putenv('HPH_REQUEST_LOG=' . $path);
    }

    /**
     * HR: Uklanja samo testnu datoteku i performance varijablu.
     * EN: Removes only the test file and the performance environment variable.
     */
    protected function tearDown(): void
    {
        putenv('HPH_REQUEST_LOG');
        if (is_file($this->logPath)) {
            unlink($this->logPath);
        }
    }

    /**
     * HR: Zapis sadrži mjerljive metapodatke bez query stringa i tijela.
     * EN: The record contains measurable metadata without query string or body.
     */
    public function testWritesSafeRequestMetrics(): void
    {
        $responseFactory = new ResponseFactory(new StreamFactory(), $this->createStub(View::class));
        $response = $responseFactory->json(['data' => ['ok' => true]]);
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);
        $request = new Request(
            'GET',
            'https://example.test/api/v1/users?token=hidden',
            ['X-HPH-Performance-Run' => 'request-profile-001'],
        );

        $returned = (new RequestMetricsMiddleware())->process($request, $handler);

        $this->assertSame($response, $returned);
        $contents = file_get_contents($this->logPath);
        $this->assertIsString($contents);
        $record = json_decode(trim($contents), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($record);
        $this->assertSame('request-profile-001', $record['request_id'] ?? null);
        $this->assertSame('GET', $record['method'] ?? null);
        $this->assertSame('/api/v1/users', $record['path'] ?? null);
        $this->assertSame(200, $record['status'] ?? null);
        $this->assertGreaterThanOrEqual(0, $record['duration_ms'] ?? -1);
        $this->assertGreaterThan(0, $record['peak_memory_bytes'] ?? 0);
        $this->assertSame($response->getBody()->getSize(), $record['response_bytes'] ?? null);
        $this->assertStringNotContainsString('hidden', $contents);
        $this->assertStringNotContainsString('"ok"', $contents);
    }
}
