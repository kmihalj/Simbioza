<?php

declare(strict_types=1);

namespace Tests\Security;

use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorExternalScripts;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlConfig;
use App\Localization\RuntimeTranslator;
use App\Security\ScriptPolicyMiddleware;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use HeartPhrame\Http\Request;
use HeartPhrame\Http\Response;
use HeartPhrame\View\View;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

#[CoversClass(ScriptPolicyMiddleware::class)]
#[UsesClass(RuntimeTranslator::class)]
final class ScriptPolicyMiddlewareTest extends TestCase
{
    /**
     * EN: Untrusted response text never gains a hash or nonce; every request receives a fresh nonce.
     *
     * HR: Nepouzdan tekst odgovora nikada ne dobiva sažetak ili nonce; svaki zahtjev dobiva novi nonce.
     */
    public function testFreshTemplateNonceDoesNotAuthorizeInjectedScripts(): void
    {
        $nonces = [];
        $view = $this->createMock(View::class);
        $view->expects($this->exactly(2))->method('addGlobal')->with('scriptNonce', $this->callback(
            static function (mixed $value) use (&$nonces): bool {
                $nonces[] = $value;
                return is_string($value) && strlen(base64_decode($value, true)) === 24;
            },
        ))->willReturnSelf();
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');
        $middleware = new ScriptPolicyMiddleware($view, new Config(new Helper(), []), $container);
        $html = '<script>window.injected=true;</script><button onclick="alert(1)">Test</button>';
        $response = new Response(200, ['Content-Type' => 'text/html', 'ETag' => 'old'], $html);
        foreach ([1, 2] as $attempt) {
            $returned = $middleware->process(new Request('GET', '/'), $this->handler($response));
            $policy = $returned->getHeaderLine('Content-Security-Policy');
            $this->assertStringContainsString("'nonce-" . $nonces[$attempt - 1] . "'", $policy);
            $this->assertStringContainsString("script-src-attr 'none'", $policy);
            $this->assertStringNotContainsString('unsafe-inline', $policy);
            $this->assertStringNotContainsString('unsafe-eval', $policy);
            $this->assertStringNotContainsString('sha256-', $policy);
            $this->assertSame($html, (string)$returned->getBody());
            $this->assertSame('private, no-store', $returned->getHeaderLine('Cache-Control'));
            $this->assertFalse($returned->hasHeader('ETag'));
        }

        $this->assertNotSame($nonces[0], $nonces[1]);
    }

    /**
     * EN: Existing respondent/component policies and non-HTML caching remain controller-owned.
     *
     * HR: Postojeća pravila sudionika/komponente i predmemorija ne-HTML odgovora ostaju kontroleru.
     */
    public function testPreservesStricterPolicyAndAssetCaching(): void
    {
        $middleware = new ScriptPolicyMiddleware(
            $this->createStub(View::class),
            new Config(new Helper(), []),
            $this->createStub(ContainerInterface::class),
        );
        $response = new Response(200, ['Content-Security-Policy' => "default-src 'none'"], 'component');
        $this->assertSame($response, $middleware->process(new Request('GET', '/'), $this->handler($response)));
        $asset = new Response(200, ['Content-Type' => 'application/javascript', 'Cache-Control' => 'public'], 'code');
        $returned = $middleware->process(new Request('GET', '/a.js'), $this->handler($asset));
        $this->assertSame('public', $returned->getHeaderLine('Cache-Control'));
    }

