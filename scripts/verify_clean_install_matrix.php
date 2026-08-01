<?php

/**
 * HR: CLI ulaz u clean-room matricu instalacija.
 * EN: CLI entry point for the clean-room installation matrix.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/Tools/CleanInstallMatrix.php';

exit(\HFClean\Tools\runCleanInstallMatrix());
