<?php

declare(strict_types=1);

namespace App\Module;

use AaiEduHr\HeartPhrameModuleOrm\Database\Database;
use AaiEduHr\HeartPhrameModuleOrm\Database\Migration\Migrator;
use AaiEduHr\HeartPhrameModuleOrm\Database\MigrationInterface;
use Composer\InstalledVersions;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

use function array_fill_keys;
use function array_keys;
use function array_reverse;
use function array_values;
use function file_get_contents;
use function glob;
use function is_array;
use function is_file;
use function is_scalar;
use function is_string;
use function method_exists;
use function pathinfo;
use function preg_match;
use function rtrim;
use function sort;
use function str_contains;
use function str_replace;
use function trim;

use const PATHINFO_FILENAME;

/**
 * HR: Provodi siguran životni ciklus opcionalnih modula: provjeru ovisnosti,
 *     uključivanje, isključivanje, NDJSON backup, uklanjanje sheme i povrat.
 * EN: Executes the safe optional-module lifecycle: dependency checks, enable,
 *     disable, NDJSON backup, schema removal, and restoration.
 *
 * @phpstan-type ModuleDefinition array{
 *     package:string,
 *     label_hr:string,
 *     label_en:string,
 *     optional:bool,
 *     recommended:bool,
 *     dependencies:list<string>,
 *     migrations:list<string>,
 *     tables:list<string>
 * }
 */
