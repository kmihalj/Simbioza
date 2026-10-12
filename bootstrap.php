<?php

declare(strict_types=1);

/**
 * EN: Loads central framework parameters before HTTP/CLI configuration, without an autoloader.
 *
 * HR: Centralne framework parametre učitava prije HTTP/CLI konfiguracije i bez autoloadera.
 */
$simbiozaFrameworkFile = __DIR__ . '/framework.php';
$simbiozaFrameworkParameters = is_file($simbiozaFrameworkFile) ? require $simbiozaFrameworkFile : [];
if (!is_array($simbiozaFrameworkParameters)) {
    throw new RuntimeException('framework.php must return an array of HPH environment parameters.');
}
$simbiozaFrameworkParameters += ['HPH_APP_PATH' => __DIR__, 'HPH_CONFIG_PATH' => __DIR__ . '/config'];
foreach ($simbiozaFrameworkParameters as $simbiozaFrameworkKey => $simbiozaFrameworkValue) {
    if (!is_string($simbiozaFrameworkKey) || preg_match('/\AHPH_[A-Z0-9_]+\z/D', $simbiozaFrameworkKey) !== 1
        || !is_string($simbiozaFrameworkValue) || str_contains($simbiozaFrameworkValue, "\0")) {
        throw new RuntimeException('Invalid framework.php parameter.');
    }
    // EN: FPM, systemd, and container environment values take precedence over the local file.
    //
    // HR: FPM, systemd i kontejnerski env imaju prednost nad lokalnom datotekom.
    $simbiozaExistingValue = getenv($simbiozaFrameworkKey);
    if ($simbiozaExistingValue === false || trim($simbiozaExistingValue) === '') {
        putenv($simbiozaFrameworkKey . '=' . $simbiozaFrameworkValue);
    }
}
unset($simbiozaFrameworkFile, $simbiozaFrameworkParameters, $simbiozaFrameworkKey,
    $simbiozaFrameworkValue, $simbiozaExistingValue);
