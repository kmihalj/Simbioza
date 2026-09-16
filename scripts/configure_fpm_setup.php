<?php

declare(strict_types=1);

// HR: Ovaj alat mijenja sistemske račune i FPM samo uz izričiti --install ili
//     --finalize. Zadani --check je potpuno read-only.
// EN: This tool changes system accounts and FPM only with explicit --install
//     or --finalize. The default --check mode is entirely read-only.

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This tool is CLI-only.\n");
    exit(2);
}

$options = getopt('', [
    'check', 'install', 'finalize', 'app-root:', 'maintainer:', 'listen::', 'php-fpm::',
    'simplesaml-config-dir:', 'help',
]);
if (isset($options['help'])) {
    fwrite(STDOUT, <<<'HELP'
Simbioza dedicated PHP-FPM setup / Namjenski PHP-FPM za Simbiozu

  sudo php scripts/configure_fpm_setup.php --install --app-root=/srv/simbioza --maintainer=LOGIN
  sudo php scripts/configure_fpm_setup.php --install --app-root=/srv/simbioza --maintainer=LOGIN \
    --simplesaml-config-dir=/srv/simbioza/data/saml/config
  sudo php scripts/configure_fpm_setup.php --finalize --app-root=/srv/simbioza --maintainer=LOGIN
  php scripts/configure_fpm_setup.php --check --app-root=/srv/simbioza --maintainer=LOGIN

`--install` creates isolated accounts, groups, FPM configuration, the strictly
allowlisted Setup helper, and temporary initial-installer permissions.
`--simplesaml-config-dir` selects an existing private SimpleSAMLphp config
inside data/. The application and its SimpleSAMLphp endpoint must use this
same FPM pool; the tool never copies shared secrets or changes SAML registry.
`--finalize` hardens ownership after the web installer has completed.
HELP . PHP_EOL);
    exit(0);
}

$mode = isset($options['install']) ? 'install' : (isset($options['finalize']) ? 'finalize' : 'check');
$root = realpath((string)($options['app-root'] ?? dirname(__DIR__)));
if (!is_string($root) || $root === '/' || !is_file($root . '/composer.json')) {
    fail('Use --app-root with a valid Simbioza installation directory.');
}
if (is_dir($root . '/.git') && $mode !== 'check') {
    fail('Refusing to change ownership of a Git working copy. Use a separate release installation.');
}
$maintainer = trim((string)($options['maintainer'] ?? getenv('SUDO_USER') ?: getenv('USER')));
assertIdentity($maintainer, 'maintainer');
$listen = trim((string)($options['listen'] ?? '127.0.0.1:9075'));
if (preg_match('/\A127\.0\.0\.1:[1-9][0-9]{2,4}\z/D', $listen) !== 1) {
    fail('The FPM listener must be a non-privileged 127.0.0.1 TCP endpoint.');
}
$phpFpm = trim((string)($options['php-fpm'] ?? defaultPhpFpm()));
if ($phpFpm === '' || !is_file($phpFpm) || !is_executable($phpFpm)) {
    fail('PHP-FPM executable was not found; pass --php-fpm=/absolute/path.');
}
$simpleSamlConfig = simpleSamlConfigDirectory($options['simplesaml-config-dir'] ?? null, $root);

$platform = PHP_OS_FAMILY === 'Darwin' ? 'darwin' : (PHP_OS_FAMILY === 'Linux' ? 'linux' : 'unsupported');
if ($platform === 'unsupported') {
    fail('Only macOS and Linux are supported by this helper.');
}

if ($mode === 'check') {
    printChecks($root, $maintainer, $phpFpm, $listen, $platform, $simpleSamlConfig);
    exit(0);
}
if (!function_exists('posix_geteuid') || posix_geteuid() !== 0) {
    fail('Run --' . $mode . ' once as root. Normal maintenance does not require root afterwards.');
}

if ($mode === 'install') {
    installIdentities($platform, $maintainer, deployHome($platform, $phpFpm));
    installFilesystem($platform, $root, false, $simpleSamlConfig);
    installHelper($root, $phpFpm, deployHome($platform, $phpFpm));
    installFpm($platform, $root, $phpFpm, $listen, $simpleSamlConfig);
    fwrite(STDOUT, "Initial FPM setup completed. Finish the web installer, then run --finalize once.\n");
} else {
    installIdentities($platform, $maintainer, deployHome($platform, $phpFpm));
    installFilesystem($platform, $root, true, $simpleSamlConfig);
    fwrite(STDOUT, "Simbioza ownership was finalized. Re-login to activate new group membership.\n");
}

printChecks($root, $maintainer, $phpFpm, $listen, $platform, $simpleSamlConfig);

