<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/config',
        __DIR__ . '/tests',
        __DIR__ . '/views',
        __DIR__ . '/hph.php',
    ])
    ->withSkip([
        \Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector::class => [
            __DIR__ . '/src/App/Views',
        ],
        \Rector\CodingStyle\Rector\ArrowFunction\ArrowFunctionDelegatingCallToFirstClassCallableRector::class => [
            __DIR__ . '/config/services.php',
        ],
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets()
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
//        privatization: true,
//        naming: true,
        instanceOf: true,
        earlyReturn: true,
//        strictBooleans: true,
//        carbon: true,
        rectorPreset: true,
        phpunitCodeQuality: true,
//        doctrineCodeQuality: true,
//        symfonyCodeQuality: true,
//        symfonyConfigs: true,
    );
