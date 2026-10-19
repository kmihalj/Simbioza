<?php

declare(strict_types=1);

// EN: Export public release inputs only; never mount the live HFC installation into a privileged fixture.
// The output is a new directory, without a database, real configuration, vendor links or Git metadata.
//
// HR: Izvozi samo javne izvore izdanja; živa HFC instalacija ne montira se u privilegirani test.
// Izlaz je novi direktorij bez baze, stvarnih postavki, vendor poveznica ili Git metapodataka.
$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
$output = $argv[1] ?? '';
(new App\Deployment\ImageContextBuilder($root, dirname($root)))->build($output, false, []);
unlink($output . '/app/resources/deployment/immutable.json');
copy($root . '/composer.json', $output . '/app/composer.json');
$files = (new App\Module\NativeProcessRunner())->run(
    ['git', 'ls-files', '--cached', '--others', '--exclude-standard', '-z',
        'scripts', 'tests/Fixtures/secured_fpm', 'docs/installation_fpm_secured_en.md'],
    $root,
);
if ($files->exitCode !== 0) {
    throw new RuntimeException('Cannot enumerate public fixture inputs.');
}

foreach (array_filter(explode("\0", $files->stdout)) as $file) {
    $source = $root . '/' . $file;
    if (!is_file($source) || is_link($source)) {
        continue;
    }

    $target = $output . '/app/' . $file;
    if (!is_dir(dirname($target))) {
        mkdir(dirname($target), 0755, true);
    }

    copy($source, $target);
}

echo "Exported a secret-free classic-installation fixture.\n";
