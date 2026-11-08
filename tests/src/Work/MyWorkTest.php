<?php

declare(strict_types=1);

namespace Tests\Work;

use AaiEduHr\HeartPhrameModuleOrm\Database\QueryExecuted;
use AaiEduHr\HeartPhrameModuleSurvey\Value\SurveyActor;
use App\Work\MyWorkRepository;
use HeartPhrame\Authn\AuthnHandlerInterface;
use HeartPhrame\Routing\UrlGenerator;
use HeartPhrame\Session\SessionInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class MyWorkTest extends TestCase
{
    private MyWorkFixture $fixture;

    /** @var array<string,mixed> */
    private array $user = ['id' => 2, 'is_admin' => false, 'is_active' => true];

    /** @var array<string,mixed> */
    private array $cursorState = [];

    /**
     * EN: Creates actual private schemas and a request-scoped current identity.
     *
     * HR: Stvara stvarne privatne sheme i trenutačni identitet zahtjeva.
     */
    protected function setUp(): void
    {
        $auth = $this->createMock(AuthnHandlerInterface::class);
        $auth->method('userData')->willReturnCallback(fn(): array => $this->user);
        $auth->method('check')->willReturn(true);
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->willReturnCallback(
            fn(string $key, mixed $default = null): mixed => $this->cursorState[$key] ?? $default,
        );
        $session->method('set')->willReturnCallback(function (string $key, mixed $value): void {
            $this->cursorState[$key] = $value;
        });
        $this->fixture = new MyWorkFixture($auth, $session);
    }

    /**
     * EN: Removes only this test's temporary configuration.
     *
     * HR: Briše samo privremenu konfiguraciju ovog testa.
     */
    protected function tearDown(): void
    {
        $this->fixture->close();
    }

    /**
     * EN: Uses real submit/publication/save transitions; review belongs to a revision,
     * including a new review on a survey whose collection is already published.
     *
     * HR: Koristi stvarne prijelaze slanja/objave/spremanja; pregled pripada reviziji,
     * uključujući novi pregled ankete čija je zbirka već objavljena.
     */
    public function testActualSurveyReviewUsesRevisionStateAndPublisherPermission(): void
    {
        $f = $this->fixture;
        $uuid = $f->survey();
        $repository = new MyWorkRepository($f->db, $f->config);
        $this->assertContains('survey', $repository->kinds('drafts'));
        $drafts = $repository->candidates('survey', 'drafts', 2, 0);
        $this->assertSame([$uuid], array_column($drafts, 'uuid'));
        $this->assertSame('draft', $drafts[0]['work_state']);
        $this->assertSame([], $repository->candidates('survey', 'review', 3, 0));
        $f->transition($uuid, 'submit');
        $repository = new MyWorkRepository($f->db, $f->config);
        $header = $f->surveyStore->survey($uuid);
        $this->assertSame('draft', $header['state']);
        $revision = $f->db->table('survey_revisions')->where('uuid', '=', $header['draft_revision_uuid'])->first();
        $this->assertSame('review', $revision['state']);
        $this->assertSame('in_review', $repository->candidates('survey', 'review', 3, 0)[0]['work_state']);
        $service = $f->service($this->urls());
        $this->assertSame([], $this->family($service->lists($this->user, 'hr', 'review', []), 'survey')['items']);
        $this->user = ['id' => 3, 'is_admin' => false, 'is_active' => true];
        $review = $this->family($service->lists($this->user, 'hr', 'review', []), 'survey');
        $this->assertCount(1, $review['items']);
        $this->assertSame('in_review', $review['items'][0]['state']);
        $f->transition($uuid, 'publish', 3);
        $this->assertSame([], $repository->candidates('survey', 'review', 3, 0));
        $published = $f->author->open(new SurveyActor(2), $uuid);
        $definition = $published['definition'];
        $definition['texts']['hr'][$uuid]['title'] = 'New working title';
        $f->author->save(new SurveyActor(2), $uuid, (int)$published['survey']['lock_version'], $definition);
        $this->assertSame('published', $f->surveyStore->survey($uuid)['state']);
        $this->assertSame('draft', $repository->candidates('survey', 'drafts', 2, 0)[0]['work_state']);
        $f->transition($uuid, 'submit');
        $repository = new MyWorkRepository($f->db, $f->config);
        $this->assertSame('published', $f->surveyStore->survey($uuid)['state']);
        $this->assertSame('in_review', $repository->candidates('survey', 'drafts', 2, 0)[0]['work_state']);
        $this->assertSame('in_review', $repository->candidates('survey', 'review', 3, 0)[0]['work_state']);
        $this->assertCount(1, $this->family($service->lists($this->user, 'hr', 'review', []), 'survey')['items']);
        $f->db->table('survey_grants')->where('subject_id', '=', 3)->update(['can_publish' => 0, 'can_manage' => 1]);
        $this->assertSame([], $this->family($service->lists($this->user, 'hr', 'review', []), 'survey')['items']);
    }

    /**
     * EN: Personal history retains the original contributor after somebody else saves the latest revision.
     *
     * HR: Osobna povijest čuva izvornog suradnika nakon što drugi korisnik spremi najnoviju reviziju.
     */
    public function testContributionHistoryIsNotLimitedToTheLastEditor(): void
    {
        $f = $this->fixture;
        $document = $f->html('Original contribution');
        $f->db->table('editor_html_document_versions')->insert(['document_id' => $document['id'],
            'language_code' => 'hr', 'version_number' => 2, 'title' => 'Latest by another author',
            'is_published' => false, 'created_by_user_id' => 4, 'updated_by_user_id' => 4,
            'created_at' => '2026-11-02 12:00:00', 'updated_at' => '2026-11-02 12:00:00']);
        $f->db->table('editor_html_documents')->where('id', '=', $document['id'])->update(['updated_by_user_id' => 4]);
        $repository = new MyWorkRepository($f->db, $f->config);
        $rows = $repository->candidates('html', 'recent', 2, 0);
        $this->assertCount(1, $rows);
        $this->assertSame('2026-11-01 12:00:00', $rows[0]['work_at']);
        $this->assertSame('Latest by another author', $rows[0]['work_title']);
        $items = $this->family($f->service($this->urls())->lists($this->user, 'hr', 'recent', []), 'html')['items'];
        $this->assertSame('Latest by another author', $items[0]['title']);
    }

    /**
     * EN: A real Workspace review requires publication now; editor history alone
     * does not grant review, and archived hosts disappear from every work list.
     *
     * HR: Stvarni pregled u području traži aktualnu ovlast objave; sama urednička
     * povijest ne dopušta pregled, a arhivirani domaćini nestaju iz svih radnih popisa.
     */
    public function testWorkspaceReviewUsesCurrentPublishRightsAndHostState(): void
    {
        $f = $this->fixture;
        $document = $f->html('Workspace review');
        $workspace = $f->workspaces->saveWorkspace(['name' => 'Review host', 'slug' => 'review-host',
            'visibility' => 'restricted'], 1);
        $workspaceId = (int)$workspace['id'];
        $node = $f->workspaces->saveNode($workspaceId, ['title' => 'Workspace review',
            'node_type' => 'document', 'document_key' => $document['document_key']], 2);
        $f->db->table('workspace_node_workflows')->insert(['node_id' => $node['id'], 'language_code' => 'hr',
            'status' => 'in_review', 'current_version_number' => 1, 'submitted_by_user_id' => 2,
            'updated_at' => '2026-11-02 12:00:00']);
        $f->workspaces->replaceWorkspaceAcl($workspaceId, ['user' => [
            2 => ['can_view' => true, 'can_edit' => true],
            3 => ['can_view' => true, 'can_publish' => true],
        ]]);
        $service = $f->service($this->urls());
        $this->assertSame([], $this->family($service->lists($this->user, 'hr', 'review', []), 'page')['items']);
        $this->user = ['id' => 3, 'is_admin' => false, 'is_active' => true];
        $review = $this->family($service->lists($this->user, 'hr', 'review', []), 'page');
        $this->assertSame(['Workspace review'], array_column($review['items'], 'title'));
        $f->db->table('workspaces')->where('id', '=', $workspaceId)->update(['is_archived' => true]);
        $this->assertSame([], $this->family($service->lists($this->user, 'hr', 'review', []), 'page')['items']);
    }

    /**
     * EN: Existing installations without the optional activity table retain the old revision-history fallback.
     *
     * HR: Postojeće instalacije bez opcionalne tablice aktivnosti zadržavaju rezervni put povijesti revizija.
     */
    public function testSurveyHistoryFallbackWithoutActivityTable(): void
    {
        $f = $this->fixture;
        $uuid = $f->survey('Legacy revision history');
        $f->transition($uuid, 'submit');
        $f->transition($uuid, 'return', 3);
        $f->db->schema()->dropIfExists('survey_author_activity');
        $repository = new MyWorkRepository($f->db, $f->config);
        $this->assertSame([$uuid], array_column($repository->candidates('survey', 'recent', 2, 0), 'uuid'));
        $this->assertSame([], $repository->candidates('survey', 'recent', 3, 0));
    }

    /**
     * EN: Saving another author's existing revision records the real coauthor timestamp;
     * publisher activity is also personal history, but never an irrevocable permission.
     *
     * HR: Spremanje postojeće tuđe revizije bilježi stvarno vrijeme doprinosa suautora;
     * i aktivnost objavljivača jest osobna povijest, ali nikada neopoziva ovlast.
     */
    public function testSurveyCoauthorSaveAndPublisherActivityUseRealCurrentPermissions(): void
    {
        $f = $this->fixture;
        $f->db->table('survey_grants')->insert(['subject_kind' => 'user', 'subject_id' => 4,
            'scope_kind' => 'global', 'scope_key' => '*', 'can_manage' => 1, 'can_publish' => 0]);
        $uuid = $f->survey('Another authors survey', owner: 4);
        $f->db->table('survey_grants')->insert(['subject_kind' => 'user', 'subject_id' => 2,
            'scope_kind' => 'survey', 'scope_key' => $uuid, 'can_manage' => 1, 'can_publish' => 0]);
        $snapshot = $f->author->open(new SurveyActor(2), $uuid);
        $revisionId = $snapshot['revision']['uuid'];
        $f->db->table('survey_revisions')->where('uuid', '=', $revisionId)
            ->update(['created_at' => '2020-01-01 00:00:00']);
        $definition = $snapshot['definition'];
        $definition['texts']['hr'][$uuid]['title'] = 'Coauthored working title';
        $before = gmdate('Y-m-d H:i:s');
        $f->author->save(new SurveyActor(2), $uuid, (int)$snapshot['survey']['lock_version'], $definition);
        $after = gmdate('Y-m-d H:i:s');
        $activity = $f->db->table('survey_author_activity')->where('survey_uuid', '=', $uuid)
            ->where('user_id', '=', 2)->first();
        $this->assertIsArray($activity);
        $this->assertGreaterThanOrEqual($before, $activity['last_at']);
        $this->assertLessThanOrEqual($after, $activity['last_at']);
        $revision = $f->db->table('survey_revisions')->where('uuid', '=', $revisionId)->first();
        $this->assertSame(4, (int)$revision['created_by_user_id']);
        $this->assertSame($revisionId, $f->surveyStore->survey($uuid)['draft_revision_uuid']);
        $repository = new MyWorkRepository($f->db, $f->config);
        foreach (['recent', 'drafts'] as $section) {
            $rows = $repository->candidates('survey', $section, 2, 0);
            $this->assertSame([$uuid], array_column($rows, 'uuid'));
            $this->assertSame($activity['last_at'], $rows[0]['work_at']);
        }

        $service = $f->service($this->urls());
        $this->assertCount(1, $this->family($service->lists($this->user, 'hr', 'recent', []), 'survey')['items']);
        $f->transition($uuid, 'submit', 2);
        $f->transition($uuid, 'publish', 3);

        $reviewer = $f->db->table('survey_author_activity')->where('survey_uuid', '=', $uuid)
            ->where('user_id', '=', 3)->first();
        $this->assertIsArray($reviewer);
        $this->user = ['id' => 3, 'is_admin' => false, 'is_active' => true];
        $rows = $repository->candidates('survey', 'recent', 3, 0);
        $this->assertSame([$uuid], array_column($rows, 'uuid'));
        $this->assertSame($reviewer['last_at'], $rows[0]['work_at']);
        $this->assertCount(1, $this->family($service->lists($this->user, 'hr', 'recent', []), 'survey')['items']);
        $f->db->table('survey_grants')->where('subject_id', '=', 3)->update(['can_publish' => 0]);
        $this->assertSame([], $this->family($service->lists($this->user, 'hr', 'recent', []), 'survey')['items']);
    }

    /**
     * EN: Hidden candidates are skipped before page size and do not create an empty first page.
     *
     * HR: Skriveni kandidati preskaču se prije veličine stranice i ne stvaraju praznu prvu stranicu.
     */
    public function testPaginationRunsAfterAclAndRevokedAccessDisappears(): void
    {
        $f = $this->fixture;
        $f->config->set('app.modules.enabled', ['aaieduhr/heartphrame-module-editor-html']);
        foreach (range(1, 65) as $index) {
            $f->html('Item ' . $index, write: $index <= 12);
        }

        $service = $f->service($this->urls());
        $first = $this->family($service->lists($this->user, 'hr', 'drafts', ['size' => 10]), 'html');
        $expected = array_map(static fn(int $i): string => 'Item ' . $i, range(12, 3));
        $this->assertSame($expected, array_column($first['items'], 'title'));
        $this->assertTrue($first['more']);
        $next = ['size' => 10, 'html_cursor' => $first['next']];
        $second = $this->family($service->lists($this->user, 'hr', 'drafts', $next), 'html');
        $this->assertSame(['Item 2', 'Item 1'], array_column($second['items'], 'title'));
        $this->assertFalse($second['more']);
        $f->db->table('editor_html_document_acl')->delete();
        $revoked = $this->family($service->lists($this->user, 'hr', 'drafts', []), 'html');
        $this->assertSame([], $revoked['items']);
        $this->assertFalse($revoked['more']);
        $this->assertFalse($revoked['unavailable']);
    }

    /**
     * EN: A full visible page followed only by hidden rows has no Next hint.
     *
     * HR: Puna vidljiva stranica nakon koje slijede samo skriveni redci nema naznaku dalje.
     */
    public function testHiddenRowsCannotCreateAMoreHint(): void
    {
        $f = $this->fixture;
        $f->config->set('app.modules.enabled', ['aaieduhr/heartphrame-module-editor-html']);
        foreach (range(1, 60) as $index) {
            $f->html('Item ' . $index, write: $index > 50);
        }

        $list = $this->family($f->service($this->urls())->lists($this->user, 'hr', 'recent', ['size' => 10]), 'html');
        $this->assertCount(10, $list['items']);
        $this->assertFalse($list['more']);
        $this->assertFalse($list['unavailable']);
    }

    /**
     * EN: The safety budget stops at fifty windows and reports an unavailable list without partial metadata.
     *
     * HR: Sigurnosni budžet staje nakon pedeset prozora i prijavljuje nedostupan popis bez djelomičnih metapodataka.
     */
    public function testCandidateBudgetFailsClosedWithoutPeekingAtHiddenMore(): void
    {
        $f = $this->fixture;
        $f->config->set('app.modules.enabled', ['aaieduhr/heartphrame-module-editor-html']);
        $f->db->transaction(function () use ($f): void {
            foreach (range(1, 2001) as $index) {
                $f->html('Hidden title ' . $index, write: false);
            }
        });
        $queries = [];
        $f->db->listen(static function (QueryExecuted $event) use (&$queries): void {
            $queries[] = $event->sql;
        });
        $list = $this->family($f->service($this->urls())->lists($this->user, 'hr', 'recent', []), 'html');
        $f->db->forgetQueryListeners();
        $this->assertTrue($list['unavailable']);
        $this->assertSame([], $list['items']);
        $this->assertFalse($list['more']);
        $this->assertCount(50, array_filter(
            $queries,
            static fn(string $sql): bool => str_starts_with($sql, 'SELECT * FROM ('),
        ));
        $this->assertStringNotContainsString('Hidden title', json_encode($list, JSON_THROW_ON_ERROR));
    }

    /**
     * EN: Disabled optional modules do not expose old rows or revive Workspace-owned pages as standalone content.
     *
     * HR: Isključeni opcionalni moduli ne izlažu stare retke niti vraćaju stranice područja kao samostalni sadržaj.
     */
    public function testDisabledModulesAndRemainingWorkspaceOwnershipFailClosed(): void
    {
        $f = $this->fixture;
        $document = $f->html('Adopted page');
        $workspace = $f->workspaces->saveWorkspace([
            'name' => 'Private', 'slug' => 'private', 'visibility' => 'restricted',
        ], 1);
        $f->workspaces->saveNode((int)$workspace['id'], [
            'title' => 'Adopted', 'document_key' => $document['document_key'], 'node_type' => 'document',
        ], 1);
        $f->config->set('app.modules.enabled', ['aaieduhr/heartphrame-module-editor-html']);

        $repository = new MyWorkRepository($f->db, $f->config);
        $this->assertSame(['html'], $repository->kinds('recent'));
        $this->assertSame([], $repository->candidates('html', 'recent', 2, 0));
        $f->config->set('app.modules.enabled', []);
        $this->assertSame([], $repository->kinds('recent'));
        $this->assertSame([], $f->service($this->urls())->lists($this->user, 'hr', 'recent', []));
    }

    /**
     * EN: SQL grows by fixed windows, not one query per returned item.
     *
     * HR: SQL raste po fiksnim prozorima, ne jednim upitom po vraćenoj stavci.
     */
    public function testOneAndTwentyFiveResultsHaveTheSameQueryBudget(): void
    {
        $f = $this->fixture;
        $f->config->set('app.modules.enabled', ['aaieduhr/heartphrame-module-editor-html']);
        $f->html('First');

        $service = $f->service($this->urls());
        $f->db->schema()->hasTable('editor_html_documents');
        $counts = [];
        foreach ([1, 25] as $count) {
            if ($count > 1) {
                foreach (range(2, 25) as $index) {
                    $f->html('Extra ' . $index);
                }
            }

            $queries = [];
            $f->db->listen(static function (QueryExecuted $event) use (&$queries): void {
                $queries[] = $event->sql;
            });
            $list = $this->family($service->lists($this->user, 'hr', 'recent', ['size' => 50]), 'html');
            $f->db->forgetQueryListeners();
            $this->assertCount($count, $list['items']);
            $counts[] = count($queries);
            $this->assertCount(1, array_filter(
                $queries,
                static fn(string $sql): bool => str_contains($sql, 'FROM "editor_html_document_acl"'),
            ));
        }

        $this->assertSame($counts[0], $counts[1]);
    }

    /**
     * EN: Survey lists also resolve current identity and grants in batches and never inspect responses.
     *
     * HR: I popisi anketa skupno razrješavaju aktualni identitet i dodjele te nikada ne čitaju odgovore.
     */
    public function testSurveyQueryBudgetDoesNotGrowPerResultOrReadResponses(): void
    {
        $f = $this->fixture;
        $f->config->set('app.modules.enabled', ['aaieduhr/heartphrame-module-survey']);
        $f->survey('First survey');

        $service = $f->service($this->urls());
        $service->lists($this->user, 'hr', 'recent', ['size' => 50]);

        $counts = [];
        foreach ([1, 25] as $count) {
            if ($count > 1) {
                foreach (range(2, 25) as $index) {
                    $f->survey('Survey ' . $index);
                }
            }

            $queries = [];
            $f->db->listen(static function (QueryExecuted $event) use (&$queries): void {
                $queries[] = $event->sql;
            });
            $list = $this->family($service->lists($this->user, 'hr', 'recent', ['size' => 50]), 'survey');
            $f->db->forgetQueryListeners();
            $this->assertCount($count, $list['items']);
            $counts[] = count($queries);
            $this->assertCount(1, array_filter(
                $queries,
                static fn(string $sql): bool => str_contains($sql, 'FROM survey_grants'),
            ));
            $this->assertSame([], array_filter(
                $queries,
                static fn(string $sql): bool => str_contains($sql, 'survey_response'),
            ));
        }

        $this->assertSame($counts[0], $counts[1]);
    }

    /**
     * EN: Supplies deterministic local links without constructing the application router.
     *
     * HR: Daje determinističke lokalne poveznice bez izgradnje aplikacijskog usmjernika.
     */
    private function urls(): UrlGenerator
    {
        $urls = $this->createMock(UrlGenerator::class);
        $urls->method('getPathFor')->willReturnCallback(
            static fn(string $name, array $params = [], array $query = []): string =>
                '/' . $name . '?' . http_build_query([...$params, ...$query]),
        );
        return $urls;
    }

    /**
     * EN: Selects one independently paged content family.
     *
     * HR: Odabire jednu neovisno straničenu vrstu sadržaja.
     *
     * @param list<array<string,mixed>> $lists
     * @return array<string,mixed>
     */
    private function family(array $lists, string $kind): array
    {
        foreach ($lists as $list) {
            if ($list['kind'] === $kind) {
                return $list;
            }
        }

        $this->fail('Missing content family: ' . $kind);
    }
}
