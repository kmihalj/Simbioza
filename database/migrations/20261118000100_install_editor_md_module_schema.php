<?php

declare(strict_types=1);

/**
 * EN: Runs the initial schema packaged with the optional Markdown Editor.
 *
 * HR: Pokreće početnu shemu iz paketa neobveznog Markdown Editora.
 */
return require dirname(__DIR__, 2)
    . '/vendor/aaieduhr/heartphrame-module-editor-md/resources/migrations/initial_editor_md_schema.php';
