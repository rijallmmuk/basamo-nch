<?php

declare(strict_types=1);

/**
 * Turunkan aset brand web dari logo transparan beresolusi tinggi.
 *
 * Penggunaan:
 * php scripts/generate_brand_assets.php /path/logo-transparent.png public
 */

$sourcePath = $argv[1] ?? null;
$publicPath = rtrim($argv[2] ?? dirname(__DIR__).'/public', '/');

if (! $sourcePath || ! is_file($sourcePath)) {
    fwrite(STDERR, "Logo sumber transparan tidak ditemukan.\n");
    exit(1);
}

$source = imagecreatefrompng($sourcePath);

if (! $source) {
    fwrite(STDERR, "Logo sumber gagal dibaca sebagai PNG.\n");
    exit(1);
}

imagealphablending($source, false);
imagesavealpha($source, true);

$minX = imagesx($source);
$minY = imagesy($source);
$maxX = 0;
$maxY = 0;

for ($y = 0; $y < imagesy($source); $y++) {
    for ($x = 0; $x < imagesx($source); $x++) {
        $alpha = (imagecolorat($source, $x, $y) >> 24) & 0x7f;

        if ($alpha < 116) {
            $minX = min($minX, $x);
            $minY = min($minY, $y);
            $maxX = max($maxX, $x);
            $maxY = max($maxY, $y);
        }
    }
}

$padding = 18;
$cropX = max(0, $minX - $padding);
$cropY = max(0, $minY - $padding);
$cropWidth = min(imagesx($source) - $cropX, $maxX - $minX + 1 + ($padding * 2));
$cropHeight = min(imagesy($source) - $cropY, $maxY - $minY + 1 + ($padding * 2));
$mark = imagecrop($source, ['x' => $cropX, 'y' => $cropY, 'width' => $cropWidth, 'height' => $cropHeight]);

if (! $mark) {
    fwrite(STDERR, "Batas logo gagal dipotong.\n");
    exit(1);
}

imagealphablending($mark, false);
imagesavealpha($mark, true);

$brandDirectory = $publicPath.'/images/brand';

if (! is_dir($brandDirectory) && ! mkdir($brandDirectory, 0775, true) && ! is_dir($brandDirectory)) {
    fwrite(STDERR, "Direktori aset brand gagal dibuat.\n");
    exit(1);
}

imagepng($mark, $brandDirectory.'/basamo-nch-mark.png', 9);

$lightMark = imagecreatetruecolor(imagesx($mark), imagesy($mark));
imagealphablending($lightMark, false);
imagesavealpha($lightMark, true);
$transparent = imagecolorallocatealpha($lightMark, 0, 0, 0, 127);
imagefill($lightMark, 0, 0, $transparent);

for ($y = 0; $y < imagesy($mark); $y++) {
    for ($x = 0; $x < imagesx($mark); $x++) {
        $rgba = imagecolorat($mark, $x, $y);
        $alpha = ($rgba >> 24) & 0x7f;
        $red = ($rgba >> 16) & 0xff;
        $green = ($rgba >> 8) & 0xff;
        $blue = $rgba & 0xff;

        // Biru navy dibuat putih pada latar gelap; aksen emas dipertahankan.
        if ($blue > $red * 1.15 && $blue > $green * 1.08) {
            $luminance = max($red, $green, $blue) / 255;
            $red = $green = $blue = (int) round(224 + (31 * $luminance));
        }

        imagesetpixel($lightMark, $x, $y, imagecolorallocatealpha($lightMark, $red, $green, $blue, $alpha));
    }
}

imagepng($lightMark, $brandDirectory.'/basamo-nch-mark-light.png', 9);

$makeSquareIcon = static function (int $size, string $destination) use ($mark): void {
    $canvas = imagecreatetruecolor($size, $size);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);
    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefill($canvas, 0, 0, $transparent);

    $available = (int) round($size * .9);
    $scale = min($available / imagesx($mark), $available / imagesy($mark));
    $width = max(1, (int) round(imagesx($mark) * $scale));
    $height = max(1, (int) round(imagesy($mark) * $scale));
    $left = (int) floor(($size - $width) / 2);
    $top = (int) floor(($size - $height) / 2);

    imagecopyresampled($canvas, $mark, $left, $top, 0, 0, $width, $height, imagesx($mark), imagesy($mark));
    imagepng($canvas, $destination, 9);
    imagedestroy($canvas);
};

$makeSquareIcon(512, $publicPath.'/favicon-512.png');
$makeSquareIcon(192, $publicPath.'/favicon-192.png');
$makeSquareIcon(180, $publicPath.'/apple-touch-icon.png');
$makeSquareIcon(32, $publicPath.'/favicon-32.png');

$faviconSvgPng = file_get_contents($publicPath.'/favicon-192.png');
$faviconSvg = sprintf(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 192"><image href="data:image/png;base64,%s" width="192" height="192"/></svg>',
    base64_encode($faviconSvgPng),
);
file_put_contents($publicPath.'/favicon.svg', $faviconSvg);

$faviconPng = file_get_contents($publicPath.'/favicon-32.png');
$icoHeader = pack('vvv', 0, 1, 1);
$icoEntry = pack('CCCCvvVV', 32, 32, 0, 0, 1, 32, strlen($faviconPng), 22);
file_put_contents($publicPath.'/favicon.ico', $icoHeader.$icoEntry.$faviconPng);

imagedestroy($lightMark);
imagedestroy($mark);
imagedestroy($source);

fwrite(STDOUT, "Aset brand dan favicon berhasil dibuat.\n");