/** HR: Zaustavlja alat jasnom porukom. EN: Stops the tool with a clear message. */
function fail(string $message): never
{
    fwrite(STDERR, $message . PHP_EOL);
    exit(2);
}

/** HR: Odbija identitete koji nisu sigurni argumenti sistemskih alata. EN: Rejects identities unsafe for system-tool arguments. */
function assertIdentity(string $value, string $label): void
{
    if (preg_match('/\A[a-z_][a-z0-9_-]{0,30}\z/D', $value) !== 1) {
        fail('Invalid ' . $label . ' identity: ' . $value);
    }
}

/** HR: Pronalazi Homebrew ili sistemski php-fpm. EN: Finds Homebrew or system PHP-FPM. */
function defaultPhpFpm(): string
{
    $candidates = [
        '/opt/homebrew/sbin/php-fpm',
        '/usr/local/sbin/php-fpm',
        '/usr/sbin/php-fpm' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
        '/usr/sbin/php-fpm',
    ];
    foreach ($candidates as $candidate) {
        if (is_file($candidate) && is_executable($candidate)) {
            return $candidate;
        }
    }

    return '';
}

/**
 * HR: Prihvaća samo već pripremljenu privatnu SimpleSAMLphp konfiguraciju
 *     unutar trajnog data direktorija. Alat nikada ne kopira zajedničke tajne.
 * EN: Accepts only a pre-created private SimpleSAMLphp configuration inside
 *     the persistent data directory. The tool never copies shared secrets.
 */
function simpleSamlConfigDirectory(mixed $value, string $root): ?string
{
    if ($value === null || trim((string)$value) === '') {
        return null;
    }

    $requested = trim((string)$value);
    $directory = realpath($requested);
    $dataRoot = realpath($root . '/data');
    if (
        !is_string($directory)
        || !is_string($dataRoot)
        || !str_starts_with($directory . DIRECTORY_SEPARATOR, $dataRoot . DIRECTORY_SEPARATOR)
        || !is_file($directory . '/config.php')
        || !is_file($directory . '/authsources.php')
    ) {
        fail('--simplesaml-config-dir must be an existing private config directory inside app-root/data.');
    }

    return $directory;
}

/** HR: Vraća Homebrew-specifičan ili Linux deploy home. EN: Returns the Homebrew-specific or Linux deploy home. */
function deployHome(string $platform, string $phpFpm): string
{
    if ($platform !== 'darwin') {
        return '/var/lib/simbioza-deploy';
    }

    return darwinPrefix($phpFpm) . '/var/simbioza-deploy';
}

/**
 * HR: Razlikuje Apple Silicon i Intel Homebrew prefiks prema stvarnom FPM-u.
 * EN: Distinguishes Apple Silicon and Intel Homebrew prefixes from the actual FPM binary.
 */
function darwinPrefix(string $phpFpm): string
{
    return str_starts_with($phpFpm, '/usr/local/') ? '/usr/local' : '/opt/homebrew';
}

/**
 * HR: Pokreće naredbu bez ljuske i po želji dopušta neuspjeh provjere.
 * EN: Runs a command without a shell and optionally tolerates probe failure.
 *
 * @param list<string> $command
 * @return array{code:int,stdout:string,stderr:string}
 */
function runCommand(array $command, bool $allowFailure = false): array
{
    $pipes = [];
    $process = proc_open($command, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start: ' . implode(' ', $command));
    }
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($process);
    $result = [
        'code' => $code,
        'stdout' => is_string($stdout) ? $stdout : '',
        'stderr' => is_string($stderr) ? $stderr : '',
    ];
    if (!$allowFailure && $code !== 0) {
        throw new RuntimeException(trim($result['stderr']) !== '' ? trim($result['stderr']) : trim($result['stdout']));
    }

    return $result;
}

/** HR: Stvara potrebne korisnike, odvojene grupe i članstva. EN: Creates required users, separated groups, and memberships. */
function installIdentities(string $platform, string $maintainer, string $deployHome): void
{
    foreach (['app-simbioza', 'deploy-simbioza', 'run-simbioza'] as $group) {
        ensureGroup($platform, $group);
    }
    ensureUser($platform, 'fpm-simbioza', 'app-simbioza', '/var/empty');
    ensureUser(
        $platform,
        'simbioza-deploy',
        'deploy-simbioza',
        $deployHome,
    );
    foreach (['fpm-simbioza', 'simbioza-deploy', $maintainer] as $user) {
        ensureMembership($platform, $user, 'run-simbioza');
    }
    ensureMembership($platform, 'simbioza-deploy', 'deploy-simbioza');
    ensureMembership($platform, $maintainer, 'deploy-simbioza');
}

