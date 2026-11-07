<?php

declare(strict_types=1);

namespace App\Update;

use AaiEduHr\HeartPhrameModuleEditorHtml\ModuleEditorHtml;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlConfig;
use AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlStandaloneAssets;
use Composer\InstalledVersions;
use HeartPhrame\Config\ConfigInterface;
use ReflectionClass;
use RuntimeException;

/**
 * EN: Refreshes existing standalone editor CSS only during explicit classic or image upgrades.
 *
 * HR: Osvježava postojeći samostalni editor CSS samo pri izričitoj klasičnoj ili image nadogradnji.
 */
final class EditorHtmlAssetsUpgrade
{
    /**
     * EN: Resolves an installed editor without enabling its GUI, booting ORM or changing HTML versions.
     *
     * HR: Razrješava instalirani editor bez uključivanja GUI-ja, pokretanja ORM-a ili promjene HTML verzija.
     */
    public static function apply(ConfigInterface $config): bool
    {
        if (!InstalledVersions::isInstalled('aaieduhr/heartphrame-module-editor-html')) {
            return false;
        }

        $file = (new ReflectionClass(ModuleEditorHtml::class))->getFileName();
        if (!is_string($file)) {
            throw new RuntimeException('HTML editor module path is unavailable.');
        }

        $editorConfig = new EditorHtmlConfig($config, dirname($file, 2));

        return (new EditorHtmlStandaloneAssets($editorConfig))->refreshStoredStylesheet();
    }
}
