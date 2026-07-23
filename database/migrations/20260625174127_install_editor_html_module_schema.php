<?php

declare(strict_types=1);

/**
 * HR: Aplikacija pokreće potpunu inicijalnu migraciju iz instaliranog HTML Editor modula.
 * Dokumenti, verzije, privici i objavljivanje time se mogu instalirati i u praznu aplikaciju.
 *
 * EN: The application runs the complete initial migration from the installed HTML Editor module.
 * Documents, versions, attachments, and publishing can therefore be installed into an empty app.
 */
return require dirname(__DIR__, 2)
    . '/vendor/aaieduhr/heartphrame-module-editor-html/resources/migrations/initial_editor_html_schema.php';
