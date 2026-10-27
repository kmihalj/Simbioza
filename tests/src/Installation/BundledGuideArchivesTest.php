<?php

declare(strict_types=1);

namespace Tests\Installation;

use AaiEduHr\HeartPhrameModuleBackup\ModuleBackup;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveReader;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupConfig;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem;
use FilesystemIterator;
use HeartPhrame\Bridge\ComposerBridge;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * EN: Validates every shipped guide with the current reader, including encrypted payload hashes.
 *
 * HR: Provjerava sve isporučene upute aktualnim čitačem, uključujući hash šifriranog sadržaja.
 */
#[CoversNothing]
final class BundledGuideArchivesTest extends TestCase
{
    /**
     * EN: Keeps private deployment instructions out of public documentation while allowing public demo links.
     *
     * HR: Drži privatne upute za postavljanje izvan javne dokumentacije, uz dopuštene poveznice na javni demo.
     */
    public function testPublicDocumentationExcludesPrivateDemoOperations(): void
    {
        $root = dirname(__DIR__, 3);
        $paths = [$root . '/README.md', $root . '/README_hr.md'];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
            $root . '/docs',
            FilesystemIterator::SKIP_DOTS,
        ));
        foreach ($files as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'md') {
                $paths[] = $file->getPathname();
            }
        }

        foreach ($paths as $path) {
            $this->assertDoesNotMatchRegularExpression(
                '~simbioza-module-demo|DemoSimbioza|demo-local/|demo_installation_'
                . '|fpm-simbioza-demo|simbioza-deploy-demo|^## Demo install|^## Demo instalacije~mi',
                (string)file_get_contents($path),
                $path,
            );
        }
    }

    /**
     * EN: Requires bundled installation pages to match their complete Markdown sources.
     * The check is offline, reads public packages only and does not require Pandoc.
     *
     * HR: Zahtijeva da ugrađene instalacijske stranice odgovaraju cjelovitim Markdown izvorima.
     * Provjera je offline, čita samo javne pakete i ne zahtijeva Pandoc.
     */
    public function testInstallationPackagesMatchMaintainedSources(): void
    {
        $root = dirname(__DIR__, 3);
        $process = proc_open(
            [PHP_BINARY, $root . '/scripts/sync_installation_guides.php', '--check'],
            [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $root,
        );
        $this->assertIsResource($process);
        $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($process), $output);
        $this->assertSame(4, substr_count($output, 'EN/HR sources verified.'));
    }

    /**
     * EN: All three installation modes contain their own download, database and wizard steps.
     * Shell examples are parsed, never executed; an empty directory is required before download.
     *
     * HR: Sva tri načina sadrže vlastite korake preuzimanja, baze i čarobnjaka.
     * Primjeri ljuske samo se parsiraju, ne izvršavaju; preuzimanje zahtijeva nepostojeći direktorij.
     */
    public function testInstallationModesAreSelfContainedAndShellExamplesParse(): void
    {
        $root = dirname(__DIR__, 3);
        $version = trim((string)file_get_contents($root . '/VERSION'));
        foreach (['mod_php', 'fpm', 'fpm_secured'] as $mode) {
            foreach (['en', 'hr'] as $locale) {
                $name = 'installation_' . $mode . '_' . $locale . '.md';
                $text = (string)file_get_contents($root . '/docs/' . $name);
                foreach (
                    ['test ! -e "$SIMBIOZA_APP_ROOT"', 'git clone --depth 1 --branch ' . $version,
                    'composer check-platform-reqs', 'CREATE DATABASE simbioza', 'createuser --pwprompt',
                    'install:prepare', 'modules migrate-status', 'php update.php --check'] as $required
                ) {
                    $this->assertStringContainsString($required, $text, $name);
                }

                $this->assertStringNotContainsString('installation_en.md#before-choosing-a-php-mode', $text);
                $this->assertStringNotContainsString(
                    'installation_hr.md#prije-odabira-načina-izvršavanja-php-a',
                    $text,
                );
                $this->assertStringNotContainsString('SIMBIOZA_TAG=0.2.', $text);
                preg_match_all('/<FilesMatch "([^"]+)">/', $text, $handlers);
                $this->assertNotEmpty($handlers[1], $name);
                foreach ($handlers[1] as $handler) {
                    $this->assertSame(1, preg_match('~' . $handler . '~', 'index.php'), $name);
                    $this->assertSame(0, preg_match('~' . $handler . '~', 'index.php.txt'), $name);
                }

                preg_match_all('/^```bash\R(.*?)^```/ms', $text, $examples);
                foreach ($examples[1] as $example) {
                    $process = proc_open(['bash', '-n'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'],
                        2 => ['pipe', 'w']], $pipes);
                    $this->assertIsResource($process);
                    fwrite($pipes[0], $example);
                    fclose($pipes[0]);
                    $error = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
                    fclose($pipes[1]);
                    fclose($pipes[2]);
                    $this->assertSame(0, proc_close($process), $name . ': ' . $error);
                }
            }
        }
    }

    /**
     * EN: Every Apache example permits the directives shipped in public/.htaccess.
     * The Indexes override class permits DirectoryIndex, not directory listing.
     *
     * HR: Svaki Apache primjer dopušta direktive isporučene u public/.htaccess.
     * Kategorija dopuštenja Indexes omogućuje DirectoryIndex, ne ispis direktorija.
     */
    public function testApacheGuidesAllowShippedHtaccessDirectives(): void
    {
        $root = dirname(__DIR__, 3);
        $htaccess = (string)file_get_contents($root . '/public/.htaccess');
        $this->assertStringContainsString('DirectoryIndex index.php', $htaccess);
        $this->assertStringContainsString('Options -Indexes', $htaccess);
        $this->assertStringContainsString('RewriteEngine On', $htaccess);
        foreach (['mod_php', 'fpm', 'fpm_secured'] as $mode) {
            foreach (['en', 'hr'] as $locale) {
                $name = 'installation_' . $mode . '_' . $locale . '.md';
                $text = (string)file_get_contents($root . '/docs/' . $name);
                preg_match_all('/^```apache\R(.*?)^```/ms', $text, $examples);
                $publicExamples = 0;
                foreach ($examples[1] as $example) {
                    if (preg_match('~<Directory\s+"?/srv/simbioza/public"?>~', $example) !== 1) {
                        continue;
                    }

                    ++$publicExamples;
                    $this->assertStringContainsString('AllowOverride FileInfo Indexes Options', $example, $name);
                    $this->assertStringContainsString('Options -Indexes', $example, $name);
                }

                $this->assertSame(2, $publicExamples, $name);
            }
        }
    }

    /**
     * EN: Optional SAML has an independent, complete walkthrough and never enters base setup commands.
     * Identity reuse and least-privilege ACL checks remain documented in both languages.
     *
     * HR: Opcionalni SAML ima odvojen, cjelovit postupak i ne ulazi u osnovne naredbe instalacije.
     * Ponovna upotreba identiteta i provjere najmanjih ACL prava ostaju opisane na oba jezika.
     */
    public function testSecuredFpmExplainsOptionalSamlAndIdentityReuse(): void
    {
        $root = dirname(__DIR__, 3);
        $headings = [
            'en' => '## Optional SimpleSAMLphp integration',
            'hr' => '## Neobvezna integracija sa SimpleSAMLphp-om',
        ];
        foreach ($headings as $locale => $heading) {
            $text = (string)file_get_contents($root . '/docs/installation_fpm_secured_' . $locale . '.md');
            $parts = explode($heading, $text, 2);
            $this->assertCount(2, $parts);
            preg_match_all('/^```bash\R(.*?)^```/ms', $parts[0], $baseCommands);
            foreach ($baseCommands[1] as $command) {
                $this->assertStringNotContainsString('--simplesaml-config-dir=', $command);
                $this->assertStringNotContainsString('simplesamlphp-2.5.3.1-full', $command);
            }

            $identities = ['UID/GID', 'app-simbioza', 'deploy-simbioza', 'run-simbioza', 'getfacl -p', 'www-data'];
            foreach ($identities as $identity) {
                $this->assertStringContainsString($identity, $parts[0]);
            }

            foreach (
                ['### S1.', '### S2.', '### S3.', '### S4.', '### S5.', 'OIDC, CAS,',
                'SIMPLESAMLPHP_CONFIG_DIR', 'clear_env = yes', 'auth_source = default-sp',
                'session.phpsession.savepath', 'HPH_CONFIG_PATH/auth/auth.json', 'ACS', 'SLO',
                'test ! -w', 'ProxyPassMatch', 'module.php/...'] as $required
            ) {
                $this->assertStringContainsString($required, $parts[1], $locale);
            }

            $this->assertSame(3, substr_count($parts[1], '--simplesaml-config-dir="$SIMBIOZA_SAML_CONFIG"'));
            $this->assertStringNotContainsString('apps-test.unizg.hr', $parts[1]);
        }
    }

    /**
     * EN: Fresh setup checks the real helper before the wizard, without stale SSH group membership.
     * Both guides require a successful runtime probe and stop chained commands after a failure.
     *
     * HR: Nova instalacija provjerava stvarni helper prije čarobnjaka, bez starih SSH članstava grupa.
     * Obje upute zahtijevaju uspješnu runtime provjeru i prekidaju niz naredbi nakon greške.
     */
    public function testSecuredFpmRequiresRealHelperChecksBeforeContinuing(): void
    {
        $root = dirname(__DIR__, 3);
        foreach (['en', 'hr'] as $locale) {
            $text = (string)file_get_contents($root . '/docs/installation_fpm_secured_' . $locale . '.md');
            foreach (
                ['[OK] Real Setup helper round trip', '[OK] Background updater CLI runtime',
                'sudo -H -u fpm-simbioza php scripts/configure_fpm_setup.php --check'] as $required
            ) {
                $this->assertStringContainsString($required, $text, $locale);
            }

            preg_match_all('/^```bash\R(.*?)^```/ms', $text, $examples);
            $installBlocks = 0;
            foreach ($examples[1] as $example) {
                if (!str_contains($example, 'scripts/configure_fpm_setup.php --install')) {
                    continue;
                }

                ++$installBlocks;
                $this->assertMatchesRegularExpression('/--maintainer=.*?&&\R/s', $example, $locale);
                $this->assertStringContainsString('scripts/configure_fpm_setup.php --check', $example, $locale);
            }

            $this->assertGreaterThanOrEqual(2, $installBlocks, $locale);
        }
    }

    /**
     * EN: Shared-host instructions bind the PHP handler to the alias's actual public directory.
     * Managed-vhost changes and obsolete custom bind cleanup are explicit, not new-install prerequisites.
     *
     * HR: Upute zajedničke domene vežu PHP handler na stvarni javni direktorij aliasa.
     * Promjene upravljanog vhosta i čišćenje starih vezanja izričiti su, ne preduvjeti nove instalacije.
     */
    public function testSharedApacheVhostUsesTheRealPublicDirectory(): void
    {
        $root = dirname(__DIR__, 3);
        foreach (['en', 'hr'] as $locale) {
            $text = (string)file_get_contents($root . '/docs/installation_fpm_secured_' . $locale . '.md');
            preg_match_all('/^```apache\R(.*?)^```/ms', $text, $examples);
            $matched = false;
            foreach ($examples[1] as $example) {
                if (!str_contains($example, 'Alias /simbioza "/srv/simbioza/public"')) {
                    continue;
                }

                $matched = true;
                $this->assertStringContainsString('<Directory "/srv/simbioza/public">', $example);
                $this->assertStringContainsString('SetHandler "proxy:fcgi://127.0.0.1:9075"', $example);
                $this->assertStringNotContainsString('/var/www/html/simbioza', $example);
            }

            $this->assertTrue($matched, $locale);
            foreach (
                ['Puppet/Ansible', '/data/www/simbioza/public', 'ProxyTimeout 900', 'DropInPaths',
                'systemctl daemon-reload', '.service.d', 'bootstrap.php'] as $required
            ) {
                $this->assertStringContainsString($required, $text, $locale);
            }
        }
    }

    /**
     * EN: The guide distinguishes generated FPM setup from subsequent manual service changes.
     * Reconfiguration validates both configurations before restarting the dedicated pool.
     *
     * HR: Uputa razlikuje generirani FPM od naknadnih ručnih promjena servisa.
     * Promjene provjeravaju obje konfiguracije prije restarta namjenskog poola.
     */
    public function testSecuredFpmExplainsGenerationAndValidatedRestarts(): void
    {
        $root = dirname(__DIR__, 3);
        foreach (['en', 'hr'] as $locale) {
            $text = (string)file_get_contents($root . '/docs/installation_fpm_secured_' . $locale . '.md');
            foreach (
                ['/etc/php/8.4/php-fpm-simbioza/php-fpm.conf', '[global]', '[simbioza]',
                'systemctl show php-fpm-simbioza.service -p ExecStart',
                '$(brew --prefix)/etc/simbioza/php-fpm.conf', 'pool.conf'] as $required
            ) {
                $this->assertStringContainsString($required, $text, $locale);
            }

            preg_match_all('/^```bash\R(.*?)^```/ms', $text, $examples);
            $matched = false;
            foreach ($examples[1] as $example) {
                if (!str_contains($example, 'sudo systemctl restart php-fpm-simbioza.service')) {
                    continue;
                }

                $matched = true;
                $command = (string)preg_replace('/\s+/', ' ', str_replace("\\\n", '', $example));
                $this->assertStringContainsString(
                    'sudo systemctl daemon-reload && '
                    . 'sudo "/usr/sbin/php-fpm$SIMBIOZA_PHP_VERSION" --test '
                    . '--fpm-config="/etc/php/$SIMBIOZA_PHP_VERSION/php-fpm-simbioza/php-fpm.conf" && '
                    . 'sudo apache2ctl configtest && '
                    . 'sudo systemctl restart php-fpm-simbioza.service && '
                    . 'sudo systemctl is-active php-fpm-simbioza.service && '
                    . 'sudo systemctl reload apache2',
                    $command,
                    $locale,
                );
            }

            $this->assertTrue($matched, $locale);
        }
    }

    public function testEveryGuideUsesTheCurrentPortableFormat(): void
    {
        $applicationRoot = dirname(__DIR__, 3);
        $archives = glob($applicationRoot . '/resources/installation/workspace/*.zip');
        $this->assertIsArray($archives);
        $this->assertCount(6, $archives);
        $temporary = sys_get_temp_dir() . '/simbioza-bundled-guide-test-' . bin2hex(random_bytes(8));
        $filesystem = new BackupFilesystem();
        $filesystem->ensureDirectory($temporary);

        $composer = $this->createStub(ComposerBridge::class);
        $composer->method('getRootPackage')->willReturn(['install_path' => $temporary]);
        $config = new BackupConfig(new Config(new Helper(), ['backup' => [
            'staging_dir' => $temporary . '/staging',
        ]], $temporary), $composer, $applicationRoot . '/vendor/aaieduhr/heartphrame-module-backup');
        try {
            foreach ($archives as $archive) {
                $reader = new BackupArchiveReader($archive, $config, $filesystem, 'SimbiozaSeed2026!');
                try {
                    $manifest = $reader->manifest();
                    $this->assertSame(ModuleBackup::ARCHIVE_VERSION, $manifest['format_version'], basename($archive));
                    $this->assertSame('0.3.0', $manifest['source']['root_version']);
                    $this->assertTrue($manifest['source']['canonical_link_paths']);
                    $this->assertSame(
                        basename($archive) === 'korisnicke-upute.zip'
                            ? 'https://bundled-guides.invalid' : 'https://piko.webhop.me/hfc',
                        $manifest['source']['site_base_url'],
                    );
                    $this->assertSame('/editor-html', $manifest['source']['link_route_roots']['editor']);
                    $this->assertSame('/workspace', $manifest['source']['link_route_roots']['workspace']);
                    $this->assertSame(
                        basename($archive) === 'korisnicke-upute.zip' ? 'workspace' : 'page',
                        $manifest['scope']['type'],
                    );
                    $this->assertNotEmpty($manifest['providers']);
                    foreach ($manifest['providers'] as $provider) {
                        if ($provider['id'] === 'editor-html-workspace') {
                            $this->assertSame(2, $provider['schema_version']);
                            foreach ($reader->records($provider['id'], 'versions') as $version) {
                                $html = $version['content_html'] ?? '';
                                $this->assertIsString($html);
                                $this->assertDoesNotMatchRegularExpression(
                                    '~simbioza-module-demo|DemoSimbioza|demo-local/|demo_installation_'
                                    . '|Demo installations|Demo instalacije~i',
                                    $html,
                                    basename($archive),
                                );
                            }
                        }

                        if ($provider['id'] === 'editor-html-files') {
                            $this->assertSame(3, $provider['schema_version']);
                        }
                    }

                    $this->assertNotEmpty($manifest['files']);
                } finally {
                    $reader->close();
                }
            }
        } finally {
            $filesystem->removeDirectory($temporary);
        }
    }
}