/** HR: Idempotentno stvara sistemsku grupu. EN: Idempotently creates a system group. */
function ensureGroup(string $platform, string $group): void
{
    assertIdentity($group, 'group');
    if ($platform === 'linux') {
        if (runCommand(['getent', 'group', $group], true)['code'] !== 0) {
            runCommand(['groupadd', '--system', $group]);
        }
        return;
    }
    if (runCommand(['dscl', '.', '-read', '/Groups/' . $group], true)['code'] === 0) {
        return;
    }
    $gid = nextDarwinId('/Groups', 'PrimaryGroupID');
    runCommand(['dscl', '.', '-create', '/Groups/' . $group]);
    runCommand(['dscl', '.', '-create', '/Groups/' . $group, 'PrimaryGroupID', (string)$gid]);
    runCommand(['dscl', '.', '-create', '/Groups/' . $group, 'RealName', 'Simbioza ' . $group]);
}

/** HR: Idempotentno stvara zaključani sistemski račun. EN: Idempotently creates a locked system account. */
function ensureUser(string $platform, string $user, string $primaryGroup, string $home): void
{
    assertIdentity($user, 'user');
    if ($platform === 'linux') {
        if (runCommand(['id', $user], true)['code'] !== 0) {
            runCommand([
                'useradd', '--system', '--gid', $primaryGroup, '--home-dir', $home,
                '--create-home', '--shell', '/usr/sbin/nologin', $user,
            ]);
        } else {
            // HR: Ponovljeno pokretanje migrira raniji raspored grupa na
            //     odvojene deploy/read uloge bez stvaranja novog računa.
            // EN: A repeated run migrates the earlier group layout to separate
            //     deploy/read roles without creating another account.
            runCommand(['usermod', '--gid', $primaryGroup, $user]);
        }
    } else {
        $exists = runCommand(['dscl', '.', '-read', '/Users/' . $user], true)['code'] === 0;
        $uid = $exists ? null : nextDarwinId('/Users', 'UniqueID');
        $group = runCommand(['dscl', '.', '-read', '/Groups/' . $primaryGroup, 'PrimaryGroupID']);
        if (preg_match('/PrimaryGroupID:\s*(\d+)/', $group['stdout'], $matches) !== 1) {
            throw new RuntimeException('Unable to resolve primary group: ' . $primaryGroup);
        }
        if (!$exists) {
            runCommand(['dscl', '.', '-create', '/Users/' . $user]);
            runCommand(['dscl', '.', '-create', '/Users/' . $user, 'UniqueID', (string)$uid]);
        }
        runCommand(['dscl', '.', '-create', '/Users/' . $user, 'PrimaryGroupID', $matches[1]]);
        runCommand(['dscl', '.', '-create', '/Users/' . $user, 'UserShell', '/usr/bin/false']);
        runCommand(['dscl', '.', '-create', '/Users/' . $user, 'NFSHomeDirectory', $home]);
        runCommand(['dscl', '.', '-create', '/Users/' . $user, 'IsHidden', '1']);
        runCommand(['dscl', '.', '-create', '/Users/' . $user, 'Password', '*']);
    }
    if (!is_dir($home) && $home !== '/var/empty') {
        mkdir($home, 0750, true);
    }
    if ($home !== '/var/empty') {
        applyMetadata($home, $user, $primaryGroup, 0750);
    }
}

/** HR: Nalazi slobodan skriveni macOS ID. EN: Finds an unused hidden macOS ID. */
function nextDarwinId(string $recordType, string $attribute): int
{
    $result = runCommand(['dscl', '.', '-list', $recordType, $attribute]);
    $used = [];
    foreach (preg_split('/\R/', $result['stdout']) ?: [] as $line) {
        if (preg_match('/\s(\d+)\s*$/', $line, $matches) === 1) {
            $used[(int)$matches[1]] = true;
        }
    }
    for ($id = 450; $id < 500; ++$id) {
        if (!isset($used[$id])) {
            return $id;
        }
    }
    throw new RuntimeException('No free macOS system identity ID is available in 450-499.');
}

/** HR: Dodaje račun u grupu bez uklanjanja postojećih članstava. EN: Adds an account to a group without removing existing memberships. */
function ensureMembership(string $platform, string $user, string $group): void
{
    if ($platform === 'linux') {
        runCommand(['usermod', '--append', '--groups', $group, $user]);
        return;
    }
    runCommand(['dseditgroup', '-o', 'edit', '-a', $user, '-t', 'user', $group]);
}

/**
 * HR: Postavlja vlasništvo koda i odvojena runtime prava. Prije web-installera
 *     zapisivi su samo ciljani instalacijski direktoriji; finalize ih sužava.
 * EN: Applies code ownership and separated runtime permissions. Before the web
 *     installer only targeted install directories are writable; finalize narrows them.
 */
