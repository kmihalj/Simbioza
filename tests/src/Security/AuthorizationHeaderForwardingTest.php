<?php

declare(strict_types=1);

namespace Tests\Security;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_get_contents;

#[CoversNothing]
final class AuthorizationHeaderForwardingTest extends TestCase
{
    /**
     * HR: Apache mora proslijediti Bearer zaglavlje PHP-u kako bi API autentikacija radila.
     *
     * EN: Apache must forward the Bearer header to PHP so API authentication can work.
     */
    public function testPublicHtaccessForwardsAuthorizationHeader(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 3) . '/public/.htaccess');

        $this->assertIsString($contents);
        $this->assertStringContainsString('RewriteCond %{HTTP:Authorization} .', $contents);
        $this->assertStringContainsString(
            'RewriteRule ^ - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]',
            $contents,
        );
    }
}
