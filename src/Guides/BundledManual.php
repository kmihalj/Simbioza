<?php

declare(strict_types=1);

namespace App\Guides;

use HeartPhrame\Config\ConfigInterface;
use RuntimeException;
use ZipArchive;

/**
 * EN: Read-only public manual from the same release archive used by the native Wiki guides.
 * This reader accepts no uploaded archives, database records or caller-supplied filesystem paths.
 *
 * HR: Javni priručnik samo za čitanje iz istog paketa izdanja koji koriste izvorne Wiki upute.
 * Čitač ne prihvaća uvezene arhive, zapise baze ni putanje datoteka zadane pozivom.
 *
 * @phpstan-type ManualPage array{slug:string,title:string,parent:?string,html:string,order:int}
 */
final readonly class BundledManual
{
    /**
     * EN: Uses only the installed release's bundled public guide package.
     *
     * HR: Koristi samo isporučeni paket javnih uputa instaliranog izdanja.
     */
    public function __construct(private ConfigInterface $config)
    {
    }

    /**
     * EN: Returns enabled, published pages for exactly one installed language, never draft revisions.
     *
     * HR: Vraća uključene objavljene stranice za točno jedan instalirani jezik, nikada nacrte.
     *
     * @return list<ManualPage>
     */
    public function pages(string $locale): array
    {
        $zip = $this->open();
        try {
            $versions = [];
            foreach ($this->records($zip, 'editor-html-workspace/versions') as $row) {
                if (($row['language_code'] ?? null) === $locale && ($row['is_published'] ?? false)) {
                    $key = $this->text($row, 'document_key') . ':' . $this->integer($row, 'version_number');
                    $versions[$key] = $this->text($row, 'content_html');
                }
            }

            $workflows = [];
            foreach ($this->records($zip, 'workspace-scope/workflows') as $row) {
                if (($row['language_code'] ?? null) === $locale && ($row['status'] ?? null) === 'published') {
                    $workflows[$this->text($row, 'node_uuid')] = $this->integer($row, 'published_version_number');
                }
            }

            $nodes = $this->records($zip, 'workspace-scope/nodes');
            $slugs = [];
            foreach ($nodes as $row) {
                $slugs[$this->text($row, 'uuid')] = $this->text($row, 'slug');
            }

            $pages = [];
            foreach ($nodes as $row) {
                $version = $workflows[$this->text($row, 'uuid')] ?? null;
                $html = $versions[$this->text($row, 'document_key') . ':' . $version] ?? null;
                if (!($row['is_enabled'] ?? false) || $version === null || $html === null) {
                    continue;
                }

                $title = is_array($row['title_translations'] ?? null) ? $row['title_translations'][$locale] ?? '' : '';
                $pages[] = [
                    'slug' => $this->text($row, 'slug'), 'title' => is_string($title) ? $title : '',
                    'parent' => is_string($row['parent_uuid'] ?? null) ? $slugs[$row['parent_uuid']] ?? null : null,
                    'html' => $html, 'order' => $this->integer($row, 'sort_order'),
                ];
            }

            usort(
                $pages,
                static fn(array $a, array $b): int => [$a['order'], $a['slug']] <=> [$b['order'], $b['slug']],
            );

            return $pages;
        } finally {
            $zip->close();
        }
    }

    /**
     * EN: Resolves only assets actually referenced by a published public guide and verifies its blob digest.
     *
     * HR: Razrješava samo privitke koje koristi objavljena javna uputa i provjerava njihov sažetak.
     *
     * @return array{content:string,mime:string,name:string}|null
     */
    public function asset(string $uuid, string $locale): ?array
    {
        if (preg_match('/\A[a-f0-9-]{36}\z/D', $uuid) !== 1) {
            return null;
        }

        $referenced = false;
        foreach ($this->pages($locale) as $page) {
            if (str_contains($page['html'], '/editor-html/asset/' . $uuid)) {
                $referenced = true;
                break;
            }
        }

        if (!$referenced) {
            return null;
        }

        $zip = $this->open();
        try {
            foreach ($this->records($zip, 'editor-html-workspace/assets') as $row) {
                if (($row['uuid'] ?? null) !== $uuid || ($row['is_deleted'] ?? false)) {
                    continue;
                }

                $blob = is_array($row['blob'] ?? null) ? $row['blob'] : [];
                $hash = $blob['sha256'] ?? '';
                if (!is_string($hash) || preg_match('/\A[a-f0-9]{64}\z/D', $hash) !== 1) {
                    throw new RuntimeException('Invalid bundled guide asset.');
                }

                $content = $zip->getFromName('blobs/' . substr($hash, 0, 2) . '/' . $hash);
                if (!is_string($content) || !hash_equals($hash, hash('sha256', $content))) {
                    throw new RuntimeException('Bundled guide asset integrity check failed.');
                }

                return [
                    'content' => $content, 'mime' => $this->text($row, 'mime_type'),
                    'name' => $this->text($row, 'original_name'),
                ];
            }

            return null;
        } finally {
            $zip->close();
        }
    }

    /**
     * EN: Rewrites native-guide URL attributes without changing prose, code or external URLs.
     * Preformatted content, comments and raw script/style contents retain their original bytes.
     *
     * HR: Prepisuje URL atribute izvornih uputa bez izmjene teksta, koda i vanjskih URL-ova.
     * Preformatirani sadržaj, komentari te sadržaj script/style elemenata zadržavaju izvorne bajtove.
     */
    public static function html(string $html, string $basePath, string $locale): string
    {
        $basePath = rtrim($basePath, '/');
        $attributes = '(?:[^<>"\x27]|"[^"]*"|\x27[^\x27]*\x27)*';

        return preg_replace_callback(
            '~(<(pre|code|script|style)\b' . $attributes . '>)(.*?)(</\2\s*>)|<!--.*?-->'
            . '|<[a-z][a-z0-9:-]*\b' . $attributes . '>~is',
            static function (array $tag) use ($basePath, $locale): string {
                if (str_starts_with($tag[0], '<!--')) {
                    return $tag[0];
                }

                $element = $tag[0];
                $suffix = '';
                if (isset($tag[1], $tag[2], $tag[3], $tag[4])) {
                    if (in_array(strtolower($tag[2]), ['pre', 'code'], true)) {
                        return $tag[0];
                    }

                    $element = $tag[1];
                    $suffix = $tag[3] . $tag[4];
                }

                $rewritten = preg_replace_callback(
                    '~(\s+)([a-z_:][a-z0-9_.:-]*)(\s*=\s*)'
                    . '("[^"]*"|\x27[^\x27]*\x27|[^\s"\x27=<>`]+)~i',
                    static function (array $attribute) use ($basePath, $locale): string {
                        if (!in_array(strtolower($attribute[2]), ['href', 'src', 'poster'], true)) {
                            return $attribute[0];
                        }

                        $value = $attribute[4];
                        $quote = in_array($value[0], ['"', "'"], true) ? $value[0] : '';
                        $url = $quote === '' ? $value : substr($value, 1, -1);
                        $rewritten = self::guideUrl($url, $basePath, $locale);

                        return $attribute[1] . $attribute[2] . $attribute[3] . $quote . $rewritten . $quote;
                    },
                    $element,
                ) ?? $element;

                return $rewritten . $suffix;
            },
            $html,
        ) ?? $html;
    }

    /**
     * EN: Resolves only canonical local guide paths; asset query parameters and fragments survive.
     *
     * HR: Razrješava samo kanonske lokalne putanje uputa; upiti i sidra privitaka ostaju očuvani.
     */
    private static function guideUrl(string $url, string $basePath, string $locale): string
    {
        $prefix = '/workspace/korisnicke-upute/';
        if (str_starts_with($url, $prefix)) {
            return $basePath . '/guides/' . substr($url, strlen($prefix));
        }

        if (preg_match('~\A/editor-html/asset/([a-f0-9-]{36})(\?[^#]*)?(#.*)?\z~D', $url, $match) !== 1) {
            return $url;
        }

        $query = $match[2] ?? '';
        $language = rawurlencode($locale);
        $count = 0;
        $query = preg_replace_callback(
            '~((?:\?|&(?:amp;)?)lang=)[^&#]*~',
            static fn(array $parameter): string => $parameter[1] . $language,
            $query,
            -1,
            $count,
        ) ?? $query;
        if ($count === 0) {
            $query = ($query === '' || $query === '?' ? '?' : $query . '&amp;') . 'lang=' . $language;
        }

        return $basePath . '/guides/assets/' . $match[1] . $query . ($match[3] ?? '');
    }

    /**
     * EN: Opens only the fixed release artifact; the seed password is public, not an authentication secret.
     *
     * HR: Otvara samo fiksni paket izdanja; lozinka početnog paketa javna je, nije tajna za prijavu.
     */
    private function open(): ZipArchive
    {
        $zip = new ZipArchive();
        $archive = $this->config->getAppRootDir() . '/resources/installation/workspace/korisnicke-upute.zip';
        if ($zip->open($archive) !== true) {
            throw new RuntimeException('The bundled public manual is unavailable.');
        }

        $zip->setPassword('SimbiozaSeed2026!');

        return $zip;
    }

    /**
     * EN: Decodes fixed, size-limited NDJSON datasets from the shipped package.
     *
     * HR: Čita fiksne NDJSON skupove ograničene veličine iz isporučenog paketa.
     *
     * @return list<array<string,mixed>>
     */
    private function records(ZipArchive $zip, string $dataset): array
    {
        $content = $zip->getFromName('data/' . $dataset . '.ndjson', 8 * 1024 * 1024 + 1);
        if (!is_string($content) || strlen($content) > 8 * 1024 * 1024) {
            throw new RuntimeException('Invalid bundled public manual dataset.');
        }

        $rows = [];
        foreach (explode("\n", trim($content)) as $line) {
            if ($line === '') {
                continue;
            }

            $row = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($row)) {
                throw new RuntimeException('Invalid bundled public manual record.');
            }

            $named = [];
            foreach ($row as $key => $value) {
                if (!is_string($key)) {
                    throw new RuntimeException('Invalid bundled public manual record key.');
                }

                $named[$key] = $value;
            }

            $rows[] = $named;
        }

        return $rows;
    }

    /**
     * EN: Validates typed public archive fields instead of coercing arbitrary values.
     *
     * HR: Provjerava tipove polja javne arhive umjesto pretvaranja proizvoljnih vrijednosti.
     *
     * @param array<string,mixed> $row
     */
    private function text(array $row, string $key): string
    {
        $value = $row[$key] ?? null;
        if (!is_string($value)) {
            throw new RuntimeException('Invalid bundled public manual field: ' . $key);
        }

        return $value;
    }

    /**
     * EN: Requires integer revision and ordering identifiers from the public package.
     *
     * HR: Zahtijeva cjelobrojne oznake revizije i redoslijeda iz javnog paketa.
     *
     * @param array<string,mixed> $row
     */
    private function integer(array $row, string $key): int
    {
        $value = $row[$key] ?? null;
        if (!is_int($value)) {
            throw new RuntimeException('Invalid bundled public manual number: ' . $key);
        }

        return $value;
    }
}
