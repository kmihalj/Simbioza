<?php

declare(strict_types=1);

namespace App\Localization;

use App\Module\ModuleCatalog;
use Composer\InstalledVersions;
use DOMDocument;
use DOMElement;
use JsonException;
use RuntimeException;

/**
 * HR: Skuplja prijevode aplikacije i svih ugrađenih modula u jedan
 *     prenosivi paket te sigurno instalira provjereni prijevod.
 * EN: Collects application and bundled-module translations into one portable
 *     pack and safely installs a validated translation.
 */
final readonly class LanguagePackManager
{
    /**
     * HR: Prima korijen aplikacije i katalog ugrađenih modula.
     * EN: Receives the application root and bundled-module catalog.
     */
    public function __construct(
        private string $appRoot,
        private ModuleCatalog $modules,
    ) {
    }

    /**
     * HR: Navodi instalirane jezične datoteke i pokrivenost u odnosu na izvorni jezik.
     * EN: Lists installed language files and their coverage against the source locale.
     *
     * @return list<array{locale:string,native_name:string,keys:int,reference_keys:int,coverage:float}>
     */
    public function status(string $sourceLocale = 'en'): array
    {
        $referenceCount = count($this->catalog($sourceLocale));
        $registry = $this->languageRegistry();
        $files = glob($this->languageDirectory() . '/*.php') ?: [];
        sort($files, SORT_STRING);
        $result = [];
        foreach ($files as $file) {
            $locale = pathinfo($file, PATHINFO_FILENAME);
            if (!is_string($locale) || !$this->validLocale($locale)) {
                continue;
            }

            $count = count($this->catalog($locale));
            $result[] = [
                'locale' => $locale,
                'native_name' => is_string($registry[$locale]['native_name'] ?? null)
                    ? $registry[$locale]['native_name']
                    : strtoupper($locale),
                'keys' => $count,
                'reference_keys' => $referenceCount,
                'coverage' => $referenceCount === 0 ? 100.0 : min(100.0, $count * 100 / $referenceCount),
            ];
        }

        return $result;
    }

    /**
     * HR: Izrađuje urediv JSON predložak sa svim jedinstvenim ključevima
     *     aplikacije i modula, uz očuvane zamjenske oznake.
     * EN: Creates an editable JSON template containing every unique application
     *     and module key while preserving placeholder tokens.
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
     * HR: Provjerava format, potpunost i zamjenske oznake bez zapisivanja.
     * EN: Validates pack format, completeness, and placeholders without writing.
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
     * HR: Instalira objedinjeni prijevod kao jednu aplikacijsku jezičnu
     *     datoteku i dodaje jezik na popis dostupnih jezika sitea.
     * EN: Installs the consolidated translation as one application language
     *     file and adds the locale to the site's available locales.
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
    public function install(string $path, bool $replace = false, bool $allowMissing = false): array
    {
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
        $target = $this->languageDirectory() . '/' . $pack['locale'] . '.php';
        if (is_file($target) && !$replace) {
            throw new RuntimeException('Language already exists; pass --replace to overwrite it.');
        }

        $translations = $pack['translations'];
        ksort($translations, SORT_STRING);
        $contents = "<?php\n\ndeclare(strict_types=1);\n\n"
        . "// HR: Objedinjeni jezični paket generiran alatom Simbioze.\n"
        . "// EN: Consolidated language pack generated by the Simbioza tool.\n"
        . 'return ' . var_export($translations, true) . ";\n";
        $this->atomicWrite($target, $contents, 0644);
        $flagTarget = $this->flagDirectory() . '/' . $pack['locale'] . '.svg';
        $this->atomicWrite($flagTarget, $pack['flag_svg'] . "\n", 0644);
        $this->registerLanguage(
            $pack['locale'],
            $pack['names'],
            $pack['native_name'],
            basename($flagTarget),
        );
        $this->enableLocale($pack['locale']);

        return $validation;
    }

    /**
     * HR: Skuplja katalog jednoga jezika uz isti prioritet kojim runtime
     *     daje prednost aplikaciji ispred modula.
     * EN: Collects one locale using the same precedence that gives application
     *     translations priority over module translations at runtime.
     *
     * @return array<string,string>
     */
    public function catalog(string $locale): array
    {
        $locale = $this->assertLocale($locale);
        $result = [];
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
     * HR: Vraća datoteke aplikacije i instaliranih modula po prioritetu.
     * EN: Returns application and installed-module files in precedence order.
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
     * HR: Pretvara ugniježdene prijevode u stabilne ključeve s točkama.
     * EN: Flattens nested translations into stable dot-separated keys.
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
     * HR: Učitava i strogo provjerava strukturu JSON paketa.
     * EN: Reads and strictly validates the JSON pack structure.
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
            ...array_keys($this->languageRegistry()),
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
        $flagSvg = $this->validatedFlagSvg($flagSvg);

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
     * HR: Vraća sortirani multiskup zamjenskih oznaka.
     * EN: Returns a sorted placeholder multiset.
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
     * HR: Strogo provjerava mali, statični podskup SVG-a dopušten za zastavice.
     * EN: Strictly validates the small static SVG subset allowed for flags.
     */
    private function validatedFlagSvg(string $svg): string
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
     * HR: Vraća provjereni registar jezika instalacije.
     * EN: Returns the validated installation language registry.
     *
     * @return array<string,array{names:array<string,string>,native_name:string,flag:string}>
     */
    private function languageRegistry(): array
    {
        $path = $this->languageRegistryPath();
        $source = is_file($path) ? require $path : [];
        $result = [];
        foreach (is_array($source) ? $source : [] as $locale => $definition) {
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
     * HR: Atomski dodaje metapodatke jezika.
     * EN: Atomically appends language metadata.
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
        ksort($registry, SORT_STRING);
        $contents = "<?php\n\ndeclare(strict_types=1);\n\n"
        . "// HR: Registar jezika održava alat Simbioze.\n"
        . "// EN: Language registry maintained by the Simbioza tool.\n"
        . 'return ' . var_export($registry, true) . ";\n";
        $this->atomicWrite($this->languageRegistryPath(), $contents, 0640);
    }

    /**
     * HR: Dodaje jezik u privatnu instalacijsku konfiguraciju.
     * EN: Adds the locale to private installation configuration.
     */
    private function enableLocale(string $locale): void
    {
        $path = $this->appRoot . '/config/installation.php';
        $configuration = is_file($path) ? require $path : [];
        if (!is_array($configuration)) {
            $configuration = [];
        }

        $locales = is_array($configuration['supported_locales'] ?? null)
        ? array_values(array_filter($configuration['supported_locales'], is_string(...)))
        : [];
        if (!in_array($locale, $locales, true)) {
            $locales[] = $locale;
        }

        $configuration['supported_locales'] = $locales;
        $contents = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($configuration, true) . ";\n";
        // HR: FPM i deploy/CLI identiteti dijele isključivu runtime grupu.
        //     Oba moraju moći pročitati i kasnije atomski ažurirati ovaj config.
        // EN: The FPM and deploy/CLI identities share the exclusive runtime
        //     group. Both must be able to read and later atomically update it.
        $this->atomicWrite($path, $contents, 0660);
    }

    /**
     * HR: Atomski zapisuje datoteku sa zadanim pravima.
     * EN: Atomically writes a file with the requested permissions.
     */
    private function atomicWrite(string $path, string $contents, int $mode): void
    {
        $this->ensureParentDirectory($path);
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
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    /** HR: Izrađuje roditeljski direktorij kada nedostaje. EN: Creates the parent directory when missing. */
    private function ensureParentDirectory(string $path): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create the language-pack directory.');
        }
    }

    /** HR: Provjerava sigurnu BCP-47 sličnu oznaku jezika. EN: Validates a safe BCP-47-like locale identifier. */
    private function assertLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));
        if (!$this->validLocale($locale)) {
            throw new RuntimeException('Invalid locale identifier.');
        }

        return $locale;
    }

    /** HR: Provjerava oblik jezične oznake. EN: Checks the locale identifier shape. */
    private function validLocale(string $locale): bool
    {
        return preg_match('/\A[a-z0-9]+(?:[-_][a-z0-9]+)*\z/D', $locale) === 1 && strlen($locale) <= 32;
    }

    /** HR: Vraća aplikacijski direktorij prijevoda. EN: Returns the application translation directory. */
    private function languageDirectory(): string
    {
        return rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/lang';
    }

    /** HR: Vraća privatni registar jezika. EN: Returns the private language registry path. */
    private function languageRegistryPath(): string
    {
        return rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/config/languages.php';
    }

    /** HR: Vraća direktorij instaliranih zastavica. EN: Returns the installed-flags directory. */
    private function flagDirectory(): string
    {
        return rtrim($this->appRoot, DIRECTORY_SEPARATOR) . '/data/languages/flags';
    }
}
