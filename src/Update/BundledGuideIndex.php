<?php

declare(strict_types=1);

namespace App\Update;

use JsonException;
use RuntimeException;

/**
 * EN: Validates the public manual source index and orders parents before children.
 *
 * HR: Provjerava javni manifest uputa i raspoređuje roditelje prije podstranica.
 */
final class BundledGuideIndex
{
    public const SUPPORTED_LOCALES = ['hr', 'en', 'de', 'es', 'fr', 'it'];

    /**
     * EN: Missing indexes permit older releases to retain their existing package inventory.
     *
     * HR: Nedostajući manifest omogućuje starijim izdanjima njihov postojeći popis paketa.
     *
     * @return list<array{slug:string,parent:?string,title:array<string,string>,sources:array<string,list<string>>}>|null
     */
    public static function load(string $root): ?array
    {
        $path = $root . '/resources/installation/manual-sources.json';
        if (!is_file($path)) {
            return null;
        }

        $text = file_get_contents($path);
        if (!is_string($text)) {
            throw new RuntimeException('The bundled manual index cannot be read.');
        }

        try {
            $pages = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new RuntimeException('The bundled manual index is invalid JSON.', 0, $jsonException);
        }

        if (!is_array($pages) || !array_is_list($pages) || $pages === [] || count($pages) > 250) {
            throw new RuntimeException('The bundled manual index must contain a bounded list of chapters.');
        }

        return self::orderedPages($pages);
    }

    /**
     * EN: Rejects duplicate identities, unsafe source paths, unknown parents, and hierarchy cycles.
     *
     * HR: Odbija dvostruke identitete, nesigurne izvore, nepoznate roditelje i cikluse hijerarhije.
     *
     * @param list<mixed> $pages
     * @return list<array{slug:string,parent:?string,title:array<string,string>,sources:array<string,list<string>>}>
     */
    public static function orderedPages(array $pages): array
    {
        $bySlug = [];
        foreach ($pages as $page) {
            if (!is_array($page) || !is_string($page['slug'] ?? null) || !self::validSlug($page['slug'])) {
                throw new RuntimeException('A bundled manual chapter has an invalid slug.');
            }

            $slug = $page['slug'];
            if (isset($bySlug[$slug])) {
                throw new RuntimeException('The bundled manual contains a duplicate chapter: ' . $slug);
            }

            if (
                !array_key_exists('parent', $page)
                || ($page['parent'] !== null && (!is_string($page['parent']) || !self::validSlug($page['parent'])))
            ) {
                throw new RuntimeException('A bundled manual chapter has an invalid parent: ' . $slug);
            }

            if (!is_array($page['title'] ?? null) || !is_array($page['sources'] ?? null)) {
                throw new RuntimeException('A bundled manual chapter lacks translated metadata: ' . $slug);
            }

            $locales = array_keys($page['sources']);
            if (
                !isset($page['sources']['hr'], $page['sources']['en'])
                || array_diff($locales, self::SUPPORTED_LOCALES) !== []
                || array_diff(array_keys($page['title']), $locales) !== []
                || array_diff($locales, array_keys($page['title'])) !== []
            ) {
                throw new RuntimeException('A bundled manual chapter has inconsistent languages: ' . $slug);
            }

            $titles = [];
            $sources = [];
            foreach (self::SUPPORTED_LOCALES as $locale) {
                if (!array_key_exists($locale, $page['sources'])) {
                    continue;
                }

                $title = $page['title'][$locale] ?? null;
                $files = $page['sources'][$locale] ?? null;
                if (
                    !is_string($title) || trim($title) === '' || !is_array($files)
                    || !array_is_list($files) || $files === [] || count($files) > 20
                ) {
                    throw new RuntimeException('A bundled manual chapter lacks translated sources: ' . $slug);
                }

                $titles[$locale] = $title;
                $sources[$locale] = [];
                foreach ($files as $file) {
                    if (
                        !is_string($file)
                        || preg_match('~\Adocs/(?:[a-zA-Z0-9_-]+/)*[a-zA-Z0-9_-]+_' . $locale . '\.md\z~D', $file) !== 1
                    ) {
                        throw new RuntimeException('A bundled manual source path is invalid: ' . $slug);
                    }

                    $sources[$locale][] = $file;
                }
            }

            $bySlug[$slug] = [
                'slug' => $slug,
                'parent' => $page['parent'],
                'title' => $titles,
                'sources' => $sources,
            ];
        }

        $ordered = [];
        $visiting = [];
        $visited = [];
        $visit = static function (string $slug) use (&$visit, &$ordered, &$visiting, &$visited, $bySlug): void {
            if (isset($visited[$slug])) {
                return;
            }

            if (isset($visiting[$slug])) {
                throw new RuntimeException('The bundled manual hierarchy contains a cycle: ' . $slug);
            }

            $visiting[$slug] = true;
            $page = $bySlug[$slug];
            if ($page['parent'] !== null) {
                if (!isset($bySlug[$page['parent']])) {
                    throw new RuntimeException('The bundled manual parent is missing: ' . $page['parent']);
                }

                $visit($page['parent']);
            }

            unset($visiting[$slug]);
            $visited[$slug] = true;
            $ordered[] = $page;
        };
        foreach (array_keys($bySlug) as $slug) {
            $visit($slug);
        }

        return $ordered;
    }

    /**
     * EN: Plans only parent changes for shipped pages; user nodes, identities, ACL, and order stay untouched.
     *
     * HR: Mijenja samo roditelje isporučenih stranica; korisnički čvorovi, identiteti, ACL i redoslijed ostaju.
     *
     * @param list<array<string,mixed>> $nodes
     * @param array<array-key,array{slug:string,parent:?string}> $definitions
     * @return list<array{id:int,parent_id:?int}>
     */
    public static function hierarchyChanges(array $nodes, array $definitions): array
    {
        $bySlug = [];
        foreach ($nodes as $node) {
            if (is_string($node['slug'] ?? null) && is_numeric($node['id'] ?? null)) {
                $bySlug[$node['slug']] = $node;
            }
        }

        $changes = [];
        foreach ($definitions as $definition) {
            $node = $bySlug[$definition['slug']] ?? null;
            if ($node === null) {
                continue;
            }

            $parent = $definition['parent'];
            if ($parent !== null && !isset($bySlug[$parent])) {
                throw new RuntimeException('An installed bundled manual parent is missing: ' . $parent);
            }

            $parentId = $parent === null ? null : (int)$bySlug[$parent]['id'];
            $current = is_numeric($node['parent_id'] ?? null) ? (int)$node['parent_id'] : null;
            if ($current !== $parentId) {
                $changes[] = ['id' => (int)$node['id'], 'parent_id' => $parentId];
            }
        }

        return $changes;
    }

    /**
     * EN: Slugs double as archive basenames and may never contain path components.
     *
     * HR: Slug je i naziv arhiva te nikada ne smije sadržavati dijelove putanje.
     */
    private static function validSlug(mixed $slug): bool
    {
        return is_string($slug) && strlen($slug) <= 120
        && preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $slug) === 1;
    }
}
