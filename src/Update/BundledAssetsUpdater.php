<?php

declare(strict_types=1);

namespace App\Update;

use AaiEduHr\HeartPhrameModuleAuth\Service\AuthUserService;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupManager;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupExportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupImportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupScope;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupValue;
use AaiEduHr\HeartPhrameModuleEditorHtml\ModuleEditorHtml;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlConfig;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlDocumentFormatter;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeArchiveService;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeConfigRepository;
use AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceRepository;
use RuntimeException;

/**
 * HR: Ažurira uključenu temu Simbioza i pojedinačne isporučene stranice uputa.
 * EN: Updates the enabled Simbioza theme and individual bundled guide pages.
 */
final readonly class BundledAssetsUpdater
{
    private const PASSPHRASE = 'SimbiozaSeed2026!';

    /**
     * @var array<string,array{file:string,slug:string,parent:?string,recovery:string,message:string}>
     */
    private const GUIDE_PAGES = [
        'meetings_sha256' => [
            'file' => 'sastanci.zip',
            'slug' => 'sastanci',
            'parent' => 'kalendari',
            'recovery' => 'previous_meeting_guide',
            'message' => 'Bilingual meeting guides updated; other pages and existing page permissions preserved.',
        ],
        'installation_sha256' => [
            'file' => 'instalacija.zip',
            'slug' => 'instalacija',
            'parent' => null,
            'recovery' => 'previous_installation_guide',
            'message' => 'Bilingual installation guides updated; other pages and existing page permissions preserved.',
        ],
    ];

    /** HR: Prima javne servise modula. EN: Receives the modules' public services. */
    public function __construct(
        private Database $database,
        private BackupManager $backups,
        private WorkspaceRepository $workspaces,
        private ?ThemeConfigRepository $themes,
        private ?ThemeArchiveService $themeArchives,
        private AuthUserService $users,
        private EditorHtmlConfig $editorConfig,
        private EditorHtmlDocumentFormatter $formatter,
    ) {
    }

    /**
     * HR: Primjenjuje izmijenjene pakete jednom i čuva privatne povratne točke.
     * EN: Applies changed packages once and keeps private recovery points.
     * @param list<string> $skippedGuideProviders
     * @return list<string>
     */
    public function run(string $root, string $basePath, array $skippedGuideProviders = []): array
    {
        $basePath = self::validatedBasePath($basePath);
        $statePath = $root . '/data/bundled-assets.json';
        $state = is_file($statePath)
        ? json_decode((string)file_get_contents($statePath), true, 512, JSON_THROW_ON_ERROR)
        : [];
        $decoded = is_array($state) ? $state : [];
        $state = [];
        foreach ($decoded as $key => $value) {
            if (!is_string($key)) {
                throw new RuntimeException('Bundled-asset state must contain named keys.');
            }

            $state[$key] = $value;
        }

        $actions = [];
        $themePath = $root . '/resources/installation/theme/simbioza.zip';
        if (
            $this->themes instanceof \AaiEduHr\HeartPhrameModuleTheme\Service\ThemeConfigRepository
            && $this->themeArchives instanceof \AaiEduHr\HeartPhrameModuleTheme\Service\ThemeArchiveService
            && !is_file($themePath)
        ) {
            throw new RuntimeException('A bundled asset package is missing: ' . basename($themePath));
        }

        $themeHash = is_file($themePath) ? hash_file('sha256', $themePath) : false;
        if (
            $this->themes instanceof \AaiEduHr\HeartPhrameModuleTheme\Service\ThemeConfigRepository
            && $this->themeArchives instanceof \AaiEduHr\HeartPhrameModuleTheme\Service\ThemeArchiveService
            && is_string($themeHash)
            && ($state['theme_sha256'] ?? null) !== $themeHash
        ) {
            $directory = $root . '/data/backups/bundled-assets';
            if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
                throw new RuntimeException('The bundled-asset recovery directory is unavailable.');
            }

            if (($this->themes->themeById('simbioza')['id'] ?? null) === 'simbioza') {
                $snapshot = $directory . '/simbioza-' . bin2hex(random_bytes(8)) . '.zip';
                $this->privateWrite($snapshot, $this->themeArchives->export('simbioza'));
                $state['previous_theme_archive'] = $snapshot;
                $this->saveState($statePath, $state);
            }

            $this->themeArchives->importFile($themePath, true, true);
            $state['theme_sha256'] = $themeHash;
            $this->saveState($statePath, $state);
            $actions[] = 'Simbioza theme updated; other themes and active-theme settings preserved.';
        }

        $pagePackages = [];
        foreach (self::GUIDE_PAGES as $stateKey => $definition) {
            $path = $root . '/resources/installation/workspace/' . $definition['file'];
            if (!is_file($path)) {
                throw new RuntimeException('A bundled asset package is missing: ' . basename($path));
            }

            $hash = hash_file('sha256', $path);
            if (!is_string($hash)) {
                throw new RuntimeException('A bundled asset package cannot be checksummed: ' . basename($path));
            }

            $pagePackages[$stateKey] = ['definition' => $definition, 'path' => $path, 'hash' => $hash];
        }

        $workspace = $this->workspaces->findWorkspaceBySlug('korisnicke-upute');
        $hasChangedPage = !is_array($workspace);
        foreach ($pagePackages as $stateKey => $package) {
            if (($state[$stateKey] ?? null) !== $package['hash']) {
                $hasChangedPage = true;
            }
        }

        if (!$hasChangedPage && is_array($workspace)) {
            $workspaceId = BackupValue::integer($workspace['id'], 'workspace.id');
            foreach ($pagePackages as $package) {
                $page = $this->workspaces->findNodeBySlug($workspaceId, $package['definition']['slug']);
                if (
                    is_array($page)
                    && $this->normalizeDocumentAssetOwnership(
                        BackupValue::string($page['document_key'], 'document_key'),
                    )
                ) {
                    $actions[] = 'Bundled-guide attachment ownership repaired; content and visibility preserved.';
                }
            }

            return array_values(array_unique($actions));
        }

        $administratorIds = $this->users->listActiveAdministratorIds();
        $actorId = $administratorIds[0] ?? 0;
        if ($actorId <= 0) {
            throw new RuntimeException('Updating the bundled guides requires an active administrator.');
        }

        if (!is_array($workspace)) {
            $context = new BackupImportContext(
                new BackupScope(BackupScope::WORKSPACE, 'korisnicke-upute'),
                BackupImportContext::CONFLICT_COPY,
                [],
                $skippedGuideProviders,
                [
                    'workspace-scope' => ['target_slug' => 'korisnicke-upute', 'preserve_name_on_copy' => true],
                    'comment-workspace' => ['fallback_users_to_actor' => true],
                    'task-workspace' => ['fallback_users_to_actor' => true],
                ],
                $actorId,
                self::PASSPHRASE,
            );
            $archive = $root . '/resources/installation/workspace/korisnicke-upute.zip';
            $this->restoreChecked($archive, $context);
            $workspace = $this->workspaces->findWorkspaceBySlug('korisnicke-upute');
            if (!is_array($workspace)) {
                throw new RuntimeException('The imported guide Workspace is unavailable.');
            }

            $nodes = $this->workspaces->nodesForWorkspace(BackupValue::integer($workspace['id'], 'workspace.id'));
            foreach ($nodes as $node) {
                $this->normalizeDocumentAssetOwnership(
                    BackupValue::string($node['document_key'] ?? '', 'document_key'),
                );
                $this->normalizeDocumentPaths(
                    BackupValue::string($node['document_key'] ?? '', 'document_key'),
                    $basePath,
                );
            }

            foreach ($pagePackages as $stateKey => $package) {
                $state[$stateKey] = $package['hash'];
            }

            $this->saveState($statePath, $state);
            $actions[] = 'Bilingual user guides installed; bundled pages and attachments are updateable.';
        } else {
            $workspaceId = BackupValue::integer($workspace['id'], 'workspace.id');
            foreach ($pagePackages as $stateKey => $package) {
                if (($state[$stateKey] ?? null) === $package['hash']) {
                    continue;
                }

                $this->updateGuidePage(
                    $workspaceId,
                    $package['definition'],
                    $package['path'],
                    $actorId,
                    $basePath,
                    $state,
                    $statePath,
                    $skippedGuideProviders,
                );
                $state[$stateKey] = $package['hash'];
                $this->saveState($statePath, $state);
                $actions[] = $package['definition']['message'];
            }
        }

        return $actions;
    }

    /**
     * HR: Sigurno zamjenjuje jednu upravljanu stranicu i prije toga izrađuje povratnu točku.
     * EN: Safely replaces one managed page after first creating a recovery point.
     * @param array{file:string,slug:string,parent:?string,recovery:string,message:string} $definition
     * @param array<string,mixed> $state
     * @param list<string> $skippedGuideProviders
     */
    private function updateGuidePage(
        int $workspaceId,
        array $definition,
        string $pagePath,
        int $actorId,
        string $basePath,
        array &$state,
        string $statePath,
        array $skippedGuideProviders,
    ): void {
        $page = $this->workspaces->findNodeBySlug($workspaceId, $definition['slug']);
        $parent = $definition['parent'] !== null
        ? $this->workspaces->findNodeBySlug($workspaceId, $definition['parent'])
        : null;
        $pageId = is_array($page) ? BackupValue::integer($page['id'], 'page.id') : null;
        $options = [
            'target_workspace' => $workspaceId,
            'target_page_id' => $pageId,
            'target_parent_id' => is_array($parent) ? BackupValue::integer($parent['id'], 'parent.id') : null,
            'import_permissions' => false,
            'include_history' => true,
        ];
        $context = new BackupImportContext(
            new BackupScope(BackupScope::PAGE, (string)$workspaceId),
            $pageId !== null ? BackupImportContext::CONFLICT_REPLACE : BackupImportContext::CONFLICT_COPY,
            [],
            $skippedGuideProviders,
            ['page-transfer' => $options, 'editor-html-workspace' => $options],
            $actorId,
            self::PASSPHRASE,
        );
        $preflight = $this->backups->preflight($pagePath, $context);
        if (!$preflight->isAllowed()) {
            throw new RuntimeException('Bundled guide failed preflight: ' . implode(' | ', $preflight->errors));
        }

        if ($pageId !== null) {
            $recoveryPassphrase = bin2hex(random_bytes(32));
            $snapshot = $this->backups->create(new BackupExportContext(
                new BackupScope(BackupScope::PAGE, (string)$pageId),
                [],
                [
                    'page-transfer' => ['include_history' => true, 'include_permissions' => true],
                    'editor-html-workspace' => ['include_history' => true, 'include_permissions' => true],
                ],
                $actorId,
                $recoveryPassphrase,
            ), 'before-bundled-' . $definition['slug'] . '-update');
            $state[$definition['recovery']] = ['archive' => $snapshot, 'passphrase' => $recoveryPassphrase];
            $this->saveState($statePath, $state);
        }

        $this->backups->restore($pagePath, $context);
        $page = $this->workspaces->findNodeBySlug($workspaceId, $definition['slug']);
        if (!is_array($page)) {
            throw new RuntimeException('The imported guide page is unavailable: ' . $definition['slug']);
        }

        $key = BackupValue::string($page['document_key'], 'document_key');
        $this->normalizeDocumentAssetOwnership($key);
        $this->normalizeDocumentPaths($key, $basePath);
    }

    /** HR: Provjerava paket prije vraćanja. EN: Validates the archive before restoring it. */
    private function restoreChecked(string $archive, BackupImportContext $context): void
    {
        $result = $this->backups->preflight($archive, $context);
        if (!$result->isAllowed()) {
            throw new RuntimeException('Bundled guides failed preflight: ' . implode(' | ', $result->errors));
        }

        $this->backups->restore($archive, $context);
    }

    /**
     * HR: Usklađuje samo datoteke privitaka odabranog dokumenta, nikada privatne backup artefakte.
     * EN: Aligns only the selected document's attachment files, never private backup artifacts.
     */
    private function normalizeDocumentAssetOwnership(string $key): bool
    {
        $document = $this->database->table(ModuleEditorHtml::TABLE_DOCUMENTS)
            ->where('document_key', '=', $key)->first();
        if (!is_array($document)) {
            return false;
        }

        $paths = [];
        $assets = $this->database->table(ModuleEditorHtml::TABLE_ASSETS)
            ->where('document_id', '=', $document['id'])->get();
        foreach ($assets as $asset) {
            if (($asset['storage_driver'] ?? '') === 'filesystem' && is_string($asset['content_path'] ?? null)) {
                $paths[] = $asset['content_path'];
            }
        }

        return BundledAssetPermissions::inheritOwnership($this->editorConfig->uploadsRoot(), $paths);
    }

    /**
     * HR: Usklađuje samo uvezeni dokument s instalacijskim poddirektorijem.
     * EN: Adjusts only the imported document for the installation subdirectory.
     */
    private function normalizeDocumentPaths(string $key, string $basePath): void
    {
        if ($key === '' || $basePath === '') {
            return;
        }

        $document = $this->database->table(ModuleEditorHtml::TABLE_DOCUMENTS)
            ->where('document_key', '=', $key)->first();
        if (!is_array($document)) {
            return;
        }

        $versions = $this->database->table(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS)
            ->where('document_id', '=', $document['id'])->get();
        foreach ($versions as $version) {
            $html = preg_replace_callback(
                '~\b(href|src|poster|data-editor-html-web-(?:href|src|poster))=(["\x27])(/(?!/)[^"\x27]*)\2~i',
                static fn(array $m): string => $m[1] . '=' . $m[2]
                    . (str_starts_with($m[3], $basePath . '/') ? '' : $basePath) . $m[3] . $m[2],
                self::guideVersionHtml($version, $this->editorConfig->documentsRoot(), $this->formatter),
            );
            if (!is_string($html)) {
                throw new RuntimeException('Bundled guide links could not be normalized.');
            }

            $this->database->table(ModuleEditorHtml::TABLE_DOCUMENT_VERSIONS)
                ->where('id', '=', $version['id'])->update([
                    'content_html' => $html, 'storage_driver' => 'database', 'content_path' => null,
                ]);
        }
    }

    /** HR: Odbija neispravni base path. EN: Rejects an invalid base path. */
    public static function validatedBasePath(string $value): string
    {
        $value = rtrim(str_replace('\\', '/', trim($value)), '/');
        if (
            $value !== '' && (preg_match('#^/(?:[A-Za-z0-9._~-]+/?)*$#D', $value) !== 1
            || preg_match('#/(?:\.|\.\.)(?:/|$)#', $value) === 1)
        ) {
            throw new RuntimeException('The bundled-asset base path is invalid.');
        }

        return $value;
    }

    /**
     * HR: Otkriva base path iz postojećih internih slika uputa starih instalacija.
     * EN: Discovers the base path from existing guide images in older installations.
     * @param list<string> $htmlVersions
     */
    public static function basePathFromGuideHtml(array $htmlVersions): string
    {
        $paths = [];
        foreach ($htmlVersions as $html) {
            if (preg_match_all('#\bsrc=["\x27]((?:/[A-Za-z0-9._~-]+)*)/editor-html/asset/#i', $html, $matches)) {
                foreach ($matches[1] as $path) {
                    $paths[$path] = true;
                }
            }
        }

        if (count($paths) > 1) {
            throw new RuntimeException('Stored guides use inconsistent base paths; run with --base-path explicitly.');
        }

        return self::validatedBasePath((string)(array_key_first($paths) ?? ''));
    }

    /**
     * HR: Čita staru verziju uputa iz baze ili sigurnog puta datoteke unutar editor spremišta.
     * EN: Reads a legacy guide version from the database or a safe file inside editor storage.
     * @param array<string,mixed> $version
     */
    public static function guideVersionHtml(
        array $version,
        string $documentsRoot,
        EditorHtmlDocumentFormatter $formatter,
    ): string {
        if (($version['storage_driver'] ?? '') !== 'filesystem') {
            return is_string($version['content_html'] ?? null) ? $version['content_html'] : '';
        }

        $relative = is_string($version['content_path'] ?? null) ? $version['content_path'] : '';
        $relative = str_replace('\\', '/', $relative);
        if (
            $relative === '' || str_starts_with($relative, '/')
            || preg_match('#(?:^|/)(?:\.|\.\.)(?:/|$)#', $relative) === 1
        ) {
            throw new RuntimeException('A legacy guide file has an invalid storage path.');
        }

        $root = realpath($documentsRoot);
        $path = is_string($root) ? realpath($root . DIRECTORY_SEPARATOR . $relative) : false;
        if (
            !is_string($root) || !is_string($path)
            || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)
        ) {
            throw new RuntimeException('A legacy guide file is missing or outside editor storage.');
        }

        $html = file_get_contents($path);
        if (!is_string($html)) {
            throw new RuntimeException('A legacy guide file cannot be read.');
        }

        return $formatter->extractFragment($html);
    }

    /**
     * HR: Čuva privatno stanje tek nakon uspješne pojedinačne radnje.
     * EN: Saves private state only after each individual successful action.
     * @param array<string,mixed> $state
     */
    private function saveState(string $path, array $state): void
    {
        $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $this->privateWrite($path, $json . PHP_EOL);
    }

    /** HR: Atomski zapisuje privatni artefakt. EN: Atomically writes a private artifact. */
    private function privateWrite(string $path, string $contents): void
    {
        $temporary = tempnam(dirname($path), '.bundled-assets-');
        if (!is_string($temporary)) {
            throw new RuntimeException('Unable to prepare bundled-asset state.');
        }

        try {
            if (
                file_put_contents($temporary, $contents, LOCK_EX) === false
                || !chmod($temporary, 0600) || !rename($temporary, $path)
            ) {
                throw new RuntimeException('Unable to save bundled-asset recovery state.');
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }
}