function installFilesystem(
    string $platform,
    string $root,
    bool $finalize,
    ?string $simpleSamlConfig,
): void {
    // HR: Samo deploy grupa piše kod. FPM ga čita kroz read-only systemd bind
    //     i world-read bit, ali ga ne može mijenjati.
    // EN: Only the deploy group writes code. FPM reads it through the read-only
    //     systemd bind and world-read bit, but cannot modify it.
    applyTree($root, 'simbioza-deploy', 'deploy-simbioza', 02775, 0664, ['data']);
    foreach (['data', 'data/cache', 'data/logs', 'data/tmp', 'data/sessions', 'data/setup-requests'] as $relative) {
        $path = $root . '/' . $relative;
        if (!is_dir($path) && !mkdir($path, 02770, true) && !is_dir($path)) {
            throw new RuntimeException('Unable to create runtime directory: ' . $path);
        }
    }
    applyTree($root . '/data', 'fpm-simbioza', 'run-simbioza', 02770, 0660);
    if ($simpleSamlConfig !== null) {
        applyTree($simpleSamlConfig, 'simbioza-deploy', 'deploy-simbioza', 02750, 0640);
        grantReadOnlyGroupAccess($platform, $simpleSamlConfig, 'app-simbioza');
    }

    $runtimeFiles = [
        'config/database.php', 'config/env.php', 'config/installation.php', 'config/modules.php',
        'config/editor-html.php', 'config/email.php', 'config/workspace.php',
    ];
    foreach ($runtimeFiles as $relative) {
        $path = $root . '/' . $relative;
        if (is_file($path)) {
            applyMetadata($path, 'fpm-simbioza', 'run-simbioza', 0660);
        }
    }

    $mutableDirectories = [
        'resources/config/menu', 'resources/config/theme',
    ];
    foreach ($mutableDirectories as $relative) {
        $path = $root . '/' . $relative;
        if (is_dir($path)) {
            applyTree($path, 'fpm-simbioza', 'run-simbioza', 02770, 0660);
        }
    }

    // HR: Sticky direktorij dopušta FPM-u atomske zamjene samo vlastitih
    //     runtime datoteka. Ne može zamijeniti release konfiguraciju čiji je
    //     vlasnik deploy korisnik, iako obje vrste datoteka dijele direktorij.
    // EN: The sticky directory lets FPM atomically replace only its own
    //     runtime files. It cannot replace deploy-owned release configuration
    //     even though both kinds of files share the directory.
    $configDirectory = $root . '/config';
    applyMetadata($configDirectory, 'simbioza-deploy', 'run-simbioza', 03770);
    foreach ($runtimeFiles as $relative) {
        $path = $root . '/' . $relative;
        if (is_file($path)) {
            applyMetadata($path, 'fpm-simbioza', 'run-simbioza', 0660);
        }
    }
}

/**
 * HR: Privatnom konfiguracijskom stablu daje samo čitanje za FPM grupu.
 *     Linux koristi imenovani POSIX ACL kako deploy grupa zadržava pisanje.
 * EN: Grants the FPM group read-only access to a private configuration tree.
 *     Linux uses a named POSIX ACL so the deploy group retains write access.
 */
function grantReadOnlyGroupAccess(string $platform, string $root, string $group): void
{
    if ($platform !== 'linux') {
        // HR: Na razvojnom macOS-u deploy račun ostaje vlasnik, a FPM grupa
        //     čita privatni config. Kod aplikacije i dalje nije zapisiv FPM-u.
        // EN: On development macOS the deploy account remains the owner and
        //     the FPM group reads private config. FPM still cannot write code.
        applyTree($root, 'simbioza-deploy', $group, 02750, 0640);
        return;
    }

    $setfacl = '/usr/bin/setfacl';
    if (!is_file($setfacl) || !is_executable($setfacl)) {
        throw new RuntimeException('Install the acl package before configuring private SimpleSAMLphp access.');
    }
    runCommand([$setfacl, '-R', '-P', '-m', 'g:' . $group . ':r-X', $root]);
    runCommand([$setfacl, '-m', 'd:g:' . $group . ':r-X', $root]);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
    );
    foreach ($iterator as $entry) {
        if ($entry->isDir() && !$entry->isLink()) {
            runCommand([$setfacl, '-m', 'd:g:' . $group . ':r-X', $entry->getPathname()]);
        }
    }
}

/**
 * HR: Rekurzivno primjenjuje vlasništvo i prava, bez praćenja poveznica.
 * EN: Recursively applies ownership and modes without following symlinks.
 *
 * @param list<string> $skipTopLevel
 */
