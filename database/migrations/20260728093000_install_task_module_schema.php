<?php

declare(strict_types=1);

// EN: Fresh installations use the module's complete initial schema.
//
// HR: Nove instalacije koriste potpunu početnu shemu modula.
return require dirname(__DIR__, 2)
    . '/vendor/aaieduhr/heartphrame-module-task/resources/migrations/initial_task_schema.php';
