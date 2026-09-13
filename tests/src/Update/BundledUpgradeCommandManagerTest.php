<?php

declare(strict_types=1);

namespace Tests\Update;

use App\Update\BundledUpgradeCommandManager;
use HeartPhrame\Command\CommandDefinition;
use HeartPhrame\Factory\CallableFactory;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

#[CoversNothing]
final class BundledUpgradeCommandManagerTest extends TestCase
{
    private string $root;

    /** HR: Priprema samo izolirane CLI datoteke testa. EN: Prepares only isolated test CLI files. */
    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/simbioza-upgrade-command-' . bin2hex(random_bytes(8));
        mkdir($this->root, 0700);
        foreach (['data', 'scripts', 'database', 'database/migrations'] as $directory) {
            mkdir($this->root . '/' . $directory, 0700);
        }

        file_put_contents(
            $this->root . '/scripts/update_bundled_assets.php',
            '<?php file_put_contents(__DIR__ . "/../data/bundle-ran", "1");',
        );
    }

    /** HR: Briše isključivo imenovane testne datoteke. EN: Deletes only named test files. */
    protected function tearDown(): void
    {
        foreach (['scripts/update_bundled_assets.php', 'data/update-maintenance.json', 'data/bundle-ran'] as $file) {
            if (is_file($this->root . '/' . $file)) {
                unlink($this->root . '/' . $file);
            }
        }

        foreach (['database/migrations', 'database', 'scripts', 'data', ''] as $directory) {
            rmdir($this->root . ($directory !== '' ? '/' . $directory : ''));
        }
    }

    /** HR: Uobičajena migracija izvan updatera ne mijenja pakete. EN: A normal migration outside the updater does not change bundles. */
    public function testNormalMigrationDoesNotApplyBundles(): void
    {
        $handler = $this->manager()->getCommand('orm-migrate:up')->getHandler();
        $this->assertSame(0, $handler([], []));
        $this->assertFileDoesNotExist($this->root . '/data/bundle-ran');
    }

    /** HR: Stari updater koji poziva novu CLI naredbu dobiva novi korak. EN: An old updater calling the new CLI command gets the new step. */
    public function testUpdaterMigrationAppliesBundlesAfterSuccess(): void
    {
        file_put_contents($this->root . '/data/update-maintenance.json', '{"target_tag":"0.1.68"}');
        $handler = $this->manager()->getCommand('orm-migrate:up')->getHandler();
        $this->assertSame(0, $handler([], ['connection' => 'default', 'path' => 'database/migrations']));
        $this->assertFileExists($this->root . '/data/bundle-ran');
    }

    /** HR: Neuspjela migracija ne smije uvesti sadržaj. EN: A failed migration must not import content. */
    public function testFailedMigrationDoesNotApplyBundles(): void
    {
        file_put_contents($this->root . '/data/update-maintenance.json', '{}');
        $handler = $this->manager(7)->getCommand('orm-migrate:up')->getHandler();
        $this->assertSame(7, $handler([], []));
        $this->assertFileDoesNotExist($this->root . '/data/bundle-ran');
    }

    /** HR: Druge veze i putanje migracija ne diraju glavnu instalaciju. EN: Other connections and migration paths do not touch the main installation. */
    public function testOtherConnectionsAndPathsDoNotApplyBundles(): void
    {
        file_put_contents($this->root . '/data/update-maintenance.json', '{}');
        $handler = $this->manager()->getCommand('orm-migrate:up')->getHandler();
        $this->assertSame(0, $handler([], ['connection' => 'other']));
        $this->assertSame(0, $handler([], ['path' => 'other/migrations']));
        $this->assertFileDoesNotExist($this->root . '/data/bundle-ran');
    }

    /** HR: Greška u paketu zaustavlja update umjesto lažne potvrde uspjeha. EN: A bundle failure stops the update instead of falsely reporting success. */
    public function testBundleFailureIsPropagated(): void
    {
        file_put_contents($this->root . '/data/update-maintenance.json', '{}');
        file_put_contents($this->root . '/scripts/update_bundled_assets.php', '<?php exit(23);');
        $handler = $this->manager()->getCommand('orm-migrate:up')->getHandler();
        $this->expectException(RuntimeException::class);
        $handler([], []);
    }

    /** HR: Read-only status naredba ostaje nepromijenjena. EN: The read-only status command remains unchanged. */
    public function testStatusCommandIsUnchanged(): void
    {
        $manager = $this->manager();
        $definition = new CommandDefinition('orm-migrate:status', 'Status', static fn(): int => 0);
        $manager->addCommands($definition);
        $this->assertSame($definition, $manager->getCommand('orm-migrate:status'));
    }

    /** HR: Stvara adapter s jednostavnom izvornom naredbom. EN: Creates the adapter with a simple original command. */
    private function manager(int $exit = 0): BundledUpgradeCommandManager
    {
        $factory = $this->createMock(CallableFactory::class);
        $factory->method('buildCallable')->willReturnCallback(static function (
            callable|object|string|array $handler,
        ): callable {
            if (!is_callable($handler)) {
                throw new RuntimeException('The test command handler must be callable.');
            }

            return $handler;
        });
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->with(CallableFactory::class)->willReturn($factory);
        $manager = new BundledUpgradeCommandManager($container, new NullLogger(), $this->root);
        $manager->addCommands(new CommandDefinition('orm-migrate:up', 'Migrate', static fn(): int => $exit));
        return $manager;
    }
}
