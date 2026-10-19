<?php

declare(strict_types=1);

namespace Tests\Setup;

use App\Configuration\ConfigurationPaths;
use App\Deployment\DeploymentPolicy;
use App\Localization\RuntimeLanguageStorage;
use App\Module\NativeProcessRunner;
use App\Setup\SetupDiagnostics;
use App\Setup\SetupGateway;
use App\Setup\SetupRequestStore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetupDiagnostics::class)]
#[UsesClass(ConfigurationPaths::class)]
#[UsesClass(DeploymentPolicy::class)]
#[UsesClass(RuntimeLanguageStorage::class)]
#[UsesClass(SetupGateway::class)]
#[UsesClass(SetupRequestStore::class)]
final class SetupDiagnosticsTest extends TestCase
{
    private string $root;

    /** @var array<string,string|false> */
    private array $environment = [];

    /**
     * EN: Isolates configuration without running a helper.
     *
     * HR: Izolira konfiguraciju i ne pokreće helper.
     */
    protected function setUp(): void
    {
        foreach (['HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $name) {
            $this->environment[$name] = getenv($name);
            putenv($name);
        }

        $this->root = sys_get_temp_dir() . '/simbioza-diagnostics-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/config', 0770, true);
        mkdir($this->root . '/external', 0770);
    }

    /**
     * EN: Restores the environment and removes only test config.
     *
     * HR: Vraća okoliš i briše samo testni config.
     */
    protected function tearDown(): void
    {
        foreach (['config', 'external'] as $directory) {
            foreach (scandir($this->root . '/' . $directory) ?: [] as $file) {
                $path = $this->root . '/' . $directory . '/' . $file;
                if (is_file($path)) {
                    unlink($path);
                }
            }

            rmdir($this->root . '/' . $directory);
        }

        rmdir($this->root);
        foreach ($this->environment as $name => $value) {
            is_string($value) ? putenv($name . '=' . $value) : putenv($name);
        }
    }

    /**
     * EN: The GUI never depends on removed data/config storage.
     *
     * HR: GUI nije ovisan o uklonjenom data/config direktoriju.
     */
    public function testDefaultModuleSwitchUsesCanonicalConfiguration(): void
    {
        file_put_contents($this->root . '/config/modules.php', '<?php return ["enabled"=>[]];');
        $report = $this->diagnostics()->report();
        $this->assertTrue($report['state_changes_allowed']);
        $checks = array_column($report['checks'], null, 'id');
        $this->assertStringContainsString(
            $this->root . '/config/modules.php',
            $checks['module_state_writable']['detail'],
        );
        $this->assertFileDoesNotExist($this->root . '/config/.modules.lock');
        $this->assertDirectoryDoesNotExist($this->root . '/data');
    }

    /**
     * EN: A read-only final layer blocks GUI without lower fallback.
     *
     * HR: Read-only zadnji sloj blokira GUI, ne prelazi na zapisivi niži.
     */
    public function testFinalLayerControlsSwitchAndLockPermissions(): void
    {
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/external');
        file_put_contents($this->root . '/external/modules.php', '<?php return ["enabled"=>[]];');
        chmod($this->root . '/external/modules.php', 0400);
        $this->assertFalse($this->diagnostics()->report()['state_changes_allowed']);
        chmod($this->root . '/external/modules.php', 0660);
        clearstatcache();
        file_put_contents($this->root . '/external/.modules.lock', '');
        chmod($this->root . '/external/.modules.lock', 0400);
        $this->assertFalse($this->diagnostics()->report()['state_changes_allowed']);
        chmod($this->root . '/external/.modules.lock', 0660);
        clearstatcache();
        $this->assertTrue($this->diagnostics()->report()['state_changes_allowed']);
        $this->assertFileDoesNotExist($this->root . '/config/modules.php');
    }

    /**
     * EN: A missing layer fails without creating files.
     *
     * HR: Nepostojeći sloj je greška bez stvaranja datoteka.
     */
    public function testMissingLayerBlocksSwitch(): void
    {
        putenv('HPH_CONFIG_PATH=' . $this->root . '/missing');
        $this->assertFalse($this->diagnostics()->report()['state_changes_allowed']);
        $this->assertDirectoryDoesNotExist($this->root . '/missing');
    }

    /**
     * EN: Diagnostics reports effective paths, never secrets or lower write targets.
     *
     * HR: Dijagnostika čita efektivne putanje, ne tajne ili niže ciljeve upisa.
     */
    public function testExternalPathsAreReportedWithoutReadingOrCreatingSettings(): void
    {
        putenv('HPH_CONFIG_PATH=' . $this->root . '/external');
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/config');
        file_put_contents($this->root . '/external/database.php', '<?php return ["password"=>"fixture-secret"];');
        file_put_contents($this->root . '/external/env.php', '<?php return ["token"=>"fixture-secret"];');
        file_put_contents($this->root . '/config/env.php', '<?php return ["token"=>"higher-secret"];');
        chmod($this->root . '/config', 01770);
        $report = $this->diagnostics()->report();
        $checks = array_column($report['checks'], null, 'id');
        foreach (
            [
                'config_writable', 'config_sticky', 'language_config_writable',
                'installation_config_writable', 'editor_config_writable', 'workspace_config_writable',
            ] as $id
        ) {
            $this->assertTrue($checks[$id]['passed'], $id);
            $this->assertStringStartsWith($this->root . '/config', $checks[$id]['detail']);
        }

        $this->assertTrue($checks['database_config_readable']['passed']);
        $this->assertStringStartsWith(
            $this->root . '/external/database.php',
            $checks['database_config_readable']['detail'],
        );
        $this->assertStringStartsWith(
            $this->root . '/config/env.php',
            $checks['environment_config_readable']['detail'],
        );
        $this->assertStringNotContainsString('secret', json_encode($report, JSON_THROW_ON_ERROR));
        foreach (
            ['.modules.lock', '.languages.lock', 'languages.php', 'installation.php',
                'editor-html/editor-html.json', 'workspace/workspace.json'] as $file
        ) {
            $this->assertFileDoesNotExist($this->root . '/config/' . $file);
        }
    }

