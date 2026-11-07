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

// EN: Development-only synchronization of public guide packages from Markdown.
// Check mode needs no Pandoc. Apply mode requires Pandoc, never reads a live database,
// and preserves document identities, permissions, other pages and attachment blobs.
//
// HR: Razvojno usklađivanje javnih paketa uputa s Markdown izvorima.
// Provjera ne treba Pandoc. Primjena treba Pandoc, ne čita aktivnu bazu i čuva
// identitete dokumenata, prava, druge stranice i datoteke privitaka.

// phpcs:disable PSR1.Files.SideEffects -- Importable development CLI entry point.

/**
 * EN: Lists the complete sources for one installed page and language.
 * Ordinary and secured FPM are independent walkthroughs on the same existing page.
 *
 * HR: Navodi cjelovite izvore jedne instalirane stranice i jezika.
 * Obični i secured FPM samostalni su postupci na istoj postojećoj stranici.
 *
 * @return list<string>
 */
function installationGuideSources(string $slug, string $locale): array
{
    if (!in_array($locale, BundledGuideIndex::SUPPORTED_LOCALES, true)) {
        throw new RuntimeException('Unsupported bundled installation language.');
    }

    $names = match ($slug) {
        'simbioza' => ['simbioza_overview'],
        'instalacija' => ['installation', 'modularity'],
        'apache-mod-php-instalacija' => ['installation_mod_php'],
        'php-fpm-instalacija' => ['installation_fpm', 'installation_fpm_secured'],
        default => throw new RuntimeException('Unknown bundled installation guide.'),
    };

    return array_map(static fn(string $name): string => $name . '_' . $locale . '.md', $names);
}

/**
 * EN: Uses shipped translations, independently of the languages installed on the build host.
 *
 * HR: Koristi isporučene prijevode, neovisno o jezicima instaliranima na razvojnom računalu.
 *
 * @return list<string>
 */
function installationGuideLocales(string $root, string $slug): array
{
    foreach (BundledGuideIndex::load($root) ?? [] as $page) {
        if ($page['slug'] === $slug) {
            return array_keys($page['sources']);
        }
    }

    return ['hr', 'en'];
}

/**
 * EN: Signs the exact source names and bytes, independent of Pandoc formatting/version.
 *
 * HR: Potpisuje točne nazive i bajtove izvora, neovisno o Pandoc formatu/verziji.
 */
