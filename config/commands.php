<?php

declare(strict_types=1);

return [
    // HeartPhrame commands.
    new \HeartPhrame\Command\CommandDefinition(
        'encryption:generate-key',
        'Generate a new encryption key',
        [\HeartPhrame\Command\EncryptionCommand::class, 'generateKey'],
    ),
    new \HeartPhrame\Command\CommandDefinition(
        'migrate:up',
        'Run migrations up',
        [\HeartPhrame\Command\MigrateCommand::class, 'up'],
    ),
];