function applyTree(
    string $root,
    string $owner,
    string $group,
    int $directoryMode,
    int $fileMode,
    array $skipTopLevel = [],
): void {
    if (!is_dir($root)) {
        return;
    }
    applyMetadata($root, $owner, $group, $directoryMode);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
    );
    foreach ($iterator as $entry) {
        $relative = substr($entry->getPathname(), strlen(rtrim($root, '/')) + 1);
        $top = explode(DIRECTORY_SEPARATOR, $relative)[0] ?? '';
        if (in_array($top, $skipTopLevel, true) || $entry->isLink()) {
            continue;
        }
        if ($entry->isDir()) {
            applyMetadata($entry->getPathname(), $owner, $group, $directoryMode);
        } else {
            $mode = (($entry->getPerms() & 0111) !== 0) ? ($fileMode | 0111) : $fileMode;
            applyMetadata($entry->getPathname(), $owner, $group, $mode);
        }
    }
}

/**
 * HR: Primjenjuje i ponovno provjerava vlasnika, grupu i prava; datotečni
 *     sustav s isključenim vlasništvom zato ne može dati lažni uspjeh.
 * EN: Applies and rechecks owner, group, and mode so a filesystem with disabled
 *     ownership cannot report a false success.
 */
function applyMetadata(string $path, string $owner, string $group, int $mode): void
{
    if (!chown($path, $owner) || !chgrp($path, $group) || !chmod($path, $mode)) {
        throw new RuntimeException('Unable to set ownership or permissions: ' . $path);
    }
    clearstatcache(true, $path);
    $ownerEntry = posix_getpwnam($owner);
    $groupEntry = posix_getgrnam($group);
    $actualOwner = fileowner($path);
    $actualGroup = filegroup($path);
    $actualMode = fileperms($path);
    if (
        !is_array($ownerEntry)
        || !is_array($groupEntry)
        || !is_int($actualOwner)
        || !is_int($actualGroup)
        || !is_int($actualMode)
        || $actualOwner !== $ownerEntry['uid']
        || $actualGroup !== $groupEntry['gid']
        || ($actualMode & 07777) !== $mode
    ) {
        throw new RuntimeException('Ownership or permissions did not take effect: ' . $path);
    }
}

/** HR: Instalira fiksni helper i minimalno sudoers pravilo za FPM račun. EN: Installs a fixed helper and minimal sudoers rule for the FPM account. */
function installHelper(string $root, string $phpFpm, string $home): void
{
    $php = dirname($phpFpm) . '/php';
    if (!is_file($php)) {
        $php = PHP_BINARY;
    }
    $worker = $root . '/scripts/setup_worker.php';
    if (PHP_OS_FAMILY === 'Linux') {
        if (!is_file('/usr/bin/systemd-run') || !is_executable('/usr/bin/systemd-run')) {
            throw new RuntimeException('systemd-run is required for the isolated Setup worker.');
        }
        $helper = <<<'SH'
#!/bin/sh
set -eu
[ "$#" -eq 1 ] || exit 64
case "$1" in
  *[!0-9a-f]*|'') exit 64 ;;
esac
[ "${#1}" -eq 48 ] || exit 64
exec /usr/bin/systemd-run --quiet --wait --pipe --collect \
  --uid=simbioza-deploy --gid=deploy-simbioza \
  --property=SupplementaryGroups=run-simbioza \
  --property=ProtectSystem=full --property=ProtectHome=true \
  --property=PrivateTmp=true --property=PrivateDevices=true \
  --property=NoNewPrivileges=true --property=UMask=0007 \
  --working-directory=__ROOT__ \
  --setenv=HOME=__HOME__ --setenv=COMPOSER_HOME=__HOME__/.composer \
  --setenv=PATH=/usr/local/bin:/usr/bin:/bin \
  __PHP__ __WORKER__ "$1"
SH;
    } else {
        $helper = <<<'SH'
#!/bin/sh
set -eu
[ "$#" -eq 1 ] || exit 64
case "$1" in
  *[!0-9a-f]*|'') exit 64 ;;
esac
[ "${#1}" -eq 48 ] || exit 64
export HOME=__HOME__
export COMPOSER_HOME="$HOME/.composer"
export PATH=/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin
exec /usr/bin/sudo -n -u simbioza-deploy -- __PHP__ __WORKER__ "$1"
SH;
    }
    $helper = str_replace(
        ['__ROOT__', '__HOME__', '__PHP__', '__WORKER__'],
        [escapeshellarg($root), escapeshellarg($home), escapeshellarg($php), escapeshellarg($worker)],
        $helper,
    );
    writeSystemFile('/usr/local/sbin/simbioza-setup', $helper . "\n", 0755, 'root', PHP_OS_FAMILY === 'Darwin' ? 'wheel' : 'root');
    $sudoers = 'fpm-simbioza ALL=(root) NOPASSWD: /usr/local/sbin/simbioza-setup *' . "\n";
    writeSystemFile('/etc/sudoers.d/simbioza-setup', $sudoers, 0440, 'root', PHP_OS_FAMILY === 'Darwin' ? 'wheel' : 'root');
    runCommand(['visudo', '-cf', '/etc/sudoers.d/simbioza-setup']);
}

