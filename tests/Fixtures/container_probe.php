<?php

declare(strict_types=1);

// EN: Run only against the disposable A4 container; all identities and content are fictional.
// The probe reads that container's credential file but never prints passwords or tokens.
//
// HR: Pokretati samo na privremenom A4 containeru; identiteti i sadržaj su izmišljeni.
// Provjera čita vjerodajnice tog containera, ali ne ispisuje lozinke niti tokene.
$root = getenv('HPH_APP_PATH') ?: '/opt/simbioza';
require $root . '/vendor/autoload.php';
$paths = new \App\Installation\InstallationPaths($root);
$mode = $argv[1] ?? 'state';
if ($mode === 'saml') {
    foreach (
        ['/runtime/saml/config', '/runtime/saml/metadata', '/runtime/saml/cert',
        '/runtime/data/saml', '/runtime/data/saml/cache', '/runtime/data/saml/sessions'] as $directory
    ) {
        if (!is_dir($directory)) {
            mkdir($directory, 0770);
        }
    }

    $saml = [
        'baseurlpath' => 'http://127.0.0.1:18084/saml/', 'secretsalt' => str_repeat('a4-fictional-', 4),
        'auth.adminpassword' => 'fictional-only-not-an-admin-deployment', 'timezone' => 'UTC',
        'cachedir' => '/runtime/data/saml/cache', 'datadir' => '/runtime/data/saml',
        'certdir' => '/runtime/saml/cert', 'logging.handler' => 'errorlog', 'logging.level' => 3,
        'store.type' => 'phpsession', 'session.phpsession.savepath' => '/runtime/data/saml/sessions',
        'session.cookie.secure' => false, 'session.cookie.samesite' => 'Lax',
        'module.enable' => ['core' => true, 'saml' => true, 'admin' => false],
        'metadata.sources' => [['type' => 'flatfile', 'directory' => '/runtime/saml/metadata']],
    ];
    file_put_contents('/runtime/saml/config/config.php', '<?php $config = ' . var_export($saml, true) . ';');
    file_put_contents('/runtime/saml/config/authsources.php', '<?php $config = ' . var_export([
        'a4-sp' => ['saml:SP', 'entityID' => 'urn:a4:fictional:sp', 'idp' => null],
    ], true) . ';');
    $_SERVER['REQUEST_URI'] = '/saml/';
    $_SERVER['HTTP_HOST'] = '127.0.0.1:18084';
    $libraryRoot = getenv('SIMBIOZA_SAML_ROOT') ?: '/opt/simplesamlphp';
    $autoload = $libraryRoot . '/vendor/autoload.php';
    require $autoload;
    $configurationClass = new ReflectionClass('SimpleSAML\\Configuration');
    $configuration = $configurationClass->getMethod('getInstance')->invoke(null);
    $cacheDirectory = is_object($configuration)
    ? $configurationClass->getMethod('getString')->invoke($configuration, 'cachedir') : null;
    if (
        !is_object($configuration)
        || $cacheDirectory !== '/runtime/data/saml/cache'
    ) {
        throw new RuntimeException('SimpleSAMLphp did not use external private configuration.');
    }

    $authClass = new ReflectionClass('SimpleSAML\\Auth\\Simple');
    $auth = $authClass->newInstance('a4-sp');
    if ($authClass->getMethod('isAuthenticated')->invoke($auth)) {
        throw new RuntimeException('The fictional SP must have no real authentication session.');
    }

    echo "SimpleSAMLphp code loaded with external config and runtime-only session/cache.\n";
    exit;
}

if ($mode === 'install') {
    $writer = new \App\Installation\InstallationConfigWriter($paths);
    $tester = new \App\Installation\InstallationDatabaseTester($writer);
    $validator = new \App\Installation\InstallationInputValidator();
    $requirements = new \App\Installation\InstallationRequirements($paths);
    $runner = new \App\Installation\InstallationRunner(
        $paths,
        new \App\Installation\InstallationAccessToken($paths),
        $writer,
        $tester,
        $validator,
        $requirements,
        new \App\Installation\InstallationLogger($paths),
    );
    $connection = (require $paths->databaseConfig())['connections']['default'];
    $result = $runner->run($connection, [
        'name' => 'A4 fictional site', 'primary_locale' => 'en', 'supported_locales' => ['en', 'hr'],
        'timezone' => 'Europe/Zagreb',
    ], [
        'login' => 'a4-admin', 'display_name' => 'A4 test administrator', 'first_name' => 'A4',
        'last_name' => 'Test', 'email' => 'a4@example.invalid', 'password' => 'Z3!FictionalOnly2026',
    ]);
    echo json_encode($result, JSON_THROW_ON_ERROR) . "\n";
    exit;
}

