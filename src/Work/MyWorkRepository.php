<?php

declare(strict_types=1);

namespace App\Work;

use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use HeartPhrame\Config\ConfigInterface;

/**
 * EN: Reads bounded authoring headers, never HTML, survey answers, or participant identities.
 *
 * HR: Čita ograničene autorske metapodatke, nikada HTML, odgovore anketa ili identitete ispitanika.
 */
final class MyWorkRepository
{
    /**
     * @var array<string,bool>
     */
    private array $tablePresence = [];

    /**
     * EN: Uses installed schemas and enabled features without requiring optional modules.
     *
     * HR: Koristi instalirane sheme i uključene funkcije bez obveznih opcionalnih modula.
     */
    public function __construct(private readonly Database $db, private readonly ConfigInterface $config)
    {
    }

    /**
     * EN: Exposes only content families whose owning module is enabled and ready.
     *
     * HR: Nudi samo vrste sadržaja čiji je matični modul uključen i spreman.
     *
     * @return list<string>
     */
    public function kinds(string $section): array
    {
        $kinds = [];
        if (
            $this->enabled('simbioza-module-workspace') && $this->enabled('heartphrame-module-editor-html')
            && $this->ready(
                [
                'workspaces', 'workspace_nodes', 'workspace_node_workflows',
                'editor_html_documents', 'editor_html_document_versions',
                ],
            )
        ) {
            $kinds[] = 'page';
        }

        if (
            $section !== 'review' && $this->enabled('heartphrame-module-editor-html')
            && $this->ready(['editor_html_documents', 'editor_html_document_versions'])
        ) {
            $kinds[] = 'html';
        }

        if (
            $this->enabled('heartphrame-module-survey') && $this->ready(
                [
                'survey_surveys', 'survey_revisions', 'survey_placements', 'survey_grants',
                ],
            )
        ) {
            $kinds[] = 'survey';
        }

        return $kinds;
    }

    /**
     * EN: Scans a fixed-size candidate window; the application applies current domain ACL before paging.
     *
     * HR: Čita prozor kandidata fiksne veličine; aplikacija prije paginacije primjenjuje važeći domenski ACL.
     *
     * @return list<array<string,mixed>>
     */
    public function candidates(string $kind, string $section, int $actor, int $offset, int $limit = 40): array
    {
        if ($actor < 1 || !in_array($kind, $this->kinds($section), true)) {
            return [];
        }

        [$sql, $parameters] = match ($kind) {
            'page' => $this->pages($section, $actor),
            'html' => $this->html($section, $actor),
            'survey' => $this->surveys($section, $actor),
            default => ['', []],
        };
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);
        $languages = $this->config->getAsArrayWithValuesAsNonEmptyStrings('app.localization.supported_locales')
        ?? ['hr', 'en'];
        $languages = array_values(
            array_filter(
                $languages,
                static fn(string $language): bool => preg_match('/^[a-z]{2}(?:[-_][a-zA-Z]{2,4})?$/D', $language) === 1,
            ),
        );
        if ($languages === []) {
            return [];
        }

