<?php

declare(strict_types=1);

namespace App\Navigation;

use AaiEduHr\HeartPhrameModuleOrm\Database\Database;

/**
 * EN: Detects standalone content without reading bodies, titles, audiences or entire inventories.
 *
 * HR: Otkriva samostalni sadržaj bez čitanja tijela, naslova, publike ili cijelih popisa.
 */
final readonly class StandaloneContentPresence
{
    /**
     * EN: Uses the mandatory ORM and tolerates optional schemas not installed yet.
     *
     * HR: Koristi obavezni ORM i podržava opcionalne sheme koje još nisu instalirane.
     */
    public function __construct(private Database $db)
    {
    }

    /**
     * EN: Checks each catalogue independently; Workspace-owned, deleted and archived content does not qualify.
     * Drafts qualify so authors retain their existing catalogue entry while preparing publication.
     *
     * HR: Svaki katalog provjerava zasebno; sadržaj područja, obrisani i arhivirani sadržaj ne ulaze.
     * Nacrti ulaze kako bi autori zadržali postojeći ulaz u katalog tijekom pripreme objave.
     */
    public function existsForRoute(string $route): bool
    {
        if ($route === 'content.pages' && $this->db->schema()->hasTable('editor_html_documents')) {
            $owner = $this->db->schema()->hasTable('workspace_nodes')
            ? ' AND NOT EXISTS (SELECT 1 FROM workspace_nodes n WHERE n.document_key=d.document_key)' : '';
            return is_array($this->db->fetchOne('SELECT d.id FROM editor_html_documents d '
                . 'WHERE d.is_enabled=? AND d.is_deleted=?' . $owner . ' LIMIT 1', [true, false]));
        }

        if ($route === 'content.surveys' && $this->db->schema()->hasTable('survey_surveys')) {
            $owner = $this->db->schema()->hasTable('survey_placements')
            ? ' AND NOT EXISTS (SELECT 1 FROM survey_placements p WHERE p.survey_uuid=s.uuid)' : '';
            return is_array($this->db->fetchOne('SELECT s.uuid FROM survey_surveys s '
                . 'WHERE s.state<>?' . $owner . ' LIMIT 1', ['archived']));
        }

        return false;
    }
}
