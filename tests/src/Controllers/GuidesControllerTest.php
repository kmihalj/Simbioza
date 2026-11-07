<?php

declare(strict_types=1);

namespace Tests\Controllers;

use App\Controllers\GuidesController;
use App\Guides\BundledManual;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use HeartPhrame\Http\ResponseFactory;
use HeartPhrame\Localization\TranslatorInterface;
use HeartPhrame\Routing\UrlGenerator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[CoversClass(GuidesController::class)]
#[UsesClass(BundledManual::class)]
final class GuidesControllerTest extends TestCase
{
    /**
     * EN: Guide choices follow installed interface locales, including extra packs and rejected unknown values.
     *
     * HR: Jezici uputa prate instalirane jezike sučelja, uključujući dodatne pakete i nepoznate vrijednosti.
     *
     * @param list<string> $installed
     */
    #[DataProvider('locales')]
    public function testGuideLanguageUsesTheInstalledLocalizationConfiguration(
        array $installed,
        string $requested,
        string $expected,
    ): void {
        $config = new Config(new Helper(), ['app' => ['localization' => ['supported_locales' => $installed]]]);
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn(['lang' => $requested]);
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('getLocale')->willReturn('en');
        $urls = $this->createMock(UrlGenerator::class);
        $urls->expects($this->once())->method('getPathFor')
            ->with('guides.page', ['slug' => 'simbioza'], ['lang' => $expected])->willReturn('/chosen-guide');
        $response = $this->createStub(ResponseInterface::class);
        $responses = $this->createMock(ResponseFactory::class);
        $responses->expects($this->once())->method('redirect')->with('/chosen-guide')->willReturn($response);
        $controller = new GuidesController(new BundledManual($config), $config, $responses, $urls, $translator);

        $this->assertSame($response, $controller->index($request));
    }

    /**
     * EN: Extra guide languages are usable only when their language pack is active in this installation.
     *
     * HR: Dodatni prijevodi uputa dostupni su samo kada je njihov jezični paket aktivan u ovoj instalaciji.
     *
     * @return iterable<string,array{list<string>,string,string}>
     */
    public static function locales(): iterable
    {
        yield 'installed French' => [['en', 'hr', 'fr'], 'fr', 'fr'];
        yield 'uninstalled French' => [['hr', 'en'], 'fr', 'hr'];
        yield 'installed Italian' => [['en', 'it'], 'it', 'it'];
        yield 'unsupported translation' => [['en', 'nl'], 'nl', 'en'];
    }
}