    /**
     * EN: Approved, selected payloads receive exact hashes without authorizing their whole remote host.
     *
     * HR: Odobreni, odabrani sadržaj dobiva točne sažetke bez odobravanja cijelog udaljenog domaćina.
     */
    public function testOnlyExactEmittedApprovedContentAddsHashes(): void
    {
        $directory = sys_get_temp_dir() . '/script-policy-' . bin2hex(random_bytes(8));
        mkdir($directory . '/config/editor-html', 0770, true);
        $inline = 'window.testLabel = "Čitanje";';
        $integrity = 'sha384-' . base64_encode(hash('sha384', 'remote fixture', true));
        $entries = [
            ['name' => 'Approved inline', 'type' => 'inline', 'value' => $inline, 'status' => 'approved'],
            ['name' => 'Approved remote', 'type' => 'url', 'value' => 'https://scripts.example/test.js',
                'integrity' => $integrity, 'status' => 'approved', 'css_url' => 'https://styles.example/table.css'],
            ['name' => 'Not selected', 'type' => 'inline', 'value' => 'window.absent=1;', 'status' => 'approved'],
            ['name' => 'Pending', 'type' => 'inline', 'value' => 'window.pending=1;', 'status' => 'pending'],
            ['name' => 'Optional integrity', 'type' => 'url', 'value' => 'https://dynamic.example/test.js?a=1',
                'status' => 'approved', 'css_url' => 'https://styles.example/table.css'],
            ['name' => 'Bundle', 'type' => 'html', 'status' => 'approved',
                'value' => '<link rel="stylesheet" href="https://styles.example/table.css">'
                    . '<script src="https://bundle.example/first.js"></script>'
                    . '<script type="text/javascript">window.bundle=1;</script>'],
        ];
        $path = $directory . '/config/editor-html/external-scripts.json';
        file_put_contents($path, json_encode(['scripts' => $entries], JSON_THROW_ON_ERROR));
        $oldPath = getenv('HPH_CONFIG_PATH');
        $oldOverrides = getenv('HPH_CONFIG_OVERRIDE_PATHS');
        putenv('HPH_CONFIG_PATH=' . $directory . '/config');
        putenv('HPH_CONFIG_OVERRIDE_PATHS');
        try {
            $config = new Config(new Helper(), ['app' => ['modules' => ['enabled' => [
                'aaieduhr/heartphrame-module-editor-html',
            ]]]]);
            $editor = new EditorHtmlConfig(
                $config,
                dirname(__DIR__, 4) . '/heartphrame-module-editor-html',
            );
            $container = $this->createMock(ContainerInterface::class);
            $container->method('get')->with(EditorHtmlConfig::class)->willReturn($editor);
            $middleware = new ScriptPolicyMiddleware($this->createStub(View::class), $config, $container);
            $registry = $editor->externalScripts();
            $definitions = array_values($registry->all());
            $html = $registry->render(EditorExternalScripts::placeholder($definitions[0]['id'])
                . EditorExternalScripts::placeholder($definitions[1]['id'])
                . EditorExternalScripts::placeholder($definitions[4]['id'])
                . EditorExternalScripts::placeholder($definitions[5]['id']))['scripts']
            . '<script>window.pending=1;</script><script src="https://unknown.example/bad.js"></script>';
            $response = new Response(200, ['Content-Type' => 'text/html'], $html);
            $returned = $middleware->process(new Request('GET', '/'), $this->handler($response));
            $policy = $returned->getHeaderLine('Content-Security-Policy');
            $this->assertSame(1, substr_count((string)$returned->getBody(), '<link rel="stylesheet"'));
            $this->assertStringContainsString('href="https://styles.example/table.css"', (string)$returned->getBody());
            $this->assertStringContainsString("'sha256-" . base64_encode(hash('sha256', $inline, true)) . "'", $policy);
            $this->assertStringContainsString("'" . $integrity . "'", $policy);
            $this->assertStringNotContainsString('scripts.example', $policy);
            $this->assertStringNotContainsString('dynamic.example', $policy);
            $this->assertMatchesRegularExpression(
                '/<script nonce="[^"]+" src="https:\/\/dynamic.example/',
                (string)$returned->getBody(),
            );
            $this->assertStringContainsString(
                '<script src="https://unknown.example/bad.js"></script>',
                (string)$returned->getBody(),
            );
            $this->assertStringNotContainsString(base64_encode(hash('sha256', 'window.absent=1;', true)), $policy);
            $this->assertStringNotContainsString(
                base64_encode(hash('sha256', 'window.pending=1;', true)),
                $policy,
            );
            $this->assertCount(6, EditorExternalScripts::definitions($entries));
            $this->assertStringContainsString(
                "'sha256-" . base64_encode(hash('sha256', 'window.bundle=1;', true)) . "'",
                $policy,
            );
            $this->assertMatchesRegularExpression(
                '/<script nonce="[^"]+" src="https:\/\/bundle.example/',
                (string)$returned->getBody(),
            );
        } finally {
            putenv($oldPath === false ? 'HPH_CONFIG_PATH' : 'HPH_CONFIG_PATH=' . $oldPath);
            putenv(
                $oldOverrides === false ? 'HPH_CONFIG_OVERRIDE_PATHS' : 'HPH_CONFIG_OVERRIDE_PATHS=' . $oldOverrides,
            );
            unlink($path);
            rmdir(dirname($path));
            rmdir($directory . '/config');
            rmdir($directory);
        }
    }

    /**
     * EN: Returns a fixed response without starting application sessions or persistence.
     *
     * HR: Vraća fiksni odgovor bez pokretanja sesija aplikacije ili pohrane.
     */
    private function handler(ResponseInterface $response): RequestHandlerInterface
    {
        $handler = $this->createStub(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);
        return $handler;
    }
}
