<?php

declare(strict_types=1);

namespace Tests\Update;

use App\Update\BundledAssetsUpdater;
use App\Update\BundledGuideIndex;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * EN: The maintained manual is portable, ordered, and cannot overwrite unrelated hierarchy or ACL data.
 *
 * HR: Održavani priručnik je prenosiv, uređen i ne smije prepisati nepovezanu hijerarhiju ili ACL.
 */
#[CoversClass(BundledGuideIndex::class)]
#[UsesClass(BundledAssetsUpdater::class)]
final class BundledGuideIndexTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/manual-index-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/resources/installation', 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root . '/resources/installation/*') ?: [] as $path) {
            unlink($path);
        }

        rmdir($this->root . '/resources/installation');
        rmdir($this->root . '/resources');
        rmdir($this->root);
    }

    /**
     * EN: A child may precede its historical parent in JSON; runtime updates must still process the parent first.
     *
     * HR: Dijete smije biti prije povijesnog roditelja u JSON-u; nadogradnja ipak prvo obrađuje roditelja.
     */
    public function testUnorderedBilingualChaptersAreSortedWithStableHistoricalReceiptKeys(): void
    {
        $this->write([
            $this->page('admin-api', 'admin-pregled'),
            $this->page('ankete-api', 'ankete'),
            $this->page('simbioza'),
            $this->page('admin-pregled'),
            $this->page('ankete'),
            $this->page('sastanci', 'kalendari'),
            $this->page('kalendari'),
        ]);
        $pages = BundledAssetsUpdater::guidePages($this->root);
        $slugs = array_column($pages, 'slug');
        $this->assertLessThan(array_search('admin-api', $slugs, true), array_search('admin-pregled', $slugs, true));
        $this->assertSame('simbioza.zip', $pages['overview_sha256']['file']);
        $this->assertSame('previous_simbioza_overview_guide', $pages['overview_sha256']['recovery']);
        $this->assertSame('previous_meeting_guide', $pages['meetings_sha256']['recovery']);
        $this->assertSame('kalendari', $pages['meetings_sha256']['parent']);
        $this->assertSame('surveys/ankete-api.zip', $pages['ankete-api_sha256']['file']);
        $this->assertSame('previous_survey_guide_ankete-api', $pages['ankete-api_sha256']['recovery']);
        $this->assertSame('admin-api.zip', $pages['admin-api_sha256']['file']);
        $this->assertSame('admin-pregled', $pages['admin-api_sha256']['parent']);
    }

    /**
     * EN: Older releases without the full source manifest retain their previous fallback inventory.
     *
     * HR: Starija izdanja bez punog manifesta zadržavaju prethodni pričuvni popis.
     */
    public function testMissingFullManifestRetainsLegacyTenPlusSurveyPackages(): void
    {
        $this->assertNull(BundledGuideIndex::load($this->root));
        $this->assertCount(10, BundledAssetsUpdater::guidePages($this->root));
        file_put_contents($this->root . '/resources/installation/survey-guides.json', json_encode([
            'ankete_sha256' => ['file' => 'surveys/ankete.zip', 'slug' => 'ankete', 'parent' => null,
                'recovery' => 'previous_survey_guide_ankete', 'message' => 'Survey guide updated.'],
        ], JSON_THROW_ON_ERROR));
        $this->assertCount(11, BundledAssetsUpdater::guidePages($this->root));
    }

    /**
     * EN: Every historical base and survey receipt remains reusable with the complete manifest.
     *
     * HR: Sve povijesne potvrde osnovnih i anketnih uputa ostaju upotrebljive s potpunim manifestom.
     */
    public function testEveryHistoricalReceiptAndRecoveryReferenceIsPreserved(): void
    {
        $source = dirname(__DIR__, 3) . '/resources/installation/';
        copy($source . 'survey-guides.json', $this->root . '/resources/installation/survey-guides.json');
        $legacy = BundledAssetsUpdater::guidePages($this->root);
        copy($source . 'manual-sources.json', $this->root . '/resources/installation/manual-sources.json');
        $current = BundledAssetsUpdater::guidePages($this->root);
        foreach ($legacy as $key => $definition) {
            $this->assertArrayHasKey($key, $current);
            foreach (['file', 'slug', 'recovery'] as $field) {
                $this->assertSame($definition[$field], $current[$key][$field], $key . '/' . $field);
            }
        }
    }

    /**
     * EN: Optional translations retain their own titles and source files alongside the historical base languages.
     *
     * HR: Dodatni prijevodi zadržavaju svoje naslove i izvore uz povijesne osnovne jezike.
     */
    public function testEverySupportedTranslationIsPreservedInCanonicalOrder(): void
    {
        $page = $this->page('example');
        foreach (['it', 'fr', 'es', 'de'] as $locale) {
            $page['title'][$locale] = 'Title ' . $locale;
            $page['sources'][$locale] = ['docs/user-guide/example_' . $locale . '.md'];
        }

        $this->write([$page]);
        $result = BundledGuideIndex::load($this->root);
        $this->assertNotNull($result);
        $this->assertSame(BundledGuideIndex::SUPPORTED_LOCALES, array_keys($result[0]['sources']));
        $this->assertSame(BundledGuideIndex::SUPPORTED_LOCALES, array_keys($result[0]['title']));
        foreach (BundledGuideIndex::SUPPORTED_LOCALES as $locale) {
            $this->assertSame($page['title'][$locale], $result[0]['title'][$locale]);
            $this->assertSame($page['sources'][$locale], $result[0]['sources'][$locale]);
        }
    }

    /**
     * EN: An invalid present index must fail instead of silently falling back to a partial manual.
     *
     * HR: Nevaljani postojeći manifest mora biti odbijen, a ne zamijenjen djelomičnim priručnikom.
     */
    #[DataProvider('invalidIndexes')]
    public function testInvalidIndexesAreRejected(string $case): void
    {
        $pages = [$this->page('root')];
        switch ($case) {
            case 'duplicate':
                $pages[] = $this->page('root');
                break;
            case 'unknown-parent':
                $pages[] = $this->page('child', 'missing');
                break;
            case 'cycle':
                $pages = [$this->page('first', 'second'), $this->page('second', 'first')];
                break;
            case 'traversal-slug':
                $pages[0]['slug'] = '../private';
                break;
            case 'traversal-source':
                $pages[0]['sources']['hr'] = ['docs/../config/private_hr.md'];
                break;
            case 'missing-translation':
                unset($pages[0]['sources']['en']);
                break;
            case 'empty-title':
                $pages[0]['title']['en'] = '';
                break;
            case 'mismatched-language':
                $pages[0]['title']['de'] = 'Deutsch';
                break;
            case 'unsupported-language':
                $pages[0]['title']['xx'] = 'Unknown';
                $pages[0]['sources']['xx'] = ['docs/example_xx.md'];
                break;
            case 'mismatched-source-language':
                $pages[0]['title']['de'] = 'Deutsch';
                $pages[0]['sources']['de'] = ['docs/example_en.md'];
                break;
            case 'receipt-collision':
                $pages = [$this->page('simbioza'), $this->page('overview')];
                break;
            case 'empty':
                $pages = [];
                break;
        }

        $this->write($pages);
        $this->expectException(RuntimeException::class);
        BundledAssetsUpdater::guidePages($this->root);
    }

    /** @return iterable<string,array{string}> */
    public static function invalidIndexes(): iterable
    {
        foreach (
            ['duplicate', 'unknown-parent', 'cycle', 'traversal-slug', 'traversal-source',
                'missing-translation', 'empty-title', 'mismatched-language', 'unsupported-language',
                'mismatched-source-language', 'receipt-collision', 'empty'] as $case
        ) {
            yield $case => [$case];
        }
    }

    /**
     * EN: Only the shipped child's parent changes; custom nodes and all other metadata remain identical.
     *
     * HR: Mijenja se samo roditelj isporučene podstranice; korisnički čvorovi i svi drugi podaci ostaju isti.
     */
    public function testHierarchyPlanPreservesIdentitiesAclAndCustomPagesAndIsIdempotent(): void
    {
        $nodes = [
            ['id' => 11, 'slug' => 'old-parent', 'parent_id' => null, 'uuid' => 'historic-parent',
                'acl' => ['private']],
            ['id' => 22, 'slug' => 'new-parent', 'parent_id' => null, 'uuid' => 'other-historic-parent'],
            ['id' => 33, 'slug' => 'child', 'parent_id' => 11, 'uuid' => 'historic-child',
                'document_key' => 'old-document', 'sort_order' => 70, 'acl' => ['can_view' => false]],
            ['id' => 44, 'slug' => 'custom-user-page', 'parent_id' => 11, 'uuid' => 'custom', 'sort_order' => 80],
        ];
        $original = $nodes;
        $definitions = [['slug' => 'new-parent', 'parent' => null], ['slug' => 'child', 'parent' => 'new-parent']];
        $this->assertSame([['id' => 33, 'parent_id' => 22]], BundledGuideIndex::hierarchyChanges($nodes, $definitions));
        $this->assertSame($original, $nodes);
        $nodes[2]['parent_id'] = 22;
        $this->assertSame([], BundledGuideIndex::hierarchyChanges($nodes, $definitions));
        unset($original[2]['parent_id'], $nodes[2]['parent_id']);
        $this->assertSame($original, $nodes);
    }

    /**
     * EN: Reparenting reads committed rows on every pass even when the repository still caches the old tree.
     *
     * HR: Promjena roditelja svaki put čita spremljene retke, čak i kada repozitorij pamti staro stablo.
     */
    public function testReparentingIgnoresStaleTreeCacheAndPreservesOtherData(): void
    {
        $helper = new \HeartPhrame\Helper\Helper();
        $config = new \HeartPhrame\Config\Config($helper, [
            'database' => ['connections' => ['default' => ['driver' => 'sqlite', 'database' => ':memory:']]],
        ]);
        $db = new \AaiEduHr\HeartPhrameModuleOrm\Database\Database($config, $helper);
        $db->execute('CREATE TABLE workspaces (id INTEGER PRIMARY KEY)');
        $db->execute('CREATE TABLE workspace_nodes (id INTEGER PRIMARY KEY, workspace_id INTEGER, '
            . 'slug TEXT, parent_id INTEGER, is_enabled INTEGER, uuid TEXT, sort_order INTEGER, acl TEXT)');
        $db->table('workspaces')->insert(['id' => 1]);
        $original = [
            ['id' => 1, 'workspace_id' => 1, 'slug' => 'parent', 'parent_id' => null, 'is_enabled' => 1,
                'uuid' => 'historical-parent', 'sort_order' => 10, 'acl' => 'private'],
            ['id' => 2, 'workspace_id' => 1, 'slug' => 'child', 'parent_id' => null, 'is_enabled' => 1,
                'uuid' => 'historical-child', 'sort_order' => 20, 'acl' => 'reader-only'],
            ['id' => 3, 'workspace_id' => 1, 'slug' => 'custom', 'parent_id' => 2, 'is_enabled' => 1,
                'uuid' => 'custom-child', 'sort_order' => 30, 'acl' => 'custom'],
            ['id' => 4, 'workspace_id' => 2, 'slug' => 'child', 'parent_id' => null, 'is_enabled' => 1,
                'uuid' => 'different-workspace', 'sort_order' => 40, 'acl' => 'other'],
        ];
        foreach ($original as $node) {
            $db->table('workspace_nodes')->insert($node);
        }

        $cache = new \AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceRepositoryRequestCache();
        $cache->nodesByWorkspace[1] = array_slice($original, 0, 3);
        $repository = new \AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceRepository($db, requestCache: $cache);
        // EN: Unrelated constructor services stay inert; this test never authenticates or reads configuration.
        //
        // HR: Ostali servisi ostaju neaktivni; test ne provodi autentikaciju niti čita konfiguraciju.
        $users = (new \ReflectionClass(\AaiEduHr\HeartPhrameModuleAuth\Service\AuthUserService::class))
            ->newInstanceWithoutConstructor();
        $editor = (new \ReflectionClass(\AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlConfig::class))
            ->newInstanceWithoutConstructor();
        $updater = new BundledAssetsUpdater($db, null, $repository, $users, $editor);
        $this->write([$this->page('parent'), $this->page('child', 'parent')]);
        $method = new \ReflectionMethod($updater, 'reconcileGuideHierarchy');
        $this->assertSame(1, $method->invoke($updater, $this->root, 1));
        $after = $db->fetchAll('SELECT * FROM workspace_nodes ORDER BY id');
        $expected = $original;
        $expected[1]['parent_id'] = 1;
        $this->assertSame($expected, $after);
        $this->assertSame(0, $method->invoke($updater, $this->root, 1));
        $this->assertSame($after, $db->fetchAll('SELECT * FROM workspace_nodes ORDER BY id'));
    }

    /**
     * EN: Fresh package generation must reference an existing historical UUID rather than a derived replacement.
     *
     * HR: Novi paket mora referencirati postojeći povijesni UUID, a ne novo izvedeni identitet roditelja.
     */
    public function testArchiveParentsUseActualHistoricalUuid(): void
    {
        require_once dirname(__DIR__, 3) . '/scripts/sync_user_guides.php';
        $uuid = '12345678-1234-4123-a123-123456789abc';
        $this->assertNotSame(\manualIdentity('node/instalacija'), $uuid);
        $this->assertSame($uuid, \manualParentUuid('instalacija', ['instalacija' => ['uuid' => $uuid]]));
        $this->assertNull(\manualParentUuid(null, []));
        $this->expectException(RuntimeException::class);
        \manualParentUuid('missing', []);
    }

    /** @param list<array<string,mixed>> $pages */
    private function write(array $pages): void
    {
        file_put_contents(
            $this->root . '/resources/installation/manual-sources.json',
            json_encode($pages, JSON_THROW_ON_ERROR),
        );
    }

    /** @return array{slug:string,parent:?string,title:array{hr:string,en:string},sources:array{hr:list<string>,en:list<string>}} */
    private function page(string $slug, ?string $parent = null): array
    {
        return ['slug' => $slug, 'parent' => $parent, 'title' => ['hr' => 'Poglavlje', 'en' => 'Chapter'],
            'sources' => ['hr' => ['docs/user-guide/example_hr.md'], 'en' => ['docs/user-guide/example_en.md']]];
    }
}
