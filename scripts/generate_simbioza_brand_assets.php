<?php

/**
 * HR: Iz cjelovitog Simbioza master PNG-a izrađuje šest paleta transparentnih
 *     hero vizuala, pripadajuće ikone i stvarne vektorske SVG izvedbe.
 *
 * EN: Builds six palettes of transparent hero artwork, matching icons, and
 *     real vector SVG variants from the complete Simbioza master PNG.
 */

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols -- HR/EN: Izvršna generator skripta s privatnim pomoćnicima.

const CANVAS_SIZE = 1600;
const ARTWORK_SIZE = 1400;
const ICON_SIZE = 512;

$root = dirname(__DIR__);
$themeRoot = $root . '/data/themes/simbioza';
$sourcePath = $themeRoot . '/source/simbioza-master-natural-dark.png';
$assetDirectory = $themeRoot . '/assets';
$temporaryDirectory = $root . '/build/simbioza-branding-' . bin2hex(random_bytes(4));

if (!extension_loaded('gd')) {
    throw new RuntimeException('The GD extension is required to generate Simbioza branding assets.');
}

if (!is_file($sourcePath)) {
    throw new RuntimeException('Missing Simbioza master image: ' . $sourcePath);
}

if (!is_executable('/opt/homebrew/bin/potrace') && trim((string)shell_exec('command -v potrace')) === '') {
    throw new RuntimeException('Potrace is required to generate real vector SVG assets.');
}

ensureDirectory($assetDirectory);
ensureDirectory($temporaryDirectory);

$palettes = [
    'natural-light' => [
        'label_hr' => 'Prirodna svijetla',
        'label_en' => 'Natural light',
        'anemone' => '#E85A40',
        'shell' => '#DDAA2C',
        'crab' => '#073E4A',
        'line' => '#FFF8EE',
    ],
    'adriatic-light' => [
        'label_hr' => 'Jadranska svijetla',
        'label_en' => 'Adriatic light',
        'anemone' => '#20AEB1',
        'shell' => '#D85D3F',
        'crab' => '#173E70',
        'line' => '#FFF8EE',
    ],
    'botanical-light' => [
        'label_hr' => 'Botanička svijetla',
        'label_en' => 'Botanical light',
        'anemone' => '#E39A16',
        'shell' => '#58A99A',
        'crab' => '#5A3153',
        'line' => '#FFF8EE',
    ],
    'natural-dark' => [
        'label_hr' => 'Prirodna tamna',
        'label_en' => 'Natural dark',
        'anemone' => '#FF6C4C',
        'shell' => '#FDB52C',
        'crab' => '#61CFC2',
        'line' => '#FFF4E7',
    ],
    'adriatic-dark' => [
        'label_hr' => 'Jadranska tamna',
        'label_en' => 'Adriatic dark',
        'anemone' => '#31C9C5',
        'shell' => '#FA785B',
        'crab' => '#7EA5DF',
        'line' => '#F7F5FF',
    ],
    'botanical-dark' => [
        'label_hr' => 'Botanička tamna',
        'label_en' => 'Botanical dark',
        'anemone' => '#FFB11F',
        'shell' => '#76C1B4',
        'crab' => '#CBA5D2',
        'line' => '#FFF7EE',
    ],
];

$source = imagecreatefrompng($sourcePath);
if (!($source instanceof GdImage)) {
    throw new RuntimeException('Unable to open the Simbioza master image.');
}

$master = transparentCanvas(CANVAS_SIZE, CANVAS_SIZE);
$offset = intdiv(CANVAS_SIZE - ARTWORK_SIZE, 2);
imagecopyresampled(
    $master,
    $source,
    $offset,
    $offset,
    0,
    0,
    ARTWORK_SIZE,
    ARTWORK_SIZE,
    imagesx($source),
    imagesy($source),
);

$classes = classifyPixels($master);
$vectorGroups = traceVectorGroups($classes, $temporaryDirectory);
$manifestAssets = [];

