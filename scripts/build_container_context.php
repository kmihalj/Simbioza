<?php

declare(strict_types=1);

// EN: Creates a new secret-free build context; --local is for unpublished development packages only.
//
// HR: Stvara novi kontekst bez tajni; --local je samo za neobjavljene razvojne pakete.
require dirname(__DIR__) . '/vendor/autoload.php';
if (PHP_SAPI !== 'cli') {
    exit(2);
}
$options = getopt('', ['output:', 'local', 'modules:']);
$output = $options['output'] ?? null;
if (!is_string($output) || $output === '') {
    fwrite(STDERR, "Usage: php scripts/build_container_context.php --output=/new/context [--local] [--modules=all|theme,backup,...]\n");
    exit(2);
}
$catalog = new \App\Module\ModuleCatalog();
$selection = $options['modules'] ?? 'all';
$optional = $selection === 'all' ? $catalog->optionalSlugs()
: (is_string($selection) && $selection !== '' ? explode(',', $selection) : []);
try {
    foreach ($optional as $slug) {
        if (!in_array($slug, $catalog->optionalSlugs(), true)) {
            throw new RuntimeException('Unknown optional module.');
        }
    }
    $builder = new \App\Deployment\ImageContextBuilder(dirname(__DIR__), dirname(__DIR__, 2));
    printf("Created sanitized context; code ID: %s\n", $builder->build($output, isset($options['local']), $optional));
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
