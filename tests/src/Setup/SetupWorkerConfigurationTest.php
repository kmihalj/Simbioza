<?php

declare(strict_types=1);

namespace Tests\Setup;

use App\Setup\SetupRequestStore;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class SetupWorkerConfigurationTest extends TestCase
{
    private string $root;

    /**
     * EN: Isolates the real worker without application data or private settings.
     *
     * HR: Izolira pravi worker bez aplikacijske baze i privatnih postavki.
     */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/simbioza-worker-config-' . bin2hex(random_bytes(8));
        foreach (['scripts', 'vendor', 'config', 'override', 'requests'] as $directory) {
            mkdir($this->root . '/' . $directory, 0770, true);
        }

        $source = dirname(__DIR__, 3);
        copy($source . '/scripts/setup_worker.php', $this->root . '/scripts/setup_worker.php');
        file_put_contents(
            $this->root . '/vendor/autoload.php',
            '<?php return require ' . var_export($source . '/vendor/autoload.php', true) . ';',
        );
        file_put_contents(
            $this->root . '/config/setup.php',
            '<?php return ["request_dir"=>' . var_export($this->root . '/unused', true) . '];',
        );
        file_put_contents(
            $this->root . '/override/setup.php',
            '<?php return ["request_dir"=>' . var_export($this->root . '/requests', true) . '];',
        );
    }

    /**
     * EN: Removes only the test-owned temporary installation.
     *
     * HR: Uklanja samo vlastitu privremenu instalaciju.
     */
    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            if ($file instanceof \SplFileInfo) {
                $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
            }
        }

        rmdir($this->root);
    }

    /**
     * EN: The CLI worker and GUI gateway share the final request_dir setting.
     *
     * HR: CLI worker i GUI gateway dijele zadnji sloj request_dir postavke.
     */
    public function testHealthCheckUsesExternalLayerRequestDirectory(): void
    {
        $requests = new SetupRequestStore($this->root . '/requests');
        $id = $requests->create(['action' => 'health-check', 'parameters' => []]);
        $environment = getenv();
        $environment['HPH_APP_PATH'] = $this->root;
        $environment['HPH_CONFIG_PATH'] = $this->root . '/config';
        $environment['HPH_CONFIG_OVERRIDE_PATHS'] = $this->root . '/override';
        $process = proc_open(
            [PHP_BINARY, $this->root . '/scripts/setup_worker.php', $id],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $this->root,
            $environment,
        );
        $this->assertIsResource($process);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($process), $output);
        $result = $requests->consumeResult($id);
        $this->assertTrue($result['ok']);
        $this->assertSame('Setup helper is ready.', $result['message']);
        $this->assertDirectoryDoesNotExist($this->root . '/unused');
        $this->assertSame([], glob($this->root . '/requests/*.json'));
    }
}