foreach ($palettes as $paletteId => $palette) {
    $heroBase = 'hero-' . $paletteId;
    $iconBase = 'icon-' . $paletteId;
    $heroPng = $assetDirectory . '/' . $heroBase . '.png';
    $heroSvg = $assetDirectory . '/' . $heroBase . '.svg';
    $iconPng = $assetDirectory . '/' . $iconBase . '.png';
    $iconSvg = $assetDirectory . '/' . $iconBase . '.svg';

    $rendered = renderPalette($master, $classes, $palette);
    writePng($rendered, $heroPng);
    writeIcon($rendered, $iconPng);
    writeSvg($heroSvg, $vectorGroups, $palette, CANVAS_SIZE, CANVAS_SIZE);
    writeSvg($iconSvg, $vectorGroups, $palette, ICON_SIZE, ICON_SIZE);

    $manifestAssets[] = assetMetadata(
        $heroBase . '.png',
        'hero',
        $palette['label_hr'] . ' - hero PNG',
        $palette['label_en'] . ' - hero PNG',
        'image/png',
        CANVAS_SIZE,
        CANVAS_SIZE,
        $heroPng,
    );
    $manifestAssets[] = assetMetadata(
        $heroBase . '.svg',
        'hero',
        $palette['label_hr'] . ' - hero SVG',
        $palette['label_en'] . ' - hero SVG',
        'image/svg+xml',
        CANVAS_SIZE,
        CANVAS_SIZE,
        $heroSvg,
    );
    $manifestAssets[] = assetMetadata(
        $iconBase . '.png',
        'icon',
        $palette['label_hr'] . ' - ikona PNG',
        $palette['label_en'] . ' - icon PNG',
        'image/png',
        ICON_SIZE,
        ICON_SIZE,
        $iconPng,
    );
    $manifestAssets[] = assetMetadata(
        $iconBase . '.svg',
        'icon',
        $palette['label_hr'] . ' - ikona SVG',
        $palette['label_en'] . ' - icon SVG',
        'image/svg+xml',
        ICON_SIZE,
        ICON_SIZE,
        $iconSvg,
    );
}

deleteDirectory($temporaryDirectory);

file_put_contents(
    $themeRoot . '/theme-assets.json',
    json_encode(
        ['version' => 1, 'assets' => $manifestAssets],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
    ) . "\n",
);

fwrite(STDOUT, "Generated " . count($manifestAssets) . " Simbioza branding assets.\n");

/**
 * HR: Kreira direktorij ako ne postoji.
 * EN: Creates a directory when it does not exist.
 */
function ensureDirectory(string $directory): void
{
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create directory: ' . $directory);
    }
}

/**
 * HR: Kreira potpuno prozirno GD platno s uključenim alpha kanalom.
 * EN: Creates a fully transparent GD canvas with alpha preservation enabled.
 */
function transparentCanvas(int $width, int $height): GdImage
{
    $image = imagecreatetruecolor($width, $height);
    if (!($image instanceof GdImage)) {
        throw new RuntimeException('Unable to create an image canvas.');
    }

    imagealphablending($image, false);
    imagesavealpha($image, true);
    $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagefill($image, 0, 0, $transparent);

    return $image;
}

/**
 * HR: Svakom pikselu dodjeljuje anatomsku skupinu prema boji master ilustracije.
 * EN: Assigns every pixel to an anatomical group based on the master artwork color.
 *
 * @return list<string>
 */
function classifyPixels(GdImage $image): array
{
    $classes = [];
    $width = imagesx($image);
    $height = imagesy($image);

    for ($y = 0; $y < $height; ++$y) {
        for ($x = 0; $x < $width; ++$x) {
            $rgba = imagecolorat($image, $x, $y);
            $alpha = ($rgba >> 24) & 0x7F;
            if ($alpha >= 126) {
                $classes[] = 'transparent';
                continue;
            }

            $red = ($rgba >> 16) & 0xFF;
            $green = ($rgba >> 8) & 0xFF;
            $blue = $rgba & 0xFF;
            [$hue, $saturation, $value] = rgbToHsv($red, $green, $blue);
            if ($saturation < 0.22 && $value > 0.65) {
                $classes[] = 'line';
            } elseif ($hue < 32.0 || $hue >= 330.0) {
                $classes[] = 'anemone';
            } elseif ($hue < 95.0) {
                $classes[] = 'shell';
            } else {
                $classes[] = 'crab';
            }
        }
    }

    return $classes;
}