/** HR: Instalira platformsku konfiguraciju namjenskog FPM poola. EN: Installs the platform-specific dedicated FPM pool configuration. */
function installFpm(
    string $platform,
    string $root,
    string $phpFpm,
    string $listen,
    ?string $simpleSamlConfig,
): void {
    $pool = fpmPool($root, $listen, $platform === 'linux', $simpleSamlConfig);
    if ($platform === 'linux') {
        $version = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $baseDirectory = '/etc/php/' . $version . '/fpm-simbioza';
        if (!is_dir('/etc/php/' . $version . '/fpm')) {
            throw new RuntimeException('Install the php' . $version . '-fpm package before running this tool.');
        }
        $configuration = "[global]\n"
            . "pid = /run/php-fpm-simbioza/php-fpm.pid\n"
            . 'error_log = ' . $root . "/data/logs/php-fpm.log\n"
            . "daemonize = no\n\n"
            . $pool;
        $configurationPath = $baseDirectory . '/php-fpm.conf';
        writeSystemFile($configurationPath, $configuration, 0644, 'root', 'root');
        runCommand([$phpFpm, '--test', '--fpm-config', $configurationPath]);

        $readWritePaths = [
            $root . '/data',
            $root . '/config',
            $root . '/resources/config/menu',
            $root . '/resources/config/theme',
            '/run/php-fpm-simbioza',
        ];
        $unit = "[Unit]\nDescription=Isolated PHP-FPM for Simbioza\nAfter=network.target\n\n"
            . "[Service]\nType=notify\n"
            . 'ExecStart=' . $phpFpm . ' --nodaemonize --fpm-config ' . $configurationPath . "\n"
            . "ExecReload=/bin/kill -USR2 \$MAINPID\nRestart=on-failure\n"
            . "RuntimeDirectory=php-fpm-simbioza\nRuntimeDirectoryMode=0755\nUMask=0007\n"
            . "ProtectSystem=strict\nProtectHome=true\nPrivateTmp=true\nPrivateDevices=true\n"
            // HR: FPM smije preko jednog root-owned helpera zatražiti od
            //     systemd-a zaseban worker. Worker ponovno uključuje
            //     NoNewPrivileges i radi kao neprivilegirani deploy račun.
            // EN: FPM may request a separate worker from systemd through one
            //     root-owned helper. The worker re-enables NoNewPrivileges
            //     and runs as the unprivileged deploy account.
            . "ProtectProc=invisible\n"
            . "TemporaryFileSystem=/data:ro /var/www:ro\n"
            . 'BindReadOnlyPaths=' . systemdQuote($root) . "\n"
            . 'InaccessiblePaths=/usr/share/simplesamlphp-aai/config '
            . "/usr/share/simplesamlphp-aai/cert /usr/share/simplesamlphp-aai/cache /var/lib/php /run/php\n"
            . 'ReadWritePaths=' . implode(' ', array_map('systemdQuote', $readWritePaths)) . "\n"
            . "MemoryHigh=768M\nMemoryMax=1G\nTasksMax=64\n\n"
            . "[Install]\nWantedBy=multi-user.target\n";
        writeSystemFile(
            '/etc/systemd/system/php-fpm-simbioza.service',
            $unit,
            0644,
            'root',
            'root',
        );
        runCommand(['systemctl', 'daemon-reload']);
        runCommand(['systemctl', 'enable', '--now', 'php-fpm-simbioza.service']);
        runCommand(['systemctl', 'restart', 'php-fpm-simbioza.service']);
        return;
    }

    $prefix = darwinPrefix($phpFpm);
    $configurationDirectory = $prefix . '/etc/simbioza';
    $runDirectory = $prefix . '/var/run/simbioza';
    foreach ([$configurationDirectory, $runDirectory] as $directory) {
        if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create ' . $directory);
        }
        applyMetadata($directory, 'fpm-simbioza', 'run-simbioza', 0750);
    }
    writeSystemFile($configurationDirectory . '/pool.conf', $pool, 0644, 'root', 'wheel');
    $global = "[global]\n"
        . 'pid = ' . $runDirectory . "/php-fpm.pid\n"
        . 'error_log = ' . $root . "/data/logs/php-fpm.log\n"
        . "daemonize = no\n"
        . 'include = ' . $configurationDirectory . "/pool.conf\n";
    writeSystemFile($configurationDirectory . '/php-fpm.conf', $global, 0644, 'root', 'wheel');
    // HR: Alat se u instalacijskom koraku izvršava kao root, dok launchd
    //     stvarni proces pokreće kao fpm-simbioza. -R vrijedi samo za ovu
    //     sintaktičku provjeru i ne ulazi u servisnu konfiguraciju.
    // EN: The installer runs as root while launchd starts the real process as
    //     fpm-simbioza. -R applies only to this syntax check and is not stored
    //     in the service configuration.
    runCommand([$phpFpm, '--test', '--allow-to-run-as-root', '--fpm-config', $configurationDirectory . '/php-fpm.conf']);

    $plist = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" '
        . '"http://www.apple.com/DTDs/PropertyList-1.0.dtd">' . "\n"
        . '<plist version="1.0"><dict>'
        . '<key>Label</key><string>hr.simbioza.php-fpm</string>'
        . '<key>UserName</key><string>fpm-simbioza</string>'
        . '<key>ProgramArguments</key><array><string>' . htmlspecialchars($phpFpm, ENT_XML1) . '</string>'
        . '<string>--nodaemonize</string><string>--fpm-config</string>'
        . '<string>' . htmlspecialchars($configurationDirectory . '/php-fpm.conf', ENT_XML1) . '</string></array>'
        . '<key>RunAtLoad</key><true/><key>KeepAlive</key><true/>'
        . '<key>StandardOutPath</key><string>' . htmlspecialchars($root . '/data/logs/php-fpm-launchd.log', ENT_XML1) . '</string>'
        . '<key>StandardErrorPath</key><string>' . htmlspecialchars($root . '/data/logs/php-fpm-launchd.log', ENT_XML1) . '</string>'
        . '</dict></plist>' . "\n";
    $plistPath = '/Library/LaunchDaemons/hr.simbioza.php-fpm.plist';
    writeSystemFile($plistPath, $plist, 0644, 'root', 'wheel');
    runCommand(['launchctl', 'bootout', 'system/' . 'hr.simbioza.php-fpm'], true);
    // HR: launchd na macOS-u može kratko zadržati upravo uklonjenu oznaku.
    //     Ponovni install zato kontrolirano pričeka samo taj prijelaz.
    // EN: launchd on macOS can briefly retain the just-removed label. A
    //     repeated install therefore waits only for that controlled transition.
    $bootstrapped = false;
    $failure = null;
    for ($attempt = 0; $attempt < 10; $attempt++) {
        try {
            runCommand(['launchctl', 'bootstrap', 'system', $plistPath]);
            $bootstrapped = true;
            break;
        } catch (RuntimeException $exception) {
            $failure = $exception;
            usleep(200_000);
        }
    }
    if (!$bootstrapped) {
        throw $failure ?? new RuntimeException('Unable to start the Simbioza PHP-FPM launchd service.');
    }
}

