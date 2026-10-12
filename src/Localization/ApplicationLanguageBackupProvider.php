<?php

declare(strict_types=1);

namespace App\Localization;

use AaiEduHr\HeartPhrameModuleBackup\Contract\BackupCommitAwareProviderInterface;
use AaiEduHr\HeartPhrameModuleBackup\Contract\BackupProviderInterface;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveReader;
use AaiEduHr\HeartPhrameModuleBackup\Service\BackupArchiveWriter;
use AaiEduHr\HeartPhrameModuleBackup\Service\StructuredConfigBackupProvider;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupExportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupImportContext;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupPreflightResult;
use AaiEduHr\HeartPhrameModuleBackup\Value\BackupProviderMetadata;
use App\Configuration\ConfigurationPaths;
use RuntimeException;
use Throwable;

/**
 * EN: The application's settings provider owns language data, registry, and transactional rollback.
 * It wraps the generic configuration provider; Backup and Framework remain unchanged.
 *
 * HR: Provider postavki aplikacije upravlja jezičnim podacima, registrom i transakcijskim rollbackom.
 * Omata generički konfiguracijski provider; Backup i Framework ostaju nepromijenjeni.
 *
 * @phpstan-type LanguageRow array{locale:string,definition:array{names:array<string,string>,native_name:string,flag:string},translations:array<string,string>|null,flag_svg:string|null,revision:array{version:string,sha256:string}|null}
 */
final class ApplicationLanguageBackupProvider implements BackupProviderInterface, BackupCommitAwareProviderInterface
{
    /** @var list<resource> */
    private array $locks = [];

    /** @var array<string,array{bytes:string|null,mode:int}> */
    private array $original = [];

    private ?string $originalRegistry = null;

    private bool $registryPublished = false;

    private int $registryMode = 0660;

    /**
     * EN: Paths and callbacks are local application policy, never archive-controlled.
     *
     * HR: Putanje i callbackovi su lokalna politika aplikacije, nikada odabrani arhivom.
     */
    public function __construct(
        private readonly StructuredConfigBackupProvider $configuration,
        private readonly ConfigurationPaths $paths,
        private readonly LanguagePackManager $languages,
        private readonly RuntimeLanguageStorage $storage,
    ) {
    }

    /**
     * EN: Keeps application settings as one component with the current schema.
     *
     * HR: Postavke aplikacije ostaju jedna komponenta s aktualnom shemom.
     */
    public function metadata(): BackupProviderMetadata
    {
        return $this->configuration->metadata();
    }

    /**
     * EN: Exports data-only catalogues, safe flags, locale metadata, and optional revisions.
     * Bundled PHP, cache, downloads, absolute paths, and locks never enter the archive.
     *
     * HR: Izvozi podatkovne kataloge, sigurne zastavice, jezične metapodatke i opcionalne revizije.
     * Paketni PHP, cache, preuzimanja, apsolutne putanje i lockovi ne ulaze u arhiv.
     */
    public function export(BackupExportContext $context, BackupArchiveWriter $writer): void
    {
        $this->configuration->export($context, $writer);
        $versions = $this->storage->versions();
        foreach ($this->languages->languageRegistry() as $locale => $definition) {
            $flag = $this->storage->bytes('flags/' . $locale . '.svg');
            $writer->writeRecord($this->metadata()->id, 'languages', [
                'locale' => $locale, 'definition' => $definition,
                'translations' => $this->storage->installed($locale) ? $this->storage->catalog($locale) : null,
                'flag_svg' => $flag === null ? null : LanguagePackManager::validatedFlagSvg($flag),
                'revision' => $versions[$locale] ?? null,
            ]);
        }
    }

    /**
     * EN: Rejects malformed, unsafe, oversized, duplicate, or uninstalled locale records before writes.
     *
     * HR: Prije zapisa odbija nevaljane, nesigurne, prevelike, ponovljene ili neinstalirane jezične zapise.
     */
    public function preflight(BackupImportContext $context, BackupArchiveReader $reader): BackupPreflightResult
    {
        $result = $this->configuration->preflight($context, $reader);
        $errors = $result->errors;
        try {
            $rows = $this->rows($reader);
            $this->paths->writableFile('languages.php');
            $this->paths->writableFile('.languages.lock');
            foreach ($this->resources($rows, $context) as $relative) {
                $this->storage->assertWritable($relative);
            }
        } catch (Throwable $error) {
            $errors[] = $error->getMessage();
            $rows = [];
        }

        return new BackupPreflightResult($errors, $result->warnings, [
            ...$result->summary, 'languages' => count($rows),
        ]);
    }

