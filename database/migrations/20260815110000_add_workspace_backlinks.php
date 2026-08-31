<?php

declare(strict_types=1);

/**
 * HR: Učitava službenu reverzibilnu migraciju izvedenog backlink indeksa.
 * EN: Loads the official reversible migration for the derived backlink index.
 */
return require dirname(__DIR__, 2)
    . '/vendor/aaieduhr/simbioza-module-workspace/resources/migrations/add_workspace_backlinks.php';
