<?php

declare(strict_types=1);

use App\Configuration\ConfigurationPaths;
use App\Configuration\PhpConfigurationReader;
use App\Localization\RuntimeLanguageStorage;
use App\Module\ModuleCatalog;
use App\Module\ModuleStateStore;

// EN: The code path is not the config path; the same rules apply before DI/GUI.
//
// HR: Putanja koda nije putanja configa; ista pravila vrijede prije DI/GUI-ja.
$configuredRoot = getenv('HPH_APP_PATH');
$appRoot = is_string($configuredRoot) && trim($configuredRoot) !== ''
? rtrim($configuredRoot, DIRECTORY_SEPARATOR)
: dirname(__DIR__);
$configuration = ConfigurationPaths::fromEnvironment($appRoot);
$reader = new PhpConfigurationReader($configuration);
$installation = $reader->read('installation.php');
$catalog = new ModuleCatalog();
$enabledModules = array_values(array_unique([
    ...$catalog->packagesForSelection([]),
    ...(new ModuleStateStore($appRoot, $catalog))->enabledPackages(),
]));

// EN: Installation-private packages are outside the public catalog and GUI state.
//     The local list is administrator configuration preserved by the updater.
//
// HR: Privatni paketi jedne instalacije nisu dio javnog kataloga ni stanja GUI-ja.
//     Lokalni popis je administratorska konfiguracija koju updater čuva.
$localModulesFile = $configuration->effectiveFile('modules.local.php');
if ($localModulesFile !== null) {
    if (function_exists('opcache_invalidate')) {
        opcache_invalidate($localModulesFile, true);
    }

    $localModules = require $localModulesFile;
    if (!is_array($localModules)) {
        throw new RuntimeException('Local modules configuration must return an array.');
    }

    foreach ($localModules as $package) {
        if (!is_string($package) || preg_match('/\A[a-z0-9][a-z0-9_.-]*\/[a-z0-9][a-z0-9_.-]*\z/D', $package) !== 1) {
            throw new RuntimeException('Invalid local module package name.');
        }

        $enabledModules[] = $package;
    }

    $enabledModules = array_values(array_unique($enabledModules));
}

$applicationName = is_string($installation['name'] ?? null) && trim($installation['name']) !== ''
? trim($installation['name'])
: 'Simbioza';
$primaryLocale = is_string($installation['primary_locale'] ?? null)
? strtolower(trim($installation['primary_locale']))
: 'hr';
$languageRegistry = $reader->readDocument('languages.php');
$runtimeLanguages = new RuntimeLanguageStorage($appRoot);
$availableLocales = [];
$localeLabels = [];
foreach ($languageRegistry as $locale => $definition) {
    if (
        is_string($locale)
        && preg_match('/\A[A-Za-z0-9]+(?:[-_][A-Za-z0-9]+)*\z/D', $locale) === 1
        && (is_file($appRoot . '/lang/' . $locale . '.php') || $runtimeLanguages->installed(strtolower($locale)))
    ) {
        $normalizedLocale = strtolower($locale);
        $availableLocales[] = $normalizedLocale;
        $nativeName = is_array($definition) ? ($definition['native_name'] ?? null) : null;
        if (is_string($nativeName) && trim($nativeName) !== '') {
            $localeLabels[$normalizedLocale] = trim($nativeName);
        }
    }
}

$availableLocales = array_values(array_unique($availableLocales));
if ($availableLocales === []) {
    $availableLocales = ['hr', 'en'];
}

$supportedLocales = is_array($installation['supported_locales'] ?? null)
? array_values(array_filter(
    $installation['supported_locales'],
    static fn(mixed $locale): bool => is_string($locale) && in_array($locale, $availableLocales, true),
))
: array_values(array_intersect(['hr', 'en'], $availableLocales));
if ($supportedLocales === []) {
    $supportedLocales = [in_array('hr', $availableLocales, true) ? 'hr' : $availableLocales[0]];
}

if (!in_array($primaryLocale, $supportedLocales, true)) {
    $primaryLocale = $supportedLocales[0];
}

$timezone = is_string($installation['timezone'] ?? null)
&& in_array($installation['timezone'], timezone_identifiers_list(), true)
? $installation['timezone']
: 'Europe/Zagreb';

// EN: Installations on one domain may use distinct session cookie names.
//
// HR: Instalacije na istoj domeni mogu imati različita imena sesijskih kolačića.
$sessionName = is_string($installation['session_name'] ?? null)
&& preg_match('/\A[A-Za-z][A-Za-z0-9_]{0,63}\z/D', $installation['session_name']) === 1
? $installation['session_name']
: 'HEARTPHRAME_SESSION';

