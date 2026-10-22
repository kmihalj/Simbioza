<?php

declare(strict_types=1);

namespace App\Localization;

use App\Configuration\ConfigurationPaths;
use App\Configuration\PhpConfigurationReader;
use App\Module\ModuleCatalog;
use Composer\InstalledVersions;
use DOMDocument;
use DOMElement;
use JsonException;
use RuntimeException;

/**
 * EN: Collects application and bundled-module translations into one portable
 *     pack and safely installs a validated translation.
 *
 * HR: Skuplja prijevode aplikacije i svih ugrađenih modula u jedan
 *     prenosivi paket te sigurno instalira provjereni prijevod.
 */
final readonly class LanguagePackManager
{
    private ConfigurationPaths $configuration;

    private PhpConfigurationReader $reader;

    private RuntimeLanguageStorage $storage;

    /**
     * EN: Receives the application root and bundled-module catalog.
     *
     * HR: Prima korijen aplikacije i katalog ugrađenih modula.
     */
    public function __construct(
        private string $appRoot,
        private ModuleCatalog $modules,
    ) {
        $this->configuration = ConfigurationPaths::fromEnvironment($appRoot);
        $this->reader = new PhpConfigurationReader($this->configuration);
        $this->storage = new RuntimeLanguageStorage($appRoot);
    }

    /**
     * EN: Lists installed language files and their coverage against the source locale.
     *
     * HR: Navodi instalirane jezične datoteke i pokrivenost u odnosu na izvorni jezik.
     *
     * @return list<array{locale:string,native_name:string,keys:int,reference_keys:int,coverage:float,active:bool}>
     */
    public function status(string $sourceLocale = 'hr'): array
    {
        $referenceCount = count($this->catalog($sourceLocale));
        $registry = $this->languageRegistry();
        $active = $this->activeLocales();
        $result = [];
        foreach ($registry as $locale => $definition) {
            if (!$this->installed($locale)) {
                continue;
            }

            $count = count($this->catalog($locale));
            $result[] = [
                'locale' => $locale,
                'native_name' => $definition['native_name'],
                'keys' => $count,
                'reference_keys' => $referenceCount,
                'coverage' => $referenceCount === 0 ? 100.0 : min(100.0, $count * 100 / $referenceCount),
                'active' => in_array($locale, $active, true),
            ];
        }

        return $result;
    }

    /**
     * EN: Creates an editable JSON template containing every unique application
     *     and module key while preserving placeholder tokens.
     *
     * HR: Izrađuje urediv JSON predložak sa svim jedinstvenim ključevima
     *     aplikacije i modula, uz očuvane zamjenske oznake.
     */
    public function createTemplate(string $locale, string $sourceLocale, ?string $output = null): string
    {
        $locale = $this->assertLocale($locale);
        $sourceLocale = $this->assertLocale($sourceLocale);
        $translations = $this->catalog($sourceLocale);
        if ($translations === []) {
            throw new RuntimeException('The source locale has no translations: ' . $sourceLocale);
        }

        ksort($translations, SORT_STRING);
        $names = [];
        foreach (array_keys($this->languageRegistry()) as $installedLocale) {
            $names[$installedLocale] = '';
        }

        $names[$locale] = '';
        ksort($names, SORT_STRING);
        $output ??= $this->appRoot . '/data/language-packs/' . $locale . '.json';
        $this->ensureParentDirectory($output);
        $payload = [
            'format' => 'simbioza-language-pack',
            'version' => 2,
            'locale' => $locale,
            'source_locale' => $sourceLocale,
            'names' => $names,
            'native_name' => '',
            'flag_svg' => '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 32 32"></svg>',
            'translations' => $translations,
        ];
        $encoded = json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
        if (file_put_contents($output, $encoded . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('Unable to write the language-pack template.');
        }

        return $output;
    }

    /**
     * EN: Validates pack format, completeness, and placeholders without writing.
     *
     * HR: Provjerava format, potpunost i zamjenske oznake bez zapisivanja.
     *
     * @return array{
     *     locale:string,
     *     native_name:string,
     *     translated:int,
     *     reference:int,
     *     missing:list<string>,
     *     placeholder_errors:list<string>
     * }
     */
    public function validate(string $path): array
    {
        $pack = $this->readPack($path);
        $reference = $this->catalog($pack['source_locale']);
        $missing = array_values(array_diff(array_keys($reference), array_keys($pack['translations'])));
        $placeholderErrors = [];
        foreach ($reference as $key => $source) {
            $translated = $pack['translations'][$key] ?? null;
            if (!is_string($translated)) {
                continue;
            }

            if ($this->placeholders($source) !== $this->placeholders($translated)) {
                $placeholderErrors[] = $key;
            }
        }

        return [
            'locale' => $pack['locale'],
            'native_name' => $pack['native_name'],
            'translated' => count($pack['translations']),
            'reference' => count($reference),
            'missing' => $missing,
            'placeholder_errors' => $placeholderErrors,
        ];
    }

    /**
     * EN: Installs consolidated translations as runtime JSON data and adds
     *     the locale to the site's available locales without modifying code.
     *
     * HR: Instalira objedinjene prijevode kao runtime JSON podatke i dodaje
     *     jezik na popis dostupnih jezika sitea, bez mijenjanja koda.
     *
     * @return array{
     *     locale:string,
     *     native_name:string,
     *     translated:int,
     *     reference:int,
     *     missing:list<string>,
     *     placeholder_errors:list<string>
     * }
     * @param array{version:string,sha256:string}|null $repositoryRevision
     */
    public function install(
        string $path,
        bool $replace = false,
        bool $allowMissing = false,
        bool $activate = true,
        ?array $repositoryRevision = null,
    ): array {
        // EN: One lock covers resources, registry, and activation for GUI and CLI.
        //
        // HR: Jedan lock pokriva resurse, registar i aktivaciju za GUI i CLI.
        return $this->withConfigurationLock(fn(): array => $this->withLocaleRollback(
            $this->readPack($path)['locale'],
            $activate,
            fn(): array => $this->installInternal($path, $replace, $allowMissing, $activate, $repositoryRevision),
        ));
    }

    /**
     * EN: Installs only after checking every configuration destination.
     *
     * HR: Provodi instalaciju tek nakon provjere svih konfiguracijskih ciljeva.
     *
     * @return array{locale:string,native_name:string,translated:int,reference:int,missing:list<string>,placeholder_errors:list<string>}
     * @param array{version:string,sha256:string}|null $repositoryRevision
     */
    private function installInternal(
        string $path,
        bool $replace,
        bool $allowMissing,
        bool $activate,
        ?array $repositoryRevision,
    ): array {
        $this->configuration->writableFile('languages.php');
        if ($activate) {
            $this->configuration->writableFile('installation.php');
            $this->reader->read('installation.php');
        }

        $validation = $this->validate($path);
        if ($validation['placeholder_errors'] !== []) {
            throw new RuntimeException(
                'Language pack changes placeholders in keys: '
                . implode(', ', array_slice($validation['placeholder_errors'], 0, 20)),
            );
        }

        if (!$allowMissing && $validation['missing'] !== []) {
            throw new RuntimeException(
                'Language pack is incomplete; missing keys: '
                . implode(', ', array_slice($validation['missing'], 0, 20)),
            );
        }

        $pack = $this->readPack($path);
        $versions = $this->storage->versions();
        unset($versions[$pack['locale']]);
        if ($repositoryRevision !== null) {
            $versions += RuntimeLanguageStorage::validateVersions([$pack['locale'] => $repositoryRevision]);
        }

        $this->storage->assertWritable('repository-versions.json');
        if ($this->installed($pack['locale']) && !$replace) {
            throw new RuntimeException('Language already exists; pass --replace to overwrite it.');
        }

        $this->storage->assertWritable('catalogs/' . $pack['locale'] . '.json');
        $this->storage->assertWritable('flags/' . $pack['locale'] . '.svg');
        $this->storage->install($pack['locale'], $pack['translations']);
        $this->storage->write('flags/' . $pack['locale'] . '.svg', $pack['flag_svg'] . "\n");
        $this->registerLanguage(
            $pack['locale'],
            $pack['names'],
            $pack['native_name'],
            $pack['locale'] . '.svg',
        );
        if ($activate) {
            $this->setActiveInternal($pack['locale'], true);
        }

        $this->storage->writeVersions($versions);

        return $validation;
    }

    /**
     * EN: Enables or disables an installed locale while keeping at least one active locale.
     *
     * HR: Uključuje ili isključuje već instalirani jezik uz barem jedan aktivni jezik.
     */
    public function setActive(string $locale, bool $enabled): void
    {
        $this->withConfigurationLock(function () use ($locale, $enabled): void {
            $this->setActiveInternal($locale, $enabled);
        });
    }

    /**
     * EN: Changes selection while holding the shared lock.
     *
     * HR: Mijenja odabir pod zajedničkim lockom.
     */
    private function setActiveInternal(string $locale, bool $enabled): void
    {
        $locale = $this->assertLocale($locale);
        if (
            !isset($this->languageRegistry()[$locale])
            || !$this->installed($locale)
        ) {
            throw new RuntimeException('The language is not installed.');
        }

        $path = $this->configuration->writableFile('installation.php');
        $configuration = $this->reader->read('installation.php');
        $previousConfiguration = $configuration;

        $locales = $this->activeLocales();
        if ($enabled && !in_array($locale, $locales, true)) {
            $locales[] = $locale;
        }

        if (!$enabled) {
            $locales = array_values(array_diff($locales, [$locale]));
            if ($locales === []) {
                throw new RuntimeException('At least one language must remain active.');
            }
        }

        $configuration['supported_locales'] = $locales;
        if (!in_array($configuration['primary_locale'] ?? null, $locales, true)) {
            $configuration['primary_locale'] = $locales[0];
        }

        if ($configuration === $previousConfiguration) {
            return;
        }

        $contents = "<?php\n\ndeclare(strict_types=1);\n\nreturn "
        . $this->exportArray($configuration)
        . ";\n";
        $this->atomicWrite($path, $contents, 0660);
    }

    /**
     * EN: Removes a locale registration; bundled source files remain in the codebase.
     *
     * HR: Uklanja registraciju jezika; ugrađene izvorne datoteke ostaju u kodu.
     */
    public function uninstall(string $locale): void
    {
        $this->withConfigurationLock(function () use ($locale): void {
            $this->withLocaleRollback($this->assertLocale($locale), true, function () use ($locale): void {
                $this->uninstallInternal($locale);
            });
        });
    }

    /**
     * EN: Removes registration and resources under the same lock.
     *
     * HR: Uklanja registraciju i resurse pod istim lockom.
     */
    private function uninstallInternal(string $locale): void
    {
        $locale = $this->assertLocale($locale);
        $registry = $this->languageRegistry();
        if (!isset($registry[$locale])) {
            throw new RuntimeException('The language is not installed.');
        }

        $this->configuration->writableFile('languages.php');

        if (in_array($locale, $this->activeLocales(), true)) {
            $this->setActiveInternal($locale, false);
        }

        unset($registry[$locale]);
        $this->writeRegistry($registry);
        $this->storage->remove('catalogs/' . $locale . '.json');
        $this->storage->remove('flags/' . $locale . '.svg');

        $versions = $this->storage->versions();
        unset($versions[$locale]);
        $this->storage->writeVersions($versions);
    }

    /**
     * EN: Collects one locale using the same precedence that gives application
     *     translations priority over module translations at runtime.
     *
     * HR: Skuplja katalog jednoga jezika uz isti prioritet kojim runtime
     *     daje prednost aplikaciji ispred modula.
     *
     * @return array<string,string>
     */
    public function catalog(string $locale): array
    {
        $locale = $this->assertLocale($locale);
        $result = $this->storage->catalog($locale);
        foreach ($this->translationFiles($locale) as $file) {
            $values = require $file;
            if (!is_array($values)) {
                continue;
            }

            foreach ($this->flatten($values) as $key => $value) {
                if (!array_key_exists($key, $result)) {
                    $result[$key] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * EN: Returns application and installed-module files in precedence order.
     *
     * HR: Vraća datoteke aplikacije i instaliranih modula po prioritetu.
     *
     * @return list<string>
     */
    private function translationFiles(string $locale): array
    {
        $files = [];
        $application = $this->languageDirectory() . '/' . $locale . '.php';
        if (is_file($application)) {
            $files[] = $application;
        }

        foreach ($this->modules->definitions() as $definition) {
            if (!InstalledVersions::isInstalled($definition['package'])) {
                continue;
            }

            $root = InstalledVersions::getInstallPath($definition['package']);
            $file = is_string($root) ? $root . '/lang/' . $locale . '.php' : '';
            if ($file !== '' && is_file($file)) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * EN: Flattens nested translations into stable dot-separated keys.
     *
     * HR: Pretvara ugniježdene prijevode u stabilne ključeve s točkama.
     *
     * @param array<array-key,mixed> $values
     * @return array<string,string>
     */
    private function flatten(array $values, string $prefix = ''): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            $path = $prefix === '' ? (string)$key : $prefix . '.' . $key;
            if (is_array($value)) {
                $result += $this->flatten($value, $path);
            } elseif (is_string($value)) {
                $result[$path] = $value;
            }
        }

        return $result;
    }

    /**
     * EN: Reads and strictly validates the JSON pack structure.
     *
     * HR: Učitava i strogo provjerava strukturu JSON paketa.
     *
     * @return array{
     *     locale:string,
     *     source_locale:string,
     *     names:array<string,string>,
     *     native_name:string,
     *     flag_svg:string,
     *     translations:array<string,string>
     * }
     */
    private function readPack(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException('Language pack does not exist: ' . $path);
        }

        try {
            $payload = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new RuntimeException('Language pack is not valid JSON.', 0, $jsonException);
        }

        if (!is_array($payload) || ($payload['format'] ?? null) !== 'simbioza-language-pack') {
            throw new RuntimeException('Unsupported language-pack format.');
        }

        if (($payload['version'] ?? null) !== 2) {
            throw new RuntimeException('Unsupported language-pack version.');
        }

        $locale = $this->assertLocale(is_string($payload['locale'] ?? null) ? $payload['locale'] : '');
        $source = $this->assertLocale(
            is_string($payload['source_locale'] ?? null) ? $payload['source_locale'] : '',
        );
        $names = [];
        foreach (is_array($payload['names'] ?? null) ? $payload['names'] : [] as $nameLocale => $name) {
            if (!is_string($nameLocale) || !is_string($name)) {
                continue;
            }

            $nameLocale = $this->assertLocale($nameLocale);
            $name = trim($name);
            if ($name !== '' && mb_strlen($name) <= 100) {
                $names[$nameLocale] = $name;
            }
        }

        $requiredNameLocales = array_values(array_unique([
            ...array_intersect(['en', 'hr'], array_keys($this->languageRegistry())),
            $locale,
        ]));
        foreach ($requiredNameLocales as $nameLocale) {
            if (!isset($names[$nameLocale])) {
                throw new RuntimeException('Language pack is missing the language name for locale: ' . $nameLocale);
            }
        }

        $nativeName = is_string($payload['native_name'] ?? null) ? trim($payload['native_name']) : '';
        if ($nativeName === '' || mb_strlen($nativeName) > 100 || ($names[$locale] ?? null) !== $nativeName) {
            throw new RuntimeException('Language pack native_name must equal names.' . $locale . '.');
        }

        $flagSvg = is_string($payload['flag_svg'] ?? null) ? $payload['flag_svg'] : '';
        $flagSvg = self::validatedFlagSvg($flagSvg);

        $translations = [];
        foreach (is_array($payload['translations'] ?? null) ? $payload['translations'] : [] as $key => $value) {
            if (is_string($key) && $key !== '' && is_string($value)) {
                $translations[$key] = $value;
            }
        }

        return [
            'locale' => $locale,
            'source_locale' => $source,
            'names' => $names,
            'native_name' => $nativeName,
            'flag_svg' => $flagSvg,
            'translations' => $translations,
        ];
    }

    /**
     * EN: Returns a sorted placeholder multiset.
     *
     * HR: Vraća sortirani multiskup zamjenskih oznaka.
     *
     * @return list<string>
     */
    private function placeholders(string $value): array
    {
        preg_match_all('/(?<!:):[A-Za-z_][A-Za-z0-9_]*|%[sd]|\{\{[^{}]+\}\}/', $value, $matches);
        $tokens = $matches[0];
        sort($tokens, SORT_STRING);

        return $tokens;
    }

    /**
     * EN: Strictly validates the small static SVG subset allowed for flags.
     *
     * HR: Strogo provjerava mali, statični podskup SVG-a dopušten za zastavice.
     */
    public static function validatedFlagSvg(string $svg): string
    {
        $svg = trim($svg);
        if ($svg === '' || strlen($svg) > 65536 || str_contains($svg, '<!')) {
            throw new RuntimeException('Language-pack flag is missing or unsafe.');
        }

        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            if (!$document->loadXML($svg, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) {
                throw new RuntimeException('Language-pack flag is not valid SVG.');
            }

            $root = $document->documentElement;
            if (!$root instanceof DOMElement || strtolower($root->localName ?? '') !== 'svg') {
                throw new RuntimeException('Language-pack flag must have an SVG root element.');
            }

            $allowedElements = array_fill_keys([
                'svg', 'g', 'defs', 'clippath', 'path', 'rect', 'circle', 'ellipse',
                'line', 'polyline', 'polygon',
            ], true);
            $allowedAttributes = array_fill_keys([
                'xmlns', 'width', 'height', 'viewbox', 'x', 'y', 'x1', 'y1', 'x2', 'y2',
                'cx', 'cy', 'r', 'rx', 'ry', 'd', 'points', 'fill', 'stroke', 'stroke-width',
                'stroke-linecap', 'stroke-linejoin', 'fill-rule', 'clip-rule', 'clip-path',
                'transform', 'opacity', 'id',
            ], true);
            foreach ($document->getElementsByTagName('*') as $element) {
                if (
                    !$element instanceof DOMElement
                    || !isset($allowedElements[strtolower($element->localName ?? '')])
                ) {
                    throw new RuntimeException('Language-pack flag contains an unsupported SVG element.');
                }

                foreach ($element->attributes as $attribute) {
                    $name = strtolower($attribute->nodeName);
                    $value = trim($attribute->nodeValue ?? '');
                    if (!isset($allowedAttributes[$name]) || str_starts_with($name, 'on')) {
                        throw new RuntimeException('Language-pack flag contains an unsupported SVG attribute.');
                    }

                    if (str_contains(strtolower($value), 'javascript:')) {
                        throw new RuntimeException('Language-pack flag contains an unsafe SVG value.');
                    }

                    if (
                        str_contains(strtolower($value), 'url(')
                        && preg_match('/\Aurl\(#[A-Za-z0-9_.:-]+\)\z/D', $value) !== 1
                    ) {
                        throw new RuntimeException('Language-pack flag may reference only local SVG definitions.');
                    }
                }
            }

            $normalized = $document->saveXML($root);
            if (!is_string($normalized) || $normalized === '') {
                throw new RuntimeException('Language-pack flag could not be normalized.');
            }

            return $normalized;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * EN: Returns the validated installation language registry.
     *
     * HR: Vraća provjereni registar jezika instalacije.
     *
     * @return array<string,array{names:array<string,string>,native_name:string,flag:string}>
     */
    public function languageRegistry(): array
    {
        $source = $this->reader->readDocument('languages.php');
        $result = [];
        foreach ($source as $locale => $definition) {
            if (!is_string($locale) || !$this->validLocale($locale) || !is_array($definition)) {
                continue;
            }

            $names = [];
            $configuredNames = is_array($definition['names'] ?? null) ? $definition['names'] : [];
            foreach ($configuredNames as $nameLocale => $name) {
                if (
                    is_string($nameLocale)
                    && $this->validLocale($nameLocale)
                    && is_string($name)
                    && trim($name) !== ''
                ) {
                    $names[$nameLocale] = trim($name);
                }
            }

            $nativeName = is_string($definition['native_name'] ?? null) ? trim($definition['native_name']) : '';
            $flag = is_string($definition['flag'] ?? null) ? basename($definition['flag']) : '';
            if ($nativeName !== '' && $flag !== '') {
                $result[strtolower($locale)] = [
                    'names' => $names,
                    'native_name' => $nativeName,
                    'flag' => $flag,
                ];
            }
        }

        return $result;
    }

    /**
     * EN: Atomically appends language metadata.
     *
     * HR: Atomski dodaje metapodatke jezika.
     *
     * @param array<string,string> $names
     */
    private function registerLanguage(string $locale, array $names, string $nativeName, string $flag): void
    {
        $registry = $this->languageRegistry();
        $registry[$locale] = [
            'names' => $names,
            'native_name' => $nativeName,
            'flag' => $flag,
        ];
        $this->writeRegistry($registry);
    }

    /**
     * EN: Atomically writes the normalized language registry.
     *
     * HR: Atomski zapisuje normalizirani registar jezika.
     *
     * @param array<string,array{names:array<string,string>,native_name:string,flag:string}> $registry
     */
    public function writeRegistry(array $registry): void
    {
        ksort($registry, SORT_STRING);
        $contents = "<?php\n\ndeclare(strict_types=1);\n\n"
        . "// EN: Language registry maintained by the Simbioza tool.\n//\n"
        . "// HR: Registar jezika održava alat Simbioze.\n"
        . 'return ' . $this->exportArray($registry) . ";\n";
        $this->atomicWrite($this->configuration->writableFile('languages.php'), $contents, 0660);
    }

    /**
     * EN: Exports private configuration using stable PHP short-array syntax.
     *
     * HR: Izvozi privatnu konfiguraciju u stabilnu PHP sintaksu kratkih polja.
     *
     * @param array<array-key,mixed> $values
     */
    private function exportArray(array $values, int $depth = 0): string
    {
        if ($values === []) {
            return '[]';
        }

        $indent = str_repeat('    ', $depth);
        $itemIndent = str_repeat('    ', $depth + 1);
        $lines = [];
        foreach ($values as $key => $value) {
            if (!is_array($value) && !is_scalar($value) && $value !== null) {
                throw new RuntimeException('Language configuration contains an unsupported value.');
            }

            $exportedKey = is_int($key) ? (string)$key : var_export($key, true);
            $exportedValue = is_array($value) ? $this->exportArray($value, $depth + 1) : var_export($value, true);
            $lines[] = $itemIndent . $exportedKey . ' => ' . $exportedValue . ',';
        }

        return "[\n" . implode("\n", $lines) . "\n" . $indent . ']';
    }

    /**
     * EN: Returns enabled locales from private installation configuration.
     *
     * HR: Vraća uključene jezike iz privatne instalacijske konfiguracije.
     *
     * @return list<string>
     */
    private function activeLocales(): array
    {
        $configuration = $this->reader->read('installation.php');

        $configured = is_array($configuration['supported_locales'] ?? null)
        ? array_values(array_filter($configuration['supported_locales'], is_string(...)))
        : [];
        if ($configured !== []) {
            return $configured;
        }

        // EN: An installation without private configuration already effectively
        //     uses the bundled HR and EN locales. Enabling the first additional
        //     locale must preserve those defaults instead of replacing them.
        //
        // HR: Instalacija bez privatne konfiguracije već učinkovito koristi
        //     ugrađene HR i EN jezike. Prvo uključivanje dodatnog jezika mora
        //     zadržati te zadane jezike umjesto da ih nehotice zamijeni.
        $registry = $this->languageRegistry();
        $installed = fn(string $locale): bool => isset($registry[$locale])
            && $this->installed($locale);
        $defaults = array_values(array_filter(['hr', 'en'], $installed));
        if ($defaults !== []) {
            return $defaults;
        }

        foreach (array_keys($registry) as $locale) {
            if (is_string($locale) && $installed($locale)) {
                return [$locale];
            }
        }

        return [];
    }

    /**
     * EN: Publishes new files atomically; existing shared configuration keeps
     * its inode, owner, group, mode and ACL under the language-operation lock.
     *
     * HR: Nove datoteke objavljuje atomski; postojeća dijeljena konfiguracija
     * pod lockom jezične radnje zadržava inode, vlasnika, grupu, prava i ACL.
     */
    private function atomicWrite(string $path, string $contents, int $mode): void
    {
        $this->ensureParentDirectory($path);
        if (is_file($path)) {
            $this->writeExistingConfiguration($path, $contents);
            return;
        }

        $temporary = tempnam(dirname($path), '.simbioza-language-');
        if (!is_string($temporary)) {
            throw new RuntimeException('Unable to create a temporary language file.');
        }

        try {
            if (file_put_contents($temporary, $contents, LOCK_EX) === false || !chmod($temporary, $mode)) {
                throw new RuntimeException('Unable to write the language file.');
            }

            if (!rename($temporary, $path)) {
                throw new RuntimeException('Unable to activate the language file.');
            }

            if (str_ends_with($path, '.php') && function_exists('opcache_invalidate')) {
                opcache_invalidate($path, true);
            }
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    /**
     * EN: Writes a validated existing regular file without replacing an FPM-owned
     * inode with a deploy-owned one. Both identities need only existing group write
     * access, never chown or write access to application code.
     *
     * HR: Zapisuje provjerenu postojeću običnu datoteku bez zamjene FPM-ova inodea
     * deploy-ovim. Oba identiteta trebaju samo postojeće grupno pravo pisanja,
     * nikada chown ili pravo pisanja aplikacijskog koda.
     */
    private function writeExistingConfiguration(string $path, string $contents): void
    {
        if (is_link($path) || !is_writable($path)) {
            throw new RuntimeException('Unable to write the language configuration.');
        }

        $handle = @fopen($path, 'r+b');
        if ($handle === false) {
            throw new RuntimeException('Unable to open the language configuration.');
        }

        try {
            $opened = fstat($handle);
            clearstatcache(true, $path);
            $current = lstat($path);
            if (
                !is_array($opened) || !is_array($current)
                || ($opened['mode'] & 0170000) !== 0100000
                || $opened['dev'] !== $current['dev'] || $opened['ino'] !== $current['ino']
                || !flock($handle, LOCK_EX)
            ) {
                throw new RuntimeException('Unable to lock the language configuration.');
            }

            $offset = 0;
            while ($offset < strlen($contents)) {
                $written = fwrite($handle, substr($contents, $offset));
                if ($written === false || $written === 0) {
                    throw new RuntimeException('Unable to write the language configuration.');
                }

                $offset += $written;
            }

            if (!ftruncate($handle, strlen($contents)) || !fflush($handle)) {
                throw new RuntimeException('Unable to flush the language configuration.');
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($path, true);
            }
        }
    }

    /**
     * EN: Creates the parent directory when missing.
     *
     * HR: Izrađuje roditeljski direktorij kada nedostaje.
     */
    private function ensureParentDirectory(string $path): void
    {
        RuntimeLanguageStorage::ensureDirectory(dirname($path));
    }

    /**
     * EN: Validates a safe BCP-47-like locale identifier.
     *
     * HR: Provjerava sigurnu BCP-47 sličnu oznaku jezika.
     */
    private function assertLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));
        if (!$this->validLocale($locale)) {
            throw new RuntimeException('Invalid locale identifier.');
        }

        return $locale;
    }

    /**
     * EN: Checks the locale identifier shape.
     *
     * HR: Provjerava oblik jezične oznake.
     */
    private function validLocale(string $locale): bool
    {
        return preg_match('/\A[a-z0-9]+(?:[-_][a-z0-9]+)*\z/D', $locale) === 1 && strlen($locale) <= 32;
    }

    /**
     * EN: Returns the application translation directory.
     *
     * HR: Vraća aplikacijski direktorij prijevoda.
     */
    private function languageDirectory(): string
    {
        return rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/lang';
    }

    /**
     * EN: Serializes every language change without ever writing to a lower layer.
     *
     * HR: Serijalizira sve promjene jezika; nikada ne zapisuje u niži sloj.
     *
     * @template T
     * @param callable():T $operation
     * @return T
     */
    private function withConfigurationLock(callable $operation): mixed
    {
        $path = $this->configuration->writableFile('.languages.lock');
        // EN: O_CREAT on another user's existing file is blocked by Linux protected_regular
        // in sticky directories. Open existing locks without creation, new locks exclusively.
        //
        // HR: Linux protected_regular u sticky direktoriju blokira O_CREAT nad tuđom postojećom
        // datotekom. Postojeći lock otvaramo bez stvaranja, a novi stvaramo isključivo.
        $handle = @fopen($path, is_file($path) ? 'r+b' : 'x+b');
        if ($handle === false && is_file($path) && !is_link($path)) {
            $handle = @fopen($path, 'r+b');
        }

        if ($handle === false) {
            throw new RuntimeException('Unable to open the language configuration lock.');
        }

        try {
            if (((fileperms($path) & 0777) !== 0660 && !chmod($path, 0660)) || !flock($handle, LOCK_EX)) {
                throw new RuntimeException('Unable to secure or acquire the language configuration lock.');
            }

            $runtimeLock = $this->storage->lock();
            try {
                return $operation();
            } finally {
                flock($runtimeLock, LOCK_UN);
                fclose($runtimeLock);
            }
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    /**
     * EN: An installed runtime catalogue or packaged application catalogue supplies the locale.
     *
     * HR: Jezik pruža instalirani runtime katalog ili paketni aplikacijski katalog.
     */
    private function installed(string $locale): bool
    {
        return $this->storage->installed($locale)
        || is_file($this->languageDirectory() . '/' . $locale . '.php');
    }

    /**
     * EN: Validates every destination and restores all changed bytes after an incomplete install/remove.
     * The caller holds both locks; package files and unrelated configuration are never targets.
     *
     * HR: Provjerava svaki cilj i vraća sve izmijenjene bajtove nakon nepotpune instalacije/uklanjanja.
     * Pozivatelj drži oba locka; paketne datoteke i nepovezane postavke nikada nisu ciljevi.
     *
     * @template T
     * @param callable():T $operation
     * @return T
     */
    private function withLocaleRollback(string $locale, bool $installation, callable $operation): mixed
    {
        $resources = ['catalogs/' . $locale . '.json', 'flags/' . $locale . '.svg', 'repository-versions.json'];
        $original = [];
        foreach ($resources as $relative) {
            $this->storage->assertWritable($relative);
            $path = $this->storage->path($relative);
            $original[$relative] = ['bytes' => $this->storage->bytes($relative),
                'mode' => is_file($path) ? fileperms($path) & 0777 : 0660];
        }

        $config = [];
        foreach ($installation ? ['languages.php', 'installation.php'] : ['languages.php'] as $file) {
            $path = $this->configuration->writableFile($file);
            $config[$path] = ['bytes' => is_file($path) ? (string)file_get_contents($path) : null,
                'mode' => is_file($path) ? fileperms($path) & 0777 : 0660];
        }

        try {
            return $operation();
        } catch (\Throwable $throwable) {
            foreach ($original as $relative => $entry) {
                if ($this->storage->bytes($relative) !== $entry['bytes']) {
                    $entry['bytes'] === null ? $this->storage->remove($relative)
                    : $this->storage->write($relative, $entry['bytes'], $entry['mode']);
                }
            }

            foreach ($config as $path => $entry) {
                $current = is_file($path) ? file_get_contents($path) : null;
                if ($current !== $entry['bytes']) {
                    $entry['bytes'] === null ? unlink($path)
                    : $this->atomicWrite($path, $entry['bytes'], $entry['mode']);
                }
            }

            throw $throwable;
        }
    }
}
