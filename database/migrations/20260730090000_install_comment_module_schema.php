<?php

declare(strict_types=1);

/**
 * HR: HFClean početna instalacija Comment modula koristi jedinu migraciju
 *     koju posjeduje sam modul.
 * EN: The HFClean initial Comment installation uses the module-owned single
 *     migration.
 */
return require dirname(__DIR__, 2)
    . '/vendor/aaieduhr/heartphrame-module-comment/resources/migrations/initial_comment_schema.php';
