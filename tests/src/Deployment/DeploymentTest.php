<?php

declare(strict_types=1);

namespace Tests\Deployment;

use App\Deployment\ContainerRuntime;
use App\Deployment\DeploymentPolicy;
use App\Deployment\ImageContextBuilder;
use App\Module\CommandResult;
use App\Module\ComposerPackageManager;
use App\Module\ModuleCatalog;
use App\Module\ProcessRunnerInterface;
use App\Setup\SetupGateway;
use App\Setup\SetupRequestStore;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * EN: Isolated deployment boundaries never read or modify live HFC data.
 *
 * HR: Izolirane granice deploymenta ne čitaju niti mijenjaju žive HFC podatke.
 */
#[CoversNothing]
final class DeploymentTest extends TestCase
{
    private string $root;

    private string|false $mode;

    /**
     * EN: Creates a uniquely owned fictional image/runtime.
     *
     * HR: Stvara jedinstveni testni image/runtime.
     */
    protected function setUp(): void
    {
        $this->mode = getenv('HPH_DEPLOYMENT_MODE');
        putenv('HPH_DEPLOYMENT_MODE');
        $this->root = sys_get_temp_dir() . '/simbioza-a4-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/resources/deployment', 0700, true);
        mkdir($this->root . '/runtime/data', 0700, true);
    }

    /**
     * EN: Deletes only the test's own files without following links.
     *
     * HR: Briše samo vlastite testne datoteke bez praćenja linkova.
     */
    protected function tearDown(): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->root);
        $this->mode === false ? putenv('HPH_DEPLOYMENT_MODE') : putenv('HPH_DEPLOYMENT_MODE=' . $this->mode);
    }

    /**
     * EN: Classical installations remain unchanged, but environment cannot disable a baked marker.
     *
     * HR: Klasična instalacija ostaje nepromijenjena, a okoliš ne može isključiti ugrađenu oznaku.
     */
    public function testBakedMarkerCannotBeDisabled(): void
    {
        $policy = new DeploymentPolicy($this->root);
        $this->assertFalse($policy->isImmutable());
        $this->marker();
        putenv('HPH_DEPLOYMENT_MODE=classic');
        $this->assertTrue($policy->isImmutable());
        foreach (['language-add', 'language-install', 'application-update-check', 'health-check'] as $action) {
            $policy->assertWorkerActionAllowed($action);
        }

        $this->expectException(RuntimeException::class);
        $policy->assertWorkerActionAllowed('application-update-start');
    }

    /**
     * EN: An invalid mode fails closed instead of enabling a package mutator.
     *
     * HR: Neispravan način rada prekida izvršavanje umjesto dopuštanja izmjene paketa.
     */
    public function testInvalidModeFailsClosed(): void
    {
        putenv('HPH_DEPLOYMENT_MODE=typo');
        $this->expectException(RuntimeException::class);
        (new DeploymentPolicy($this->root))->isImmutable();
    }

    /**
     * EN: Composer and helper requests are blocked before any process or request file exists.
     *
     * HR: Composer i helper zahtjevi blokirani su prije pokretanja procesa ili stvaranja datoteke zahtjeva.
     */
    public function testMutatorsFailBeforeSideEffects(): void
    {
        $this->marker();
        $runner = new class implements ProcessRunnerInterface {
            /**
             * EN: Unexpected process execution fails this test.
             *
             * HR: Neočekivano pokretanje procesa ruši test.
             */
            public function run(array $command, string $workingDirectory): CommandResult
            {
                throw new \LogicException('A process must not run.');
            }
        };
        $manager = new ComposerPackageManager(new ModuleCatalog(), $runner, $this->root);
        $gateway = new SetupGateway(new SetupRequestStore($this->root . '/requests'), $runner, $this->root, '', true);
        foreach (
            [fn() => $manager->install('audit'), fn() => $manager->uninstall('audit'),
            fn(): string => $gateway->execute('packages-prepare')] as $operation
        ) {
            try {
                $operation();
                self::fail('Immutable mutation must fail.');
            } catch (RuntimeException $error) {
                $this->assertStringContainsString('Immutable deployment', $error->getMessage());
            }
        }

        $this->assertDirectoryDoesNotExist($this->root . '/requests');
        $this->assertFileDoesNotExist($this->root . '/composer.json');
    }

    /**
     * EN: New sites can install; existing sites refuse an un-migrated image and accept only its exact ID.
     *
     * HR: Novi site može instalirati; postojeći odbija nemigrirani image i prihvaća samo njegov točan ID.
     */
    public function testInstalledSiteRequiresMatchingMigrationState(): void
    {
        $this->marker();
        $data = $this->root . '/runtime/data';
        file_put_contents($data . '/.container-initialized', '1');
        $runtime = new ContainerRuntime($this->root, $this->root . '/runtime');
        $runtime->assertReady();
        file_put_contents($data . '/installation.lock', '{}');
        foreach ([null, str_repeat('b', 64)] as $id) {
            if ($id !== null) {
                file_put_contents($data . '/.image-state.json', json_encode(['code_id' => $id]));
            }

            try {
                $runtime->assertReady();
                self::fail('A missing/stale image migration must block serving.');
            } catch (RuntimeException $error) {
                $this->assertStringContainsString('Run migrate', $error->getMessage());
            }
        }

        file_put_contents($data . '/.image-state.json', json_encode(['code_id' => str_repeat('a', 64)]));
        $runtime->assertReady();
        $this->assertSame(str_repeat('a', 64), $runtime->image()['code_id']);
    }

    /**
     * EN: A runtime symlink cannot redirect a container marker outside its private volume.
     *
     * HR: Runtime symlink ne smije preusmjeriti oznaku containera izvan privatnog volumena.
     */
    public function testRuntimeSymlinkIsRejected(): void
    {
        symlink($this->root . '/resources', $this->root . '/runtime/data/.container-initialized');
        $this->expectException(RuntimeException::class);
        (new ContainerRuntime($this->root, $this->root . '/runtime'))->assertReady();
    }

    /**
     * EN: Writes a fictional code-owned marker without any private configuration.
     *
     * HR: Zapisuje testnu oznaku u kodu bez privatne konfiguracije.
     */
    private function marker(): void
    {
        file_put_contents($this->root . '/resources/deployment/immutable.json', json_encode([
            'format' => 'simbioza-image-v1', 'code_id' => str_repeat('a', 64), 'modules' => [],
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * EN: Build export uses an allowlist, never the live config/data/vendor, and refuses reuse.
     *
     * HR: Izvoz za gradnju koristi allowlist, ne živi config/data/vendor, i odbija ponovno korištenje.
     */
    public function testBuildContextExcludesPrivateState(): void
    {
        $source = $this->root . '/source';
        mkdir($source, 0700);
        foreach (['config/auth', 'data', 'vendor', 'src', 'docker'] as $directory) {
            mkdir($source . '/' . $directory, 0700, true);
        }

        foreach (
            ['config/env.php', 'config/database.php', 'config/auth/auth.json', 'data/private.txt',
            'vendor/private.php', 'composer.local.json'] as $private
        ) {
            file_put_contents($source . '/' . $private, 'fictional-private-value');
        }

        foreach (
            ['config/app.php', 'src/Example.php', 'docker/Dockerfile', 'docker/apache.conf',
            'docker/php.ini'] as $code
        ) {
            file_put_contents($source . '/' . $code, 'public-example-code');
        }

        $requirements = ['php' => '^8.2', 'aaieduhr/heartphrame-framework' => '^0.0.25'];
        $catalog = new ModuleCatalog();
        foreach ($catalog->requiredSlugs() as $slug) {
            $requirements[$catalog->definitionFor($slug)['package']] = '^0.3.0';
        }

        file_put_contents($source . '/composer.json', json_encode(['require' => $requirements], JSON_THROW_ON_ERROR));
        $processes = new \App\Module\NativeProcessRunner();
        $this->assertSame(0, $processes->run(['git', 'init', '--quiet'], $source)->exitCode);
        $context = $this->root . '/context';
        $builder = new ImageContextBuilder($source, $this->root);
        $id = $builder->build($context, false, []);
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $id);
        $this->assertFileExists($context . '/app/config/app.php');
        $this->assertFileExists($context . '/app/src/Example.php');
        foreach (
            ['config/env.php', 'config/database.php', 'config/auth/auth.json', 'data/private.txt',
            'vendor/private.php', 'composer.local.json'] as $private
        ) {
            $this->assertFileDoesNotExist($context . '/app/' . $private);
        }

        $manifest = json_decode(
            (string)file_get_contents($context . '/app/composer.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $this->assertArrayHasKey('aaieduhr/heartphrame-module-backup', $manifest['require']);
        $this->assertArrayNotHasKey('aaieduhr/heartphrame-module-calendar', $manifest['require']);
        $this->expectException(RuntimeException::class);
        $builder->build($context, false, []);
    }
}
