<?php

declare(strict_types=1);

/**
 * Entry point for all HTTP requests
 */

// Autoload
use HeartPhrame\App;
use HeartPhrame\CodeBook\EnvKeyEnum;

$hphAppPath = is_string($hphAppPath = getenv('HPH_APP_PATH')) ? $hphAppPath : dirname(__DIR__);

require_once $hphAppPath . implode(DIRECTORY_SEPARATOR, ['', 'vendor', 'autoload.php']);

$configPath = is_string($configPath = getenv(EnvKeyEnum::HPH_CONFIG_PATH->value)) ?
    $configPath :
    $hphAppPath . DIRECTORY_SEPARATOR . 'config';

// Create and run the application
(new App($configPath))->run();
