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
    new \HeartPhrame\Command\CommandDefinition(
        'modules',
        'List, add, enable, disable, remove, and restore bundled modules',
        [\App\Module\ModuleCommand::class, 'run'],
    ),
    new \HeartPhrame\Command\CommandDefinition(
        'languages',
        'Create, validate, and install consolidated language packs',
        [\App\Localization\LanguageCommand::class, 'run'],
    ),
];
