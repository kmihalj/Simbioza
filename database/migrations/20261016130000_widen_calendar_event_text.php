<?php

declare(strict_types=1);

/**
 * EN: Runs the Calendar module's lossless title/location expansion on existing installations.
 * Fresh installations already use the expanded initial schema.
 *
 * HR: Pokreće proširenje naslova/lokacija Calendar modula bez gubitka podataka na postojećim instalacijama.
 * Nove instalacije već koriste proširenu početnu shemu.
 */
return require dirname(__DIR__, 2)
    . '/vendor/aaieduhr/heartphrame-module-calendar/resources/migrations/widen_calendar_event_text.php';
