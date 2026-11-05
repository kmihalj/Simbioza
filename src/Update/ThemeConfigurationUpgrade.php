<?php

declare(strict_types=1);

namespace App\Update;

use AaiEduHr\HeartPhrameModuleTheme\ModuleTheme;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeConfigRepository;
use Composer\InstalledVersions;
use HeartPhrame\Config\ConfigInterface;
use ReflectionClass;
use RuntimeException;

/**
 * EN: Shares the additive Theme upgrade between classic and immutable deployments.
 *
 * HR: Dijeli dopunu postavki Tema između klasičnih i nepromjenjivih instalacija.
 */
final class ThemeConfigurationUpgrade
{
    /**
     * EN: Resolves an installed Theme without enabling its GUI or requiring Backup.
     *
     * HR: Razrješava instalirani Theme bez uključivanja GUI-ja ili zahtijevanja Backupa.
     */
    public static function repository(ConfigInterface $config): ?ThemeConfigRepository
    {
        if (!InstalledVersions::isInstalled('aaieduhr/heartphrame-module-theme')) {
            return null;
        }

        $file = (new ReflectionClass(ModuleTheme::class))->getFileName();
        if (!is_string($file)) {
            throw new RuntimeException('Theme module path is unavailable.');
        }

        return new ThemeConfigRepository($config, dirname($file, 2));
    }

    /**
     * EN: Adds missing keys and new collection IDs once; existing theme values remain unchanged.
     *
     * HR: Dodaje nedostajuće ključeve i nove ID-jeve kolekcije jednom; čuva postojeće vrijednosti tema.
     */
    public static function apply(ConfigInterface $config): int
    {
        $repository = self::repository($config);
        if (!$repository instanceof ThemeConfigRepository) {
            return 0;
        }

        return $repository->persistMissingButtonTextColors()
        + (new BundledThemeCollection($repository))->apply($config->getAppRootDir());
    }
}
