<?php

declare(strict_types=1);

// EN: Root-only check on the disposable FPM fixture with fictional configuration.
// The deploy account must neither modify nor publish private administrator files.
//
// HR: Provjera kao root samo na privremenom FPM testu s izmišljenim postavkama.
// Deploy račun ne smije mijenjati niti učiniti javnima privatne administratorske datoteke.
if (
    !is_file('/.dockerenv') || !is_file('/usr/local/share/simbioza-fixture/secured-fpm')
    || !function_exists('posix_geteuid') || posix_geteuid() !== 0
) {
    throw new RuntimeException('Root inside the disposable fixture required.');
}

$root = dirname(__DIR__, 3);
$framework = $root . '/framework.php';
if (!file_exists($framework)) {
    $bytes = '<?php return ' . var_export(['HPH_CONFIG_PATH' => $root . '/config'], true) . ";\n";
    if (file_put_contents($framework, $bytes) !== strlen($bytes)) {
        throw new RuntimeException('Cannot create fictional central configuration.');
    }

    chown($framework, 'fixture-admin');
    chgrp($framework, 'run-simbioza');
    chmod($framework, 0640);
}

$themePath = $root . '/config/theme/themes.json';
$themes = json_decode((string)file_get_contents($themePath), true, 512, JSON_THROW_ON_ERROR);
foreach ($themes as &$theme) {
    if ($theme['id'] === 'simbioza') {
        $theme['light']['colors']['muted_text'] = '#D6D6D6';
        $theme['dark']['colors']['muted_text'] = '#121212';
        $theme['components']['header']['height_px'] = 108;
    }
}

unset($theme);
file_put_contents($themePath, json_encode($themes, JSON_THROW_ON_ERROR));
$menuPath = $root . '/config/menu/settings.json';
$menu = json_decode((string)file_get_contents($menuPath), true, 512, JSON_THROW_ON_ERROR);
if (!in_array('auth', array_column($menu, 'id'), true)) {
    $menu[] = ['id' => 'auth', 'label' => ['en' => 'Users'], 'children' => [[
        'id' => 'auth.setup.login', 'label' => ['en' => 'Login'], 'route' => 'auth.setup',
        'query' => ['section' => 'overview'], 'enabled' => true, 'order' => 10,
    ]], 'order' => 30, 'enabled' => true];
}

if (!in_array('editor-html', array_column($menu, 'id'), true)) {
    $menu[] = ['id' => 'editor-html', 'label' => ['en' => 'HTML editor'], 'children' => [[
        'id' => 'editor-html.settings', 'label' => ['en' => 'Editor settings'],
        'route' => 'editor-html.settings', 'enabled' => true, 'order' => 10,
    ]], 'order' => 90, 'enabled' => true];
}

foreach ($menu as &$item) {
    if (in_array($item['id'], ['theme', 'email.settings.group', 'editor-html', 'auth'], true)) {
        $item['label'] = ['en' => 'Preserved administrator ' . $item['id']];
        $item['enabled'] = false;
    }

    foreach ($item['children'] ?? [] as $index => $child) {
        if (in_array($child['id'], ['email.settings', 'editor-html.settings', 'auth.setup.login'], true)) {
            $child['label'] = ['en' => 'Preserved administrator ' . $child['id']];
            $child['enabled'] = false;
            $child['route'] = null;
            $child['url'] = '/settings/setup';
            $child['query'] = [];
            $item['children'][$index] = $child;
        }
    }
}

unset($item);
file_put_contents($menuPath, json_encode(
    $menu,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
) . "\n");
$asset = $root . '/data/themes/simbioza/administrator-owned.txt';
file_put_contents($asset, 'Fictional private theme asset.');
chown($asset, 'fixture-admin');
chgrp($asset, 'run-simbioza');
chmod($asset, 0640);
$paths = [$framework, $asset];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
    $root . '/config',
    FilesystemIterator::SKIP_DOTS,
));
foreach ($iterator as $file) {
    if ($file->isFile() && !$file->isLink()) {
        $paths[] = $file->getPathname();
    }
}

$before = [];
foreach ($paths as $path) {
    $before[$path] = [hash_file('sha256', $path), fileowner($path), filegroup($path), fileperms($path) & 07777];
}

$process = proc_open(
    ['runuser', '-u', 'simbioza-deploy', '--', PHP_BINARY, $root . '/scripts/update_bundled_assets.php'],
    [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes,
    $root,
);
if (!is_resource($process)) {
    throw new RuntimeException('Cannot start the real deploy-account finalizer.');
}

$output = stream_get_contents($pipes[1]);
$error = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
if (proc_close($process) !== 0) {
    throw new RuntimeException('Deploy-account finalization failed: ' . $output . $error);
}

foreach ($before as $path => $expected) {
    clearstatcache(true, $path);
    $actual = [hash_file('sha256', $path), fileowner($path), filegroup($path), fileperms($path) & 07777];
    if ($expected !== $actual) {
        throw new RuntimeException('Private configuration or asset changed: ' . substr($path, strlen($root) + 1));
    }
}

printf(
    "PASS deploy finalization preserves %d configuration/theme files, including private framework.php (0640).\n",
    count($paths),
);
