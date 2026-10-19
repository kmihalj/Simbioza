<?php

declare(strict_types=1);

namespace Tests\Installation;

use AaiEduHr\HeartPhrameModuleBackup\ModuleBackup;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveReader;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupConfig;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupFilesystem;
use HeartPhrame\Bridge\ComposerBridge;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * EN: Validates every shipped guide with the current reader, including encrypted payload hashes.
 *
 * HR: Provjerava sve isporučene upute aktualnim čitačem, uključujući hash šifriranog sadržaja.
 */
#[CoversNothing]
final class BundledGuideArchivesTest extends TestCase
{
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
                    $this->assertSame('https://piko.webhop.me/hfc', $manifest['source']['site_base_url']);
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
