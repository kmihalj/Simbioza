<?php

declare(strict_types=1);

namespace App\Module;

/** HR: Izvršava već sastavljen argumentni niz bez ljuske. EN: Executes a pre-built argument vector without a shell. */
interface ProcessRunnerInterface
{
    /**
     * HR: Pokreće samo predane argumente u zadanom direktoriju.
     * EN: Runs only the supplied arguments in the requested working directory.
     *
     * @param list<string> $command
     */
    public function run(array $command, string $workingDirectory): CommandResult;
}
