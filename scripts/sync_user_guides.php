<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveReader;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveWriter;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupConfig;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupProviderMetadata;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupScope;
use App\Update\BundledGuideIndex;
use HeartPhrame\Bridge\ComposerBridge;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;

// EN: Maintains public installation packages only; never loads the application or its database.
//
// HR: Održava samo javne instalacijske pakete; nikada ne učitava aplikaciju ni njezinu bazu.
// phpcs:disable PSR1.Files.SideEffects -- Importable development CLI entry point.
require_once __DIR__ . '/sync_installation_guides.php';

/**
 * EN: Provides an isolated archive configuration, independent of live settings.
 *
 * HR: Daje izdvojenu konfiguraciju arhiva, neovisnu o aktivnim postavkama.
 */
function manualArchiveConfig(string $temporary, string $root): BackupConfig
{
    $composer = new class ($temporary) extends ComposerBridge {
        /**
         * EN: Keeps all generated files in the isolated staging root.
         *
         * HR: Zadržava sve generirane datoteke u izdvojenom radnom direktoriju.
         */
        public function __construct(private readonly string $root)
        {
        }

        /**
         * EN: Supplies archive metadata without reading the running application.
         *
         * HR: Daje metapodatke arhive bez čitanja aktivne aplikacije.
         *
         * @return array<string,mixed>
         */
        public function getRootPackage(): array
        {
            return ['install_path' => $this->root];
        }
    };
    return new BackupConfig(new Config(new Helper(), ['backup' => [
        'staging_dir' => $temporary . '/staging', 'archive_dir' => $temporary . '/archives',
    ]]), $composer, $root . '/vendor/aaieduhr/heartphrame-module-backup');
}

/**
 * EN: Stable identities allow updates to preserve existing page links and permissions.
 *
 * HR: Stabilni identiteti omogućuju nadogradnju uz očuvanje poveznica i ovlasti stranica.
 */
function manualIdentity(string $key): string
{
    $hash = hash('sha256', 'simbioza-public-manual/' . $key);
    return substr($hash, 0, 8) . '-' . substr($hash, 8, 4) . '-5' . substr($hash, 13, 3)
    . '-a' . substr($hash, 17, 3) . '-' . substr($hash, 20, 12);
}

/**
 * EN: Resolves a parent's real archived identity instead of deriving historical UUIDs again.
 *
 * HR: Razrješava stvarni arhivirani identitet roditelja bez ponovnog izvođenja povijesnih UUID-ova.
 *
 * @param array<string,array<string,mixed>> $nodesBySlug
 */
function manualParentUuid(?string $parent, array $nodesBySlug): ?string
{
    if ($parent === null) {
        return null;
    }

    $uuid = $nodesBySlug[$parent]['uuid'] ?? null;
    if (!is_string($uuid) || preg_match('/\A[a-f0-9]{8}(?:-[a-f0-9]{4}){3}-[a-f0-9]{12}\z/D', $uuid) !== 1) {
        throw new RuntimeException('The archived manual parent is missing or invalid: ' . $parent);
    }

    return $uuid;
}

/**
 * EN: Refreshes replaced public attachment bytes without changing their stable identity or ownership.
 *
 * HR: Osvježava zamijenjene bajtove javnog privitka bez promjene njegova stalnog identiteta i vlasništva.
 *
 * @param array<string,mixed> $row
 * @param list<array<string,mixed>> $versions
 * @param array{file:string,sha256:string,original_name?:string} $asset
 * @return array{asset:array<string,mixed>,versions:list<array<string,mixed>>}
 */
