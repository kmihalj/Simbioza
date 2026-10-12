<?php

declare(strict_types=1);

namespace App\Setup;

use App\Deployment\DeploymentPolicy;
use App\Module\ProcessRunnerInterface;
use RuntimeException;

/**
 * EN: The sole web boundary to the privileged Setup worker. The executable is
 *     administrator configuration, and the user can supply only a request ID.
 *
 * HR: Jedina web-granica prema privilegiranom Setup workeru. Izvršna datoteka
 *     je administratorska konfiguracija, a korisnik može predati samo ID.
 */
final readonly class SetupGateway
{
    /**
     * EN: Receives fixed configuration and a shell-free process runner.
     *
     * HR: Prima fiksnu konfiguraciju i procesni runner bez ljuske.
     */
    public function __construct(
        private SetupRequestStore $requests,
        private ProcessRunnerInterface $processes,
        private string $appRoot,
        private string $helper,
        private bool $directLocalTesting,
    ) {
    }

    /**
     * EN: Submits one pre-allowed action and waits for a validated result.
     *
     * HR: Predaje jednu unaprijed dopuštenu radnju i čeka provjeren rezultat.
     *
     * @param array<string,mixed> $parameters
     */
    public function execute(string $action, array $parameters = []): string
    {
        (new DeploymentPolicy($this->appRoot))->assertWorkerActionAllowed($action);
        if (
            !in_array($action, [
            'health-check',
            'application-update-runtime-check',
            'packages-prepare',
            'packages-cleanup',
            'package-install',
            'package-uninstall',
            'language-add',
            'language-install',
            'language-install-many',
            'language-enable',
            'language-disable',
            'language-remove',
            'application-update-check',
            'application-update-start',
            ], true)
        ) {
            throw new RuntimeException('Unsupported Setup action.');
        }

        $id = $this->requests->create(['action' => $action, 'parameters' => $parameters]);
        $command = $this->directLocalTesting
        ? [PHP_BINARY, $this->appRoot . '/scripts/setup_worker.php', $id]
        : ['sudo', '-n', $this->helper, $id];
        try {
            $result = $this->processes->run($command, $this->appRoot);
            if ($result->exitCode !== 0) {
                $message = trim($result->stderr) !== '' ? trim($result->stderr) : trim($result->stdout);
                throw new RuntimeException('Setup helper failed: ' . $message);
            }

            $worker = $this->requests->consumeResult($id);
        } catch (\Throwable $throwable) {
            $this->requests->discard($id);
            throw $throwable;
        }

        if (!$worker['ok']) {
            throw new RuntimeException($worker['message']);
        }

        return $worker['message'];
    }

    /**
     * EN: Checks whether a local or system worker path is available.
     *
     * HR: Provjerava postoji li lokalni ili sistemski put do workera.
     */
    public function isAvailable(): bool
    {
        return $this->directLocalTesting
        ? is_file($this->appRoot . '/scripts/setup_worker.php')
        : is_file($this->helper) && is_executable($this->helper);
    }

    /**
     * EN: Diagnostics uses the same directory as GUI requests.
     *
     * HR: Dijagnostika koristi isti direktorij kao GUI zahtjevi.
     */
    public function requestDirectory(): string
    {
        return $this->requests->directory();
    }

    /**
     * EN: Performs a real no-op round trip through the helper.
     *
     * HR: Izvršava stvarni no-op prolaz kroz helper.
     */
    public function probe(): bool
    {
        try {
            return $this->execute('health-check') !== '';
        } catch (\Throwable) {
            return false;
        }
    }
}
