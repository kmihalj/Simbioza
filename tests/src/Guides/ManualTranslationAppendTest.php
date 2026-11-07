<?php

declare(strict_types=1);

namespace Tests\Guides;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * EN: A new guide language extends the portable archive without changing historical identities or revisions.
 *
 * HR: Novi jezik uputa proširuje prenosivu arhivu bez promjene povijesnih identiteta ili revizija.
 */
#[CoversNothing]
final class ManualTranslationAppendTest extends TestCase
{
    /**
     * EN: Translation appends use document-local revision numbers and preserve all old records.
     *
     * HR: Dodani prijevodi koriste brojeve revizija dokumenta i čuvaju sve stare zapise.
     */
    public function testNewLanguagesAppendDistinctDocumentRevisionsAndLeaveHistoricalRecordsIntact(): void
    {
        require_once dirname(__DIR__, 3) . '/scripts/sync_user_guides.php';
        $versions = [
            ['source_id' => 12, 'document_key' => 'guide', 'language_code' => 'hr',
                'version_number' => 7, 'title' => 'Uputa', 'content_html' => '<p>hr</p>', 'is_published' => 1],
            ['source_id' => 15, 'document_key' => 'guide', 'language_code' => 'en',
                'version_number' => 9, 'title' => 'Guide', 'content_html' => '<p>en</p>', 'is_published' => 1],
            ['source_id' => 50, 'document_key' => 'custom', 'language_code' => 'en',
                'version_number' => 80, 'title' => 'Custom', 'content_html' => '<p>custom</p>', 'is_published' => 1],
        ];
        $workflows = [
            ['node_uuid' => 'historical-node', 'language_code' => 'hr', 'status' => 'published',
                'current_version_number' => 7, 'published_version_number' => 7, 'published_by_user' => 'Author'],
            ['node_uuid' => 'historical-node', 'language_code' => 'en', 'status' => 'published',
                'current_version_number' => 9, 'published_version_number' => 9, 'published_by_user' => 'Author'],
        ];
        $documents = [
            ['document_key' => 'guide', 'uuid' => 'historical-document', 'current_version_source_id' => 15],
            ['document_key' => 'custom', 'uuid' => 'custom-document', 'current_version_source_id' => 50],
        ];
        $original = [$versions, $workflows, $documents];
        foreach (['de', 'es', 'fr', 'it'] as $index => $locale) {
            \appendManualTranslation(
                $versions,
                $workflows,
                $documents,
                ['document_key' => 'guide', 'uuid' => 'historical-node'],
                $locale,
                'Title ' . $locale,
                '<p>' . $locale . '</p>',
            );
            $this->assertSame(10 + $index, $versions[3 + $index]['version_number']);
            $this->assertSame(51 + $index, $versions[3 + $index]['source_id']);
            $this->assertSame(
                $versions[3 + $index]['version_number'],
                $workflows[2 + $index]['published_version_number'],
            );
            $this->assertSame($locale, $workflows[2 + $index]['language_code']);
        }

        $this->assertSame($original[0], array_slice($versions, 0, 3));
        $this->assertSame($original[1], array_slice($workflows, 0, 2));
        $this->assertSame($original[2][1], $documents[1]);
        $this->assertSame('historical-document', $documents[0]['uuid']);
        $this->assertSame(54, $documents[0]['current_version_source_id']);
        $this->expectException(RuntimeException::class);
        \appendManualTranslation(
            $versions,
            $workflows,
            $documents,
            ['document_key' => 'guide', 'uuid' => 'historical-node'],
            'de',
            'Duplicate',
            '<p>Duplicate</p>',
        );
    }
}