function refreshManualAssetPayload(array $row, array $versions, array $asset, string $root): array
{
    $path = $root . '/' . $asset['file'];
    clearstatcache(true, $path);
    $size = filesize($path);
    $mime = mime_content_type($path);
    if (!is_int($size) || !is_string($mime)) {
        throw new RuntimeException('Unable to read public attachment: ' . $asset['file']);
    }

    $row['original_name'] = $asset['original_name'] ?? basename($path);
    $row['display_name'] = $row['original_name'];
    $row['stored_name'] = $row['uuid'] . '-' . basename($path);
    $row['mime_type'] = $mime;
    $row['file_size'] = $size;
    $row['blob'] = ['sha256' => $asset['sha256'], 'size' => $size,
        'path' => 'blobs/' . substr($asset['sha256'], 0, 2) . '/' . $asset['sha256'],
        'media_type' => $mime, 'original_name' => $row['original_name']];
    foreach ($versions as &$version) {
        if ($version['asset_uuid'] !== $row['uuid'] || (int)$version['is_current'] !== 1) {
            continue;
        }

        foreach (['original_name', 'stored_name', 'mime_type', 'file_size'] as $field) {
            $version[$field] = $row[$field];
        }

        // EN: The current version reuses the refreshed asset payload; older versions remain unchanged.
        //
        // HR: Trenutačna verzija koristi osvježeni sadržaj privitka; starije verzije ostaju nepromijenjene.
        $version['blob'] = null;
    }

    unset($version);
    return ['asset' => $row, 'versions' => $versions];
}

/**
 * EN: Reads public package datasets while keeping their schema descriptors.
 *
 * HR: Čita skupove javnog paketa uz očuvanje opisa njihove sheme.
 * @return array<string,array<string,list<array<string,mixed>>>>
 */
function manualRecords(BackupArchiveReader $reader): array
{
    $records = [];
    foreach ($reader->manifest()['providers'] as $provider) {
        foreach (array_keys($provider['datasets']) as $dataset) {
            $records[$provider['id']][$dataset] = iterator_to_array($reader->records($provider['id'], $dataset));
        }
    }

    return $records;
}

/**
 * EN: Fingerprints Markdown and the exact referenced image bytes.
 *
 * HR: Potpisuje Markdown i točne bajtove korištenih slika.
 * @param array<string,mixed> $page
 * @param array<string,array{file:string,sha256:string,original_name?:string}> $assets
 */
