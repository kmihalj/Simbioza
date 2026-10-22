<?php

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects -- Disposable HTTP verifier defines and invokes local helpers.

// EN: Exercises the actual FPM login, language-check and update forms with a private synthetic repository.
// Only this disposable fixture's repository URL and baseline are temporarily replaced; every changed
// application/configuration/runtime file is restored. No live site or public repository is changed.
//
// HR: Provjerava stvarne FPM obrasce prijave, provjere jezika i nadogradnje privatnim testnim repozitorijem.
// Privremeno se zamjenjuju samo URL repozitorija i polazište ovog izdvojenog testa; sve promijenjene
// aplikacijske, konfiguracijske i runtime datoteke vraćaju se. Ne mijenja živi site ni javni repozitorij.
if (!is_file('/.dockerenv') || !is_file('/usr/local/share/simbioza-fixture/secured-fpm')) {
    throw new RuntimeException('Disposable fixture required.');
}

$root = dirname(__DIR__, 3);
require $root . '/bootstrap.php';
require $root . '/vendor/autoload.php';

/**
 * EN: Returns the selected rendered form's actual action and hidden input fields, including fresh CSRF.
 *
 * HR: Vraća stvarnu radnju odabranog renderiranog obrasca i skrivena polja, uključujući svježi CSRF.
 *
 * @return array{url:string,fields:array<string,string>}
 */
function languageFixtureForm(string $html, string $selector): array
{
    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="UTF-8">' . $html);
    $xpath = new DOMXPath($document);
    $forms = $xpath->query($selector);
    if ($forms === false || $forms->length !== 1 || !$forms->item(0) instanceof DOMElement) {
        throw new RuntimeException('Expected language fixture form is missing.');
    }

    $form = $forms->item(0);
    $fields = [];
    foreach ($form->getElementsByTagName('input') as $input) {
        if ($input->getAttribute('type') === 'hidden') {
            $fields[$input->getAttribute('name')] = $input->getAttribute('value');
        }
    }

    return ['url' => 'http://127.0.0.1' . $form->getAttribute('action'), 'fields' => $fields];
}

/**
 * EN: Keeps cookies and follows the real form redirects without printing credentials or CSRF tokens.
 *
 * HR: Čuva kolačiće i prati stvarna preusmjeravanja obrasca bez ispisa lozinki ili CSRF tokena.
 *
 * @param array<string,string>|null $fields
 */
function languageFixtureRequest(CurlHandle $curl, string $url, ?array $fields = null): string
{
    curl_setopt_array($curl, [CURLOPT_URL => $url, CURLOPT_POST => $fields !== null]);
    if ($fields !== null) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($fields));
    }

    $body = curl_exec($curl);
    if (!is_string($body) || curl_getinfo($curl, CURLINFO_RESPONSE_CODE) !== 200) {
        throw new RuntimeException('Language fixture HTTP request failed.');
    }

    return $body;
}

$paths = [
    $root . '/src/Localization/LanguageRepository.php',
    $root . '/resources/localization/bundled-languages.json',
    $root . '/config/languages.php', $root . '/config/installation.php',
    $root . '/data/languages/catalogs/en.json', $root . '/data/languages/flags/en.svg',
    $root . '/data/languages/repository-versions.json', $root . '/data/cache/language-catalog.json',
];
$original = [];
foreach ($paths as $path) {
    $original[$path] = ['bytes' => is_file($path) ? (string)file_get_contents($path) : null,
        'stat' => is_file($path) ? stat($path) : null];
}

$repository = $root . '/data/e2e-language-repository';
if (!mkdir($repository, 0755)) {
    throw new RuntimeException('A new isolated language repository is required.');
}

