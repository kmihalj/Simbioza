<?php

declare(strict_types=1);

namespace Tests\Tools;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
final class DocumentationAuditTest extends TestCase
{
    /**
     * EN: Loads the importable audit entry point without running its CLI main function.
     *
     * HR: Učitava audit bez pokretanja njegove glavne CLI funkcije.
     */
    protected function setUp(): void
    {
        parent::setUp();
        require_once dirname(__DIR__, 3) . '/scripts/audit_module_documentation.php';
    }

    /**
     * EN: All four extra locales are accepted only for registered application sources;
     *     other documents and untranslated manual chapters still need only EN/HR pairs.
     *
     * HR: Sva četiri dodatna jezika dopuštena su samo za registrirane aplikacijske izvore;
     *     ostali dokumenti i neprevedena poglavlja i dalje trebaju samo EN/HR parove.
     */
    public function testAcceptsOptionalTranslationsOfRegisteredApplicationManuals(): void
    {
        $documents = [
            'docs/reference_en.md',
            'docs/reference_hr.md',
            'docs/user-guide/another_en.md',
            'docs/user-guide/another_hr.md',
        ];
        foreach (['en', 'hr', 'de', 'es', 'fr', 'it'] as $locale) {
            $documents[] = 'docs/user-guide/guide_' . $locale . '.md';
        }

        $issues = $this->auditDocumentFixture($documents, [
            'docs/user-guide/guide_en.md',
            'docs/user-guide/another_en.md',
        ]);
        $this->assertSame([], $issues);
    }

    /**
     * EN: A registered translation needs its English file; unsupported suffixes and
     *     translations of generic documents cannot bypass the original pair rule.
     *
     * HR: Registrirani prijevod treba englesku datoteku; nepodržani nastavci i prijevodi
     *     generičkih dokumenata ne mogu zaobići izvorno pravilo jezičnih parova.
     */
    public function testRejectsOrphanUnsupportedAndGenericTranslations(): void
    {
        $documents = [
            'docs/reference_en.md',
            'docs/reference_hr.md',
            'docs/reference_de.md',
            'docs/user-guide/guide_en.md',
            'docs/user-guide/guide_hr.md',
            'docs/user-guide/guide_pt.md',
        ];
        foreach (['de', 'es', 'fr', 'it'] as $locale) {
            $documents[] = 'docs/user-guide/orphan_' . $locale . '.md';
        }

        $issues = $this->auditDocumentFixture($documents, [
            'docs/user-guide/guide_en.md',
            'docs/user-guide/orphan_en.md',
        ]);
        $this->assertCount(6, $issues);
        $this->assertContains('fixture: legacy mixed or unpaired document reference_de.md', $issues);
        $this->assertContains('fixture: legacy mixed or unpaired document guide_pt.md', $issues);
        foreach (['de', 'es', 'fr', 'it'] as $locale) {
            $this->assertContains(
                'fixture: missing English source orphan_en.md for orphan_' . $locale . '.md',
                $issues,
            );
        }
    }

    /**
     * EN: Registering a manual source never relaxes English/Croatian pairs, and
     *     a module with a matching English file still cannot add other locales.
     *
     * HR: Registriranje izvora ne ublažava englesko-hrvatske parove, a modul ni uz
     *     odgovarajući engleski dokument ne smije dodavati ostale jezične nastavke.
     */
    public function testKeepsEnglishCroatianPairsStrictForManualsAndModules(): void
    {
        $issues = $this->auditDocumentFixture([
            'docs/user-guide/manual_en.md',
            'docs/generic_en.md',
            'docs/orphan_hr.md',
        ], ['docs/user-guide/manual_en.md']);
        $this->assertContains('fixture: missing manual_hr.md', $issues);
        $this->assertContains('fixture: missing generic_hr.md', $issues);
        $this->assertContains('fixture: missing orphan_en.md', $issues);
        $this->assertContains('fixture: EN/HR document count differs (2/1)', $issues);

        $issues = $this->auditDocumentFixture([
            'docs/guide_en.md',
            'docs/guide_hr.md',
            'docs/guide_it.md',
        ]);
        $this->assertSame(['fixture: legacy mixed or unpaired document guide_it.md'], $issues);
    }

