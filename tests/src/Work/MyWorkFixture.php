<?php

declare(strict_types=1);

namespace Tests\Work;

use AaiEduHr\HeartPhrameModuleAuth\Configuration\AuthConfigurationStore;
use AaiEduHr\HeartPhrameModuleAuth\ModuleAuth;
use AaiEduHr\HeartPhrameModuleAuth\Service\AuthGroupService;
use AaiEduHr\HeartPhrameModuleAuth\Service\AuthUserService;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlConfig;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlSanitizer;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorPagePermissions;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleSurvey\Contract\SurveyActorProviderInterface;
use AaiEduHr\HeartPhrameModuleSurvey\Service\SurveyActorResolver;
use AaiEduHr\HeartPhrameModuleSurvey\Service\SurveyAuthorActivity;
use AaiEduHr\HeartPhrameModuleSurvey\Service\SurveyAuthoringService;
use AaiEduHr\HeartPhrameModuleSurvey\Service\SurveyAuthorization;
use AaiEduHr\HeartPhrameModuleSurvey\Service\SurveyDefinitionValidator;
use AaiEduHr\HeartPhrameModuleSurvey\Service\SurveySettings;
use AaiEduHr\HeartPhrameModuleSurvey\Storage\SurveyAuthoringSchema;
use AaiEduHr\HeartPhrameModuleSurvey\Storage\SurveyRevisionStore;
use AaiEduHr\HeartPhrameModuleSurvey\Storage\SurveySchema;
use AaiEduHr\HeartPhrameModuleSurvey\Value\SurveyActor;
use AaiEduHr\HeartPhrameModuleSurvey\Value\SurveyId;
use AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceAccessService;
use AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceConfig;
use AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceContentRegistry;
use AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceRepository;
use AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceWorkflowService;
use App\Work\MyWorkAccess;
use App\Work\MyWorkCursor;
use App\Work\MyWorkRepository;
use App\Work\MyWorkService;
use HeartPhrame\Authn\AuthnHandlerInterface;
use HeartPhrame\Config\Config;
use HeartPhrame\Container\Container;
use HeartPhrame\Helper\Helper;
use HeartPhrame\Routing\UrlGenerator;
use HeartPhrame\Session\SessionInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

/**
 * EN: Real module schemas and synthetic headers in private memory/storage only.
 *
 * HR: Stvarne sheme modula i sintetska zaglavlja samo u privatnoj memoriji/pohrani.
 */
final class MyWorkFixture
{
    public Database $db;

    public Config $config;

    public Container $container;

    public SurveyAuthoringService $author;

    public SurveyRevisionStore $surveyStore;

    public WorkspaceRepository $workspaces;

    public EditorHtmlConfig $editorConfig;

    public string $root;

    /** @var array<string,string|false> */
    private array $environment = [];

    /**
     * EN: Loads canonical migrations without touching an installed application's configuration or database.
     *
     * HR: Učitava kanonske migracije bez dodirivanja konfiguracije ili baze radne aplikacije.
     */
    public function __construct(public AuthnHandlerInterface $auth, private readonly SessionInterface $session)
    {
        $this->root = sys_get_temp_dir() . '/my-work-test-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/config', 0700, true);
        file_put_contents(
            $this->root . '/config/app.php',
            "<?php return ['auth' => ['require_provider_verification' => false]];",
        );
        foreach (['HPH_APP_PATH', 'HPH_CONFIG_PATH', 'HPH_CONFIG_OVERRIDE_PATHS'] as $key) {
            $this->environment[$key] = getenv($key);
            $value = $key === 'HPH_CONFIG_OVERRIDE_PATHS' ? ''
            : $this->root . ($key === 'HPH_CONFIG_PATH' ? '/config' : '');
            putenv($key . '=' . $value);
        }

        $store = new AuthConfigurationStore($this->root . '/config/auth/auth.json', true);
        $store->initialize($store->defaults());

        $helper = new Helper();
        $this->config = new Config($helper, ['app' => [
            'timezone' => 'Europe/Zagreb', 'localization' => ['supported_locales' => ['hr', 'en'], 'locale' => 'hr'],
            'modules' => ['enabled' => ['aaieduhr/heartphrame-module-editor-html',
                'aaieduhr/heartphrame-module-survey', 'aaieduhr/simbioza-module-workspace']],
        ], 'database' => ['connections' => ['default' => [
            'driver' => 'sqlite', 'database' => $this->root . '/test.sqlite',
        ]]]], $this->root);
        $this->db = new Database($this->config, $helper);
        $authRoot = dirname((new ReflectionClass(ModuleAuth::class))->getFileName(), 2);
        $editorRoot = dirname((new ReflectionClass(EditorHtmlConfig::class))->getFileName(), 3);
        $workspaceRoot = dirname((new ReflectionClass(WorkspaceRepository::class))->getFileName(), 3);
        (require $authRoot . '/resources/migrations/initial_auth_schema.php')->up($this->db);
        (require $editorRoot . '/resources/migrations/initial_editor_html_schema.php')->up($this->db);
        (require $workspaceRoot . '/resources/migrations/initial_workspace_schema.php')->up($this->db);
        (new SurveySchema())->up($this->db);
        (new SurveyAuthoringSchema())->up($this->db);
        // EN: Migrations use their own schema builders; a new connection models the next real request.
        //
        // HR: Migracije koriste vlastite schema buildere; nova veza predstavlja sljedeći stvarni zahtjev.
        $this->db = new Database($this->config, $helper);
        foreach (range(1, 4) as $id) {
            $this->db->table('auth_users')->insert(['id' => $id, 'login_identifier' => 'test-user-' . $id,
                'is_admin' => $id === 1, 'is_active' => true]);
        }