/** HR: Sigurno navodi apsolutnu putanju u systemd direktivi. EN: Safely quotes an absolute path in a systemd directive. */
function systemdQuote(string $path): string
{
    if ($path === '' || $path[0] !== '/' || str_contains($path, "\n") || str_contains($path, "\r")) {
        throw new RuntimeException('Invalid systemd path: ' . $path);
    }

    return '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $path) . '"';
}

/** HR: Gradi zaključani pool s jasnom aplikacijskom oznakom. EN: Builds a locked-down pool with an explicit application marker. */
function fpmPool(string $root, string $listen, bool $includeIdentity, ?string $simpleSamlConfig): string
{
    $identity = $includeIdentity ? "user = fpm-simbioza\ngroup = app-simbioza\n" : '';
    $simpleSamlEnvironment = $simpleSamlConfig === null
        ? ''
        : 'env[SIMPLESAMLPHP_CONFIG_DIR] = ' . $simpleSamlConfig . "\n";
    return "[simbioza]\n"
        . $identity
        . 'listen = ' . $listen . "\n"
        . "listen.allowed_clients = 127.0.0.1\n"
        . "pm = ondemand\npm.max_children = 8\npm.process_idle_timeout = 10s\npm.max_requests = 500\n"
        // HR: Instalacija i Composer radnje mogu trajati dulje od zadanih 30
        //     sekundi. Ograničeni Setup worker i dalje prihvaća samo unaprijed
        //     dopuštene radnje, pa ih namjenski pool ne prekida usred promjene.
        // EN: Installation and Composer operations can exceed the default 30
        //     seconds. The restricted Setup worker still accepts only
        //     allowlisted actions, so the dedicated pool does not terminate
        //     those operations mid-change.
        . "request_terminate_timeout = 0\n"
        . "clear_env = yes\ncatch_workers_output = yes\ndecorate_workers_output = no\n"
        . "security.limit_extensions = .php\n"
        . "env[SIMBIOZA_SETUP_POOL] = 1\n"
        . 'env[HPH_APP_PATH] = ' . $root . "\n"
        . $simpleSamlEnvironment
        . 'env[TMPDIR] = ' . $root . "/data/tmp\n"
        . "env[PATH] = /opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin\n"
        . 'php_admin_value[session.save_path] = ' . $root . "/data/sessions\n"
        . 'php_admin_value[upload_tmp_dir] = ' . $root . "/data/tmp\n"
        . 'php_admin_value[sys_temp_dir] = ' . $root . "/data/tmp\n"
        . "php_admin_value[max_execution_time] = 0\n"
        // HR: Dinamičke config datoteke mijenjaju se atomskim renameom kroz
        //     Setup GUI. Namjenski pool mora ih provjeriti u svakom zahtjevu.
        // EN: Setup GUI changes dynamic config files through atomic renames.
        //     The dedicated pool must revalidate them on every request.
        . "php_admin_flag[opcache.validate_timestamps] = On\n"
        . "php_admin_value[opcache.revalidate_freq] = 0\n"
        . "php_admin_value[expose_php] = Off\n";
}

