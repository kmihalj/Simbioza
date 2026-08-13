<?php

declare(strict_types=1);

$template = dirname(__DIR__, 2) . '/../heartphrame-module-backup/resources/migrations/initial_backup_schema.php';
if (!is_file($template)) {
    $template = dirname(__DIR__, 2) . '/vendor/aaieduhr/heartphrame-module-backup/resources/migrations/initial_backup_schema.php';
}

return require $template;
