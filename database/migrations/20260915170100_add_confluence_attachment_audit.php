<?php

/**
 * HR: Čuva izvornog učitavača i vrijeme uvezenih Confluence verzija privitaka.
 * EN: Preserves source uploader and time for imported Confluence attachment versions.
 */

declare(strict_types=1);

return require dirname(__DIR__, 2)
. '/vendor/aaieduhr/simbioza-module-confluence-import/resources/migrations/add_confluence_attachment_audit.php';
