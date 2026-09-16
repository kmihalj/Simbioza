<?php

/**
 * HR: Dodaje binarnu povijest privitaka i postojeće privitke bilježi kao verziju 1.
 * EN: Adds attachment binary history and records existing attachments as version 1.
 */

declare(strict_types=1);

return require dirname(__DIR__, 2)
. '/vendor/aaieduhr/heartphrame-module-editor-html/resources/migrations/add_editor_asset_versions.php';
