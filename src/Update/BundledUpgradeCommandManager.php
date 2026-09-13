<?php

declare(strict_types=1);

namespace App\Update;

use HeartPhrame\Command\CommandDefinition;
use HeartPhrame\Command\CommandManager;
use HeartPhrame\Factory\CallableFactory;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * HR: Nakon migracija updatera primjenjuje pakete i kada radi već učitana stara verzija update.php.
 * EN: Applies bundles after updater migrations even when an older update.php is already loaded.
 */
final class BundledUpgradeCommandManager extends CommandManager
{
    /** HR: Prima CLI servise i stvarni korijen aplikacije. EN: Receives CLI services and the actual application root. */
    public function __construct(ContainerInterface $container, LoggerInterface $logger, private readonly string $root)
    {
        parent::__construct($container, $logger);
    }

    /** HR: Omata samo uspješnu naredbu migracije prema gore. EN: Wraps only the successful upward-migration command. */
    public function getCommand(string $name): ?CommandDefinition
    {
        $command = parent::getCommand($name);
        if ($name !== 'orm-migrate:up' || !$command instanceof CommandDefinition) {
            return $command;
        }

        $factory = $this->container->get(CallableFactory::class);
        if (!$factory instanceof CallableFactory) {
            throw new RuntimeException('The CLI callable factory service is invalid.');
        }

        $handler = $factory->buildCallable($command->getHandler());
        $wrappedHandler = function (array $arguments = [], array $options = []) use ($handler): int {
            $result = $handler($arguments, $options);
            $exit = is_int($result) ? $result : 0;
            if ($exit === 0) {
                $connection = $options['connection'] ?? 'default';
                $path = $options['path'] ?? 'database/migrations';
                if (is_string($connection) && is_string($path)) {
                    $this->afterUpdaterMigrations($connection, $path);
                }
            }

            return $exit;
        };
        return new CommandDefinition(
            $command->getName(),
            $command->getDescription(),
            $wrappedHandler,
            $command->getOptions(),
            $command->getHelp(),
        );
    }

    /**
     * HR: Aktivira korak samo za glavnu shemu tijekom eksplicitnog updatea, izvan ORM transakcije.
     * EN: Activates the step only for the main schema during an explicit update, outside the ORM transaction.
     */
    private function afterUpdaterMigrations(string $connection, string $path): void
    {
        $maintenance = $this->root . '/data/update-maintenance.json';
        if (
            PHP_SAPI !== 'cli' || !is_file($maintenance)
            || $connection !== 'default'
        ) {
            return;
        }

        $path = str_starts_with($path, '/') ? $path : $this->root . '/' . $path;

        $actualPath = realpath($path);
        if ($actualPath === false || $actualPath !== realpath($this->root . '/database/migrations')) {
            return;
        }

        $script = $this->root . '/scripts/update_bundled_assets.php';
        if (!is_file($script)) {
            throw new RuntimeException('The bundled-asset update script is missing.');
        }

        $process = proc_open([PHP_BINARY, $script], [0 => STDIN, 1 => STDOUT, 2 => STDERR], $pipes, $this->root);
        if (!is_resource($process) || proc_close($process) !== 0) {
            throw new RuntimeException(
                'The bundled-asset update failed after migrations; maintenance must remain enabled.',
            );
        }
    }
}
