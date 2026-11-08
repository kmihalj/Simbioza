<?php

declare(strict_types=1);

namespace App\Work;

use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorPagePermissions;
use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleSurvey\Contract\SurveyActorProviderInterface;
use AaiEduHr\HeartPhrameModuleSurvey\Service\SurveyAuthorization;
use AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceAccessService;
use AaiEduHr\SimbiozaModuleWorkspace\Service\WorkspaceRepository;
use HeartPhrame\Config\ConfigInterface;
use Psr\Container\ContainerInterface;

/**
 * EN: Rechecks current owning-module capabilities in batches before exposing any authoring header.
 *
 * HR: Skupno provjerava važeće ovlasti matičnog modula prije otkrivanja autorskih metapodataka.
 */
final readonly class MyWorkAccess
{
    /**
     * EN: Optional domain services are resolved only for the requested enabled content family.
     *
     * HR: Opcionalni domenski servisi razrješavaju se samo za traženu uključenu vrstu sadržaja.
     */
    public function __construct(
        private ContainerInterface $container,
        private Database $db,
        private ConfigInterface $config,
    ) {
    }

    /**
     * EN: Returns permitted source row positions without retaining revoked permissions across requests.
     *
     * HR: Vraća položaje dopuštenih izvornih redaka bez zadržavanja opozvanih prava kroz zahtjeve.
     *
     * @param list<array<string,mixed>> $rows
     * @param array<string,mixed> $user
     * @return array<int,bool>
     */
    public function allowed(string $kind, string $section, array $rows, array $user): array
    {
        if ($rows === []) {
            return [];
        }

        if ($kind === 'html') {
            $permissions = $this->service(EditorPagePermissions::class)->writePermissionsForDocuments($rows);
            return array_map(
                static fn(array $row): bool => $permissions[MyWorkValue::text($row['document_key'])] ?? false,
                $rows,
            );
        }

        if ($kind === 'page') {
            $rights = $this->workspaceRights(array_column($rows, 'node_id'), $user);
            return array_map(
                fn(array $row): bool => $this->mayWork($rights[MyWorkValue::integer($row['node_id'])] ?? [], $section),
                $rows,
            );
        }

        $standalone = [];
        $references = [];
        foreach ($rows as $row) {
            if ($row['owner_kind'] === null) {
                $standalone[] = $row;
            } elseif ($row['owner_kind'] === 'workspace') {
                $references[] = MyWorkValue::text($row['host_reference']);
            }
        }

        $surveyActor = $this->service(SurveyActorProviderInterface::class)->resolve();
        $decisions = $this->service(SurveyAuthorization::class)->standaloneDecisions($surveyActor, $standalone);
        $nodes = $this->placedNodeIds($references);
        $rights = $this->workspaceRights(array_values($nodes), $user);
        $allowed = [];
        foreach ($rows as $index => $row) {
            if ($row['owner_kind'] !== null) {
                $allowed[$index] = $row['owner_kind'] === 'workspace'
                && $this->mayWork($rights[$nodes[MyWorkValue::text($row['host_reference'])] ?? 0] ?? [], $section);
            } else {
                $decision = $decisions[MyWorkValue::text($row['uuid'])] ?? [];
                $allowed[$index] = (bool)($decision[$section === 'review' ? 'publish' : 'preview'] ?? false);
            }
        }

        return $allowed;
    }

    /**
     * EN: Resolves host node UUIDs together; unavailable Workspace integration never falls back to standalone rights.
     *
     * HR: Skupno razrješava UUID-e čvorova domaćina; nedostupna integracija ne vraća samostalna prava.
     *
     * @param list<string> $references
     * @return array<string,int>
     */
    private function placedNodeIds(array $references): array
    {
        if ($references === [] || !$this->workspaceEnabled()) {
            return [];
        }

        $parameters = array_values(array_unique($references));
        $rows = $this->db->fetchAll('SELECT id,uuid FROM workspace_nodes WHERE is_enabled=? AND uuid IN ('
            . implode(',', array_fill(0, count($parameters), '?')) . ')', [true, ...$parameters]);
        $ids = [];
        foreach ($rows as $row) {
            $ids[MyWorkValue::text($row['uuid'])] = (int)$row['id'];
        }

        return $ids;
    }

    /**
     * EN: Loads complete ancestor paths and evaluates each Workspace in bulk, never one query per result.
     *
     * HR: Učitava potpune putove predaka i skupno provjerava svako Područje, bez upita po rezultatu.
     *
     * @param list<mixed> $ids
     * @param array<string,mixed> $user
     * @return array<int,array<string,bool>>
     */
    private function workspaceRights(array $ids, array $user): array
    {
        if ($ids === [] || !$this->workspaceEnabled()) {
            return [];
        }

        $repository = $this->service(WorkspaceRepository::class);
        $access = $this->service(WorkspaceAccessService::class);
        $nodeIds = array_values(array_unique(array_map(MyWorkValue::integer(...), $ids)));
        $nodes = $repository->nodesWithAncestorsByIds($nodeIds);
        $groups = [];
        foreach ($nodes as $node) {
            $groups[MyWorkValue::integer($node['workspace_id'])][] = $node;
        }

        $rights = [];
        foreach ($repository->workspacesByIds(array_keys($groups)) as $workspace) {
            if ((bool)$workspace['is_archived']) {
                continue;
            }

            $workspaceNodes = $groups[MyWorkValue::integer($workspace['id'])];
            $rights += $access->nodePermissionsForNodes($workspace, $workspaceNodes, $user);
        }

        $byId = [];
        foreach ($nodes as $node) {
            $byId[MyWorkValue::integer($node['id'])] = $node;
        }

        foreach ($rights as $id => $unused) {
            if (!$this->completePath($id, $byId)) {
                unset($rights[$id]);
            }
        }

        return $rights;
    }

    /**
     * EN: An incomplete, cross-Workspace or cyclic ancestor chain never becomes an access grant.
     *
     * HR: Nepotpun, međupodručni ili ciklički lanac predaka nikada ne postaje ovlast pristupa.
     *
     * @param array<int,array<string,mixed>> $nodes
     */
    private function completePath(int $id, array $nodes): bool
    {
        $workspace = MyWorkValue::integer($nodes[$id]['workspace_id'] ?? 0);
        $seen = [];
        while ($id > 0) {
            if (
                !isset($nodes[$id]) || isset($seen[$id]) || count($seen) >= 256
                || MyWorkValue::integer($nodes[$id]['workspace_id']) !== $workspace
            ) {
                return false;
            }

            $seen[$id] = true;
            $id = MyWorkValue::integer($nodes[$id]['parent_id'] ?? 0);
        }

        return $workspace > 0;
    }

    /**
     * EN: Authorship is not an access grant; review requires publishing permission now.
     *
     * HR: Autorstvo nije ovlast pristupa; pregled zahtijeva trenutačno pravo objavljivanja.
     *
     * @param array<string,bool> $rights
     */
    private function mayWork(array $rights, string $section): bool
    {
        return ($rights['can_view'] ?? false) && ($section === 'review'
            ? ($rights['can_publish'] ?? false)
            : (($rights['can_edit'] ?? false)
                || ($rights['can_publish'] ?? false) || ($rights['can_manage'] ?? false)));
    }

    /**
     * EN: Requires the optional host module to remain enabled and its schema ready.
     *
     * HR: Zahtijeva da opcionalni domaćin ostane uključen i da njegova shema bude spremna.
     */
    private function workspaceEnabled(): bool
    {
        $enabled = $this->config->getAsArrayWithValuesAsNonEmptyStrings('app.modules.enabled') ?? [];
        return in_array('aaieduhr/simbioza-module-workspace', $enabled, true)
        && class_exists(WorkspaceRepository::class) && $this->db->schema()->hasTable('workspace_nodes');
    }

    /**
     * EN: Validates the lazily resolved public service contract.
     *
     * HR: Provjerava ugovor lijeno razriješenog javnog servisa.
     *
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    private function service(string $class): object
    {
        $service = $this->container->get($class);
        if (!$service instanceof $class) {
            throw new \LogicException('Invalid My Work service: ' . $class);
        }

        return $service;
    }
}
