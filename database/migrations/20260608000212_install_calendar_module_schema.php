<?php

declare(strict_types=1);

/**
 * HR: Aplikacija pokreće potpunu inicijalnu migraciju iz instaliranog Calendar modula.
 * Modul tako ostaje jedini vlasnik svoje prenosive sheme za podržane baze podataka.
 *
 * EN: The application runs the complete initial migration from the installed Calendar module.
 * This keeps the module as the single owner of its portable schema for supported databases.
 */
return require dirname(__DIR__, 2)
    . '/vendor/aaieduhr/heartphrame-module-calendar/resources/migrations/initial_calendar_schema.php';
