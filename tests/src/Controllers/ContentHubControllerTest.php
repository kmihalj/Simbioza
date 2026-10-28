<?php

declare(strict_types=1);

namespace Tests\Controllers;

use App\Controllers\ContentHubController;
use App\Localization\RuntimeTranslator;
use HeartPhrame\Config\ConfigInterface;
use HeartPhrame\Http\ResponseFactory;
use HeartPhrame\Localization\TranslatorInterface;
use HeartPhrame\Routing\UrlGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(ContentHubController::class)]
#[UsesClass(RuntimeTranslator::class)]
final class ContentHubControllerTest extends TestCase
{
    /**
     * EN: Product entry points follow active features and leave the full Wiki homepage unchanged.
     *
     * HR: Ulazi alata prate aktivne funkcije i ostavljaju naslovnicu cjelovitog Wikija nepromijenjenom.
     */
    public function testProductHomeSelectionAndMixedDependencies(): void
    {
        $prefix = 'aaieduhr/heartphrame-module-';
        $this->assertNull(ContentHubController::homeRoute([]));
        $this->assertSame('content.pages', ContentHubController::homeRoute([$prefix . 'editor-html']));
        $this->assertSame('calendar.index', ContentHubController::homeRoute([$prefix . 'calendar']));
        $this->assertSame(
            'content.surveys',
            ContentHubController::homeRoute([$prefix . 'editor-html', $prefix . 'survey']),
        );
        $this->assertNull(ContentHubController::homeRoute([
            $prefix . 'editor-html', $prefix . 'survey', 'aaieduhr/simbioza-module-workspace',
        ]));
    }

    /**
     * EN: Disabled or uninstalled feature URLs return 404 without resolving any optional domain service.
     *
     * HR: URL-ovi isključenih ili neinstaliranih funkcija vraćaju 404 bez dohvata opcionalnih domenskih servisa.
     */
    public function testAbsentFeaturesDoNotResolveOptionalDependencies(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');
        $config = $this->createStub(ConfigInterface::class);
        $config->method('getAsArrayWithValuesAsNonEmptyStrings')->willReturn([]);
        $response = $this->createStub(ResponseInterface::class);
        $responses = $this->createMock(ResponseFactory::class);
        $responses->expects($this->exactly(2))->method('text')->with($this->isType('string'), 404)
            ->willReturn($response);
        $controller = new ContentHubController(
            $container,
            $config,
            $responses,
            $this->createStub(UrlGenerator::class),
            $this->createStub(TranslatorInterface::class),
        );
        $request = $this->createStub(ServerRequestInterface::class);
        $this->assertSame($response, $controller->pages($request));
        $this->assertSame($response, $controller->surveys($request));
    }
}
