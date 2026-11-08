<?php

declare(strict_types=1);

namespace App\Work;

use DateTimeImmutable;
use DateTimeZone;
use HeartPhrame\Config\ConfigInterface;
use HeartPhrame\Routing\UrlGenerator;

/**
 * EN: Builds personal work lists from current authoring permissions, not historical ownership alone.
 *
 * HR: Gradi osobne radne popise prema važećim autorskim ovlastima, ne samo povijesnom vlasništvu.
 */
final readonly class MyWorkService
{
    private const MAX_CANDIDATE_WINDOWS = 50;

    /**
     * EN: Lists each installed content family independently with bounded server pagination.
     *
     * HR: Svaku instaliranu vrstu sadržaja popisuje zasebno uz ograničenu poslužiteljsku paginaciju.
     */
    public function __construct(
        private MyWorkRepository $repository,
        private MyWorkAccess $access,
        private UrlGenerator $urls,
        private ConfigInterface $config,
        private MyWorkCursor $cursors,
    ) {
    }

    /**
     * EN: Never accepts a user identifier from URL parameters; the controller supplies the trusted identity.
     *
     * HR: Ne prihvaća korisnički identifikator iz URL-a; kontroler predaje pouzdani identitet.
     *
     * @param  array<string,mixed> $user
     * @param  array<string,mixed> $query
     * @return list<array{kind:string,items:list<array<string,string>>,start:int,next:string,more:bool,unavailable:bool}>
     */
    public function lists(array $user, string $locale, string $section, array $query): array
    {
        if (MyWorkValue::integer($user['id'] ?? 0) <= 0) {
            return [];
        }

        $size = $this->pageSize($query['size'] ?? null);
        $result = [];
        foreach ($this->repository->kinds($section) as $kind) {
            $start = $this->cursors->resolve(
                $query[$kind . '_cursor'] ?? null,
                MyWorkValue::integer($user['id']),
                $kind,
                $section,
                $size,
            );
            $offset = $start;
            $items = [];
            $more = false;
            $unavailable = false;
            $windows = 0;
            // EN: Stream fixed-size windows to an authorized next item; hidden rows never create paging hints.
            //
            // HR: Čitaj fiksne prozore do dopuštene sljedeće stavke; skriveni redci ne stvaraju naznake paginacije.
            while (true) {
                // EN: Never silently truncate a personal list or allow unbounded ACL work in one request.
                //
                // HR: Ne skraćivati osobni popis potajno niti dopustiti neograničenu provjeru prava po zahtjevu.
                if (++$windows > self::MAX_CANDIDATE_WINDOWS) {
                    $items = [];
                    $unavailable = true;
                    break;
                }

                $rows = $this->repository->candidates($kind, $section, MyWorkValue::integer($user['id']), $offset);
                $allowed = $this->access->allowed($kind, $section, $rows, $user);
                foreach ($rows as $position => $row) {
                    if ($allowed[$position] ?? false) {
                        if (count($items) >= $size) {
                            $more = true;
                            break 2;
                        }

                        $items[] = $this->item($kind, $row, $locale);
                    }

                    ++$offset;
                }

                if (count($rows) < 40) {
                    break;
                }
            }

            $next = $more ? $this->cursors->issue(
                $offset,
                MyWorkValue::integer($user['id']),
                $kind,
                $section,
                $size,
            ) : '';
            $result[] = ['kind' => $kind, 'items' => $items, 'start' => $start, 'next' => $next,
                'more' => $more, 'unavailable' => $unavailable];
        }

        return $result;
    }

    /**
     * EN: Allows a small standard set; an untrusted URL cannot request an unbounded result list.
     *
     * HR: Dopušta mali standardni skup; nepouzdan URL ne može zatražiti neograničen popis rezultata.
     */
    public function pageSize(mixed $value): int
    {
        return is_scalar($value) && in_array((int)$value, [10, 25, 50], true) ? (int)$value : 10;
    }

    /**
     * EN: Builds links and labels only after access has passed; no stored HTML is rendered here.
     *
     * HR: Gradi poveznice i oznake tek nakon provjere pristupa; ovdje ne prikazuje spremljeni HTML.
     *
     * @param  array<string,mixed> $row
     * @return array<string,string>
     */
    private function item(string $kind, array $row, string $locale): array
    {
        $language = $this->text($row['work_language'] ?? $locale);
        $title = $this->text($row['work_title'] ?? $row['title'] ?? '');
        if ($kind === 'page') {
            $translations = json_decode($this->text($row['title_translations'] ?? ''), true);
            if (
                is_array($translations) && is_string($translations[$language] ?? null)
                && $translations[$language] !== ''
            ) {
                $title = $translations[$language];
            }

            $parameters = ['lang' => $language];
            if (in_array($this->text($row['work_state'] ?? ''), ['draft', 'in_review'], true)) {
                $parameters['draft'] = 'preview';
            }

            $path = $this->urls->getPathFor(
                'workspace.node.show',
                [
                'workspaceSlug' => $this->text($row['workspace_slug']), 'nodeSlug' => $this->text($row['node_slug']),
                ],
                $parameters,
            );
        } elseif ($kind === 'survey') {
            $path = $this->urls->getPathFor('survey.edit', ['survey' => $this->text($row['uuid'])]);
        } else {
            $path = $this->urls->getPathFor(
                'editor-html.index',
                [],
                [
                'document' => $this->text($row['document_key']), 'lang' => $language,
                ],
            );
        }

        $time = $this->text($row['work_at'] ?? '');
        $zone = $this->config->getAsString('app.timezone', 'UTC') ?? 'UTC';
        $date = $time !== '' ? DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i:s',
            $time,
            new DateTimeZone($kind === 'survey' ? 'UTC' : $zone),
        ) : false;
        return ['title' => $title, 'url' => $path, 'language' => $language,
            'state' => $this->text($row['work_state'] ?? ''), 'workspace' => $this->text($row['workspace_title'] ?? ''),
            'time' => $date !== false ? $date->setTimezone(new DateTimeZone($zone))->format('d.m.Y. H:i') : $time,
            'datetime' => $date !== false ? $date->format(DATE_ATOM) : ''];
    }

    /**
     * EN: Accepts scalar metadata only, never implicit array-to-string conversions.
     *
     * HR: Prihvaća samo skalarne metapodatke, bez implicitnih pretvorbi nizova u tekst.
     */
    private function text(mixed $value): string
    {
        return is_scalar($value) ? (string)$value : '';
    }
}
