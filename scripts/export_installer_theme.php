<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleTheme\ModuleTheme;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeArchiveService;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeAssetLibrary;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeConfigRepository;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;

$applicationRoot = dirname(__DIR__);
require $applicationRoot . '/vendor/autoload.php';

$helper = new Helper();
$config = new Config($helper, [], $applicationRoot);
$config->loadLayeredDirectories([$applicationRoot . '/config']);
$moduleFile = (new ReflectionClass(ModuleTheme::class))->getFileName();
if (!is_string($moduleFile)) {
    throw new RuntimeException('Theme module path could not be resolved.');
}

$repository = new ThemeConfigRepository($config, dirname($moduleFile, 2));
$archive = new ThemeArchiveService($repository, new ThemeAssetLibrary($repository));
$outputDirectory = $applicationRoot . '/resources/installation/theme';
if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0775, true) && !is_dir($outputDirectory)) {
    throw new RuntimeException('Installer theme resource directory could not be created.');
}

$outputFile = $outputDirectory . '/simbioza.zip';
if (file_put_contents($outputFile, $archive->export('simbioza'), LOCK_EX) === false) {
    throw new RuntimeException('Installer theme resource could not be written.');
}

chmod($outputFile, 0644);
fwrite(STDOUT, "resources/installation/theme/simbioza.zip\n");
