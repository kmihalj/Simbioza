<?php

declare(strict_types=1);

namespace Tests\Controllers;

use App\Controllers\HomeController;
use App\Localization\RuntimeTranslator;
use HeartPhrame\Http\ResponseFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

#[CoversClass(HomeController::class)]
#[UsesClass(RuntimeTranslator::class)]
final class HomeControllerTest extends TestCase
{
    /**
     * EN: Proves that the application retains its built-in homepage without a Workspace resolver.
     *
     * HR: Dokazuje da aplikacija ostaje na ugrađenoj naslovnici bez Workspace resolvera.
     */
    public function testBuiltInHomepageRemainsWhenResolverIsUnavailable(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $responses = $this->createMock(ResponseFactory::class);
        $responses->expects($this->once())
            ->method('view')
            ->with('home/index')
            ->willReturn($response);
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('has')
            ->with('heartphrame.application_homepage_resolver')
            ->willReturn(false);

        $this->assertSame($response, (new HomeController($responses, $container))->index());
    }

    /**
     * EN: Proves the temporary private redirect to the optional resolver result.
     *
     * HR: Dokazuje privremeni, privatni redirect na rezultat opcionalnog resolvera.
     */
    public function testWorkspaceResolverRedirectsWithoutCachingUserChoice(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $responses = $this->createMock(ResponseFactory::class);
        $responses->expects($this->once())
            ->method('redirect')
            ->with(
                '/workspace/portal/pocetna?lang=hr',
                302,
                [
                    'Cache-Control' => 'private, no-store, max-age=0',
                    'Vary' => 'Cookie, Accept-Language',
                ],
            )
            ->willReturn($response);
        $resolver = new class {
            /**
             * EN: Returns a safe internal test path.
             *
             * HR: Vraća sigurnu internu testnu putanju.
             */
            public function resolvePath(): string
            {
                return '/workspace/portal/pocetna?lang=hr';
            }
        };
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($resolver);

        $this->assertSame($response, (new HomeController($responses, $container))->index());
    }
}