final readonly class ModuleLifecycleManager
{
    /** HR: Prima sve trajne granice potrebne upravljanju modulima. EN: Receives every durable boundary needed for module management. */
    public function __construct(
        private Database $database,
        private ModuleCatalog $catalog,
        private ModuleStateStore $state,
        private ModuleDataArchive $archives,
        private string $appRoot,
        private ?ComposerPackageManager $packages = null,
    ) {
    }

    /**
     * HR: Vraća stanje svakog ugrađenog modula bez izmjene instalacije.
     * EN: Returns the state of every bundled module without changing the installation.
     *
     * @return list<array{slug:string,package:string,label_hr:string,label_en:string,optional:bool,recommended:bool,package_installed:bool,enabled:bool,schema_installed:bool,backup_available:bool,state:string}>
     */
    public function status(): array
    {
        // HR: Jedna svježa snimka s diska izbjegava i zastarjeli FPM cache i
        //     N+1 čitanja Composer metapodataka za svaki prikazani modul.
        // EN: One fresh on-disk snapshot avoids both stale FPM cache and N+1
        //     Composer metadata reads for every displayed module.
        $installedPackages = $this->packages?->installedPackages();
        $ran = array_fill_keys($this->ranMigrations(), true);
        $removed = array_fill_keys($this->state->removedSlugs(), true);
        $result = [];
        foreach ($this->catalog->definitions() as $slug => $definition) {
            $packageInstalled = $this->packageInstalled($slug, $definition['package'], $installedPackages);
            $schemaInstalled = $this->schemaInstalled($definition, $ran);
            $enabled = $packageInstalled && $this->state->isEnabled($slug);
            $backupAvailable = $this->archives->latest($slug) !== null;
            $state = !$packageInstalled
            ? (isset($removed[$slug]) ? 'removed' : 'not-installed')
            : ($enabled
            ? 'enabled'
            : (isset($removed[$slug]) ? 'removed' : ($schemaInstalled ? 'disabled' : 'not-installed')));
            $result[] = [
                'slug' => $slug,
                'package' => $definition['package'],
                'label_hr' => $definition['label_hr'],
                'label_en' => $definition['label_en'],
                'optional' => $definition['optional'],
                'recommended' => $definition['recommended'],
                'package_installed' => $packageInstalled,
                'enabled' => $enabled,
                'schema_installed' => $schemaInstalled,
                'backup_available' => $backupAvailable,
                'state' => $state,
            ];
        }

        return $result;
    }

    /** HR: Omogućuje već instalirani opcionalni modul. EN: Enables an already installed optional module. */
    public function enable(string $slug): void
    {
        $definition = $this->optionalDefinition($slug);
        $this->assertDependenciesEnabled($definition);
        if (!$this->packageInstalled($slug, $definition['package'])) {
            throw new RuntimeException('Module package is not installed; use `modules add ' . $slug . '`.');
        }

        if (!$this->schemaInstalled($definition, array_fill_keys($this->ranMigrations(), true))) {
            throw new RuntimeException('Module schema is not installed; use `modules add ' . $slug . '`.');
        }

        $this->state->enable($slug);
    }

    /** HR: Isključuje opcionalni modul bez brisanja njegovih podataka. EN: Disables an optional module without deleting its data. */
    public function disable(string $slug): void
    {
        $this->optionalDefinition($slug);
        $this->assertNoEnabledDependants($slug);
        $this->state->disable($slug);
    }

    /**
     * HR: Prije uklanjanja sheme stvara NDJSON kopiju, isključuje modul i
     *     poništava samo migracije koje mu katalog izričito dodjeljuje.
     * EN: Before removing the schema, creates an NDJSON backup, disables the
     *     module, and reverses only migrations explicitly owned by it.
     */
    public function remove(string $slug): string
    {
        return $this->removeInternal($slug, true);
    }

    /**
     * HR: Uklanja shemu i sprema kopiju nakon što je Setup odvojio paketnu
     *     radnju. Ovu metodu smije koristiti samo kontrolirani GUI tijek.
     * EN: Removes schema and creates a backup after Setup has separated the
     *     package operation. Only the controlled GUI flow may use this method.
     */
    public function removePrepared(string $slug): string
    {
        return $this->removeInternal($slug, false);
    }

    /** HR: Provodi zajednički dio uklanjanja uz opcionalnu Composer radnju. EN: Runs shared removal with an optional Composer operation. */
    private function removeInternal(string $slug, bool $removePackage): string
    {
        $definition = $this->optionalDefinition($slug);
        $this->assertNoEnabledDependants($slug);
        if ($slug === 'confluence-import') {
            $this->assertConfluenceRuntimeReferencesResolved();
        }

        $archive = $this->archives->create($slug, $definition['tables']);
        $ran = array_fill_keys($this->ranMigrations(), true);
        $ownedMigrations = [];
        foreach (array_reverse($definition['migrations']) as $catalogMigration) {
            $migrationName = $this->resolvedMigrationName($catalogMigration, $ran);
            if ($migrationName === null || !isset($ran[$migrationName])) {
                continue;
            }

            // HR: Migraciju učitaj prije uklanjanja Composer paketa; omotač
            //     nakon uspješnog uklanjanja više ne može autoloadati klasu modula.
            // EN: Load the migration before removing the Composer package; its
            //     wrapper can no longer autoload the module class afterwards.
            $ownedMigrations[$migrationName] = $this->migration($migrationName);
        }

        // HR: Paketna pogreška mora ostaviti aktivnu shemu i stanje netaknutima.
        // EN: A package failure must leave the active schema and state untouched.
        if ($removePackage && $this->packages instanceof ComposerPackageManager) {
            $this->packages->uninstall($slug);
        }

        $this->state->disable($slug);
        foreach ($ownedMigrations as $migrationName => $migration) {
            if (method_exists($migration, 'down')) {
                $migration->down($this->database);
            }

            $this->database->table(Migrator::DEFAULT_REPOSITORY_TABLE)
                ->where('migration', '=', $migrationName)
                ->delete();
        }

        // HR: Stariji omotači migracija nisu svi izlagali `down()`. Katalog
        //     zato nakon reverzibilnih koraka zajamčeno uklanja isključivo
        //     tablice u vlasništvu odabranog opcionalnog modula.
        // EN: Some legacy migration wrappers did not expose `down()`. After
        //     reversible steps, the catalog therefore guarantees removal of
        //     only the tables owned by the selected optional module.
        foreach (array_reverse($definition['tables']) as $table) {
            $this->database->schema()->dropIfExists($table);
        }

        $this->state->markRemoved($slug);

        return $archive;
    }

    /**
     * HR: Instalira nedostajuće migracije i po izboru vraća zadnju kopiju ili
     *     započinje s praznim tablicama, zatim omogućuje modul.
     * EN: Installs missing migrations and optionally restores the latest backup
     *     or starts with empty tables, then enables the module.
     */
    public function add(string $slug, bool $restore): ?string
    {
        return $this->addInternal($slug, $restore, true);
    }

    /**
     * HR: Instalira shemu paketa koji je Setup helper već sigurno dodao.
     * EN: Installs the schema of a package already added safely by the Setup helper.
     */
    public function addPrepared(string $slug, bool $restore): ?string
    {
        return $this->addInternal($slug, $restore, false);
    }

    /** HR: Provodi zajednički dio dodavanja uz opcionalnu Composer radnju. EN: Runs shared addition with an optional Composer operation. */
    private function addInternal(string $slug, bool $restore, bool $installPackage): ?string
    {
        $definition = $this->optionalDefinition($slug);
        $this->assertDependenciesEnabled($definition);
        if ($installPackage && $this->packages instanceof ComposerPackageManager) {
            $this->packages->install($slug);
        }

        $this->packages?->registerCurrentAutoloader();
        if (!$this->packageInstalled($slug, $definition['package'])) {
            throw new RuntimeException('Module package is not installed: ' . $slug);
        }

        $migrations = [];
        foreach ($definition['migrations'] as $catalogMigration) {
            $migrationName = $this->resolvedMigrationName($catalogMigration);
            if ($migrationName === null) {
                throw new RuntimeException('Module migration is missing: ' . $catalogMigration);
            }

            $migrations[$migrationName] = $this->migration($migrationName);
        }

        $this->database->migrator()->migrate($migrations);

        $archive = null;
        if ($restore) {
            $archive = $this->archives->latest($slug);
            if ($archive === null) {
                throw new RuntimeException('No module data backup is available for restore.');
            }

            $this->archives->restore($archive, $definition['tables']);
        }

        $this->state->enable($slug);

        return $archive;
    }

    /**
     * HR: Vraća dostupne kopije opcionalnog modula.
     * EN: Returns available backups for an optional module.
     *
     * @return list<string>
     */
    public function backups(string $slug): array
    {
        $this->optionalDefinition($slug);

        return $this->archives->all($slug);
    }

    /**
     * HR: Primjenjuje aplikacijske migracije samo za stvarno instalirane
     *     pakete. Isključeni modul ostaje uključen u skup jer mu je shema sačuvana.
     * EN: Applies application migrations only for packages that are actually
     *     installed. A disabled module remains in the set because its schema is retained.
     *
     * @return list<string>
     */
    public function migrateInstalled(): array
    {
        return array_values($this->database->migrator()->migrate($this->installedMigrations()));
    }

    /**
     * HR: Vraća status migracija instaliranih paketa bez učitavanja omotača
     *     opcionalnih paketa koji nisu prisutni u Composer instalaciji.
     * EN: Returns migration status for installed packages without loading
     *     wrappers belonging to optional packages absent from Composer.
     *
     * @return array{ran:list<string>,pending:list<string>}
     */
    public function installedMigrationStatus(): array
    {
        $migrations = $this->installedMigrations();
        $ranLookup = array_fill_keys($this->ranMigrations(), true);
        $ran = [];
        $pending = [];
        foreach (array_keys($migrations) as $name) {
            if (isset($ranLookup[$name])) {
                $ran[] = $name;
            } else {
                $pending[] = $name;
            }
        }

        return ['ran' => $ran, 'pending' => $pending];
    }

    /**
     * HR: Vraća i provjerava da se korisnička radnja odnosi samo na opcionalni modul.
     * EN: Returns and validates that a user action targets an optional module only.
     *
     * @return ModuleDefinition
     */
    private function optionalDefinition(string $slug): array
    {
        $definition = $this->catalog->definitionFor($slug);
        if (!$definition['optional']) {
            throw new RuntimeException('Required modules cannot be disabled or removed.');
        }

        return $definition;
    }

    /**
     * HR: Zahtijeva da su sve tvrde ovisnosti ciljnog modula omogućene.
     * EN: Requires every hard dependency of the target module to be enabled.
     *
     * @param ModuleDefinition $definition
     */
    private function assertDependenciesEnabled(array $definition): void
    {
        foreach ($definition['dependencies'] as $dependency) {
            if (!$this->state->isEnabled($dependency)) {
                throw new RuntimeException('Required module is disabled: ' . $dependency);
            }
        }
    }

    /** HR: Sprječava isključivanje modula koji još treba drugi omogućeni modul. EN: Prevents disabling a module still required by another enabled module. */
    private function assertNoEnabledDependants(string $slug): void
    {
        foreach ($this->catalog->definitions() as $candidateSlug => $definition) {
            if (
                $candidateSlug !== $slug
                && $this->state->isEnabled($candidateSlug)
                && in_array($slug, $definition['dependencies'], true)
            ) {
                throw new RuntimeException('Disable dependent module first: ' . $candidateSlug);
            }
        }
    }

    /**
     * HR: Provjerava da su sve migracije modula evidentirane.
     * EN: Checks that every module migration is recorded.
     *
     * @param ModuleDefinition $definition
     * @param array<string,true> $ran
     */
    private function schemaInstalled(array $definition, array $ran): bool
    {
        foreach ($definition['migrations'] as $catalogMigration) {
            $migration = $this->resolvedMigrationName($catalogMigration, $ran);
            if ($migration === null || !isset($ran[$migration])) {
                return false;
            }
        }

        return true;
    }

    /**
     * HR: Normalizira evidenciju izvršenih migracija prije rada s ključevima polja.
     * EN: Normalizes the executed-migration ledger before using it as array keys.
     *
     * @return list<string>
     */
    private function ranMigrations(): array
    {
        $migrations = [];
        foreach ($this->database->migrator()->ran() as $migration) {
            if (is_string($migration)) {
                $migrations[] = $migration;
            }
        }

        return $migrations;
    }

    /**
     * HR: Učitava migracijske datoteke izdanja, ali preskače one kojima katalog
     *     dodjeljuje paket koji nije instaliran. Time minimalna instalacija ne
     *     pokušava requireati nepostojeći vendor kod opcionalnog modula.
     * EN: Loads release migration files while skipping those assigned by the
     *     catalog to a package that is not installed. This prevents a minimal
     *     installation from requiring absent optional-module vendor code.
     *
     * @return array<string,MigrationInterface>
     */
    private function installedMigrations(): array
    {
        $installedPackages = $this->packages?->installedPackages();
        $definitions = $this->catalog->definitions();

        $files = glob(rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/database/migrations/*.php') ?: [];
        sort($files, SORT_NATURAL);
        $migrations = [];
        foreach ($files as $path) {
            $name = pathinfo($path, PATHINFO_FILENAME);
            $owner = $name !== '' ? $this->catalog->slugForMigration($name) : null;
            if ($name === '') {
                continue;
            }

            if (
                $owner !== null
                && isset($definitions[$owner])
                && !$this->packageInstalled($owner, $definitions[$owner]['package'], $installedPackages)
            ) {
                continue;
            }

            $migrations[$name] = $this->migration($name);
        }

        return $migrations;
    }

    /**
     * HR: Pronalazi stvarni omotač migracije, uključujući onaj kojem je čarobnjak
     *     pri instalaciji dodijelio novu vremensku oznaku.
     * EN: Finds the actual migration wrapper, including one assigned a fresh
     *     timestamp by the installation wizard.
     *
     * @param array<string,true> $ran
     */
    private function resolvedMigrationName(string $catalogMigration, array $ran = []): ?string
    {
        $files = glob(rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/database/migrations/*.php') ?: [];
        $candidates = [];
        foreach ($files as $path) {
            $name = pathinfo($path, PATHINFO_FILENAME);
            if ($name !== '' && $this->catalog->migrationMatches($catalogMigration, $name)) {
                $candidates[] = $name;
            }
        }

        sort($candidates, SORT_NATURAL);
        if ($ran !== []) {
            foreach (array_reverse($candidates) as $candidate) {
                if (isset($ran[$candidate])) {
                    return $candidate;
                }
            }
        }

        return $candidates !== [] ? $candidates[array_key_last($candidates)] : null;
    }

    /**
     * HR: Ne dopušta uklanjanje Confluence pomoćnih tablica dok ijedna stara
     *     verzija stranice još sadrži privremeni import proxy ili dinamičku
     *     referencu na izvorno područje.
     * EN: Refuses to remove Confluence helper tables while any legacy page
     *     version still contains a temporary import proxy or a dynamic source
     *     workspace reference.
     */
    private function assertConfluenceRuntimeReferencesResolved(): void
    {
        $needles = ['/confluence-import/link/', '"workspace_refs"', '&quot;workspace_refs&quot;'];
        if ($this->database->schema()->hasTable('editor_html_document_versions')) {
            foreach ($needles as $needle) {
                $match = $this->database->table('editor_html_document_versions')
                    ->select('id')
                    ->where('content_html', 'LIKE', '%' . $needle . '%')
                    ->first();
                if (is_array($match)) {
                    throw new RuntimeException(
                        'Confluence import cannot be removed while page versions contain unresolved import references.',
                    );
                }
            }
        }

        $root = $this->editorDocumentsRoot();
        if (!is_dir($root)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        );
        foreach ($files as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'html') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if (!is_string($contents)) {
                throw new RuntimeException('A stored page version could not be checked before module removal.');
            }

            foreach ($needles as $needle) {
                if (str_contains($contents, $needle)) {
                    throw new RuntimeException(
                        'Confluence import cannot be removed while page versions contain unresolved import references.',
                    );
                }
            }
        }
    }

    /**
     * HR: Vraća provjereni korijen filesystem verzija HTML dokumenata.
     * EN: Returns the validated root of filesystem-backed HTML document versions.
     */
    private function editorDocumentsRoot(): string
    {
        $relative = 'editor-html';
        $configPath = rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/config/editor-html.php';
        $config = is_file($configPath) ? require $configPath : [];
        $storage = is_array($config) && is_array($config['storage'] ?? null) ? $config['storage'] : [];
        if (is_scalar($storage['filesystem_path'] ?? null)) {
            $relative = trim(str_replace('\\', '/', (string)$storage['filesystem_path']), '/');
        }

        if (
            $relative === ''
            || preg_match('#(?:^|/)(?:\.|\.\.)(?:/|$)#', $relative) === 1
            || str_contains($relative, ':')
        ) {
            throw new RuntimeException('The editor filesystem path is invalid.');
        }

        return rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/data/' . $relative;
    }

    /**
     * HR: Provjerava stvarnu dostupnost paketa, uz opcionalnu zajedničku snimku
     *     koja sprječava N+1 čitanja pri prikazu svih modula.
     * EN: Checks actual package availability, with an optional shared snapshot
     *     that prevents N+1 reads while rendering all modules.
     *
     * @param null|list<string> $installedPackages
     */
    private function packageInstalled(string $slug, string $package, ?array $installedPackages = null): bool
    {
        if (is_array($installedPackages)) {
            return in_array($package, $installedPackages, true);
        }

        return $this->packages instanceof ComposerPackageManager
        ? $this->packages->isInstalled($slug)
        : InstalledVersions::isInstalled($package);
    }

    /** HR: Učitava samo migraciju iz fiksnog aplikacijskog direktorija. EN: Loads a migration only from the fixed application directory. */
    private function migration(string $name): MigrationInterface
    {
        $path = rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/database/migrations/' . $name . '.php';
        if (!is_file($path) || pathinfo($path, PATHINFO_FILENAME) !== $name) {
            throw new RuntimeException('Module migration is missing: ' . $name);
        }

        $migration = require $path;
        if (!$migration instanceof MigrationInterface) {
            throw new RuntimeException('Invalid module migration: ' . $name);
        }

        return $migration;
    }
}