/**
 * HR: Pretvara RGB u HSV radi stabilnog razdvajanja triju boja motiva.
 * EN: Converts RGB to HSV for stable separation of the artwork's three colors.
 *
 * @return array{float, float, float}
 */
function rgbToHsv(int $red, int $green, int $blue): array
{
    $r = $red / 255;
    $g = $green / 255;
    $b = $blue / 255;
    $maximum = max($r, $g, $b);
    $minimum = min($r, $g, $b);
    $delta = $maximum - $minimum;
    $hue = 0.0;

    if ($delta > 0.0) {
        if ($maximum === $r) {
            $hue = 60.0 * fmod((($g - $b) / $delta), 6.0);
        } elseif ($maximum === $g) {
            $hue = 60.0 * ((($b - $r) / $delta) + 2.0);
        } else {
            $hue = 60.0 * ((($r - $g) / $delta) + 4.0);
        }
    }

    if ($hue < 0.0) {
        $hue += 360.0;
    }

    return [$hue, $maximum === 0.0 ? 0.0 : $delta / $maximum, $maximum];
}

/**
 * HR: Renderira jednu paletu uz očuvanje izvornog alpha ruba.
 * EN: Renders one palette while preserving the source alpha edge.
 *
 * @param list<string> $classes
 * @param array<string, string> $palette
 */
function renderPalette(GdImage $master, array $classes, array $palette): GdImage
{
    $image = transparentCanvas(imagesx($master), imagesy($master));
    $colors = [];
    foreach (['anemone', 'shell', 'crab', 'line'] as $class) {
        $colors[$class] = hexRgb($palette[$class]);
    }

    $index = 0;
    for ($y = 0; $y < imagesy($master); ++$y) {
        for ($x = 0; $x < imagesx($master); ++$x, ++$index) {
            $class = $classes[$index];
            if ($class === 'transparent') {
                continue;
            }

            $source = imagecolorat($master, $x, $y);
            $alpha = ($source >> 24) & 0x7F;
            [$red, $green, $blue] = $colors[$class];
            $color = imagecolorallocatealpha($image, $red, $green, $blue, $alpha);
            imagesetpixel($image, $x, $y, $color);
        }
    }

    return $image;
}

/**
 * HR: Pretvara heksadecimalnu boju u RGB komponente.
 * EN: Converts a hexadecimal color to RGB components.
 *
 * @return array{int, int, int}
 */
function hexRgb(string $hex): array
{
    $hex = ltrim($hex, '#');

    return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
}

/**
 * HR: Sprema transparentni PNG bez gubitka alpha kanala.
 * EN: Saves a transparent PNG without losing its alpha channel.
 */
function writePng(GdImage $image, string $path): void
{
    if (!imagepng($image, $path, 9)) {
        throw new RuntimeException('Unable to write PNG: ' . $path);
    }
}

/**
 * HR: Iz hero motiva izrađuje kvadratnu ikonu visoke kvalitete.
 * EN: Creates a high-quality square icon from the hero artwork.
 */
function writeIcon(GdImage $hero, string $path): void
{
    $icon = transparentCanvas(ICON_SIZE, ICON_SIZE);
    imagecopyresampled(
        $icon,
        $hero,
        0,
        0,
        0,
        0,
        ICON_SIZE,
        ICON_SIZE,
        imagesx($hero),
        imagesy($hero),
    );
    writePng($icon, $path);
}

/**
 * HR: Za svaku anatomsku skupinu izrađuje Potrace vektorsku grupu.
 * EN: Builds a Potrace vector group for every anatomical color group.
 *
 * @param list<string> $classes
 * @return array<string, string>
 */
