<?php

declare(strict_types=1);

// EN: Disposable Linux FPM fixture only; no real installation is accepted.
//
// HR: Samo privremeni Linux FPM fixture; stvarna instalacija nije prihvaćena.
$root = getenv('HPH_APP_PATH');
if ($root !== '/tmp/simbioza-r030-fpm-fixture' || PHP_SAPI !== 'fpm-fcgi') {
    throw new RuntimeException('Unexpected FPM audit fixture.');
}
$resolvedRoot = realpath($root);
if ($resolvedRoot !== $root || !is_file($resolvedRoot . '/vendor/autoload.php')) {
    throw new RuntimeException('Missing or redirected FPM audit fixture.');
}
$root = $resolvedRoot;
require __DIR__ . '/audit_fpm_loader.php';
loadAuditFpmFixture($root);
$layers = \HeartPhrame\Config\ConfigPathResolver::fromEnvironment($root);
$app = new \HeartPhrame\App($layers, $root);
$app->loadCommands();
$container = $app->getContainer();
$container->get(\AaiEduHr\HeartPhrameModuleAuth\Service\AuthSettingsService::class)->saveLoginDurationDays(45);
$registry = $container->get(\AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorHtmlConfig::class)->externalScripts();
$definition = \AaiEduHr\HeartPhrameModuleEditorHtml\Service\EditorExternalScripts::definition([
    'type' => 'inline', 'name' => 'Synthetic FPM probe', 'value' => 'window.auditFpm = true;',
]);
$registry->importPending([$definition]);
$registry->decide($definition['id'], 'approved');
$menu = $container->get(\AaiEduHr\HeartPhrameModuleMenu\Service\MenuConfigRepository::class);
$menu->mutateItemsForSection($menu::SECTION_SETTINGS, static fn(array $items): array => $items);
$theme = $container->get(\AaiEduHr\HeartPhrameModuleTheme\Service\ThemeConfigRepository::class);
$theme->saveSettings($theme->activeThemeId(), $theme::MODE_AUTO, true);
$target = $layers[count($layers) - 1];
$checks = [
    'real_fpm' => true,
    'auth_config' => is_file($target . '/auth/auth.json'),
    'script_registry' => is_file($target . '/editor-html/external-scripts.json')
        && ($registry->all()[$definition['id']]['status'] ?? null) === 'approved',
    'menu_config' => is_file($target . '/menu/settings.json'),
    'theme_config' => is_file($target . '/theme/settings.json'),
    'code_read_only' => !is_writable($root . '/src'),
    'data_writable' => is_writable($root . '/data'),
    'no_old_config_root' => !is_dir($root . '/data/config'),
];

// EN: Installed language packs only write JSON/SVG data and the declared config layer.
// This offline fixture covers install, replacement, runtime lookup, and removal.
//
// HR: Instalirani jezični paketi zapisuju samo JSON/SVG podatke i deklarirani config sloj.
// Ovaj offline fixture pokriva instalaciju, zamjenu, runtime čitanje i uklanjanje.
$languages = $container->get(\App\Localization\LanguagePackManager::class);
$languageFile = $languages->createTemplate('zz-a3', 'en', $root . '/data/a3-language.json');
$languagePack = json_decode((string)file_get_contents($languageFile), true, 512, JSON_THROW_ON_ERROR);
$languagePack['names'] = ['en' => 'Synthetic A3', 'hr' => 'Probni A3', 'zz-a3' => 'Synthetic A3'];
$languagePack['native_name'] = 'Synthetic A3';
$languagePack['translations']['Jezici'] = 'A3 language data';
file_put_contents($languageFile, json_encode($languagePack, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
$languages->install($languageFile, false, false, false);
$languages->setActive('zz-a3', true);
$translator = $container->get(\HeartPhrame\Localization\TranslatorInterface::class);
$checks['language_json_only'] = is_file($root . '/data/languages/catalogs/zz-a3.json')
&& !is_file($root . '/lang/zz-a3.php');
$checks['language_runtime_translation'] = $translator->trans('Jezici', [], 'zz-a3') === 'A3 language data';
$checks['language_module_fallback'] = $translator->trans('Omogući lokalnu prijavu', [], 'en') === 'Enable local login';
$languages->install($languageFile, true, false, false);
$checks['language_replace_with_read_only_code'] = !is_writable($root . '/lang')
&& $languages->catalog('zz-a3')['Jezici'] === 'A3 language data';
$languages->uninstall('zz-a3');
$checks['language_remove_data_only'] = !is_file($root . '/data/languages/catalogs/zz-a3.json')
&& !is_file($root . '/data/languages/flags/zz-a3.svg');
unlink($languageFile);
if (count($layers) > 1) {
    $checks['lower_config_read_only'] = !is_writable($layers[0]);
}
header('Content-Type: application/json');
echo json_encode($checks, JSON_THROW_ON_ERROR), PHP_EOL;
if (in_array(false, $checks, true)) {
    throw new RuntimeException('FPM audit failed.');
}