    /**
     * EN: Acquires the same configuration and data locks as GUI/CLI, before provider preparation.
     * The wrapped configuration definitions omit only this centrally owned language lock.
     *
     * HR: Prije pripreme providera uzima iste konfiguracijske i podatkovne lockove kao GUI/CLI.
     * Omotane konfiguracijske definicije izostavljaju samo ovaj centralno upravljani jezični lock.
     */
    public function prepareImport(BackupImportContext $context, BackupArchiveReader $reader): void
    {
        $this->rows($reader);
        $path = $this->paths->writableFile('.languages.lock');
        $handle = fopen($path, 'c+b');
        if ($handle === false) {
            throw new RuntimeException('Unable to open the language configuration lock.');
        }

        $this->locks[] = $handle;
        try {
            if (!chmod($path, 0660) || !flock($handle, LOCK_EX | LOCK_NB)) {
                throw new RuntimeException('Language configuration is being edited.');
            }

            $this->locks[] = $this->storage->lock();
            $this->configuration->prepareImport($context, $reader);
        } catch (Throwable $error) {
            $this->release();
            throw $error;
        }
    }

    /**
     * EN: Stages validated language data in memory without publishing files.
     *
     * HR: Priprema provjerene jezične podatke u memoriji bez objave datoteka.
     */
    public function import(BackupImportContext $context, BackupArchiveReader $reader): void
    {
        $this->configuration->import($context, $reader);
        $context->state->put('application-language-state', $this->rows($reader));
    }

    /**
     * EN: Publishes data and registry in the transactional phase; any later failure rolls them back.
     * Replace removes absent runtime packs; merge keeps local-only locales. Stock code never changes.
     *
     * HR: Objavljuje podatke i registar u transakcijskoj fazi; kasnija greška vraća izvorno stanje.
     * Replace uklanja izostavljene runtime pakete; merge čuva lokalne jezike. Paketni kod se ne mijenja.
     */
    public function finalizeImport(BackupImportContext $context, BackupArchiveReader $reader): void
    {
        $this->configuration->finalizeImport($context, $reader);
        $rows = $this->rows($reader);
        $registryPath = $this->paths->writableFile('languages.php');
        $this->originalRegistry = is_file($registryPath) ? (string)file_get_contents($registryPath) : null;
        $this->registryMode = is_file($registryPath) ? fileperms($registryPath) & 0777 : 0660;
        foreach ($this->resources($rows, $context) as $relative) {
            $path = $this->storage->path($relative);
            $this->original[$relative] = ['bytes' => $this->storage->bytes($relative),
                'mode' => is_file($path) ? fileperms($path) & 0777 : 0660];
        }

        $replace = $context->conflictMode === BackupImportContext::CONFLICT_REPLACE;
        $registry = $replace ? [] : $this->languages->languageRegistry();
        $versions = $replace ? [] : $this->storage->versions();
        if ($replace) {
            foreach ($this->resources($rows, $context) as $relative) {
                if ($relative !== 'repository-versions.json') {
                    $this->storage->remove($relative);
                }
            }
        }

        foreach ($rows as $locale => $row) {
            $registry[$locale] = $row['definition'];
            if ($row['translations'] !== null) {
                $this->storage->install($locale, $row['translations']);
            } else {
                $this->storage->remove('catalogs/' . $locale . '.json');
            }

            if ($row['flag_svg'] !== null) {
                $this->storage->write('flags/' . $locale . '.svg', $row['flag_svg'] . "\n");
            } else {
                $this->storage->remove('flags/' . $locale . '.svg');
            }

            unset($versions[$locale]);
            if ($row['revision'] !== null) {
                $versions[$locale] = $row['revision'];
            }
        }

        $this->storage->writeVersions($versions);
        $this->registryPublished = true;
        $this->languages->writeRegistry($registry);
    }

    /**
     * EN: Restores previous resource bytes and permissions, then releases both provider locks.
     *
     * HR: Vraća prethodne bajtove i prava resursa, pa oslobađa lockove oba providera.
     */
    public function abortImport(BackupImportContext $context): void
    {
        try {
            foreach ($this->original as $relative => $entry) {
                $entry['bytes'] === null ? $this->storage->remove($relative)
                : $this->storage->write($relative, $entry['bytes'], $entry['mode']);
            }

            if ($this->registryPublished) {
                $path = $this->paths->writableFile('languages.php');
                if ($this->originalRegistry === null) {
                    if (is_file($path)) {
                        unlink($path);
                    }
                } else {
                    $temporary = tempnam(dirname($path), '.language-rollback-');
                    if (!is_string($temporary)) {
                        throw new RuntimeException('Unable to roll back the language registry.');
                    }

                    try {
                        if (
                            file_put_contents($temporary, $this->originalRegistry) === false
                            || !chmod($temporary, $this->registryMode) || !rename($temporary, $path)
                        ) {
                            throw new RuntimeException('Unable to roll back the language registry.');
                        }
                    } finally {
                        if (is_file($temporary)) {
                            unlink($temporary);
                        }
                    }

                    if (function_exists('opcache_invalidate')) {
                        opcache_invalidate($path, true);
                    }
                }
            }
        } finally {
            try {
                $this->configuration->abortImport($context);
            } finally {
                $this->clear($context);
            }
        }
    }

