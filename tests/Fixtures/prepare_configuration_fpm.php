<?php

declare(strict_types=1);

// EN: Prepares only an empty, named Colima fixture without private settings.
//
// HR: Priprema samo praznu, imenovanu Colima fixture bez privatnih postavki.
if (PHP_SAPI !== 'cli' || ($argv[1] ?? '') !== '/srv/simbioza-config-test') {
    throw new RuntimeException('Use the isolated /srv/simbioza-config-test fixture only.');
}

$base = $argv[1];
foreach (['normal', 'secure'] as $name) {
    $root = $base . '/' . $name;
    $secure = $name === 'secure';
    $config = $secure ? $base . '/secure-config' : $root . '/config';
    $override = $secure ? $base . '/secure-override' : '';
    $directories = [$config, $root . '/data', $root . '/data/cache', $root . '/data/logs',
        $root . '/data/tmp', $root . '/data/sessions', $override];
    foreach ($directories as $directory) {
        if ($directory !== '' && !is_dir($directory)) {
            mkdir($directory, 0770, true);
        }
    }

    $write = static function (string $path, array $value): void {
        file_put_contents($path, '<?php return ' . var_export($value, true) . ';' . PHP_EOL);
        chmod($path, 0660);
    };
    $write($root . '/framework.php', ['HPH_APP_PATH' => $root, 'HPH_CONFIG_PATH' => $config,
        'HPH_CONFIG_OVERRIDE_PATHS' => $override]);
    $write($config . '/database.php', ['connections' => ['default' => [
        'driver' => 'sqlite', 'database' => $root . '/data/fixture.sqlite',
    ]]]);
    $write($config . '/env.php', ['salt' => 'fixture-only', 'environment' => 'development',
        'debug' => true, 'encryption_key' => 'fixture-only', 'log_level' => 'info']);
    $write($config . '/installation.php', ['name' => 'FPM fixture',
        'primary_locale' => 'hr', 'supported_locales' => ['hr']]);
    $write($config . '/modules.php', ['enabled' => ['aaieduhr/heartphrame-module-orm',
        'aaieduhr/heartphrame-module-menu', 'aaieduhr/heartphrame-module-auth'], 'removed' => []]);
    $write($config . '/languages.php', ['hr' => ['native_name' => 'Hrvatski', 'flag' => 'hr.svg'],
        'en' => ['native_name' => 'English', 'flag' => 'en.svg']]);
    // EN: Fresh fixtures use JSON; legacy files are covered by a separate upgrade test.
    //
    // HR: Nova fixture instalacija koristi JSON; stare datoteke testira zaseban upgrade test.
    mkdir($config . '/email', 0770, true);
    file_put_contents($config . '/email/email.json', json_encode([
        'enabled' => false, 'smtp' => ['username' => 'fixture', 'password' => 'fixture-secret'],
    ], JSON_THROW_ON_ERROR) . PHP_EOL);
    chmod($config . '/email/email.json', 0660);
    if ($override !== '') {
        mkdir($override . '/email', 0770, true);
        file_put_contents($override . '/email/email.json', '{}' . PHP_EOL);
        chmod($override . '/email/email.json', 0660);
    }

    // EN: Editor and Workspace own their configuration; content remains in data/.
    //
    // HR: Editor i Workspace imaju vlastite konfiguracije; sadržaj ostaje u data/.
    foreach (['editor-html', 'workspace'] as $module) {
        mkdir($config . '/' . $module, 0770, true);
        $values = $module === 'editor-html'
        ? ['view' => ['slug' => ['enabled' => true, 'path' => 'fixture-view']],
                'gui' => ['enabled' => false], 'fixture_extension' => 'preserved']
        : ['fixture_extension' => 'preserved'];
        file_put_contents(
            $config . '/' . $module . '/' . $module . '.json',
            json_encode($values, JSON_THROW_ON_ERROR) . PHP_EOL,
        );
        chmod($config . '/' . $module . '/' . $module . '.json', 0660);
        if ($override !== '') {
            mkdir($override . '/' . $module, 0770, true);
            file_put_contents($override . '/' . $module . '/' . $module . '.json', '{}' . PHP_EOL);
            chmod($override . '/' . $module . '/' . $module . '.json', 0660);
        }
    }

    // EN: Menu/Theme configuration belongs to module folders; context menus remain data.
    //
    // HR: Menu/Theme konfiguracija pripada direktorijima modula; kontekstni meniji ostaju podaci.
    foreach (['menu', 'theme'] as $module) {
        mkdir($config . '/' . $module, 0770, true);
        $write($config . '/' . $module . '/' . $module . '.php', ['fixture' => 'manual']);
        file_put_contents($config . '/' . $module . '/settings.json', $module === 'menu' ? '[]' : '{}');
        if ($override !== '') {
            mkdir($override . '/' . $module, 0770, true);
            file_put_contents($override . '/' . $module . '/settings.json', $module === 'menu' ? '[]' : '{}');
        }
    }

    mkdir($root . '/data/menu', 0770, true);
    // EN: Headless Backup/API settings remain readable in secured FPM; only data is writable.
    //
    // HR: Backup/API postavke bez GUI-ja ostaju čitljive u sigurnom FPM-u; zapisivi su samo podaci.
    foreach (['backup', 'api'] as $module) {
        mkdir($config . '/' . $module, 0770, true);
        if ($override !== '') {
            mkdir($override . '/' . $module, 0770, true);
        }
    }

    $write($config . '/backup/backup.php', ['archive_suffix' => 'fixture.zip',
        'archive_dir' => 'data/custom-archives', 'chunk_size' => 524288]);
    $write($config . '/backup/backup-providers.php', ['providers' => []]);
    $write($config . '/api/api.php', ['rate_limit_per_minute' => 37,
        'require_if_match' => false, 'webhooks' => ['enabled' => true, 'max_attempts' => 3,
        'allowed_hosts' => ['base.test', 'other.test']]]);
    $write($config . '/app.php', ['timezone' => 'UTC', 'logs' => [
        'dir' => $root . '/data/logs', 'filename' => 'fixture.log',
    ]]);
    if ($override !== '') {
        $write($override . '/backup/backup.php', ['chunk_size' => 1048576]);
        $write($override . '/backup/backup-providers.php', ['providers' => []]);
        $write($override . '/api/api.php', ['webhooks' => [
            'max_attempts' => 5, 'allowed_hosts' => ['override.test'],
        ]]);
    }

    file_put_contents($root . '/data/menu/contexts.json', '[]');
    copy($root . '/tests/Fixtures/menu_theme_fpm_probe.php', $root . '/public/menu-theme-probe.php');
    copy($root . '/tests/Fixtures/configuration_fpm_probe.php', $root . '/public/configuration-probe.php');
    copy($root . '/tests/Fixtures/email_fpm_probe.php', $root . '/public/email-probe.php');
    copy($root . '/tests/Fixtures/editor_workspace_fpm_probe.php', $root . '/public/editor-workspace-probe.php');
    copy($root . '/tests/Fixtures/workspace_data_fpm_probe.php', $root . '/public/workspace-data-probe.php');
    copy($root . '/tests/Fixtures/backup_api_fpm_probe.php', $root . '/public/backup-api-probe.php');
}
