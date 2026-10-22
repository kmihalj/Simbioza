<?php

declare(strict_types=1);

namespace Tests\Deployment;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * EN: Keeps documented host rotation and container output limits consistent.
 *
 * HR: Čuva usklađenost sistemske rotacije u uputama i granica izlaza kontejnera.
 */
#[CoversNothing]
final class LogRotationConfigurationTest extends TestCase
{
    /**
     * EN: Both languages retain the same explicit logrotate rule without app.log overlap.
     *
     * HR: Oba jezika čuvaju isto izričito logrotate pravilo bez preklapanja s app.log.
     */
    public function testHostRotationExamplesAgree(): void
    {
        $root = dirname(__DIR__, 3);
        $rules = [];
        foreach (['en', 'hr'] as $locale) {
            $text = (string)file_get_contents($root . '/docs/logging_' . $locale . '.md');
            $this->assertSame(1, preg_match('/```conf\n(.*?)```/s', $text, $matches));
            $rule = $matches[1];
            foreach (
                ['email-worker.log', 'application-update.log', 'installer.log',
                'php-fpm.log', 'php-fpm-launchd.log', 'maxsize 10M', 'rotate 14',
                'copytruncate', 'nocreate', 'su root root'] as $required
            ) {
                $this->assertStringContainsString($required, $rule);
            }

            $this->assertStringNotContainsString('/app.log', $rule);
            $rules[] = $rule;
        }

        $this->assertSame($rules[0], $rules[1]);
    }

    /**
     * EN: Every bundled service uses the bounded local logging policy.
     *
     * HR: Svaki priloženi servis koristi ograničenu local politiku logiranja.
     */
    public function testComposeBoundsAllServiceOutput(): void
    {
        $compose = (string)file_get_contents(dirname(__DIR__, 3) . '/docker/compose.yaml');
        $this->assertStringContainsString('logging: &bounded-logging', $compose);
        $this->assertSame(2, substr_count($compose, 'logging: *bounded-logging'));
        $this->assertStringContainsString('max-size: "10m"', $compose);
        $this->assertStringContainsString('max-file: "5"', $compose);
    }
}
