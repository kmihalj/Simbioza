<?php

declare(strict_types=1);

/**
 * HR: Aplikacija pokreće potpunu inicijalnu migraciju iz instaliranog Workspace modula.
 * Područja, stablo, ACL i tijek objavljivanja zato nisu duplicirani u aplikaciji.
 *
 * EN: The application runs the complete initial migration from the installed Workspace module.
 * Workspaces, tree, ACL, and publishing workflow are therefore not duplicated in the app.
 */
return require dirname(__DIR__, 2)
    . '/vendor/aaieduhr/simbioza-module-workspace/resources/migrations/initial_workspace_schema.php';