function manualSourceHash(string $root, array $page, string $locale, array $assets): string
{
    if (
        in_array(
            $page['slug'],
            ['simbioza', 'instalacija', 'apache-mod-php-instalacija', 'php-fpm-instalacija'],
            true,
        )
    ) {
        return installationGuideSourceHash($root, $page['slug'], $locale);
    }

    $source = [$page['title'][$locale], $page['parent']];
    foreach ($page['sources'][$locale] as $file) {
        $text = file_get_contents($root . '/' . $file);
        if (!is_string($text)) {
            throw new RuntimeException('Missing guide source: ' . $file);
        }

        $source[$file] = $text;
        foreach ($assets as $uuid => $asset) {
            if (str_contains($text, basename($asset['file']))) {
                $source[$uuid] = $asset['sha256'];
            }
        }
    }

    return hash('sha256', json_encode($source, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

/**
 * EN: Renders portable pages with native links and attachment identities.
 *
 * HR: Renderira prenosive stranice s izvornim poveznicama i identitetima privitaka.
 * @param array<string,mixed> $page
 * @param list<array<string,mixed>> $pages
 * @param array<string,array{file:string,sha256:string,original_name?:string}> $assets
 */
function renderManualPage(string $root, array $page, string $locale, array $pages, array $assets): string
{
    if (
        in_array(
            $page['slug'],
            ['simbioza', 'instalacija', 'apache-mod-php-instalacija', 'php-fpm-instalacija'],
            true,
        )
    ) {
        return renderInstallationGuide($root, $page['slug'], $locale);
    }

    $html = '<section data-simbioza-manual-sha256="' . manualSourceHash($root, $page, $locale, $assets)
    . '">' . PHP_EOL;
    foreach ($page['sources'][$locale] as $file) {
        $text = (string)file_get_contents($root . '/' . $file);
        // EN: The host provides the page heading; retain lower-level content headings.
        //
        // HR: Naslov stranice daje aplikacija; zadržavamo niže naslove sadržaja.
        $text = preg_replace('/\A# [^\r\n]+\R+/', '', $text) ?? $text;
        $process = proc_open(
            ['pandoc', '--from=gfm', '--to=html5', '--no-highlight', '--wrap=none'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        if (!is_resource($process)) {
            throw new RuntimeException('Pandoc is required to rebuild the public manual.');
        }

        fwrite($pipes[0], $text);
        fclose($pipes[0]);
        $rendered = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0 || !is_string($rendered) || $rendered === '') {
            throw new RuntimeException('Pandoc failed: ' . $file . ': ' . $error);
        }

        $rendered = preg_replace_callback(
            '/(href|src|poster)="([^"]+)"/',
            static function (array $match) use ($root, $file, $pages, $assets): string {
                $url = html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (!preg_match('~\A(?:[a-z][a-z0-9+.-]*:|/|#)~i', $url)) {
                    $parts = explode('#', $url, 2);
                    $target = realpath($root . '/' . dirname($file) . '/' . rawurldecode($parts[0]));
                    $mapped = false;
                    foreach ($assets as $uuid => $asset) {
                        if ($target === realpath($root . '/' . $asset['file'])) {
                            $url = '/editor-html/asset/' . $uuid;
                            $mapped = true;
                            break;
                        }
                    }

                    foreach ($pages as $candidate) {
                        foreach (array_keys($candidate['sources']) as $language) {
                            foreach ($candidate['sources'][$language] as $candidateFile) {
                                if (!$mapped && $target === realpath($root . '/' . $candidateFile)) {
                                    $url = '/workspace/korisnicke-upute/' . $candidate['slug'] . '?lang=' . $language;
                                    $url .= isset($parts[1]) ? '#' . $parts[1] : '';
                                    $mapped = true;
                                }
                            }
                        }
                    }

                    if (!$mapped) {
                        if ($target === false) {
                            throw new RuntimeException('Unresolved guide link in ' . $file . ': ' . $url);
                        }

                        $relative = substr($target, strlen($root) + 1);
                        $url = 'https://github.com/kmihalj/Simbioza/blob/main/' . $relative;
                        $url .= isset($parts[1]) ? '#' . $parts[1] : '';
                    }
                }

                return $match[1] . '="' . htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
            },
            $rendered,
        );
        $html .= styleGuideTables($rendered);
    }

    return $html . '</section>' . PHP_EOL;
}

/**
 * EN: Writes and reopens a staged archive before any repository artifact is replaced.
 *
 * HR: Zapisuje i ponovno otvara radnu arhivu prije zamjene ijednog paketa u repozitoriju.
 * @param array<string,mixed> $manifest
 * @param array<string,array<string,list<array<string,mixed>>>> $records
 * @param array<string,string> $blobs
 */
function writeManualArchive(
    BackupConfig $config,
    BackupFilesystem $fs,
    array $manifest,
    array $records,
    array $blobs,
): string {
    $writer = new BackupArchiveWriter(
        $config,
        $fs,
        new BackupScope($manifest['scope']['type'], $manifest['scope']['identifier']),
        $manifest['source'],
        $manifest['label'],
        'SimbiozaSeed2026!',
    );
    foreach ($manifest['providers'] as $provider) {
        $writer->beginProvider(new BackupProviderMetadata(
            $provider['id'],
            $provider['package'],
            $provider['schema_version'],
            $provider['label'],
            $provider['dependencies'],
            $provider['scopes'],
            $provider['required_for_full_restore'],
            $provider['contains_sensitive_data'],
            $provider['required_enabled_packages'],
            $provider['automatic'],
            $provider['component_groups'],
        ));
        foreach ($records[$provider['id']] ?? [] as $dataset => $rows) {
            foreach ($rows as $row) {
                $writer->writeRecord($provider['id'], $dataset, $row);
            }
        }
    }

    foreach ($blobs as $file) {
        $writer->addBlobFile($file);
    }

    $archive = $writer->finish();
    $verify = new BackupArchiveReader($archive, $config, $fs, 'SimbiozaSeed2026!');
    $verify->close();
    return $archive;
}

/**
 * EN: Adds one language without renumbering the existing document history or importing any permissions.
 *
 * HR: Dodaje jedan jezik bez promjene brojeva postojeće povijesti dokumenta ili uvoza ovlasti.
 *
 * @param list<array<string,mixed>> $versions
 * @param list<array<string,mixed>> $workflows
 * @param list<array<string,mixed>> $documents
 * @param array<string,mixed> $node
 */
function appendManualTranslation(
    array &$versions,
    array &$workflows,
    array &$documents,
    array $node,
    string $locale,
    string $title,
    string $html,
): void {
    $key = $node['document_key'];
    $template = null;
    $maximum = 0;
    foreach ($versions as $version) {
        if ($version['document_key'] !== $key) {
            continue;
        }

        if ($version['language_code'] === $locale) {
            throw new RuntimeException('The public guide translation already exists: ' . $key . '/' . $locale);
        }

        $template = $version;
        $maximum = max($maximum, (int)$version['version_number']);
    }

    $workflow = null;
    foreach ($workflows as $row) {
        if ($row['node_uuid'] === $node['uuid']) {
            $workflow = $row;
            break;
        }
    }

    if ($template === null || $workflow === null) {
        throw new RuntimeException('The public guide lacks an existing version or workflow: ' . $key);
    }

    $template['source_id'] = max(array_column($versions, 'source_id')) + 1;
    $template['language_code'] = $locale;
    $template['version_number'] = $maximum + 1;
    $template['title'] = $title;
    $template['content_html'] = $html;
    $template['is_published'] = 1;
    $template['revision_token'] = manualIdentity('revision/' . $key . '/' . $locale);
    $versions[] = $template;

    $workflow['language_code'] = $locale;
    $workflow['status'] = 'published';
    $workflow['current_version_number'] = $template['version_number'];
    $workflow['published_version_number'] = $template['version_number'];
    $workflows[] = $workflow;
    foreach ($documents as &$document) {
        if ($document['document_key'] === $key) {
            $document['current_version_source_id'] = $template['source_id'];
        }
    }

    unset($document);
}

/**
 * EN: Rebuilds every translated chapter and its incremental-update page package.
 *
 * HR: Obnavlja svako prevedeno poglavlje i njegov paket za pojedinačnu nadogradnju.
 */
function syncUserGuides(string $root, bool $apply): void
{
    require_once $root . '/vendor/autoload.php';
    $pages = BundledGuideIndex::load($root)
    ?? throw new RuntimeException('The public manual source index is missing.');
    $assets = json_decode(
        (string)file_get_contents($root . '/resources/installation/manual-assets.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );
    foreach ($assets as $asset) {
        if (!hash_equals($asset['sha256'], hash_file('sha256', $root . '/' . $asset['file']) ?: '')) {
            throw new RuntimeException('Public guide asset changed: ' . $asset['file']);
        }
    }

    $temporary = sys_get_temp_dir() . '/simbioza-public-manual-' . bin2hex(random_bytes(8));
    $fs = new BackupFilesystem();
    $fs->ensureDirectory($temporary);

    $config = manualArchiveConfig($temporary, $root);
    $full = new BackupArchiveReader(
        $root . '/resources/installation/workspace/korisnicke-upute.zip',
        $config,
        $fs,
        'SimbiozaSeed2026!',
    );
    $template = new BackupArchiveReader(
        $root . '/resources/installation/workspace/simbioza.zip',
        $config,
        $fs,
        'SimbiozaSeed2026!',
    );
    try {
        $records = manualRecords($full);
        // EN: Localize the fresh installation's guide area; incremental page updates do not replace live area settings.
        //
        // HR: Prevodi područje uputa nove instalacije; pojedinačne nadogradnje ne zamjenjuju žive postavke područja.
        foreach ($records['workspace-scope']['workspace'] as &$guideWorkspace) {
            $guideWorkspace['name_translations'] = [
                ...(is_array($guideWorkspace['name_translations'] ?? null) ? $guideWorkspace['name_translations'] : []),
                'de' => 'Benutzerhandbuch', 'es' => 'Guías de uso',
                'fr' => 'Guides d’utilisation', 'it' => 'Guide per gli utenti',
            ];
            $guideWorkspace['description_translations'] = [
                ...(is_array($guideWorkspace['description_translations'] ?? null)
                    ? $guideWorkspace['description_translations'] : []),
                'de' => 'Praktische Anleitungen zur Nutzung von Simbioza.',
                'es' => 'Guías prácticas para utilizar Simbioza.',
                'fr' => 'Guides pratiques pour utiliser Simbioza.',
                'it' => 'Guide pratiche per utilizzare Simbioza.',
            ];
        }

        unset($guideWorkspace);
        // EN: The source manifest is authoritative. Retired chapters must leave the public
        // package, not remain as orphaned pages inherited from its previous version.
        // This only filters generated seed records; it never removes live user pages.
        //
        // HR: Manifest izvora je mjerodavan. Povučena poglavlja moraju izaći iz javnog
        // paketa, a ne ostati kao nepovezane stranice iz prethodne verzije paketa.
        // Filtriraju se samo zapisi instalacijskog paketa; žive korisničke stranice ne brišu se.
        $publishedSlugs = array_fill_keys(array_column($pages, 'slug'), true);
        $retiredNodes = [];
        $retiredDocuments = [];
        foreach ($records['workspace-scope']['nodes'] as $node) {
            if (!isset($publishedSlugs[$node['slug']])) {
                $retiredNodes[$node['uuid']] = true;
                $retiredDocuments[$node['document_key']] = true;
            }
        }

        if (!$apply && $retiredNodes !== []) {
            throw new RuntimeException(
                'The bundled manual still contains retired chapters; regenerate it from the source manifest.',
            );
        }

        $retiredDocumentIds = [];
        foreach ($records['editor-html-workspace']['documents'] as $document) {
            if (isset($retiredDocuments[$document['document_key']])) {
                $retiredDocumentIds[(int)$document['source_id']] = true;
            }
        }

        foreach ($records as $provider => $datasets) {
            foreach ($datasets as $dataset => $rows) {
                $records[$provider][$dataset] = array_values(array_filter(
                    $rows,
                    static fn(array $row): bool => !isset($retiredDocuments[$row['document_key'] ?? ''])
                        && !isset($retiredNodes[$row['node_uuid'] ?? ''])
                        && !($provider === 'workspace-scope' && $dataset === 'nodes'
                            && isset($retiredNodes[$row['uuid'] ?? '']))
                        && !($provider === 'comment-workspace' && $dataset === 'settings'
                            && isset($retiredDocumentIds[(int)($row['document_id'] ?? 0)])),
                ));
            }
        }

        $nodes = &$records['workspace-scope']['nodes'];
        $documents = &$records['editor-html-workspace']['documents'];
        $versions = &$records['editor-html-workspace']['versions'];
        $workflows = &$records['workspace-scope']['workflows'];
        $nodesBySlug = array_column($nodes, null, 'slug');
        foreach ($pages as $pageIndex => $page) {
            $slug = $page['slug'];
            $starterOrder = ($pageIndex + 1) * 10;
            if (!isset($nodesBySlug[$slug])) {
                $node = $nodesBySlug['simbioza'];
                $node['uuid'] = manualIdentity('node/' . $slug);
                $node['source_id'] = max(array_column($nodes, 'source_id')) + 1;
                $node['slug'] = $slug;
                $node['document_key'] = $slug;
                $node['title'] = $page['title']['hr'];
                $node['title_translations'] = $page['title'];
                $node['parent_uuid'] = manualParentUuid($page['parent'], $nodesBySlug);
                $node['sort_order'] = $starterOrder;
                $node['is_homepage'] = 0;
                $nodes[] = $node;
                $nodesBySlug[$slug] = $node;
                $document = $documents[0];
                $document['source_id'] = max(array_column($documents, 'source_id')) + 1;
                $document['uuid'] = manualIdentity('document/' . $slug);
                $document['document_key'] = $slug;
                $document['title'] = $page['title']['hr'];
                foreach (array_keys($page['sources']) as $localeIndex => $locale) {
                    $version = $versions[0];
                    $version['source_id'] = max(array_column($versions, 'source_id')) + 1;
                    $version['document_key'] = $slug;
                    $version['language_code'] = $locale;
                    $version['title'] = $page['title'][$locale];
                    $version['version_number'] = $localeIndex + 1;
                    $version['revision_token'] = manualIdentity('revision/' . $slug . '/' . $locale);
                    $versions[] = $version;
                    $document['current_version_source_id'] = $version['source_id'];
                    $workflow = $workflows[0];
                    $workflow['node_uuid'] = $node['uuid'];
                    $workflow['language_code'] = $locale;
                    $workflow['current_version_number'] = $version['version_number'];
                    $workflow['published_version_number'] = $version['version_number'];
                    $workflows[] = $workflow;
                }

                $documents[] = $document;
            }

            // EN: Older chapters use historical UUIDs; resolve actual parent identities,
            //     including existing nodes whose documented hierarchy has changed.
            //
            // HR: Starija poglavlja imaju povijesne UUID-ove; koristimo stvarne identitete
            //     roditelja, uključujući postojeće čvorove s promijenjenom hijerarhijom.
            $parentUuid = manualParentUuid($page['parent'], $nodesBySlug);
            foreach ($nodes as &$existingNode) {
                if ($existingNode['slug'] !== $slug) {
                    continue;
                }

                if (!$apply && ($existingNode['parent_uuid'] ?? null) !== $parentUuid) {
                    throw new RuntimeException('Guide hierarchy drift: ' . $slug);
                }

                // EN: Only the generated starter adopts the manifest order; incremental imports preserve local order.
                //
                // HR: Samo generirani početni paket slijedi redoslijed manifesta;
                //     pojedinačni uvoz čuva lokalni redoslijed.
                if (!$apply && ($existingNode['sort_order'] ?? null) !== $starterOrder) {
                    throw new RuntimeException('Guide starter order drift: ' . $slug);
                }

                $existingNode['sort_order'] = $starterOrder;
                $existingNode['parent_uuid'] = $parentUuid;
                $existingNode['title'] = $page['title']['hr'];
                $existingNode['title_translations'] = $page['title'];
                $nodesBySlug[$slug] = $existingNode;
            }

            unset($existingNode);
            $key = $nodesBySlug[$slug]['document_key'];
            // EN: New languages append document-wide revisions; existing revision numbers stay unchanged.
            //
            // HR: Novi jezici dodaju revizije na razini dokumenta; postojeći brojevi ostaju nepromijenjeni.
            foreach (array_keys($page['sources']) as $locale) {
                $expected = manualSourceHash($root, $page, $locale, $assets);
                $found = false;
                foreach ($versions as &$version) {
                    if ($version['document_key'] !== $key || $version['language_code'] !== $locale) {
                        continue;
                    }

                    $found = true;
                    if ($apply) {
                        $version['content_html'] = renderManualPage($root, $page, $locale, $pages, $assets);
                    } elseif (!str_contains($version['content_html'], '-sha256="' . $expected . '"')) {
                        throw new RuntimeException('Source drift: ' . $slug . '/' . $locale);
                    }
                }

                unset($version);
                if (!$found) {
                    if (!$apply) {
                        throw new RuntimeException('Missing published guide: ' . $slug . '/' . $locale);
                    }

                    appendManualTranslation(
                        $versions,
                        $workflows,
                        $documents,
                        $nodesBySlug[$slug],
                        $locale,
                        $page['title'][$locale],
                        renderManualPage($root, $page, $locale, $pages, $assets),
                    );
                }
            }
        }

        $blobs = [];
        $usedAssets = [];
        foreach ($versions as $version) {
            preg_match_all('~/editor-html/asset/([a-f0-9-]{36})~', $version['content_html'], $matches);
            foreach ($matches[1] as $uuid) {
                if (!isset($assets[$uuid])) {
                    throw new RuntimeException('Missing maintained attachment: ' . $uuid);
                }

                $usedAssets[$uuid] = true;
            }
        }

        $assetRows = &$records['editor-html-workspace']['assets'];
        foreach (array_keys($usedAssets) as $uuid) {
            $asset = $assets[$uuid];
            $blobs[$asset['sha256']] = $root . '/' . $asset['file'];
            if (in_array($uuid, array_column($assetRows, 'uuid'), true)) {
                foreach ($assetRows as &$existingAsset) {
                    if ($existingAsset['uuid'] === $uuid) {
                        $refreshed = refreshManualAssetPayload(
                            $existingAsset,
                            $records['editor-html-workspace']['asset-versions'],
                            $asset,
                            $root,
                        );
                        $existingAsset = $refreshed['asset'];
                        $records['editor-html-workspace']['asset-versions'] = $refreshed['versions'];
                        break;
                    }
                }

                unset($existingAsset);
                continue;
            }

            $path = $root . '/' . $asset['file'];
            $owner = null;
            foreach ($versions as $version) {
                if (str_contains($version['content_html'], '/editor-html/asset/' . $uuid)) {
                    $owner = $version['document_key'];
                    break;
                }
            }

            $row = $assetRows[0];
            $row['uuid'] = $uuid;
            $row['document_key'] = $owner;
            $row['original_name'] = $row['display_name'] = $asset['original_name'] ?? basename($path);
            $row['caption'] = $row['description'] = $row['alt_text'] = '';
            $row['stored_name'] = $uuid . '-' . basename($path);
            $row['mime_type'] = mime_content_type($path);
            $row['file_size'] = filesize($path);
            $row['blob'] = ['sha256' => $asset['sha256'], 'size' => $row['file_size'],
                'path' => 'blobs/' . substr($asset['sha256'], 0, 2) . '/' . $asset['sha256'],
                'media_type' => $row['mime_type'], 'original_name' => $row['original_name']];
            $assetRows[] = $row;
            $assetVersion = $records['editor-html-workspace']['asset-versions'][0];
            $assetVersion['uuid'] = manualIdentity('asset-version/' . $uuid);
            $assetVersion['asset_uuid'] = $uuid;
            $assetVersion['document_key'] = $owner;
            foreach (['original_name', 'stored_name', 'mime_type', 'file_size'] as $field) {
                $assetVersion[$field] = $row[$field];
            }

            $records['editor-html-workspace']['asset-versions'][] = $assetVersion;
        }

        $assetRows = array_values(array_filter(
            $assetRows,
            static fn(array $row): bool => isset($usedAssets[$row['uuid']]),
        ));
        $records['editor-html-workspace']['asset-versions'] = array_values(array_filter(
            $records['editor-html-workspace']['asset-versions'],
            static fn(array $row): bool => isset($usedAssets[$row['asset_uuid']]),
        ));
        $prepared = [];
        $archiveRoot = $root . '/resources/installation/workspace/';
        if ($apply) {
            $prepared[$archiveRoot . 'korisnicke-upute.zip'] = writeManualArchive(
                $config,
                $fs,
                $full->manifest(),
                $records,
                $blobs,
            );
        }

        foreach ($pages as $page) {
            $slug = $page['slug'];
            $destination = $archiveRoot . (str_starts_with($slug, 'ankete') ? 'surveys/' : '') . $slug . '.zip';
            $node = $nodesBySlug[$slug];
            $key = $node['document_key'];
            if (!$apply) {
                $reader = new BackupArchiveReader($destination, $config, $fs, 'SimbiozaSeed2026!');
                try {
                    $pageVersions = manualRecords($reader)['editor-html-workspace']['versions'];
                    foreach (array_keys($page['sources']) as $locale) {
                        $found = false;
                        foreach ($pageVersions as $version) {
                            if ($version['language_code'] === $locale) {
                                $found = str_contains(
                                    $version['content_html'],
                                    '-sha256="' . manualSourceHash($root, $page, $locale, $assets) . '"',
                                );
                            }
                        }

                        if (!$found) {
                            throw new RuntimeException('Page package drift: ' . $slug . '/' . $locale);
                        }
                    }
                } finally {
                    $reader->close();
                }

                continue;
            }

            $subset = [];
            $pageBlobs = [];
            foreach ($records['editor-html-workspace'] as $dataset => $rows) {
                $subset['editor-html-workspace'][$dataset] = array_values(array_filter(
                    $rows,
                    static fn(array $row): bool => ($row['document_key'] ?? '') === $key,
                ));
            }

            foreach ($subset['editor-html-workspace']['assets'] as $asset) {
                $pageBlobs[$asset['blob']['sha256']] = $blobs[$asset['blob']['sha256']];
            }

            $transfer = array_intersect_key($node, array_flip(['source_id', 'uuid', 'node_type', 'slug', 'title',
                'document_key', 'is_tree_hidden', 'is_enabled', 'contents_visibility', 'created_by_user',
                'updated_by_user', 'created_at', 'updated_at']));
            $transfer['source_workspace'] = 'korisnicke-upute';
            $transfer['title_translations'] = json_encode(
                $node['title_translations'],
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
            );
            $transfer['permissions_included'] = false;
            $transfer['history_included'] = false;
            $subset['page-transfer']['page'] = [$transfer];
            foreach ($workflows as $workflow) {
                if ($workflow['node_uuid'] === $node['uuid']) {
                    unset($workflow['node_uuid']);
                    $subset['page-transfer']['workflows'][] = $workflow;
                }
            }

            $pageManifest = $template->manifest();
            usort($pageManifest['providers'], static fn(array $a, array $b): int =>
                ($a['id'] === 'page-transfer' ? 0 : 1) <=> ($b['id'] === 'page-transfer' ? 0 : 1));
            $pageManifest['label'] = 'Bundled user guide - ' . $slug;
            $pageManifest['source'] = $full->manifest()['source'];
            $pageManifest['scope']['identifier'] = $slug;
            $prepared[$destination] = writeManualArchive($config, $fs, $pageManifest, $subset, $pageBlobs);
        }

        foreach ($prepared as $destination => $source) {
            if (
                is_link($destination) || is_link($destination . '.partial')
                || !copy($source, $destination . '.partial') || !rename($destination . '.partial', $destination)
            ) {
                throw new RuntimeException('Unable to publish public guide: ' . basename($destination));
            }
        }

        echo count($pages) . ' chapters, ' . implode('/', array_keys($pages[0]['sources']))
        . ' and ' . count($usedAssets)
        . ' referenced public assets verified.' . PHP_EOL;
    } finally {
        $full->close();
        $template->close();
        $fs->removeDirectory($temporary);
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    if (PHP_SAPI !== 'cli' || count($argv) !== 2 || !in_array($argv[1], ['--check', '--apply'], true)) {
        throw new RuntimeException('CLI usage: php scripts/sync_user_guides.php --check|--apply');
    }

    syncUserGuides(dirname(__DIR__), $argv[1] === '--apply');
}
