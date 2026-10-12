<?php

/**
 * EN: Developer-only SQL scaling audit of a retained, disposable matrix
 * installation. Creates only fictional records; never accepts HFC or a live site.
 * Run after verify_clean_install_matrix.php --case=all --local --keep.
 * An optional second argument selects mysql, mariadb or pgsql; these must use
 * the dedicated local disposable database codex_a2, never a real-site database.
 *
 * HR: Razvojni SQL audit skaliranja zadržane privremene matrix instalacije.
 * Stvara samo izmišljene retke; ne prihvaća HFC ni stvarni site.
 * Pokrenuti nakon verify_clean_install_matrix.php --case=all --local --keep.
 * Opcionalni drugi argument odabire mysql, mariadb ili pgsql; za njih je obavezna
 * namjenska lokalna privremena baza codex_a2, nikad baza stvarnog sitea.
 */

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleAuth\Service\AuthUserService;
use AaiEduHr\HeartPhrameModuleComment\Service\CommentService;
use AaiEduHr\HeartPhrameModuleNotification\Service\NotificationService;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\QueryExecuted;
use AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceRepository;
use AaiEduHr\SimbiozaModuleUser\Service\FollowTargetService;
use HeartPhrame\App;
use HeartPhrame\Config\ConfigPathResolver;

if (PHP_SAPI !== 'cli') {
    throw new RuntimeException('CLI-only audit.');
}
$root = realpath($argv[1] ?? '');
$parent = realpath(sys_get_temp_dir() . '/heartphrame-clean-matrix');
$driver = $argv[2] ?? 'sqlite';
if (
    !is_string($root) || !is_string($parent) || dirname($root) !== $parent
    || preg_match('/\Aall-[a-f0-9]+\z/D', basename($root)) !== 1
    || !in_array($driver, ['sqlite', 'mysql', 'mariadb', 'pgsql'], true)
) {
    throw new RuntimeException('Use an explicitly retained disposable all-module matrix, never HFC.');
}
$configuration = require $root . '/config/database.php';
$connection = $configuration['connections']['default'] ?? [];
if (($connection['driver'] ?? null) !== $driver || ($driver === 'sqlite'
    ? !is_string($connection['database'] ?? null) || realpath($connection['database']) !== realpath($root . '/data/matrix.sqlite')
    : (($connection['database'] ?? null) !== 'codex_a2' || ($connection['host'] ?? null) !== '127.0.0.1'))) {
    throw new RuntimeException('Only the disposable local matrix database is allowed.');
}
putenv('HPH_APP_PATH=' . $root);
putenv('HPH_CONFIG_PATH=' . $root . '/config');
putenv('HPH_CONFIG_OVERRIDE_PATHS');
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = '/';
require $root . '/vendor/autoload.php';
$app = new App(ConfigPathResolver::fromEnvironment($root), $root);
$app->loadCommands();
$container = $app->getContainer();
$users = $container->get(AuthUserService::class);
if ($users->countUsers() !== 0) {
    throw new RuntimeException('The scaling fixture must be new and contain no existing users.');
}
$admin = $users->createInitialAdministrator(
    'audit-admin',
    'Audit Admin',
    'Audit',
    'Admin',
    'audit-admin@example.invalid',
    'AuditFixture2026!',
);
$member = $users->createLocalUser('audit-reader', 'Audit Reader', 'audit-reader@example.invalid', 'AuditFixture2026!');
$adminId = (int)$admin['id'];
$memberId = (int)$member['id'];
$workspaces = $container->get(WorkspaceRepository::class);
$workspace = $workspaces->saveWorkspace(['name' => 'Audit scaling', 'slug' => 'audit-scaling',
    'visibility' => 'public', 'primary_language' => 'en'], $adminId);
