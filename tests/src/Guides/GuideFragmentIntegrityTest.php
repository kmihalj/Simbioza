<?php

declare(strict_types=1);

namespace Tests\Guides;

use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorDocumentOutlineBuilder;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlSanitizer;
use App\Guides\BundledManual;
use App\Update\BundledGuideIndex;
use DOMDocument;
use DOMElement;
use DOMXPath;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * EN: Shipped guide navigation survives the same sanitizer and outline pipeline as published Wiki pages.
 * Only the public archive is read; installed content, private configuration and the database are not used.
 *
 * HR: Navigacija isporučenih uputa ostaje ispravna nakon čišćenja i izrade sadržaja objavljenih Wiki stranica.
 * Čita se samo javni paket; ne koriste se instalirani sadržaj, privatna konfiguracija ni baza podataka.
 */
#[CoversNothing]
final class GuideFragmentIntegrityTest extends TestCase
{
    /**
     * EN: Every real same-page or cross-guide fragment resolves to one unique published target in every language.
     * Escaped examples and external links are not interpreted as application navigation.
     *
     * HR: Svako stvarno sidro iste ili druge upute vodi na jedinstveni objavljeni cilj na svakom jeziku.
     * Escapirani primjeri i vanjske poveznice ne tumače se kao navigacija aplikacije.
     */
    #[DataProvider('guideModes')]
    public function testEveryPublishedGuideFragmentHasOneTarget(bool $standalone): void
    {
        $root = dirname(__DIR__, 3);
        $manual = new BundledManual(new Config(new Helper(), [], $root));
        $sanitizer = new EditorHtmlSanitizer();
        $outline = new EditorDocumentOutlineBuilder();
        $definitions = BundledGuideIndex::load($root);
        $this->assertNotNull($definitions);
        $pages = [];
        $duplicateIds = [];
        foreach (BundledGuideIndex::SUPPORTED_LOCALES as $locale) {
            $chapters = $manual->pages($locale);
            $this->assertCount(count($definitions), $chapters, $locale);
            foreach ($chapters as $chapter) {
                $html = $standalone ? BundledManual::html($chapter['html'], '/example', $locale) : $chapter['html'];
                $rendered = $outline->build($sanitizer->sanitize($html))->html;
                $page = $this->navigation($rendered);
                $pages[$locale][$chapter['slug']] = $page;
                foreach ($page['ids'] as $id => $count) {
                    if ($count !== 1) {
                        $duplicateIds[] = $locale . '/' . $chapter['slug'] . '#' . $id;
                    }
                }
            }
        }

        $failures = [];
        $fragmentCount = 0;
        foreach ($pages as $locale => $chapters) {
            foreach ($chapters as $slug => $page) {
                foreach ($page['links'] as $href) {
                    $target = $this->target($href, $slug, $locale);
                    if ($target === null) {
                        continue;
                    }

                    ++$fragmentCount;
                    $matches = $pages[$target['locale']][$target['page']]['ids'][$target['id']] ?? 0;
                    if ($matches !== 1) {
                        $failures[] = $locale . '/' . $slug . ': ' . $href . ' (' . $matches . ' targets)';
                    }
                }
            }
        }

        $this->assertGreaterThan(0, $fragmentCount, 'The shipped manual must contain real fragment navigation.');
        $this->assertSame([], $duplicateIds, 'Published guide IDs must remain unique after rendering.');
        $this->assertSame([], $failures, 'Every published guide fragment must have exactly one target.');
    }

    /**
     * EN: Wiki and standalone routes expose the same bundled chapters through different local URLs.
     *
     * HR: Wiki i samostalne rute prikazuju ista zapakirana poglavlja preko različitih lokalnih URL-ova.
     *
     * @return iterable<string,array{bool}>
     */
    public static function guideModes(): iterable
    {
        yield 'native Wiki guides' => [false];
        yield 'standalone HTML guides' => [true];
    }

    /**
     * EN: Parses rendered content as UTF-8 and inspects actual elements rather than escaped source examples.
     *
     * HR: Parsira prikazani sadržaj kao UTF-8 i provjerava stvarne elemente umjesto escapiranih primjera koda.
     *
     * @return array{ids:array<string,int>,links:list<string>}
     */
    private function navigation(string $html): array
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            if (
                !$document->loadHTML(
                    '<?xml encoding="UTF-8"><!doctype html><html><body>' . $html . '</body></html>',
                    LIBXML_HTML_NODEFDTD,
                )
            ) {
                self::fail('A published guide could not be parsed.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        $xpath = new DOMXPath($document);
        $elements = $xpath->query('//*[@id]');
        if ($elements === false) {
            self::fail('Guide IDs could not be inspected.');
        }

        $ids = [];
        foreach ($elements as $element) {
            if (!$element instanceof DOMElement) {
                self::fail('A guide ID must belong to an element.');
            }

            $id = $element->getAttribute('id');
            $ids[$id] = ($ids[$id] ?? 0) + 1;
        }

        $anchors = $xpath->query('//a[@href and not(ancestor::pre or ancestor::code)]');
        if ($anchors === false) {
            self::fail('Guide anchors could not be inspected.');
        }

        $links = [];
        foreach ($anchors as $anchor) {
            if (!$anchor instanceof DOMElement) {
                self::fail('A guide anchor must belong to an element.');
            }

            $links[] = $anchor->getAttribute('href');
        }

        return ['ids' => $ids, 'links' => $links];
    }

    /**
     * EN: Resolves only same-page fragments and the canonical local Wiki or standalone manual routes.
     *
     * HR: Razrješava samo sidra iste stranice i kanonske lokalne rute Wiki ili samostalnih uputa.
     *
     * @return array{page:string,locale:string,id:string}|null
     */
    private function target(string $href, string $page, string $locale): ?array
    {
        if (!str_contains($href, '#') || preg_match('~\A(?:[a-z][a-z0-9+.-]*:|//)~i', $href) === 1) {
            return null;
        }

        $parts = parse_url($href);
        if (!is_array($parts) || !isset($parts['fragment']) || $parts['fragment'] === '') {
            return null;
        }

        $path = $parts['path'] ?? '';
        if ($path !== '') {
            if (preg_match('~/(?:workspace/korisnicke-upute|guides)/([^/]+)\z~', $path, $matches) !== 1) {
                return null;
            }

            $page = rawurldecode($matches[1]);
        }

        $query = [];
        parse_str($parts['query'] ?? '', $query);
        if (is_string($query['lang'] ?? null) && $query['lang'] !== '') {
            $locale = $query['lang'];
        }

        return ['page' => $page, 'locale' => $locale, 'id' => rawurldecode($parts['fragment'])];
    }
}