    /**
     * EN: Audits a disposable tree with the same optional manifest input as the
     *     application entry point; cleanup removes only this newly created tree.
     *
     * HR: Provjerava privremeno stablo s istim neobveznim manifestom kao aplikacijski
     *     ulaz; čišćenje uklanja samo ovo novostvoreno stablo.
     *
     * @param list<string> $documents
     * @param list<string> $manualEnglishSources
     * @return list<string>
     */
    private function auditDocumentFixture(array $documents, array $manualEnglishSources = []): array
    {
        $directory = sys_get_temp_dir() . '/simbioza-locale-docs-' . bin2hex(random_bytes(8));
        $this->assertTrue(mkdir($directory, 0700));
        try {
            foreach (array_merge($documents, ['README.md', 'README_hr.md']) as $document) {
                $path = $directory . '/' . $document;
                if (!is_dir(dirname($path))) {
                    mkdir(dirname($path), 0700, true);
                }

                file_put_contents($path, "# Documentation\n");
            }

            $sources = [];
            if ($manualEnglishSources !== []) {
                mkdir($directory . '/resources/installation', 0700, true);
                file_put_contents(
                    $directory . '/resources/installation/manual-sources.json',
                    json_encode([['sources' => ['en' => $manualEnglishSources]]], JSON_THROW_ON_ERROR),
                );
                $sources = \applicationManualEnglishSources($directory);
            }

            $issues = [];
            \auditDocumentationPairs($directory, 'fixture', $issues, $sources);

            return $issues;
        } finally {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    rmdir($file->getPathname());
                } else {
                    unlink($file->getPathname());
                }
            }