        $this->editorConfig = new EditorHtmlConfig($this->config, $editorRoot);
        $this->container = new Container();
        $registry = new WorkspaceContentRegistry();
        $this->workspaces = new WorkspaceRepository($this->db, contentTypes: $registry);
        $this->container->set(WorkspaceRepository::class, $this->workspaces);
        $this->container->set(WorkspaceAccessService::class, new WorkspaceAccessService(
            $this->workspaces,
            $auth,
            new WorkspaceConfig($this->config, $workspaceRoot),
            new WorkspaceWorkflowService($this->workspaces),
            $registry,
        ));
        $this->container->set(
            EditorPagePermissions::class,
            new EditorPagePermissions($this->db, $auth, $this->editorConfig),
        );

        $authorization = new SurveyAuthorization($this->db);
        $this->container->set(SurveyAuthorization::class, $authorization);
        $this->container->set(SurveyActorProviderInterface::class, new SurveyActorResolver(
            $auth,
            new AuthUserService($this->db),
            new AuthGroupService($this->db),
        ));
        $validator = new SurveyDefinitionValidator(new SurveySettings($this->config), new EditorHtmlSanitizer());
        $this->surveyStore = new SurveyRevisionStore($this->db, $validator);
        $this->author = new SurveyAuthoringService(
            $this->db,
            $this->surveyStore,
            $validator,
            $authorization,
            activity: new SurveyAuthorActivity($this->db),
        );
        foreach ([[2, 1, 0], [3, 0, 1]] as [$id, $manage, $publish]) {
            $this->db->table('survey_grants')->insert(['subject_kind' => 'user', 'subject_id' => $id,
                'scope_kind' => 'global', 'scope_key' => '*', 'can_manage' => $manage, 'can_publish' => $publish]);
        }
    }

    /**
     * EN: Composes actual repository, domain access, and list service.
     *
     * HR: Sastavlja stvarni repozitorij, domenske ovlasti i servis popisa.
     */
    public function service(UrlGenerator $urls): MyWorkService
    {
        return new MyWorkService(
            new MyWorkRepository($this->db, $this->config),
            new MyWorkAccess($this->container, $this->db, $this->config),
            $urls,
            $this->config,
            new MyWorkCursor($this->session),
        );
    }

    /**
     * EN: Inserts valid version metadata without creating or reading content payload files.
     *
     * HR: Unosi valjane metapodatke verzije bez stvaranja ili čitanja datoteka sadržaja.
     *
     * @return array<string,mixed>
     */
    public function html(string $key, int $author = 2, bool $write = true, bool $published = false): array
    {
        $this->db->table('editor_html_documents')->insert(['uuid' => SurveyId::generate(), 'document_key' => $key,
            'title' => $key, 'owner_user_id' => $author, 'created_by_user_id' => $author,
            'created_at' => '2026-11-01 12:00:00', 'updated_at' => '2026-11-01 12:00:00']);
        $document = $this->db->table('editor_html_documents')->where('document_key', '=', $key)->first();
        $this->db->table('editor_html_document_versions')->insert(['document_id' => $document['id'],
            'language_code' => 'hr', 'version_number' => 1, 'title' => $key, 'is_published' => $published,
            'created_by_user_id' => $author, 'updated_by_user_id' => $author,
            'created_at' => '2026-11-01 12:00:00', 'updated_at' => '2026-11-01 12:00:00']);
        if ($write) {
            $this->db->table('editor_html_document_acl')->insert(['document_id' => $document['id'],
                'subject_type' => 'user', 'subject_id' => $author, 'subject_key' => 'test-user-' . $author,
                'can_read' => true, 'can_write' => true]);
        }

        return $document;
    }

    /**
     * EN: Creates and saves a complete questionnaire through the real authoring service.
     *
     * HR: Stvara i sprema potpuni upitnik kroz stvarni autorski servis.
     */
    public function survey(string $title = 'Actual survey', int $owner = 2): string
    {
        $actor = new SurveyActor($owner);
        $uuid = $this->author->create($actor, $title, 'hr');
        $definition = $this->author->open($actor, $uuid)['definition'];
        $page = SurveyId::generate();
        $block = SurveyId::generate();
        $question = SurveyId::generate();
        $definition['pages'] = [['uuid' => $page, 'require_complete' => true, 'blocks' => [['uuid' => $block,
            'kind' => 'question', 'question' => ['uuid' => $question, 'type' => 'text', 'alias' => 'Q1',
                'required' => true, 'settings' => [], 'choices' => []]]]]];
        $definition['texts']['hr'][$page] = ['title' => 'Page'];
        $definition['texts']['hr'][$question] = ['text' => 'Question', 'help_html' => ''];
        $this->author->save($actor, $uuid, 0, $definition);
        return $uuid;
    }

    /**
     * EN: Transitions the actual current revision with its optimistic version.
     *
     * HR: Provodi prijelaz stvarne aktualne revizije uz njezinu optimističku verziju.
     */
    public function transition(string $uuid, string $action, int $actor = 2): void
    {
        $header = $this->surveyStore->survey($uuid);
        $this->author->transition(
            new SurveyActor($actor),
            $uuid,
            (int)$header['lock_version'],
            $header['draft_revision_uuid'],
            $action,
            true,
        );
    }

    /**
     * EN: Removes only this fixture's temporary files and restores the caller's environment.
     *
     * HR: Briše samo privremene datoteke ovog primjera i vraća okruženje pozivatelja.
     */
    public function close(): void
    {
        foreach ($this->environment as $key => $value) {
            putenv($value === false ? $key : $key . '=' . $value);
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
            $this->root,
            RecursiveDirectoryIterator::SKIP_DOTS,
        ), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($this->root);
    }
}
