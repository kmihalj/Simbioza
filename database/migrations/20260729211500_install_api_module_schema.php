<?php

declare(strict_types=1);

/**
 * EN: HFClean runs the API module's single portable initial migration.
 *
 * HR: HFClean pokreće jedinu prijenosnu početnu migraciju API modula.
 */
return require dirname(__DIR__, 2)
    . '/vendor/aaieduhr/heartphrame-module-api/resources/migrations/initial_api_schema.php';