return [
    // EN: Initial Simbioza policy; Auth stores it in JSON on first creation.
    //
    // HR: Početna politika Simbioze; Auth je pri prvom kreiranju sprema u JSON.
    'auth' => ['require_provider_verification' => true],
    // Application name
    'name' => $applicationName,

    // EN: Reuse the installed public prefix in HTTP and CLI integrations alike.
    //
    // HR: Koristiti instalirani javni prefiks jednako u HTTP i CLI integracijama.
    'base_path' => is_string($installation['base_path'] ?? null) ? rtrim($installation['base_path'], '/') : null,

    // EN: Error responses retain a restrictive fallback. ScriptPolicyMiddleware
    // adds request nonces and exact approved content hashes to normal responses.
    // HSTS belongs to the deployment's env.php, not here.
    //
    // HR: Odgovori pogreške zadržavaju stroga zadana pravila. ScriptPolicyMiddleware
    // uobičajenim odgovorima dodaje nonce zahtjeva i točne sažetke odobrenog sadržaja.
    // HSTS pripada instalacijskom env.php, ne ovoj datoteci.
    'security_headers' => [
        'Content-Security-Policy' => "script-src 'self'; script-src-attr 'none'; "
            . "base-uri 'self'; object-src 'none'; frame-ancestors 'self'",
    ],

    // Localization
    'localization' => [
        'locale' => $primaryLocale,
        'fallback_locale' => $primaryLocale,
        'supported_locales' => $supportedLocales,
        'locale_labels' => $localeLabels,
        'flags_dir' => $appRoot . '/data/languages/flags',
        // EN: Browser language applies only without a remembered manual choice;
        //     an unsupported language falls back to the application default.
        //
        // HR: Preglednikov jezik vrijedi samo bez zapamćenog ručnog odabira;
        //     nepodržan jezik pada na zadani jezik aplikacije.
        'detect_browser_locale' => (bool)($installation['detect_browser_locale'] ?? true),
        'translations_dir' => $appRoot . '/lang',
    ],

    // Cache directory
    'cache_dir' => $appRoot . '/data/cache',

    // Logs configuration
    'logs' => [
        // Logs directory
        'dir' => $appRoot . '/data/logs',
        'filename' => 'app.log',
        // EN: Rotation limits technical-log growth to approximately 100 MB.
        //
        // HR: Rotacija ograničava rast tehničkog loga na približno 100 MB.
        'max_bytes' => 10485760,
        'max_files' => 10,
    ],

    // Views configuration
    'views' => [
        'path' => $appRoot . '/views',
        'default_layout' => 'main',
    ],

    'timezone' => $timezone,

    // Session configuration
    'session' => [
        // EN: Backup restore stores only allowlisted portable session options.
        //
        // HR: Vraćanje kopije sprema samo dopuštene prenosive opcije sesije.
        'options' => array_replace([
            'use_cookies' => 1,
            'cookie_secure' => 1,
            'cookie_httponly' => 1,
            'cookie_samesite' => 'Lax',
            'use_only_cookies' => 1,
            'name' => $sessionName,
            // EN: The Auth module enforces the shorter administrator-configured login duration.
            //
            // HR: Auth modul primjenjuje kraće, administratorski podesivo trajanje prijave.
            'gc_maxlifetime' => 31536000,
            'cookie_lifetime' => 0,
        ], array_intersect_key(
            is_array($installation['session_options'] ?? null) ? $installation['session_options'] : [],
            array_flip(['gc_maxlifetime', 'cookie_lifetime', 'cookie_secure', 'cookie_httponly', 'cookie_samesite']),
        )),
        // List of route prefixes for which the session will not be started by the StartSessionMiddleware.
        'excluded_routes' => [
            '/sample/route/prefix', // All routes that start with this prefix will be excluded.
            '/api/v1',
        ],
    ],

    // Modules configuration
    'modules' => [
        // List of loadable module types
        'loadable_types' => [
            'heartphrame-module',
        ],
        // List of enabled modules (package names)
        'enabled' => $enabledModules,
    ],

    'csrf' => [
        // List of route prefixes for which the CSRF token check will not be performed by the CheckCsrfMiddleware.
        'excluded_routes' => [
            '/sample/route/prefix', // All routes that start with this prefix will be excluded.
            '/caldav',
            '/api/v1',
        ],
    ],
];
