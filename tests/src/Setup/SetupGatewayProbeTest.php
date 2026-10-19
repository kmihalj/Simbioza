<?php

declare(strict_types=1);

namespace Tests\Setup;

use App\Module\CommandResult;
use App\Module\ProcessRunnerInterface;
use App\Setup\SetupGateway;
use App\Setup\SetupRequestStore;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * EN: Exercises the health-check error boundary using private fixtures and no privileged commands.
 *
 * HR: Provjerava prijenos greške probnog poziva privatnim testnim podacima, bez privilegiranih naredbi.
 */
#[CoversNothing]
final class SetupGatewayProbeTest extends TestCase
{
    /**
     * EN: The helper's real failure is visible and all temporary requests are removed.
     *
     * HR: Stvarna greška helpera ostaje vidljiva, a svi privremeni zahtjevi uklanjaju se.
     */
    public function testProbeRetainsFailureInsteadOfHidingIt(): void
    {
        $root = sys_get_temp_dir() . '/simbioza-helper-probe-' . bin2hex(random_bytes(8));
        mkdir($root, 0700);
        $runner = new class implements ProcessRunnerInterface {
            public function run(array $command, string $workingDirectory): CommandResult
            {
                return new CommandResult(
                    1,
                    '',
                    "Failed to start transient service unit: Source path is not absolute.\n",
                );
            }
        };
        $gateway = new SetupGateway(
            new SetupRequestStore($root . '/requests'),
            $runner,
            $root,
            '/fixture/helper',
            false,
        );
        try {
            $result = $gateway->probeResult();
            $this->assertFalse($result['ready']);
            $this->assertSame(
                'Setup helper failed: Failed to start transient service unit: Source path is not absolute.',
                $result['detail'],
            );
            $this->assertFalse($gateway->probe());
            $this->assertSame([], glob($root . '/requests/*'));
        } finally {
            rmdir($root . '/requests');
            rmdir($root);
        }
    }
}
