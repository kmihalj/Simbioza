<?php

declare(strict_types=1);

namespace Tests\Configuration;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * EN: Checks bootstrap with dummy settings in separate CLI/HTTP processes.
 *
 * HR: U odvojenim CLI/HTTP procesima provjerava bootstrap s lažnim postavkama.
 */
#[CoversNothing]
final class ConfigurationBootstrapTest extends TestCase
{
    private string $root;

    /**
     * EN: Copies only bootstrap code, never private HFC settings.
     *
     * HR: Kopira samo bootstrap kod, nikada privatne HFC postavke.
     */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/simbioza-bootstrap-' . bin2hex(random_bytes(8));
        $this->assertTrue(mkdir($this->root, 0700));
        // EN: __DIR__ resolves the macOS /var symlink; expectations use the same isolated root.
        //
        // HR: __DIR__ razrješava macOS /var symlink; očekivanja koriste isti izolirani korijen.
        $realRoot = realpath($this->root);
        $this->assertIsString($realRoot);
        $this->root = $realRoot;
        $this->assertTrue(copy(dirname(__DIR__, 3) . '/bootstrap.php', $this->root . '/bootstrap.php'));
        $application = dirname(__DIR__, 3);
        $frameworkSource = $application . '/vendor/aaieduhr/heartphrame-framework/src/';
        $script = '<?php declare(strict_types=1);' . "\n"
        . 'require __DIR__ . "/bootstrap.php";' . "\n"
        . 'require ' . var_export($frameworkSource . 'CodeBook/EnvKeyEnum.php', true) . ';' . "\n"
        . 'require ' . var_export($frameworkSource . 'Config/ConfigPathResolver.php', true) . ';' . "\n"
        . 'require ' . var_export($application . '/src/Configuration/ConfigurationPaths.php', true) . ';' . "\n"
        . <<<'PHP_WRAP'
            $paths = PHP_SAPI === 'cli'
                ? HeartPhrame\Config\ConfigPathResolver::fromEnvironment(__DIR__)
                : App\Configuration\ConfigurationPaths::fromEnvironment(__DIR__)->directories();
            if (PHP_SAPI !== 'cli') {
                header('Content-Type: application/json');
            }
            echo json_encode(['paths' => $paths, 'sapi' => PHP_SAPI], JSON_THROW_ON_ERROR);
            PHP_WRAP;
        file_put_contents($this->root . '/index.php', $script);
    }

    /**
     * EN: Removes only the owned fixture and process logs.
     *
     * HR: Uklanja samo vlastiti fixture i procesne logove.
     */
    protected function tearDown(): void
    {
        foreach (new \DirectoryIterator($this->root) as $file) {
            if (!$file->isDot()) {
                unlink($file->getPathname());
            }
        }

        rmdir($this->root);
    }

    /**
     * EN: The HTTP helper and original CLI resolver see the same default/private/environment layers.
     *
     * HR: HTTP pomoćni sloj i izvorni CLI resolver vide iste zadane/privatne/env slojeve.
     *
     * @param array<string,string>|null $privateParameters
     * @param array<string,string> $processParameters
     * @param list<string> $expectedSuffixes
     */
    #[DataProvider('bootstrapCases')]
    public function testHttpAndCliUseTheSamePaths(
        ?array $privateParameters,
        array $processParameters,
        array $expectedSuffixes,
    ): void {
        if ($privateParameters !== null) {
            $values = $this->expandParameters($privateParameters);
            file_put_contents($this->root . '/framework.php', '<?php return ' . var_export($values, true) . ';');
        }

        $environment = $this->processEnvironment($this->expandParameters($processParameters));
        $expected = array_map(fn(string $suffix): string => $this->root . '/' . $suffix, $expectedSuffixes);
        $process = proc_open([PHP_BINARY, $this->root . '/index.php'], [
            0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
        ], $pipes, $this->root, $environment);
        $this->assertIsResource($process);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($process), (string)$errors);
        $this->assertSame(
            ['paths' => $expected, 'sapi' => 'cli'],
            json_decode((string)$output, true, flags: JSON_THROW_ON_ERROR),
        );

        $socket = stream_socket_server('tcp://127.0.0.1:0');
        $this->assertIsResource($socket);
        $address = stream_socket_get_name($socket, false);
        $this->assertIsString($address);
        fclose($socket);
        $server = proc_open([PHP_BINARY, '-S', $address, '-t', $this->root], [
            0 => ['file', $this->root . '/server.in', 'r'],
            1 => ['file', $this->root . '/server.out', 'w'],
            2 => ['file', $this->root . '/server.err', 'w'],
        ], $serverPipes, $this->root, $environment);
        $this->assertIsResource($server);
        try {
            $connected = false;
            $deadline = microtime(true) + 5;
            while (microtime(true) < $deadline) {
                $connection = @stream_socket_client('tcp://' . $address, timeout: 0.1);
                if (is_resource($connection)) {
                    fclose($connection);
                    $connected = true;
                    break;
                }

                usleep(10000);
            }

            $this->assertTrue($connected, 'Isolated HTTP configuration probe did not start.');
            $response = file_get_contents('http://' . $address . '/index.php', false, stream_context_create([
                'http' => ['timeout' => 5],
            ]));
            $this->assertIsString($response);
            $this->assertSame(
                ['paths' => $expected, 'sapi' => 'cli-server'],
                json_decode($response, true, flags: JSON_THROW_ON_ERROR),
            );
        } finally {
            proc_terminate($server);
            proc_close($server);
        }
    }

    /**
     * EN: Cases covering process precedence, the private file, and the default path.
     *
     * HR: Skup slučajeva za prednost procesa, privatne datoteke i zadane putanje.
     *
     * @return \Iterator<string, array{(array<string, string> | null), array<string, string>, list<string>}>
     */
    public static function bootstrapCases(): \Iterator
    {
        $private = ['HPH_CONFIG_PATH' => '{root}/private', 'HPH_CONFIG_OVERRIDE_PATHS' => '{root}/local'];
        yield 'default' => [null, [], ['config']];
        yield 'private file' => [$private, [], ['private', 'local']];
        yield 'blank process values' => [
            $private, ['HPH_CONFIG_PATH' => ' ', 'HPH_CONFIG_OVERRIDE_PATHS' => ''], ['private', 'local'],
        ];
        yield 'process wins' => [$private, [
            'HPH_CONFIG_PATH' => '{root}/external',
            'HPH_CONFIG_OVERRIDE_PATHS' => '{root}/first' . PATH_SEPARATOR . '{root}/last',
        ], ['external', 'first', 'last']];
    }

    /**
     * EN: Expands dummy paths under the isolated absolute test root.
     *
     * HR: Pretvara lažne putanje u izolirani apsolutni testni korijen.
     *
     * @param array<string,string> $parameters
     * @return array<string,string>
     */
    private function expandParameters(array $parameters): array
    {
        return array_map(fn(string $value): string => str_replace('{root}', $this->root, $value), $parameters);
    }

    /**
     * EN: The child process does not inherit real HFC configuration paths.
     *
     * HR: Dječji proces ne nasljeđuje stvarne HFC konfiguracijske putanje.
     *
     * @param array<string,string> $parameters
     * @return array<string,string>
     */
    private function processEnvironment(array $parameters): array
    {
        $environment = getenv();
        unset($environment['HPH_APP_PATH'], $environment['HPH_CONFIG_PATH'], $environment['HPH_CONFIG_OVERRIDE_PATHS']);
        file_put_contents($this->root . '/server.in', '');

        return array_merge($environment, $parameters);
    }
}
