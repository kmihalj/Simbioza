<?php

declare(strict_types=1);

namespace Tests\Security;

use HeartPhrame\Config\Config;
use HeartPhrame\Exception\ExceptionResponseFactory;
use HeartPhrame\Exception\ForbiddenException;
use HeartPhrame\Helper\Helper;
use HeartPhrame\Http\Request;
use HeartPhrame\Http\Response;
use HeartPhrame\Middleware\ErrorHandlerMiddleware;
use HeartPhrame\Middleware\SecurityHeadersMiddleware;
use HeartPhrame\Middleware\TrustedProxyMiddleware;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\NullLogger;

/**
 * EN: Uses the unchanged framework to protect normal and exceptional responses.
 *
 * HR: Nepromijenjenim frameworkom štiti normalne odgovore i odgovore iznimki.
 */
#[CoversNothing]
final class SecurityHeadersConfigurationTest extends TestCase
{
    /**
     * EN: Performance profiling cannot sit outside the mandatory security/error pair.
     *
     * HR: Profiliranje performansi ne smije biti izvan obaveznog security/error para.
     */
    public function testRegisteredOrderKeepsSecurityAndErrorHandlingAdjacent(): void
    {
        $previous = getenv('HPH_REQUEST_LOG');
        try {
            putenv('HPH_REQUEST_LOG=' . sys_get_temp_dir() . '/fictional-metrics.jsonl');
            $stack = require dirname(__DIR__, 3) . '/config/middleware.php';
            self::assertSame([TrustedProxyMiddleware::class, SecurityHeadersMiddleware::class,
                ErrorHandlerMiddleware::class], array_slice($stack, 0, 3));
            self::assertSame(3, array_search(\App\Performance\RequestMetricsMiddleware::class, $stack, true));
        } finally {
            putenv($previous === false ? 'HPH_REQUEST_LOG' : 'HPH_REQUEST_LOG=' . $previous);
        }
    }

    /**
     * EN: HTML, redirects and error responses all receive framing and MIME defenses.
     * HSTS is emitted only with explicit HTTPS deployment policy, never plain HTTP.
     *
     * HR: HTML, preusmjerenja i odgovori pogreške dobivaju zaštitu okvira i MIME tipa.
     * HSTS se šalje samo uz izričitu HTTPS politiku instalacije, nikada uz obični HTTP.
     */
    public function testResponseAndExceptionHeaders(): void
    {
        $config = new Config(new Helper(), ['app' => ['security_headers' => [
            'Content-Security-Policy' => "base-uri 'self'; object-src 'none'; frame-ancestors 'self'",
        ]], 'env' => ['debug' => false, 'security_headers' => [
            'strict_transport_security' => 'max-age=3600',
        ]]]);
        $logger = new NullLogger();
        $headers = new SecurityHeadersMiddleware($config, $logger);
        foreach ([200, 302, 403, 500] as $status) {
            $inner = new class ($status) implements RequestHandlerInterface {
                public function __construct(private int $status)
                {
                }

                /**
                 * EN: Produces a fictional response or throws an exception without private data.
                 *
                 * HR: Proizvodi izmišljeni odgovor ili iznimku bez privatnih podataka.
                 */
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    if ($this->status === 403) {
                        throw new ForbiddenException('Private detail');
                    }
                    if ($this->status === 500) {
                        throw new \RuntimeException('Private detail');
                    }
                    return new Response($this->status, ['Content-Type' => 'text/html'], '<p>Test</p>');
                }
            };
            $errors = new ErrorHandlerMiddleware(new ExceptionResponseFactory($config), $logger);
            $handler = new class ($errors, $inner) implements RequestHandlerInterface {
                public function __construct(
                    private ErrorHandlerMiddleware $errors,
                    private RequestHandlerInterface $inner,
                ) {
                }

                /**
                 * EN: Wraps the throwing handler in the application's actual framework error boundary.
                 *
                 * HR: Omata handler iznimke stvarnom frameworkovom granicom pogrešaka aplikacije.
                 */
                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->errors->process($request, $this->inner);
                }
            };
            foreach (['http', 'https'] as $scheme) {
                $response = $headers->process(new Request('GET', $scheme . '://example.invalid/'), $handler);
                self::assertSame($status, $response->getStatusCode());
                self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
                self::assertNotSame('', $response->getHeaderLine('X-Frame-Options'));
                self::assertNotSame('', $response->getHeaderLine('Content-Security-Policy'));
                self::assertSame(
                    $scheme === 'https' ? 'max-age=3600' : '',
                    $response->getHeaderLine('Strict-Transport-Security'),
                );
                self::assertStringNotContainsString('Private detail', (string)$response->getBody());
            }
        }
    }
}
