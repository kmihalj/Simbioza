<?php

declare(strict_types=1);

use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeArchiveService;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeAssetLibrary;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeConfigRepository;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemePackageExporter;
use AaiEduHr\HeartPhrameModuleTheme\Service\ThemeRenderer;
use App\Update\BundledThemeCollection;
use HeartPhrame\Config\Config;
use HeartPhrame\Helper\Helper;
use HeartPhrame\Routing\UrlGenerator;

// EN: Builds isolated, native previews without reading or changing installed site settings.
//
// HR: Izrađuje odvojene izvorne preglede bez čitanja ili mijenjanja postavki instalacije.
require __DIR__ . '/../vendor/autoload.php';
$root = dirname(__DIR__);
$out = $argv[1] ?? $root . '/output/theme-gallery';
if (!str_starts_with($out, '/') || is_link($out)) {
    throw new RuntimeException('Use an absolute, non-linked output directory.');
}

$fixture = $out . '/fixture';
@mkdir($fixture . '/config/theme', 0770, true);
putenv('HPH_CONFIG_PATH=' . $fixture . '/config');
putenv('HPH_CONFIG_OVERRIDE_PATHS');
$config = new Config(new Helper(), [
    'app' => ['localization' => ['locale' => 'en', 'fallback_locale' => 'en', 'supported_locales' => ['en', 'hr']]],
], $fixture);
$themes = new ThemeConfigRepository($config, $root . '/vendor/aaieduhr/heartphrame-module-theme');
$archives = new ThemeArchiveService($themes, new ThemeAssetLibrary($themes));
$archives->importFile($root . '/resources/installation/theme/simbioza.zip', true);
if (!is_dir($fixture . '/resources')) {
    symlink($root . '/resources', $fixture . '/resources');
}

(new BundledThemeCollection($themes))->apply($fixture);
$urls = new class extends UrlGenerator {
    public function __construct()
    {
    }

    public function namedRouteExists(string $name): bool
    {
        return false;
    }

    public function getBasePath(): string
    {
        return '';
    }
};
$exporter = new ThemePackageExporter($themes, new ThemeRenderer($themes, $urls));
$catalog = [];
@mkdir($out . '/packages', 0770, true);
foreach ($themes->themes() as $theme) {
    $id = $theme['id'];
    $preview = $out . '/preview/' . $id;
    @mkdir($preview, 0770, true);
    $temporary = $out . '/preview.zip';
    file_put_contents($temporary, $exporter->export($id));
    $zip = new ZipArchive();
    if ($zip->open($temporary) !== true || !$zip->extractTo($preview)) {
        throw new RuntimeException('Cannot extract portable preview.');
    }

    $zip->close();
    unlink($temporary);
    $html = (string)file_get_contents($preview . '/index.html');
    $name = htmlspecialchars($themes->labelFor($theme['label'], 'en'), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    file_put_contents($preview . '/index.html', str_replace(['Theme guidelines', 'Upute za temu'], $name, $html));
    file_put_contents($out . '/packages/' . $id . '.zip', $archives->export($id));
    $catalog[] = ['id' => $id, 'label' => $theme['label'],
        'light' => $id . '-light.webp', 'dark' => $id . '-dark.webp'];
    echo $id . PHP_EOL;
}

if (count($catalog) !== 26) {
    throw new RuntimeException('Expected all 26 release themes.');
}

file_put_contents($out . '/catalog.json', json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL);
