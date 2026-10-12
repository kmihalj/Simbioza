<?php

declare(strict_types=1);

namespace Tests\Setup;

use App\Setup\ApplicationUpdateStatusStore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApplicationUpdateStatusStore::class)]
final class ApplicationUpdateStatusStoreTest extends TestCase
{
    private string $root;

    /**
     * EN: Prepares an isolated private status directory.
     *
     * HR: Priprema izolirani privatni statusni direktorij.
     */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/simbioza-update-status-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/data', 0700, true);
        file_put_contents($this->root . '/VERSION', "1.2.3\n");
    }

    /**
     * EN: Removes only the isolated test files.
     *
     * HR: Uklanja samo izolirane testne datoteke.
     */
    protected function tearDown(): void
    {
        foreach (['data/application-update-status.json', 'VERSION'] as $file) {
            if (is_file($this->root . '/' . $file)) {
                unlink($this->root . '/' . $file);
            }
        }

        rmdir($this->root . '/data');
        rmdir($this->root);
    }

    /**
     * EN: A live process keeps the update in its running state.
     *
     * HR: Živi proces zadržava stanje pokrenute nadogradnje.
     */
    public function testLiveUpdateProcessRemainsRunning(): void
    {
        $this->writeStatus('running', getmypid());

        $status = (new ApplicationUpdateStatusStore($this->root))->status();

        $this->assertSame('running', $status['state']);
        $this->assertSame('running', $status['stage']);
        $this->assertNull($status['progress']);
        $this->assertSame(getmypid(), $status['pid']);
        $this->assertSame('1.2.3', $status['current_version']);
    }

    /**
     * EN: A missing process must no longer leave the GUI queued forever.
     *
     * HR: Nestali proces više ne smije zauvijek zaključati GUI u redu čekanja.
     */
    public function testMissingUpdateProcessBecomesFailed(): void
    {
        $pid = 99_999_999;
        $this->writeStatus('queued', $pid);

        $status = (new ApplicationUpdateStatusStore($this->root))->status();

        $this->assertSame('failed', $status['state']);
        $this->assertSame('failed', $status['stage']);
        $this->assertNull($status['pid']);
        $this->assertSame('Application update process is no longer running.', $status['message']);
    }

    /**
     * EN: The GUI receives a restricted stage and percentage while legacy completed state is normalized safely.
     *
     * HR: GUI dobiva ograničenu fazu i postotak, a završeno starije stanje sigurno se normalizira.
     */
    public function testProgressIsNormalizedForCurrentAndLegacyStatusPayloads(): void
    {
        $this->writeStatus('running', getmypid(), 'dependencies', 150);
        $running = (new ApplicationUpdateStatusStore($this->root))->status();
        $this->assertSame('dependencies', $running['stage']);
        $this->assertSame(100, $running['progress']);

        file_put_contents($this->root . '/data/application-update-status.json', json_encode([
            'state' => 'success',
            'started_at' => '2026-09-21T11:07:46+00:00',
            'finished_at' => '2026-09-21T11:08:12+00:00',
            'pid' => null,
            'message' => 'done',
        ], JSON_THROW_ON_ERROR));
        $success = (new ApplicationUpdateStatusStore($this->root))->status();
        $this->assertSame('complete', $success['stage']);
        $this->assertSame(100, $success['progress']);
    }

    /**
     * EN: Writes one controlled status record.
     *
     * HR: Zapisuje jedan kontrolirani status.
     */
    private function writeStatus(string $state, int $pid, ?string $stage = null, ?int $progress = null): void
    {
        file_put_contents($this->root . '/data/application-update-status.json', json_encode([
            'state' => $state,
            'stage' => $stage,
            'progress' => $progress,
            'started_at' => '2026-09-21T11:07:46+00:00',
            'finished_at' => null,
            'pid' => $pid,
            'message' => 'test',
        ], JSON_THROW_ON_ERROR));
    }
}
