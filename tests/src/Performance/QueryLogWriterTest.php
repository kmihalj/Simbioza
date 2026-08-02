<?php

declare(strict_types=1);

namespace Tests\Performance;

use AaiEduHr\HeartPhrameModuleOrm\Database\QueryExecuted;
use App\Performance\QueryLogWriter;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(QueryLogWriter::class)]
#[UsesClass(QueryExecuted::class)]
final class QueryLogWriterTest extends TestCase
{
    private string $logPath;

    /** @var array<string,mixed> */
    private array $serverBackup;

    /**
     * HR: Priprema praznu privremenu datoteku i čuva globalne server vrijednosti.
     * EN: Prepares an empty temporary file and preserves global server values.
     */
    protected function setUp(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'hph_query_log_');
        $this->assertIsString($path);
        $this->logPath = $path;
        $this->serverBackup = $_SERVER;
    }

    /**
     * HR: Uklanja samo testnu datoteku i vraća prethodno server stanje.
     * EN: Removes only the test file and restores the previous server state.
     */
    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
        if (is_file($this->logPath)) {
            unlink($this->logPath);
        }
    }

    /**
     * HR: Query zapis čuva mjerni kontekst i SQL predložak bez URL query stringa.
     * EN: A query record keeps measurement context and SQL without the URL query string.
     */
    public function testWritesOneSafeJsonLineForMeasuredRequest(): void
    {
        $_SERVER['HTTP_X_HPH_PERFORMANCE_RUN'] = 'workspace-list-001';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/api/v1/workspaces?limit=25&token=hidden';
        $writer = new QueryLogWriter($this->logPath);

        $writer(new QueryExecuted(" SELECT  *\n FROM workspaces WHERE owner_id = ? ", 'default', 1.23456789));

        $contents = file_get_contents($this->logPath);
        $this->assertIsString($contents);
        $record = json_decode(trim($contents), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($record);
        $this->assertSame('workspace-list-001', $record['request_id'] ?? null);
        $this->assertSame('GET', $record['method'] ?? null);
        $this->assertSame('/api/v1/workspaces', $record['path'] ?? null);
        $this->assertSame('default', $record['connection'] ?? null);
        $this->assertEqualsWithDelta(1.234568, $record['duration_ms'] ?? null, PHP_FLOAT_EPSILON);
        $this->assertSame('SELECT * FROM workspaces WHERE owner_id = ?', $record['sql'] ?? null);
        $this->assertStringNotContainsString('hidden', $contents);
    }

    /**
     * HR: Writer odbija cilj čiji direktorij ne postoji.
     * EN: The writer rejects a target whose directory does not exist.
     */
    public function testRejectsMissingTargetDirectory(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new QueryLogWriter(sys_get_temp_dir() . '/missing-hph-query-dir/log.jsonl');
    }
}
