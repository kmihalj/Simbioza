<?php

declare(strict_types=1);

// EN: New installations receive the current Auth schema directly, without upgrade export.
//
// HR: Nove instalacije odmah dobivaju aktualnu Auth shemu, bez upgrade izvoza.
return require dirname(__DIR__, 2) . '/vendor/aaieduhr/heartphrame-module-auth/resources/migrations/initial_auth_schema.php';
