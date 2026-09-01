<?php

declare(strict_types=1);

namespace Tests\Update;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Simbioza\Update\ApplicationUpdateCommand;

use function bin2hex;
use function chmod;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function getenv;
use function is_file;
use function mkdir;
use function putenv;
use function random_bytes;
use function rmdir;
use function strpos;
use function sys_get_temp_dir;
use function trim;
use function unlink;

#[CoversNothing]
final class ApplicationUpdateCommandTest extends TestCase
{
    /** HR: Učitava samostalni updater bez njegova pokretanja. EN: Loads the standalone updater without running it. */
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 3) . '/update.php';
    }

    /**
     * HR: Updater bira najveći stabilni semantički tag i zanemaruje prerelease i dereferencirane tagove.
     *
     * EN: The updater chooses the greatest stable semantic tag and ignores prerelease and dereferenced tags.
     */
    public function testGreatestStableTagIsSelected(): void
    {
        $remoteOutput = <<<'TAGS'
111 refs/tags/0.1.9
222 refs/tags/0.1.10
333 refs/tags/0.2.0-rc1
444 refs/tags/0.1.10^{}
555 refs/tags/v0.2.0
TAGS;

        $this->assertSame('v0.2.0', ApplicationUpdateCommand::greatestStableTag($remoteOutput));
    }

    /**
     * HR: Updater jasno prepoznaje izlaz u kojem nema stabilnog izdanja.
     *
     * EN: The updater clearly recognises output that contains no stable release.
     */
    public function testMissingStableTagReturnsNull(): void
    {
        $this->assertNull(ApplicationUpdateCommand::greatestStableTag("111 refs/tags/0.2.0-rc1\n"));
    }

    /**
     * HR: Verzija izvornog koda i zaštita održavanja sastavni su dio release paketa.
     *
     * EN: The source version and maintenance guard are part of the release package.
     */
    public function testReleaseMetadataAndMaintenanceGuardArePresent(): void
    {
        $root = dirname(__DIR__, 3);
        $this->assertSame('0.1.8', trim((string)file_get_contents($root . '/VERSION')));

        $frontController = file_get_contents($root . '/public/index.php');
        $this->assertIsString($frontController);
        $this->assertStringContainsString('/data/update-maintenance.json', $frontController);
        $maintenancePosition = strpos($frontController, '$updateMaintenanceFile');
        $autoloadPosition = strpos($frontController, 'require_once $hphAppPath');
        $this->assertIsInt($maintenancePosition);
        $this->assertIsInt($autoloadPosition);
        $this->assertLessThan($autoloadPosition, $maintenancePosition);
    }

    /**
     * HR: Privatni GitHub dohvat ponovno se pokušava s tokenom iz okruženja bez zapisivanja tajne.
     * EN: A private GitHub fetch is retried with the environment token without persisting the secret.
     */
    public function testPrivateRepositoryCheckUsesEnvironmentToken(): void
    {
        $root = sys_get_temp_dir() . '/simbioza-update-test-' . bin2hex(random_bytes(8));
        $binaryDirectory = $root . '/bin';
        mkdir($binaryDirectory, 0700, true);
        mkdir($root . '/config', 0700);
        mkdir($root . '/public', 0700);
        mkdir($root . '/data', 0700);
        file_put_contents($root . '/composer.json', "{\"name\":\"aaieduhr/simbioza\"}\n");
        file_put_contents($root . '/config/app.php', "<?php return [];\n");
        file_put_contents($root . '/public/index.php', "<?php\n");
        file_put_contents($root . '/VERSION', "0.1.7\n");
        $git = $binaryDirectory . '/git';
        file_put_contents($git, <<<'SH'
#!/bin/sh
if [ "${GIT_CONFIG_KEY_0:-}" = "http.https://github.com/.extraHeader" ] \
    && [ "${GIT_CONFIG_VALUE_0:-}" = "Authorization: Bearer test-read-only-token" ]; then
    printf '111 refs/tags/0.1.8\n'
    exit 0
fi
printf "fatal: could not read Username for 'https://github.com': No such device or address\n" >&2
exit 128
SH);
        chmod($git, 0700);

        $originalPath = getenv('PATH');
        $originalToken = getenv('SIMBIOZA_GITHUB_TOKEN');
        $originalGitCount = getenv('GIT_CONFIG_COUNT');
        $originalGitKey = getenv('GIT_CONFIG_KEY_0');
        $originalGitValue = getenv('GIT_CONFIG_VALUE_0');
        $originalComposerAuth = getenv('COMPOSER_AUTH');
        try {
            putenv('PATH=' . $binaryDirectory);
            putenv('SIMBIOZA_GITHUB_TOKEN=test-read-only-token');
            putenv('GIT_CONFIG_COUNT');
            putenv('GIT_CONFIG_KEY_0');
            putenv('GIT_CONFIG_VALUE_0');
            putenv('COMPOSER_AUTH');

            $command = new ApplicationUpdateCommand($root, ['--check', '--lang=en']);
            $this->assertSame(0, $command->run());
        } finally {
            $this->restoreEnvironment('PATH', $originalPath);
            $this->restoreEnvironment('SIMBIOZA_GITHUB_TOKEN', $originalToken);
            $this->restoreEnvironment('GIT_CONFIG_COUNT', $originalGitCount);
            $this->restoreEnvironment('GIT_CONFIG_KEY_0', $originalGitKey);
            $this->restoreEnvironment('GIT_CONFIG_VALUE_0', $originalGitValue);
            $this->restoreEnvironment('COMPOSER_AUTH', $originalComposerAuth);
            $files = [
                $git,
                $root . '/composer.json',
                $root . '/config/app.php',
                $root . '/public/index.php',
                $root . '/VERSION',
            ];
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }

            rmdir($binaryDirectory);
            rmdir($root . '/config');
            rmdir($root . '/public');
            rmdir($root . '/data');
            rmdir($root);
        }
    }

    /** HR: Vraća jednu varijablu okruženja. EN: Restores one environment variable. */
    private function restoreEnvironment(string $name, string|false $value): void
    {
        putenv($value === false ? $name : $name . '=' . $value);
    }
}