    /**
     * EN: After commit, drops rollback copies without changing restored data.
     *
     * HR: Nakon commita uklanja rollback kopije bez izmjene vraćenih podataka.
     */
    public function completeImport(BackupImportContext $context): void
    {
        try {
            $this->configuration->completeImport($context);
        } finally {
            $this->clear($context);
        }
    }

    /**
     * EN: Enumerates fixed data targets, including removed locales only for explicit replace.
     *
     * HR: Navodi fiksne podatkovne ciljeve; uklonjene jezike uključuje samo za izričiti replace.
     *
     * @param array<string,LanguageRow> $rows
     * @return list<string>
     */
    private function resources(array $rows, BackupImportContext $context): array
    {
        $locales = array_keys($rows);
        if ($context->conflictMode === BackupImportContext::CONFLICT_REPLACE) {
            $locales = array_unique([...$locales, ...$this->storage->locales(),
                ...array_keys($this->languages->languageRegistry())]);
        }

        $result = ['repository-versions.json'];
        foreach ($locales as $locale) {
            $result[] = 'catalogs/' . $locale . '.json';
            $result[] = 'flags/' . $locale . '.svg';
        }

        return $result;
    }

    /**
     * EN: Normalizes all archive records before any destination mutation, without PHP evaluation.
     *
     * HR: Normalizira sve arhivske zapise prije izmjene odredišta, bez izvršavanja PHP-a.
     *
     * @return array<string,LanguageRow>
     */
    private function rows(BackupArchiveReader $reader): array
    {
        $rows = [];
        $bytes = 0;
        foreach ($reader->records($this->metadata()->id, 'languages') as $record) {
            $locale = RuntimeLanguageStorage::assertLocale(
                is_string($record['locale'] ?? null) ? $record['locale'] : '',
            );
            $definition = $record['definition'] ?? null;
            if (
                isset($rows[$locale]) || !is_array($definition) || !is_array($definition['names'] ?? null)
                || !is_string($definition['native_name'] ?? null) || trim($definition['native_name']) === ''
                || ($definition['flag'] ?? null) !== $locale . '.svg'
            ) {
                throw new RuntimeException('Invalid or duplicate language registry record.');
            }

            $names = [];
            foreach ($definition['names'] as $nameLocale => $name) {
                if (!is_string($nameLocale) || !is_string($name) || trim($name) === '' || mb_strlen($name) > 100) {
                    throw new RuntimeException('Invalid localized language name.');
                }

                $names[RuntimeLanguageStorage::assertLocale($nameLocale)] = $name;
            }

            if (($names[$locale] ?? null) !== $definition['native_name']) {
                throw new RuntimeException('Invalid native language name.');
            }

            $translations = ($record['translations'] ?? null) === null ? null
            : RuntimeLanguageStorage::translations($record['translations']);
            if ($translations === null && !in_array($locale, ['hr', 'en'], true)) {
                throw new RuntimeException('The archive is missing an installed language catalogue.');
            }

            $flag = $record['flag_svg'] ?? null;
            if ($flag !== null && !is_string($flag)) {
                throw new RuntimeException('Invalid language flag.');
            }

            $revision = ($record['revision'] ?? null) === null ? null
            : RuntimeLanguageStorage::validateVersions([$locale => $record['revision']])[$locale];
            $row = ['locale' => $locale, 'definition' => ['names' => $names,
                'native_name' => $definition['native_name'], 'flag' => $locale . '.svg'],
                'translations' => $translations,
                'flag_svg' => $flag === null ? null : LanguagePackManager::validatedFlagSvg($flag),
                'revision' => $revision];
            $size = strlen(json_encode($row, JSON_THROW_ON_ERROR));
            $bytes += $size;
            if ($size > RuntimeLanguageStorage::MAX_BYTES || $bytes > 33554432) {
                throw new RuntimeException('Language backup exceeds the data-size limit.');
            }

            $rows[$locale] = $row;
        }

        if ($rows === []) {
            throw new RuntimeException('The application settings archive is missing language state.');
        }

        return $rows;
    }

    /**
     * EN: Releases only handles created by this provider.
     *
     * HR: Oslobađa samo handleove koje je stvorio ovaj provider.
     */
    private function release(): void
    {
        foreach (array_reverse($this->locks) as $lock) {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        $this->locks = [];
    }

    /**
     * EN: Clears per-import memory after abort or commit.
     *
     * HR: Čisti memoriju pojedinog importa nakon prekida ili commita.
     */
    private function clear(BackupImportContext $context): void
    {
        $this->original = [];
        $this->originalRegistry = null;
        $this->registryPublished = false;
        $context->state->forget('application-language-state');
        $this->release();
    }
}