mkdir($repository . '/packs', 0755);
$manager = new App\Localization\LanguagePackManager($root, new App\Module\ModuleCatalog());
$packPath = $manager->createTemplate('en', 'en', $repository . '/packs/en.json');
$pack = json_decode((string)file_get_contents($packPath), true, 512, JSON_THROW_ON_ERROR);
$pack['names'] = ['en' => 'English', 'hr' => 'Engleski'];
$pack['native_name'] = 'English';
$pack['flag_svg'] = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 3 2">'
. '<rect width="3" height="2" fill="#123456"/></svg>';
$publish = static function () use ($packPath, $repository, &$pack): string {
    file_put_contents($packPath, json_encode($pack, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    $hash = (string)hash_file('sha256', $packPath);
    file_put_contents($repository . '/manifest.json', json_encode([
        'format' => 'simbioza-language-catalog', 'version' => 1, 'languages' => [[
            'locale' => 'en', 'native_name' => 'English', 'version' => '2026.10.13.1',
            'file' => 'packs/en.json', 'sha256' => $hash, 'status' => 'released',
        ]],
    ], JSON_THROW_ON_ERROR));
    return $hash;
};

try {
    // EN: The finalizer may have refreshed real published packs. Start this synthetic
    // HTTP scenario from bundled English; all previous runtime bytes are restored below.
    //
    // HR: Finalizacija je možda osvježila stvarne objavljene pakete. Ovaj izmišljeni HTTP
    // scenarij započinje ugrađenim engleskim; prethodni runtime bajtovi vraćaju se ispod.
    foreach ([$paths[4], $paths[5]] as $runtimePath) {
        if (is_file($runtimePath)) {
            unlink($runtimePath);
        }
    }

    $versions = is_file($paths[6])
    ? json_decode((string)file_get_contents($paths[6]), true, 64, JSON_THROW_ON_ERROR) : [];
    unset($versions['en']);
    file_put_contents($paths[6], json_encode($versions === [] ? new stdClass() : $versions, JSON_THROW_ON_ERROR));
    $baseline = json_decode((string)$original[$paths[1]]['bytes'], true, 64, JSON_THROW_ON_ERROR);
    $baseline['repository_baseline']['en']['sha256'] = $publish();
    file_put_contents($paths[1], json_encode($baseline, JSON_THROW_ON_ERROR));
    $code = preg_replace(
        "~private const DEFAULT_MANIFEST = '[^']+';~",
        "private const DEFAULT_MANIFEST = 'file://" . $repository . "/manifest.json';",
        (string)$original[$paths[0]]['bytes'],
        -1,
        $count,
    );
    if ($count !== 1) {
        throw new RuntimeException('Unexpected fixture repository declaration.');
    }

    file_put_contents($paths[0], $code);
    // EN: A separate FPM process may still hold the preceding PHP opcode for two seconds.
    //
    // HR: Odvojeni FPM proces može još dvije sekunde čuvati prethodno prevedeni PHP kod.
    sleep(3);
    if (is_file($paths[7])) {
        unlink($paths[7]);
    }

    $curl = curl_init();
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEFILE => '', CURLOPT_TIMEOUT => 60]);
    $login = languageFixtureRequest($curl, 'http://127.0.0.1/simbioza/auth/login');
    $form = languageFixtureForm($login, '//form[@id="local_override_login"]');
    languageFixtureRequest($curl, $form['url'], $form['fields'] + [
        'login' => 'secured-admin', 'password' => 'E9!IsolatedRoundtrip2026',
    ]);
    $page = languageFixtureRequest($curl, 'http://127.0.0.1/simbioza/settings/setup');
    if (
        !str_contains($page, 'data-language-source="bundled"')
        || !str_contains($page, 'Simbioza ' . trim((string)file_get_contents($root . '/VERSION')))
        || str_contains($page, 'Local revision is not recorded')
    ) {
        throw new RuntimeException('The fresh FPM GUI does not recognize bundled translations.');
    }

    $key = 'Jedan jezični paket sadrži prijevode, višejezične nazive jezika i sigurnu SVG zastavicu.';
    $pack['translations'][$key] = 'Verified fixture language update without a CLI installation.';
    $publish();
    $form = languageFixtureForm($page, '//form[input[@name="action" and @value="language-update-check"]]');
    $page = languageFixtureRequest($curl, $form['url'], $form['fields']);
    $form = languageFixtureForm(
        $page,
        '//tr[@data-language-locale="en"]//form[input[@name="action" and @value="language-install"]]',
    );
    $page = languageFixtureRequest($curl, $form['url'], $form['fields']);
    if (
        !str_contains($page, 'data-language-source="repository"')
        || !str_contains($page, $pack['translations'][$key])
    ) {
        $document = new DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">' . $page);
        $alerts = (new DOMXPath($document))->query('//*[@role="alert"]');
        $messages = [];
        foreach ($alerts === false ? [] : $alerts as $alert) {
            $messages[] = trim($alert->textContent);
        }

        throw new RuntimeException('The real FPM GUI did not apply the verified language update: '
        . implode('; ', $messages));
    }

    echo "PASS real FPM GUI login, bundled revision, digest-only check and language update.\n";
} finally {
    foreach ($original as $path => $entry) {
        if ($entry['bytes'] === null) {
            if (is_file($path)) {
                unlink($path);
            }

            continue;
        }

        // EN: Atomic replacement also respects Linux protected_regular in sticky runtime directories.
        //
        // HR: Atomska zamjena poštuje i Linux protected_regular u sticky runtime direktorijima.
        $temporary = tempnam(dirname($path), '.fixture-restore-');
        if (
            !is_string($temporary) || file_put_contents($temporary, $entry['bytes']) === false
            || !chown($temporary, $entry['stat']['uid']) || !chgrp($temporary, $entry['stat']['gid'])
            || !chmod($temporary, $entry['stat']['mode'] & 07777) || !rename($temporary, $path)
        ) {
            throw new RuntimeException('Unable to restore the isolated language fixture.');
        }
    }

    unlink($packPath);
    unlink($repository . '/manifest.json');
    rmdir($repository . '/packs');
    rmdir($repository);
}