$notifications = $container->get(NotificationService::class);
$comments = $container->get(CommentService::class);
$results = [];
$previous = 0;
foreach ([1, 100, 1000] as $size) {
    $start = $previous + 1;
    for ($index = $start; $index <= $size; ++$index) {
        $node = $workspaces->saveNode((int)$workspace['id'], ['title' => 'Audit page ' . $index,
            'slug' => 'audit-page-' . $index, 'node_type' => 'external_link',
            'target_url' => 'https://example.invalid/', 'primary_language' => 'en'], $adminId);
        $notifications->notifyUser(
            $memberId,
            'audit.scaling',
            'Audit notice',
            'Fictional audit data',
            '',
            'simbioza-user',
            (string)$node['id'],
            'audit-scaling-' . $index,
            ['target_type' => 'page', 'target_id' => (string)$node['id']],
            false,
        );
        $comments->create('audit-comments', 'en', $adminId, 'Audit Admin', 'Fictional comment', false);
    }
    foreach (['comments', 'notifications'] as $domain) {
        // EN: Each measurement boots a fresh service graph, like a new HTTP request.
        //
        // HR: Svako mjerenje pokreće novi skup servisa, kao novi HTTP zahtjev.
        $readApp = new App(ConfigPathResolver::fromEnvironment($root), $root);
        $readApp->loadCommands();
        $readContainer = $readApp->getContainer();
        $database = $readContainer->get(Database::class);
        $queries = [];
        $database->listen(static function (QueryExecuted $event) use (&$queries): void {
            $queries[] = $event->sql;
        });
        $started = hrtime(true);
        $result = $domain === 'comments'
        ? $readContainer->get(CommentService::class)->comments('audit-comments', 'en', $memberId, false, 1, 30)
        : $readContainer->get(NotificationService::class)->inbox($memberId, 1, 30);
        $elapsed = round((hrtime(true) - $started) / 1_000_000, 3);
        $database->forgetQueryListeners();
        if ($result['total'] !== $size) {
            throw new RuntimeException('The measured rows did not match the fixture size for ' . $domain);
        }
        $results[$domain][(string)$size] = ['queries' => count($queries), 'duration_ms' => $elapsed,
            'returned_rows' => count($result['items']), 'total_rows' => $result['total'],
            'auth_user_reads' => count(array_filter(
                $queries,
                static fn(string $sql): bool => preg_match('/FROM\s+["`]?auth_users["`]?/i', $sql) === 1,
            ))];
    }
    $previous = $size;
}
if ($results['notifications']['100']['queries'] > 12
    || $results['notifications']['100']['auth_user_reads'] !== 1
    || $results['notifications']['1000']['auth_user_reads'] !== 8) {
    throw new RuntimeException('Notification query/window budget regressed.');
}

// EN: Exercise recursive ancestry and direct grants on the selected SQL driver.
// No cached decision may survive a mutation on the same service graph.
//
// HR: Provjerava rekurzivne pretke i izravna prava na odabranom SQL driveru.
// Nijedna cacheirana odluka ne smije preživjeti izmjenu u istom skupu servisa.
$restricted = $workspaces->saveWorkspace(['name' => 'Audit ACL', 'slug' => 'audit-acl',
    'visibility' => 'restricted'], $adminId);
$wid = (int)$restricted['id'];
$workspaces->replaceWorkspaceAcl($wid, ['user' => [$memberId => ['can_view' => true]]]);
$parentNode = $workspaces->saveNode($wid, ['title' => 'Restricted ancestor', 'node_type' => 'separator'], $adminId);
$leaf = $workspaces->saveNode($wid, ['title' => 'Child', 'node_type' => 'document',
    'document_key' => 'audit-acl-child', 'parent_id' => $parentNode['id']], $adminId);
$workspaces->replaceNodeAcl($wid, (int)$parentNode['id'], ['user' => [$memberId => ['can_view' => false]]]);
$targets = [['type' => 'workspace', 'id' => (string)$wid], ['type' => 'page', 'id' => (string)$leaf['id']],
    ['type' => 'task_list', 'id' => 'audit-tasks', 'document_id' => 'audit-acl-child']];
$resolver = $container->get(FollowTargetService::class);
if ($resolver->canViewTargets($memberId, $targets) !== [true, false, false]) {
    throw new RuntimeException('Ancestor restrictions were not enforced.');
}
$workspaces->replaceNodeDirectPermissions($wid, (int)$leaf['id'], [$memberId => ['can_view' => true]]);
if ($resolver->canViewTargets($memberId, $targets) !== [true, true, true]) {
    throw new RuntimeException('Direct grants were not preserved.');
}
$workspaces->replaceNodeDirectPermissions($wid, (int)$leaf['id'], []);
if ($resolver->canViewTargets($memberId, $targets) !== [true, false, false]) {
    throw new RuntimeException('Revoked direct grants leaked cached permissions.');
}
$report = ['status' => 'measured', 'database' => $driver, 'measurements' => $results,
    'checks' => ['cold_query_budget' => true, 'ancestor_restrictions' => true, 'direct_grants' => true, 'fresh_revocation' => true]];
$json = json_encode(
    $report,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
) . PHP_EOL;
file_put_contents(dirname(__DIR__) . '/build/notification-scaling-' . $driver . '.json', $json);
echo $json;
