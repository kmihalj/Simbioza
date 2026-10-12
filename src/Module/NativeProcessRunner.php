<?php

declare(strict_types=1);

namespace App\Module;

use RuntimeException;

/**
 * EN: Runs an argument vector directly through proc_open without shell evaluation.
 *
 * HR: Pokreće argumentni niz izravno preko proc_open, bez shell evaluacije.
 */
final readonly class NativeProcessRunner implements ProcessRunnerInterface
{
    /**
     * EN: Captures output and always closes every process pipe.
     *
     * HR: Prikuplja izlaz i uvijek zatvara sve pipeove procesa.
     *
     * @param list<string> $command
     */
    public function run(array $command, string $workingDirectory): CommandResult
    {
        $pipes = [];
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $workingDirectory,
        );
        if (!is_resource($process)) {
            throw new RuntimeException('The allowlisted process could not be started.');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return new CommandResult(
            proc_close($process),
            is_string($stdout) ? $stdout : '',
            is_string($stderr) ? $stderr : '',
        );
    }
}