    /**
     * EN: A read-only language target or lock blocks saving without fallback.
     *
     * HR: Nezapisiv jezični cilj ili lock blokira spremanje bez fallbacka.
     */
    public function testReadOnlyLanguageLayerAndLockFailClearly(): void
    {
        putenv('HPH_CONFIG_OVERRIDE_PATHS=' . $this->root . '/external');
        file_put_contents($this->root . '/external/languages.php', '<?php return [];');
        chmod($this->root . '/external/languages.php', 0400);
        $checks = array_column($this->diagnostics()->report()['checks'], null, 'id');
        $this->assertFalse($checks['language_config_writable']['passed']);
        $this->assertStringContainsString(
            $this->root . '/external/languages.php',
            $checks['language_config_writable']['detail'],
        );
        chmod($this->root . '/external/languages.php', 0660);
        file_put_contents($this->root . '/external/.languages.lock', '');
        chmod($this->root . '/external/.languages.lock', 0400);
        clearstatcache();
        $checks = array_column($this->diagnostics()->report()['checks'], null, 'id');
        $this->assertFalse($checks['language_config_writable']['passed']);
        $this->assertStringContainsString('.languages.lock', $checks['language_config_writable']['detail']);
        chmod($this->root . '/external/.languages.lock', 0660);
        clearstatcache();
        $checks = array_column($this->diagnostics()->report()['checks'], null, 'id');
        $this->assertTrue($checks['language_config_writable']['passed']);
        $this->assertFileDoesNotExist($this->root . '/config/languages.php');
    }

    /**
     * EN: Activation depends on language permissions, not the module file.
     *
     * HR: Aktivacija ovisi o jezičnim pravima, ne o datoteci modula.
     */
    public function testLanguageActivationChecksItsOwnSettingsAndLock(): void
    {
        file_put_contents($this->root . '/config/languages.php', '<?php return ["en"=>[]];');
        file_put_contents($this->root . '/config/modules.php', '<?php return [];');
        chmod($this->root . '/config/modules.php', 0400);
        $report = $this->diagnostics()->report();
        $this->assertFalse($report['state_changes_allowed']);
        $this->assertTrue($report['language_changes_allowed']);
        file_put_contents($this->root . '/config/installation.php', '<?php return [];');
        chmod($this->root . '/config/installation.php', 0400);
        clearstatcache();
        $this->assertFalse($this->diagnostics()->report()['language_changes_allowed']);
        chmod($this->root . '/config/installation.php', 0660);
        file_put_contents($this->root . '/config/.languages.lock', '');
        chmod($this->root . '/config/.languages.lock', 0400);
        clearstatcache();
        $this->assertFalse($this->diagnostics()->report()['language_changes_allowed']);
        chmod($this->root . '/config/.languages.lock', 0660);
        clearstatcache();
        $this->assertTrue($this->diagnostics()->report()['language_changes_allowed']);
    }

    /**
     * EN: Diagnostics follows the actual request store.
     *
     * HR: Dijagnostika prati stvarno spremište zahtjeva.
     */
    public function testRequestDirectoryMatchesGatewayWithoutCreatingFiles(): void
    {
        $checks = array_column($this->diagnostics($this->root . '/external')->report()['checks'], null, 'id');
        $this->assertTrue($checks['setup_requests_writable']['passed']);
        $this->assertStringStartsWith($this->root . '/external', $checks['setup_requests_writable']['detail']);
        $this->assertDirectoryDoesNotExist($this->root . '/data');
    }

    /**
     * EN: A blocked helper leaves the dependent CLI check untested, not falsely missing extensions.
     *
     * HR: Nedostupan helper ostavlja ovisnu CLI provjeru neizvršenom, bez lažne tvrdnje o ekstenzijama.
     */
    public function testUnavailableHelperDoesNotClaimMissingCliExtensions(): void
    {
        $report = $this->diagnostics()->report();
        $checks = array_column($report['checks'], null, 'id');
        $this->assertFalse($report['application_updates_allowed']);
        $this->assertFalse($checks['application_update_runtime']['checked']);
        $this->assertSame(
            'CLI provjera nije provedena jer Setup helper nije dostupan.',
            $checks['application_update_runtime']['detail'],
        );
        $this->assertDirectoryDoesNotExist($this->root . '/data');
    }

    /**
     * EN: Keeps package operations disabled in the CLI test.
     *
     * HR: Zadržava paketne radnje isključenima u CLI testu.
     */
    private function diagnostics(?string $requestDirectory = null): SetupDiagnostics
    {
        return new SetupDiagnostics($this->root, new SetupGateway(
            new SetupRequestStore($requestDirectory ?? $this->root . '/data/setup-requests'),
            new NativeProcessRunner(),
            $this->root,
            '',
            false,
        ));
    }
}