function installationGuideSourceHash(string $root, string $slug, string $locale): string
{
    $sources = [];
    foreach (installationGuideSources($slug, $locale) as $name) {
        $text = file_get_contents($root . '/docs/' . $name);
        if (!is_string($text)) {
            throw new RuntimeException('Missing installation source: ' . $name);
        }

        $sources[$name] = $text;
    }

    return hash('sha256', json_encode($sources, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

/**
 * EN: Gives generated guide tables readable theme-aware spacing without changing escaped code examples.
 *
 * HR: Daje generiranim tablicama uputa čitljive razmake prema temi, bez mijenjanja escapiranih primjera koda.
 */
function styleGuideTables(string $html): string
{
    $styled = preg_replace_callback(
        '~<table(?=[\s>])(?:[^>"\']|"[^"]*"|\'[^\']*\')*>~i',
        static function (array $match): string {
            $tag = $match[0];
            $required = ['table', 'table-bordered', 'align-middle'];
            if (
                preg_match(
                    '~(?<![\w:-])class\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))~is',
                    $tag,
                    $attribute,
                    PREG_OFFSET_CAPTURE,
                )
            ) {
                $value = ($attribute[1][0] ?? '') ?: (($attribute[2][0] ?? '') ?: ($attribute[3][0] ?? ''));
                $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $classes = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $classes = array_values(array_unique([...$classes, ...$required]));
                $replacement = 'class="' . htmlspecialchars(implode(' ', $classes), ENT_QUOTES | ENT_HTML5, 'UTF-8')
                . '"';
                return substr_replace($tag, $replacement, $attribute[0][1], strlen($attribute[0][0]));
            }

            return substr($tag, 0, -1) . ' class="' . implode(' ', $required) . '">';
        },
        $html,
    );
    if (!is_string($styled)) {
        throw new RuntimeException('Unable to format public guide tables.');
    }

    return $styled;
}

/**
 * EN: Renders maintained Markdown and maps local guide links to portable application routes.
 *
 * HR: Renderira održavani Markdown i povezuje lokalne upute s prenosivim aplikacijskim rutama.
 */
function renderInstallationGuide(string $root, string $slug, string $locale): string
{
    $hash = installationGuideSourceHash($root, $slug, $locale);
    $html = '<section data-simbioza-docs-sha256="' . $hash . '">' . PHP_EOL;
    if ($slug === 'php-fpm-instalacija') {
        $html .= match ($locale) {
            'hr' => '<p>Odaberite jedan cjelovit postupak: <a href="#ordinary-fpm">Obični FPM</a> ili '
            . '<a href="#secured-fpm">Secured FPM</a>. Svaki kreće od početka.</p>',
            'de' => '<p>Wählen Sie eine vollständige Anleitung: <a href="#ordinary-fpm">Standard-FPM</a> oder '
            . '<a href="#secured-fpm">Secured FPM</a>. Beide beginnen mit einer Neuinstallation.</p>',
            'es' => '<p>Elija un procedimiento completo: <a href="#ordinary-fpm">FPM estándar</a> o '
            . '<a href="#secured-fpm">Secured FPM</a>. Ambos comienzan desde cero.</p>',
            'fr' => '<p>Choisissez une procédure complète : <a href="#ordinary-fpm">FPM standard</a> ou '
            . '<a href="#secured-fpm">Secured FPM</a>. Chacune commence par une nouvelle installation.</p>',
            'it' => '<p>Scegli una procedura completa: <a href="#ordinary-fpm">FPM standard</a> o '
            . '<a href="#secured-fpm">Secured FPM</a>. Entrambe partono da una nuova installazione.</p>',
            default => '<p>Choose one complete walkthrough: <a href="#ordinary-fpm">Ordinary FPM</a> or '
            . '<a href="#secured-fpm">Secured FPM</a>. Each starts from scratch.</p>',
        } . PHP_EOL;
    }

    foreach (installationGuideSources($slug, $locale) as $name) {
        $id = $slug === 'php-fpm-instalacija'
        ? (str_contains($name, '_secured_') ? 'secured-fpm' : 'ordinary-fpm')
        : basename($name, '.md');
        $process = proc_open(
            ['pandoc', '--from=gfm', '--to=html5', '--no-highlight', '--wrap=none', '--id-prefix=' . $id . '-',
                $root . '/docs/' . $name],
            [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
        );
        if (!is_resource($process)) {
            throw new RuntimeException('Pandoc is required only to rebuild the public guide packages.');
        }

        $rendered = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0 || !is_string($rendered) || $rendered === '') {
            throw new RuntimeException('Pandoc failed for ' . $name . ': ' . $error);
        }

        // EN: Prefix compatibility anchors too, so the ordinary and secured FPM walkthroughs cannot collide.
        //
        // HR: Prefiksira i kompatibilna sidra kako se obični i secured FPM postupci ne bi preklapali.
        $rendered = preg_replace_callback(
            '~<a id="([^"]+)"></a>~',
            static fn(array $match): string => '<a id="' . $id . '-' . $match[1] . '"></a>',
            $rendered,
        );
        if (!is_string($rendered)) {
            throw new RuntimeException('Unable to namespace installation guide anchors.');
        }

        $rendered = preg_replace_callback('/href="([^"]+)"/', static function (array $match): string {
            $href = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $pattern = '/\A(installation(?:_mod_php|_fpm(?:_secured)?)?)_(en|hr|de|es|fr|it)\.md(?:#.*)?\z/D';
            if (preg_match($pattern, $href, $target)) {
                $page = match ($target[1]) {
                    'installation_mod_php' => 'apache-mod-php-instalacija',
                    'installation_fpm', 'installation_fpm_secured' => 'php-fpm-instalacija',
                    default => 'instalacija',
                };
                $anchor = match ($target[1]) {
                    'installation_fpm' => '#ordinary-fpm', 'installation_fpm_secured' => '#secured-fpm',
                    default => '',
                };
                $href = '/workspace/korisnicke-upute/' . $page . '?lang=' . $target[2] . $anchor;
            } elseif (preg_match('/\A[^:\/#]+\.md(?:#.*)?\z/D', $href)) {
                $href = 'https://github.com/kmihalj/Simbioza/blob/main/docs/' . $href;
            }

            return 'href="' . htmlspecialchars($href, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"';
        }, $rendered);
        if (!is_string($rendered)) {
            throw new RuntimeException('Unable to map installation guide links.');
        }

        $rendered = preg_replace_callback(
            '/(href|src)="images\/theme-gallery\/([a-z0-9-]+\.webp)"/',
            static function (array $match) use ($root): string {
                if (!is_file($root . '/public/assets/theme-gallery/' . $match[2])) {
                    throw new RuntimeException('Missing public theme gallery image: ' . $match[2]);
                }

                return $match[1] . '="/assets/theme-gallery/' . $match[2] . '"';
            },
            $rendered,
        );
        if (!is_string($rendered)) {
            throw new RuntimeException('Unable to map theme gallery images.');
        }

        $html .= '<section id="' . $id . '">' . PHP_EOL . styleGuideTables($rendered) . '</section>' . PHP_EOL;
    }

    return $html . '</section>' . PHP_EOL;
}

/**
 * EN: Checks or rebuilds the full archive and four page archives without touching installed content.
 *
 * HR: Provjerava ili obnavlja punu arhivu i četiri arhive stranica bez mijenjanja instaliranog sadržaja.
 */
function syncInstallationGuides(string $root, bool $apply): void
{
    require_once $root . '/vendor/autoload.php';
    $temporary = sys_get_temp_dir() . '/simbioza-installation-guides-' . bin2hex(random_bytes(8));
    $filesystem = new BackupFilesystem();
    $filesystem->ensureDirectory($temporary);

    $composer = new class ($temporary) extends ComposerBridge {
        /**
         * EN: Pins all generated data to the isolated public-guide staging directory.
         *
         * HR: Sav generirani sadržaj veže uz izolirani radni direktorij javnih uputa.
         */
        public function __construct(private readonly string $root)
        {
        }

        /**
         * EN: Returns staging metadata without consulting the live application's configuration.
         *
         * HR: Vraća radne metapodatke bez čitanja konfiguracije aktivne aplikacije.
         *
         * @return array<string,mixed>
         */
        public function getRootPackage(): array
        {
            return ['install_path' => $this->root];
        }
    };
    $config = new BackupConfig(new Config(new Helper(), ['backup' => [
        'staging_dir' => $temporary . '/staging', 'archive_dir' => $temporary . '/archives',
    ]]), $composer, $root . '/vendor/aaieduhr/heartphrame-module-backup');
    $slugs = ['simbioza', 'instalacija', 'apache-mod-php-instalacija', 'php-fpm-instalacija'];
    $locales = [];
    foreach ($slugs as $slug) {
        $locales[$slug] = installationGuideLocales($root, $slug);
    }

    try {
        $rendered = [];
        if ($apply) {
            foreach ($slugs as $slug) {
                foreach ($locales[$slug] as $locale) {
                    $rendered[$slug][$locale] = renderInstallationGuide($root, $slug, $locale);
                }
            }
        }

        $prepared = [];
        foreach (['korisnicke-upute', ...$slugs] as $archiveName) {
            $archive = $root . '/resources/installation/workspace/' . $archiveName . '.zip';
            $reader = new BackupArchiveReader($archive, $config, $filesystem, 'SimbiozaSeed2026!');
            try {
                $manifest = $reader->manifest();
                $writer = $apply ? new BackupArchiveWriter(
                    $config,
                    $filesystem,
                    new BackupScope($manifest['scope']['type'], $manifest['scope']['identifier']),
                    $manifest['source'],
                    $manifest['label'],
                    'SimbiozaSeed2026!',
                ) : null;
                $seen = [];
                foreach ($manifest['providers'] as $metadata) {
                    $writer?->beginProvider(new BackupProviderMetadata(
                        $metadata['id'],
                        $metadata['package'],
                        $metadata['schema_version'],
                        $metadata['label'],
                        $metadata['dependencies'],
                        $metadata['scopes'],
                        $metadata['required_for_full_restore'],
                        $metadata['contains_sensitive_data'],
                        $metadata['required_enabled_packages'],
                        $metadata['automatic'],
                        $metadata['component_groups'],
                    ));
                    foreach (array_keys($metadata['datasets']) as $dataset) {
                        foreach ($reader->records($metadata['id'], $dataset) as $record) {
                            if ($metadata['id'] === 'editor-html-workspace' && $dataset === 'versions') {
                                foreach ($slugs as $slug) {
                                    $key = $record['document_key'] ?? '';
                                    $locale = $record['language_code'] ?? '';
                                    if (
                                        ($key === $slug || str_starts_with($key, $slug . '--'))
                                        && in_array($locale, $locales[$slug], true)
                                    ) {
                                        $expected = installationGuideSourceHash($root, $slug, $locale);
                                        if ($apply) {
                                            $record['content_html'] = $rendered[$slug][$locale];
                                        } elseif (
                                            !str_contains(
                                                $record['content_html'] ?? '',
                                                'data-simbioza-docs-sha256="' . $expected . '"',
                                            )
                                        ) {
                                            throw new RuntimeException(
                                                $archiveName . ': source drift for ' . $slug . '/' . $locale,
                                            );
                                        }

                                        $seen[$slug][$locale] = true;
                                        break;
                                    }
                                }
                            }

                            $writer?->writeRecord($metadata['id'], $dataset, $record);
                        }
                    }
                }

                foreach ($archiveName === 'korisnicke-upute' ? $slugs : [$archiveName] as $slug) {
                    foreach ($locales[$slug] as $locale) {
                        if (!isset($seen[$slug][$locale])) {
                            throw new RuntimeException(
                                'Missing installation record: ' . $archiveName . '/' . $slug . '/' . $locale,
                            );
                        }
                    }
                }

                if ($writer instanceof \AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveWriter) {
                    foreach ($manifest['files'] as $file) {
                        if (str_starts_with($file['path'], 'blobs/')) {
                            $writer->addBlobFile($reader->blobPath($file['sha256']));
                        }
                    }

                    $rebuilt = $writer->finish();
                    $verified = new BackupArchiveReader($rebuilt, $config, $filesystem, 'SimbiozaSeed2026!');
                    $verified->close();
                    $prepared[$archive] = $rebuilt;
                }

                echo $archiveName . ': translated sources verified.' . PHP_EOL;
            } finally {
                $reader->close();
            }
        }

        foreach ($prepared as $destination => $source) {
            if (
                is_link($destination) || is_link($destination . '.partial')
                || !copy($source, $destination . '.partial') || !rename($destination . '.partial', $destination)
            ) {
                throw new RuntimeException('Unable to publish guide package: ' . basename($destination));
            }
        }
    } finally {
        $filesystem->removeDirectory($temporary);
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    if (PHP_SAPI !== 'cli' || array_diff(array_slice($argv, 1), ['--check', '--apply']) !== []) {
        throw new RuntimeException('CLI usage: php scripts/sync_installation_guides.php [--check|--apply]');
    }

    syncInstallationGuides(dirname(__DIR__), in_array('--apply', $argv, true));
}