            rmdir($directory);
        }
    }

    /**
     * EN: CRLF examples are parsed as separate fences without consuming subsequent prose.
     *
     * HR: CRLF primjeri razdvajaju se prema ogradama bez uključivanja kasnijeg teksta.
     */
    public function testParsesCompleteExamplesAndAcceptsExplicitFragments(): void
    {
        $issues = [];
        \auditDocumentationExamples(
            "```php\r\n<?php return [];\r\n```\r\nText.\r\n"
            . "```json\r\n{\"enabled\":true}\r\n```\r\n"
            . "```php\n'modules' => [],\n```\n",
            'module/docs_en.md',
            $issues,
        );
        $this->assertSame([], $issues);
        \auditDocumentationExamples(
            "```php\n<?php return [;\n```\n```json\n{\"enabled\":}\n```\n",
            'module/docs_en.md',
            $issues,
        );
        $this->assertCount(2, $issues);
        $this->assertStringContainsString('invalid php example', (string) $issues[0]);
        $this->assertStringContainsString('invalid json example', (string) $issues[1]);
    }

    /**
     * EN: Historical syntax is not current guidance and requires a visible boundary notice.
     *
     * HR: Povijesna sintaksa nije aktualna uputa i zahtijeva vidljivu napomenu o granici.
     */
    public function testRequiresHistoricalReleaseNotice(): void
    {
        $issues = [];
        \auditDocumentationExamples('Old instructions.', 'app/release-0.2.12_en.md', $issues);
        $this->assertCount(1, $issues);
        foreach (['Historical release note', 'Povijesna bilješka izdanja'] as $notice) {
            $issues = [];
            \auditDocumentationExamples($notice . "\n```json\ninvalid\n```", 'app/release-0.2.12_hr.md', $issues);
            $this->assertSame([], $issues);
        }
    }

    /**
     * EN: The current release remains current guidance and must validate its examples.
     *
     * HR: Aktualno izdanje ostaje aktualna uputa i mora provjeravati primjere.
     */
    public function testCurrentReleaseExamplesAreValidated(): void
    {
        $version = trim((string)file_get_contents(dirname(__DIR__, 3) . '/VERSION'));
        $issues = [];
        \auditDocumentationExamples("```json\ninvalid\n```", 'app/release-' . $version . '_en.md', $issues);
        $this->assertCount(1, $issues);
    }

    /**
     * EN: Independent module releases do not inherit the application's version, but examples remain checked.
     *
     * HR: Samostalna izdanja modula ne nasljeđuju verziju aplikacije, ali primjeri se i dalje provjeravaju.
     */
    public function testModuleReleaseVersionIsIndependentAndExamplesRemainValidated(): void
    {
        $issues = [];
        \auditDocumentationExamples(
            "# API 0.3.1\n```json\n{\"enabled\":true}\n```",
            'module/release-0.3.1_en.md',
            $issues,
            false,
        );
        $this->assertSame([], $issues);
        \auditDocumentationExamples(
            "```json\ninvalid\n```",
            'module/release-0.3.1_en.md',
            $issues,
            false,
        );
        $this->assertCount(1, $issues);
        $this->assertStringContainsString('invalid json example', (string)$issues[0]);
    }

    /**
     * EN: Starting examples need valid JSON and two non-empty ordered language sections.
     *
     * HR: Početni primjeri trebaju valjan JSON i dva neprazna uređena jezična odjeljka.
     */
    public function testChecksJsonStartingExampleDescriptions(): void
    {
        $issues = [];
        \auditJsonStartingExample('{"_documentation":{"en":"Example","hr":"Primjer"}}', 'valid', $issues);
        $this->assertSame([], $issues);
        \auditJsonStartingExample('{"_description_en":"Example","_description_hr":"Primjer"}', 'valid', $issues);
        $this->assertSame([], $issues);
        foreach (
            ['{', '{}', '{"_documentation":{"hr":"Primjer","en":"Example"}}',
            '{"_documentation":{"en":"","hr":"Primjer"}}',
            '{"_description_hr":"Primjer","_description_en":"Example"}'] as $text
        ) {
            $issues = [];
            \auditJsonStartingExample($text, 'invalid', $issues);
            $this->assertCount(1, $issues);
        }
    }

    /**
     * EN: README anchors use Unicode heading IDs, duplicate suffixes and explicit HTML anchors.
     * Only this test's newly created temporary directory is removed.
     *
     * HR: README sidra koriste Unicode naslove, nastavke duplikata i izričita HTML sidra.
     * Uklanja se samo novi privremeni direktorij ovog testa.
     */
    public function testChecksReadmeHeadingTargetsWithoutFetchingExternalLinks(): void
    {
        $directory = sys_get_temp_dir() . '/simbioza-a5-docs-' . bin2hex(random_bytes(8));
        $this->assertTrue(mkdir($directory, 0700));
        try {
            file_put_contents($directory . '/guide.md', "# Čista instalacija\n## Save / Update / Delete\n"
                . "## Save / Update / Delete\n<a id=\"custom\"></a>\n```php\n# not-a-heading\n```\n");
            file_put_contents($directory . '/README.md', '[OK](guide.md#čista-instalacija)'
                . "\n[OK](guide.md#save--update--delete)\n[OK](guide.md#save--update--delete-1)"
                . "\n[OK](guide.md#custom)\n[External](https://example.test/guide.md#missing)"
                . "\n[Wrong](guide.md#missing)\n[Code is not a heading](guide.md#not-a-heading)\n");
            $issues = [];
            \auditDocumentationAnchors($directory . '/README.md', 'fixture/README.md', $issues);
            $this->assertCount(2, $issues);
            $this->assertStringContainsString('#missing', (string) $issues[0]);
            $this->assertStringContainsString('#not-a-heading', (string) $issues[1]);
        } finally {
            unlink($directory . '/README.md');
            unlink($directory . '/guide.md');
            rmdir($directory);
        }
    }
}
