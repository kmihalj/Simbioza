<?php

declare(strict_types=1);

// EN: Fresh installations use the module's complete initial schema.
//
// HR: Nove instalacije koriste potpunu početnu shemu modula.
return require dirname(__DIR__, 2)
    . '/vendor/aaieduhr/simbioza-module-user/resources/migrations/initial_simbioza_user_schema.php';
