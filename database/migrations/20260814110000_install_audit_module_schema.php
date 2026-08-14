<?php

declare(strict_types=1);

/**
 * HR: Aplikacijska migracija učitava kanonsku shemu iz instaliranog Audit modula.
 * EN: Application migration loads the canonical schema from the installed Audit module.
 */
$migration = dirname(__DIR__, 2)
    . '/vendor/aaieduhr/heartphrame-module-audit/resources/migrations/initial_audit_schema.php';

if (!is_file($migration)) {
    throw new RuntimeException('Audit module migration is unavailable: ' . $migration);
}

return require $migration;