function traceVectorGroups(array $classes, string $temporaryDirectory): array
{
    $groups = [];
    foreach (['anemone', 'shell', 'crab', 'line'] as $class) {
        $mask = imagecreate(CANVAS_SIZE, CANVAS_SIZE);
        if (!($mask instanceof GdImage)) {
            throw new RuntimeException('Unable to create a vector mask.');
        }

        $white = imagecolorallocate($mask, 255, 255, 255);
        $black = imagecolorallocate($mask, 0, 0, 0);
        imagefill($mask, 0, 0, $white);

        $index = 0;
        for ($y = 0; $y < CANVAS_SIZE; ++$y) {
            for ($x = 0; $x < CANVAS_SIZE; ++$x, ++$index) {
                if ($classes[$index] === $class) {
                    imagesetpixel($mask, $x, $y, $black);
                }
            }
        }

        $pngPath = $temporaryDirectory . '/' . $class . '.png';
        $pbmPath = $temporaryDirectory . '/' . $class . '.pbm';
        $svgPath = $temporaryDirectory . '/' . $class . '.svg';
        imagepng($mask, $pngPath);

        runCommand(['magick', $pngPath, '-threshold', '50%', $pbmPath]);
        runCommand([
            'potrace', '--svg', '--flat', '--turdsize', '3', '--opttolerance', '0.18', '--output', $svgPath, $pbmPath,
        ]);
        $svg = file_get_contents($svgPath);
        if (!is_string($svg) || preg_match('#(<g transform="[^"]+"[^>]*>.*?</g>)#s', $svg, $match) !== 1) {
            throw new RuntimeException('Unable to read traced SVG group: ' . $class);
        }

        $groups[$class] = preg_replace('/fill="#[0-9A-Fa-f]{6}"/', 'fill="{{COLOR}}"', $match[1], 1) ?? '';
    }

    return $groups;
}

/**
 * HR: Izvršava vanjsku naredbu bez shell interpolacije i provjerava izlazni status.
 * EN: Runs an external command without shell interpolation and checks its exit status.
 *
 * @param list<string> $arguments
 */
function runCommand(array $arguments): void
{
    $command = implode(' ', array_map(escapeshellarg(...), $arguments));
    exec($command . ' 2>&1', $output, $status);
    if ($status !== 0) {
        throw new RuntimeException('Command failed: ' . implode("\n", $output));
    }
}

/**
 * HR: Piše samostalni skalabilni SVG s potpuno prozirnom pozadinom.
 * EN: Writes a standalone scalable SVG with a fully transparent background.
 *
 * @param array<string, string> $groups
 * @param array<string, string> $palette
 */
function writeSvg(
    string $path,
    array $groups,
    array $palette,
    int $width,
    int $height,
): void {
    $content = [];
    foreach (['anemone', 'shell', 'crab', 'line'] as $class) {
        $content[] = str_replace('{{COLOR}}', $palette[$class], $groups[$class]);
    }

    $svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
    . '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '"'
    . ' viewBox="0 0 ' . CANVAS_SIZE . ' ' . CANVAS_SIZE . '" role="img"'
    . ' aria-labelledby="title description">' . "\n"
    . '  <title id="title">Simbioza</title>' . "\n"
    . '  <desc id="description">Hermit crab and sea anemone brand mark.</desc>' . "\n"
    . implode("\n", $content) . "\n"
    . '</svg>' . "\n";

    if (file_put_contents($path, $svg) === false) {
        throw new RuntimeException('Unable to write SVG: ' . $path);
    }
}

/**
 * HR: Gradi zapis biblioteke teme za jednu generiranu datoteku.
 * EN: Builds one theme-library record for a generated file.
 *
 * @return array<string, mixed>
 */
function assetMetadata(
    string $file,
    string $role,
    string $labelHr,
    string $labelEn,
    string $mime,
    int $width,
    int $height,
    string $path,
): array {
    return [
        'file' => $file,
        'role' => $role,
        'label' => ['hr' => $labelHr, 'en' => $labelEn],
        'mime' => $mime,
        'width' => $width,
        'height' => $height,
        'bytes' => filesize($path),
        'sha256' => hash_file('sha256', $path),
    ];
}

/**
 * HR: Rekurzivno uklanja isključivo privremeni direktorij generatora.
 * EN: Recursively removes only the generator's temporary directory.
 */
function deleteDirectory(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $entry) {
        if ($entry->isDir()) {
            rmdir($entry->getPathname());
        } else {
            unlink($entry->getPathname());
        }
    }

    rmdir($directory);
}
