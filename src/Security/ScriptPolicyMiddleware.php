<?php

declare(strict_types=1);

namespace App\Security;

use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorExternalScripts;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlConfig;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorScriptBundle;
use HeartPhrame\Config\ConfigInterface;
use HeartPhrame\Http\StreamFactory;
use HeartPhrame\View\View;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * EN: Authorizes template scripts with a fresh request nonce and exact approved content hashes.
 *     Response HTML never grants itself permission to execute arbitrary inline code.
 *
 * HR: Odobrava skripte predložaka svježim nonceom zahtjeva i točnim sažecima odobrenog sadržaja.
 *     HTML odgovora nikada sam sebi ne daje dopuštenje za proizvoljni ugrađeni kod.
 */
final readonly class ScriptPolicyMiddleware implements MiddlewareInterface
{
    /**
     * EN: Uses the host view and lazily resolves the optional page-script registry after module loading.
     *
     * HR: Koristi prikaz domaćina i tek nakon učitavanja modula dohvaća opcionalni registar skripti stranica.
     */
    public function __construct(
        private View $view,
        private ConfigInterface $config,
        private ContainerInterface $container,
    ) {
    }

    /**
     * EN: Adds a nonce only through trusted templates; preserves stricter controller-specific policies.
     *
     * HR: Nonce dodaje samo kroz pouzdane predloške; zadržava stroža pravila pojedinih kontrolera.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $nonce = base64_encode(random_bytes(24));
        $this->view->addGlobal('scriptNonce', $nonce);
        $response = $handler->handle($request);
        if ($response->hasHeader('Content-Security-Policy')) {
            return $response;
        }

        $hashes = [];
        if (str_contains(strtolower($response->getHeaderLine('Content-Type')), 'text/html')) {
            $html = (string)$response->getBody();
            $content = $this->contentScripts($html, $nonce);
            $hashes = $content['hashes'];
            if ($html !== $content['html']) {
                $response = $response->withBody((new StreamFactory())->createStream($content['html']))
                    ->withoutHeader('Content-Length');
            }

            $response = $response->withHeader('Cache-Control', 'private, no-store')
                ->withoutHeader('ETag')->withoutHeader('Last-Modified');
        }

        return $response->withHeader(
            'Content-Security-Policy',
            "script-src 'self' 'nonce-" . $nonce . "'" . ($hashes === [] ? '' : ' ' . implode(' ', $hashes))
            . "; script-src-attr 'none'; base-uri 'self'; object-src 'none'; frame-ancestors 'self'",
        );
    }

    /**
     * EN: Only exact approved markup emitted on this page is authorized. Optional SRI-free URLs get this nonce.
     *
     * HR: Odobrava samo točno odobreni ispis na ovoj stranici. Opcionalni URL-ovi bez SRI-ja dobivaju ovaj nonce.
     *
     * @return array{html:string,hashes:list<string>}
     */
    private function contentScripts(string $html, string $nonce): array
    {
        $enabled = $this->config->get('app.modules.enabled', []);
        $hasContentScript = str_contains($html, '<script');
        if (
            !is_array($enabled) || !in_array('aaieduhr/heartphrame-module-editor-html', $enabled, true)
            || !class_exists(EditorHtmlConfig::class) || !$hasContentScript
        ) {
            return ['html' => $html, 'hashes' => []];
        }

        $editor = $this->container->get(EditorHtmlConfig::class);
        if (!$editor instanceof EditorHtmlConfig) {
            throw new \RuntimeException('Page script registry is unavailable.');
        }

        $registry = $editor->externalScripts();
        $approved = array_filter(
            $registry->all(),
            static fn(array $entry): bool => $entry['status'] === 'approved',
        );
        if ($approved === []) {
            return ['html' => $html, 'hashes' => []];
        }

        $hashes = [];
        foreach ($approved as $entry) {
            foreach (EditorExternalScripts::assets($entry) as $asset) {
                if ($asset['type'] === 'css') {
                    continue;
                }

                $markup = EditorScriptBundle::tag($asset);
                if (!str_contains($html, $markup)) {
                    continue;
                }

                if ($asset['type'] === 'url' && $asset['integrity'] !== '') {
                    $hashes[] = "'" . $asset['integrity'] . "'";
                } elseif ($asset['type'] === 'url') {
                    $html = str_replace($markup, '<script nonce="' . $nonce . '"' . substr($markup, 7), $html);
                } else {
                    $code = str_ireplace('</script', '<\\/script', $asset['value']);
                    $code = str_replace(["\r\n", "\r"], "\n", $code);
                    $hashes[] = "'sha256-" . base64_encode(hash('sha256', $code, true)) . "'";
                }
            }
        }

        return ['html' => $html, 'hashes' => array_values(array_unique($hashes))];
    }
}
