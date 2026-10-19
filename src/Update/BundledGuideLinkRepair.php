<?php

declare(strict_types=1);

namespace App\Update;

use DOMDocument;
use DOMElement;
use DOMProcessingInstruction;

/**
 * EN: Repairs only explicitly mapped application-owned guide routes, never arbitrary same-slug pages.
 * The recorded archive origin is data; no development hostname is assumed or fetched.
 *
 * HR: Popravlja samo izričito mapirane rute aplikacijskih uputa, nikada proizvoljne stranice istog sluga.
 * Zabilježeni izvor arhive jest podatak; razvojni host ne pretpostavlja se niti dohvaća.
 */
final readonly class BundledGuideLinkRepair
{
    /**
     * EN: Preserves complete filesystem HTML and database fragments, including text/code and external URLs.
     * Repeated current prefixes and source-local URLs resolve only when their guide target exists.
     *
     * HR: Čuva cjelovit HTML datoteka i fragmente baze, uključujući tekst/kod i vanjske URL-ove.
     * Ponovljeni prefiksi i izvorni lokalni URL-ovi razrješavaju se samo ako ciljna uputa postoji.
     *
     * @param array<string,string> $routes
     */
    public static function rewrite(string $html, string $basePath, array $routes, string $sourceSite): string
    {
        $basePath = BundledAssetsUpdater::validatedBasePath($basePath);
        $wholeDocument = preg_match('~<!doctype\b|<html\b~i', $html) === 1;
        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . ($wholeDocument ? $html
            : '<div id="bundled-guide-link-repair">' . $html . '</div>'), LIBXML_NONET | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $dom->encoding = 'UTF-8';
        $changed = false;
        foreach ($dom->getElementsByTagName('*') as $element) {
            foreach (
                ['href', 'src', 'poster', 'data-editor-html-web-href', 'data-editor-html-web-src',
                'data-editor-html-web-poster'] as $attribute
            ) {
                if (!$element->hasAttribute($attribute)) {
                    continue;
                }

                $value = $element->getAttribute($attribute);
                $rewritten = self::resolve($value, $basePath, $routes, $sourceSite);
                if ($rewritten !== $value) {
                    $element->setAttribute($attribute, $rewritten);
                    $changed = true;
                }
            }
        }

        if (!$changed) {
            return $html;
        }

        if ($wholeDocument) {
            foreach (iterator_to_array($dom->childNodes) as $child) {
                if ($child instanceof DOMProcessingInstruction && $child->target === 'xml') {
                    $dom->removeChild($child);
                }
            }

            return (string)$dom->saveHTML();
        }

        $container = $dom->getElementById('bundled-guide-link-repair');
        if (!$container instanceof DOMElement) {
            return $html;
        }

        $result = '';
        foreach ($container->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return $result;
    }

    /**
     * EN: Matches the full recorded origin and base boundary before considering a source URL internal.
     *
     * HR: Provjerava cjelovit zabilježeni izvor i granicu osnove prije prepoznavanja internog URL-a.
     *
     * @param array<string,string> $routes
     */
    private static function resolve(string $url, string $base, array $routes, string $sourceSite): string
    {
        if ($url === '' || $url[0] === '#' || preg_match('/[\x00-\x20\x7f\\\\]/', $url) === 1) {
            return $url;
        }

        $source = parse_url($sourceSite);
        $parts = parse_url($url);
        if (!is_array($source) || !is_array($parts) || isset($parts['user']) || isset($parts['pass'])) {
            return $url;
        }

        $path = $parts['path'] ?? '';
        if (isset($parts['host']) || isset($parts['scheme'])) {
            $scheme = strtolower($parts['scheme'] ?? $source['scheme'] ?? '');
            $sourceScheme = strtolower($source['scheme'] ?? '');
            $sourceBase = rtrim($source['path'] ?? '', '/');
            if (
                !in_array($scheme, ['http', 'https'], true) || $scheme !== $sourceScheme
                || strtolower($parts['host'] ?? '') !== strtolower($source['host'] ?? '')
                || ($parts['port'] ?? ($scheme === 'https' ? 443 : 80))
                !== ($source['port'] ?? ($sourceScheme === 'https' ? 443 : 80))
                || ($sourceBase !== '' && !str_starts_with($path, $sourceBase . '/'))
            ) {
                return $url;
            }

            $path = substr($path, strlen($sourceBase));
        } elseif (!str_starts_with($path, '/')) {
            return $url;
        }

        do {
            if (isset($routes[$path])) {
                return $routes[$path] . (isset($parts['query']) ? '?' . $parts['query'] : '')
                . (isset($parts['fragment']) ? '#' . $parts['fragment'] : '');
            }

            if ($base === '' || !str_starts_with($path, $base . '/')) {
                break;
            }

            $path = substr($path, strlen($base));
        } while ($path !== '');

        return $url;
    }
}