        return $this->db->fetchAll(
            'SELECT * FROM (' . $sql . ') work_items WHERE work_language IN ('
            . implode(',', array_fill(0, count($languages), '?')) . ') ORDER BY work_at DESC, work_id DESC LIMIT '
            . $limit . ' OFFSET ' . $offset,
            [...$parameters, ...$languages],
        );
    }

    /**
     * EN: Finds the user's last contribution even after a different editor saves a newer version.
     *
     * HR: Pronalazi korisnikov zadnji doprinos i nakon što drugi urednik spremi noviju verziju.
     *
     * @return array{string,list<mixed>}
     */
    private function pages(string $section, int $actor): array
    {
        $at = $section === 'recent' ? 'COALESCE(m.last_at,n.created_at)' : 'f.updated_at';
        $sql = "SELECT f.id AS work_id, $at AS work_at, f.language_code AS work_language, f.status AS work_state,"
        . ' n.id AS node_id,n.workspace_id,n.slug AS node_slug,n.title,n.title_translations,n.document_key,'
        . ' w.name AS workspace_title,w.slug AS workspace_slug FROM workspace_node_workflows f'
        . ' JOIN workspace_nodes n ON n.id=f.node_id JOIN workspaces w ON w.id=n.workspace_id'
        . ' JOIN editor_html_documents d ON d.document_key=n.document_key'
        . ' LEFT JOIN (' . $this->contributions() . ') m ON m.document_id=d.id AND m.language_code=f.language_code'
        . ' WHERE w.is_deleted=? AND w.is_archived=? AND n.is_enabled=? AND d.is_deleted=? AND d.is_enabled=?';
        $params = [$actor, $actor, $actor, false, false, true, false, true];
        if ($section === 'review') {
            $sql .= " AND f.status='in_review'";
        } else {
            $sql .= ' AND (m.document_id IS NOT NULL OR n.created_by_user_id=?)';
            $params[] = $actor;
            $sql .= $section === 'drafts' ? " AND f.status IN ('draft','in_review')" : " AND f.status<>'archived'";
        }

        return [$sql, $params];
    }

    /**
     * EN: Standalone content never includes documents still owned by an unavailable Workspace.
     *
     * HR: Samostalni sadržaj ne uključuje dokumente koji i dalje pripadaju nedostupnom Području.
     *
     * @return array{string,list<mixed>}
     */
    private function html(string $section, int $actor): array
    {
        $at = $section === 'recent' ? 'COALESCE(m.last_at,d.created_at)' : 'COALESCE(v.updated_at,v.created_at)';
        $sql = "SELECT v.id AS work_id,$at AS work_at,v.language_code AS work_language,"
        . "CASE WHEN v.is_published=? THEN 'published' ELSE 'draft' END AS work_state,"
        . 'd.*,v.title AS work_title FROM editor_html_documents d'
        . ' JOIN editor_html_document_versions v ON v.document_id=d.id'
        . ' AND v.version_number=(SELECT MAX(v2.version_number) FROM editor_html_document_versions v2'
        . ' WHERE v2.document_id=d.id AND v2.language_code=v.language_code)'
        . ' LEFT JOIN (' . $this->contributions() . ') m ON m.document_id=d.id AND m.language_code=v.language_code'
        . ' WHERE d.is_deleted=? AND d.is_enabled=? AND (m.document_id IS NOT NULL OR d.created_by_user_id=?)';
        $params = [true, $actor, $actor, $actor, false, true, $actor];
        if ($this->ready(['workspace_nodes'])) {
            $sql .= ' AND NOT EXISTS (SELECT 1 FROM workspace_nodes n WHERE n.document_key=d.document_key)';
        }

        if ($section === 'drafts') {
            $sql .= ' AND v.is_published=?';
            $params[] = false;
        }

        return [$sql, $params];
    }

    /**
     * EN: Survey activity uses authorship/review headers only; response tables are outside this query.
     *
     * HR: Aktivnost anketa koristi samo zaglavlja autorstva/pregleda; tablice odgovora nisu dio upita.
     *
     * @return array{string,list<mixed>}
     */
    private function surveys(string $section, int $actor): array
    {
        $activity = $this->ready(['survey_author_activity']);
        $lastContribution = $activity ? 'COALESCE(a.last_at,m.last_at,s.created_at)'
        : 'COALESCE(m.last_at,s.created_at)';
        $at = $section === 'recent' ? $lastContribution : 's.updated_at';
        $sql = "SELECT s.*,s.uuid AS work_id,$at AS work_at,s.primary_language AS work_language,"
        . "CASE WHEN r.state='review' THEN 'in_review' WHEN r.state='draft' THEN 'draft'"
        . ' ELSE s.state END AS work_state,r.title AS work_title,p.owner_kind,p.host_reference FROM survey_surveys s'
        . ' JOIN survey_revisions r ON r.uuid=COALESCE(s.draft_revision_uuid,s.published_revision_uuid)'
        . ' LEFT JOIN survey_placements p ON p.survey_uuid=s.uuid'
        . ' LEFT JOIN (SELECT survey_uuid,MAX(created_at) AS last_at FROM survey_revisions'
        . ' WHERE created_by_user_id=? GROUP BY survey_uuid) m ON m.survey_uuid=s.uuid';
        $params = [$actor];
        if ($activity) {
            $sql .= ' LEFT JOIN survey_author_activity a ON a.survey_uuid=s.uuid AND a.user_id=?';
            $params[] = $actor;
        }

        $sql .= " WHERE s.state NOT IN ('deleted','archived')";
        if ($section === 'review') {
            $sql .= " AND r.state='review'";
        } else {
            $sql .= ' AND (m.survey_uuid IS NOT NULL OR s.owner_user_id=?'
            . ($activity ? ' OR a.user_id IS NOT NULL)' : ')');
            $params[] = $actor;
            if ($section === 'drafts') {
                $sql .= " AND r.state IN ('draft','review')";
            }
        }

        return [$sql, $params];
    }

    /**
     * EN: Aggregates only the actor's own revision timestamps in one bounded relational query.
     *
     * HR: Jednim relacijskim upitom grupira samo vremenske oznake korisnikovih vlastitih revizija.
     */
    private function contributions(): string
    {
        return 'SELECT document_id,language_code,MAX(CASE WHEN updated_by_user_id=?'
        . ' THEN COALESCE(updated_at,created_at) ELSE created_at END) AS last_at'
        . ' FROM editor_html_document_versions WHERE created_by_user_id=? OR updated_by_user_id=?'
        . ' GROUP BY document_id,language_code';
    }

    /**
     * EN: Reuses schema inspection without creating optional data or migrations.
     *
     * HR: Koristi pregled sheme bez stvaranja opcionalnih podataka ili migracija.
     *
     * @param list<string> $tables
     */
    private function ready(array $tables): bool
    {
        foreach ($tables as $table) {
            $this->tablePresence[$table] ??= $this->db->schema()->hasTable($table);
            if (!$this->tablePresence[$table]) {
                return false;
            }
        }

        return true;
    }

    /**
     * EN: Disabled modules cannot leak old content through their remaining tables.
     *
     * HR: Isključeni moduli ne mogu otkriti stari sadržaj kroz preostale tablice.
     */
    private function enabled(string $module): bool
    {
        $enabled = $this->config->getAsArrayWithValuesAsNonEmptyStrings('app.modules.enabled') ?? [];
        return in_array('aaieduhr/' . $module, $enabled, true);
    }
}