/** HR: Atomski zapisuje root-owned sistemsku datoteku. EN: Atomically writes a root-owned system file. */
function writeSystemFile(string $path, string $contents, int $mode, string $owner, string $group): void
{
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create ' . $directory);
    }
    $temporary = tempnam($directory, '.simbioza-');
    if (!is_string($temporary)) {
        throw new RuntimeException('Unable to prepare ' . $path);
    }
    try {
        if (file_put_contents($temporary, $contents, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write ' . $path);
        }
        applyMetadata($temporary, $owner, $group, $mode);
        if (!rename($temporary, $path)) {
            throw new RuntimeException('Unable to activate ' . $path);
        }
    } finally {
        if (is_file($temporary)) {
            unlink($temporary);
        }
    }
}

/** HR: Ispisuje provjere potrebne prije i poslije instalacije. EN: Prints checks required before and after installation. */
function printChecks(
    string $root,
    string $maintainer,
    string $phpFpm,
    string $listen,
    string $platform,
    ?string $simpleSamlConfig,
): void {
    $checks = [
        ['FPM user', identityExists($platform, 'fpm-simbioza', false)],
        ['Deploy user', identityExists($platform, 'simbioza-deploy', false)],
        ['Maintainer deploy group', membershipExists($platform, $maintainer, 'deploy-simbioza')],
        ['Maintainer runtime group', membershipExists($platform, $maintainer, 'run-simbioza')],
        ['Setup helper', is_file('/usr/local/sbin/simbioza-setup') && is_executable('/usr/local/sbin/simbioza-setup')],
        ['Sudoers rule', is_file('/etc/sudoers.d/simbioza-setup')],
        ['Application readable', is_readable($root . '/public/index.php')],
        ['Runtime data writable', is_writable($root . '/data')],
        ['Runtime config is sticky', is_dir($root . '/config') && (fileperms($root . '/config') & 01000) !== 0],
    ];
    if ($simpleSamlConfig !== null) {
        $checks[] = [
            'Private SimpleSAMLphp config',
            is_readable($simpleSamlConfig . '/config.php')
                && is_readable($simpleSamlConfig . '/authsources.php'),
        ];
    }
    fwrite(STDOUT, "\nSimbioza FPM check ({$listen}, {$phpFpm})\n");
    foreach ($checks as [$label, $passed]) {
        fwrite(STDOUT, sprintf("  [%s] %s\n", $passed ? 'OK' : '--', $label));
    }
    fwrite(STDOUT, "  Platform: {$platform}\n");
}

/** HR: Provjerava postoji li korisnik ili grupa. EN: Checks whether a user or group exists. */
function identityExists(string $platform, string $identity, bool $group): bool
{
    $command = $platform === 'linux'
        ? ['getent', $group ? 'group' : 'passwd', $identity]
        : ['dscl', '.', '-read', ($group ? '/Groups/' : '/Users/') . $identity];
    return runCommand($command, true)['code'] === 0;
}

/** HR: Provjerava trajno članstvo, ne samo trenutni shell. EN: Checks persistent membership, not only the current shell. */
function membershipExists(string $platform, string $user, string $group): bool
{
    if (!identityExists($platform, $group, true)) {
        return false;
    }
    $result = $platform === 'linux'
        ? runCommand(['id', '-nG', $user], true)
        : runCommand(['dseditgroup', '-o', 'checkmember', '-m', $user, $group], true);
    if ($result['code'] !== 0) {
        return false;
    }

    return $platform === 'linux'
        ? in_array($group, preg_split('/\s+/', trim($result['stdout'])) ?: [], true)
        : str_contains(strtolower($result['stdout']), 'yes');
}