$app = new \HeartPhrame\App(\HeartPhrame\Config\ConfigPathResolver::fromEnvironment($root), $root);
$app->loadCommands();
$database = $app->getContainer()->get(\AaiEduHr\HeartPhrameModuleOrm\Database\Database::class);
if ($mode === 'languages') {
    $languages = new \App\Localization\LanguagePackManager($root, new \App\Module\ModuleCatalog());
    $template = $languages->createTemplate('de', 'en', '/tmp/a4-language.json');
    $pack = json_decode((string)file_get_contents($template), true, 512, JSON_THROW_ON_ERROR);
    $pack['names'] = ['en' => 'Fictional German', 'hr' => 'Izmišljeni njemački', 'de' => 'A4 Deutsch'];
    $pack['native_name'] = 'A4 Deutsch';
    file_put_contents($template, json_encode($pack, JSON_THROW_ON_ERROR));
    $languages->install($template, replace: true);
    $catalog = '/runtime/data/languages/catalogs/de.json';
    $expected = hash_file('sha256', $catalog);
    $manager = $app->getContainer()->get(\AaiEduHr\HeartPhrameModuleBackup\Service\BackupManager::class);
    $scope = new \AaiEduHr\HeartPhrameModuleBackup\Value\BackupScope('component');
    $archive = $manager->create(new \AaiEduHr\HeartPhrameModuleBackup\Value\BackupExportContext(
        $scope,
        ['application-config'],
        options: ['site_base_url' => 'http://127.0.0.1:18084'],
        actorUserId: 1,
        passphrase: 'A4FictionalBackupOnly!',
    ), 'A4 fictional language roundtrip');
    $manager->restore($archive, new \AaiEduHr\HeartPhrameModuleBackup\Value\BackupImportContext(
        $scope,
        'merge',
        ['application-config'],
        options: ['site_base_url' => 'http://127.0.0.1:18084'],
        actorUserId: 1,
        passphrase: 'A4FictionalBackupOnly!',
    ));
    if (hash_file('sha256', $catalog) !== $expected || is_writable($root . '/lang')) {
        throw new RuntimeException('Runtime language roundtrip changed bytes or writable-code isolation.');
    }

    echo "Language installation and application-config backup/restore passed with read-only packaged lang.\n";
    exit;
}

if ($mode === 'backup') {
    $manager = $app->getContainer()->get(\AaiEduHr\HeartPhrameModuleBackup\Service\BackupManager::class);
    $scope = new \AaiEduHr\HeartPhrameModuleBackup\Value\BackupScope('workspace', 'korisnicke-upute');
    $archive = $manager->create(new \AaiEduHr\HeartPhrameModuleBackup\Value\BackupExportContext(
        $scope,
        [],
        options: ['site_base_url' => 'http://127.0.0.1:18084'],
        actorUserId: 1,
        passphrase: 'A4FictionalBackupOnly!',
    ), 'A4 fictional guide roundtrip');
    $manager->restore($archive, new \AaiEduHr\HeartPhrameModuleBackup\Value\BackupImportContext(
        $scope,
        'merge',
        [],
        options: ['site_base_url' => 'http://127.0.0.1:18084'],
        actorUserId: 1,
        passphrase: 'A4FictionalBackupOnly!',
    ));
    echo "Workspace archive export/import completed with immutable code and PostgreSQL.\n";
    exit;
}

if ($mode === 'worker') {
    $emailDefaultsPath = $root . '/vendor/aaieduhr/heartphrame-module-email/config/email.php';
    $email = new \AaiEduHr\HeartPhrameModuleEmail\Service\EmailConfigurationStore($root, require $emailDefaultsPath);
    $email->mutate(static fn(array $settings): array => array_replace_recursive($settings, [
        'enabled' => true, 'smtp' => ['host' => 'smtp.example.invalid', 'port' => 25, 'encryption' => 'none'],
        'sender' => ['email' => 'a4@example.invalid', 'name' => 'A4 fictional sender'],
    ]));
    echo "Fictional SMTP configuration enabled; the queue is empty and no mail will be sent.\n";
    exit;
}

$connection = (require $paths->databaseConfig())['connections']['default'];
$pdo = new PDO('pgsql:host=database;dbname=simbioza', $connection['username'], $connection['password']);
if ($mode === 'seed') {
    $pdo->exec('CREATE TABLE a4_fixture (id integer PRIMARY KEY, value text NOT NULL)');
    $pdo->exec("INSERT INTO a4_fixture VALUES (1, 'persistent synthetic content')");
    file_put_contents('/runtime/data/a4-persistent.txt', 'synthetic persisted attachment');
    file_put_contents('/runtime/config/a4.php', "<?php return ['fictional' => true];\n");
}

if ($mode === 'guards') {
    if (
        is_writable('/opt/simbioza/src') || is_writable('/opt/simbioza/vendor')
        || file_exists('/usr/local/bin/composer') || !is_writable('/runtime/config')
        || !is_writable('/runtime/data')
    ) {
        throw new RuntimeException('Immutable runtime permissions are incorrect.');
    }

    $catalog = new \App\Module\ModuleCatalog();
    $packages = new \App\Module\ComposerPackageManager(
        $catalog,
        new \App\Module\NativeProcessRunner(),
        '/opt/simbioza',
    );
    foreach ([fn() => $packages->install('audit'), fn() => $packages->uninstall('audit')] as $mutate) {
        try {
            $mutate();
            throw new LogicException('Code changes must be blocked.');
        } catch (RuntimeException $error) {
            if (!str_contains($error->getMessage(), 'Immutable deployment')) {
                throw $error;
            }
        }
    }

    echo "Immutable package, code and writable runtime checks passed.\n";
    exit;
}

$counts = [];
foreach (['auth_users', 'editor_html_documents', '_hph_migrations', 'a4_fixture'] as $table) {
    $counts[$table] = (int)$pdo->query('SELECT count(*) FROM ' . $table)->fetchColumn();
}

$roleQuery = $pdo->query("SELECT rolsuper FROM pg_roles WHERE rolname = current_user");
$counts['app_role_superuser'] = $roleQuery->fetchColumn();
$counts['private_config_hash'] = hash_file('sha256', '/runtime/config/auth/auth.json');
$counts['attachment_hash'] = hash_file('sha256', '/runtime/data/a4-persistent.txt');
echo json_encode($counts, JSON_THROW_ON_ERROR) . "\n";
