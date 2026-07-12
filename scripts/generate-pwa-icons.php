<?php

/**
 * Generate square PWA / Apple touch icons with a light solid background.
 * Transparent logos otherwise show as black in many Android install UIs.
 */

$source = __DIR__.'/../public/images/awan_logo.png';
$targetDir = __DIR__.'/../public/images/pwa';
$appleTouch = __DIR__.'/../public/apple-touch-icon-v3.png';
$backgroundHex = '#F3F5F9';

if (! file_exists($source)) {
    fwrite(STDERR, "Missing source icon: {$source}\n");
    exit(1);
}

if (! is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$src = imagecreatefrompng($source);

if ($src === false) {
    fwrite(STDERR, "Unable to read source icon: {$source}\n");
    exit(1);
}

imagealphablending($src, true);
imagesavealpha($src, true);

[$bgR, $bgG, $bgB] = sscanf($backgroundHex, '#%02x%02x%02x');

/**
 * @return GdImage
 */
function makeSquareIcon(GdImage $src, int $size, float $logoScale, int $bgR, int $bgG, int $bgB): GdImage
{
    $dst = imagecreatetruecolor($size, $size);
    imagealphablending($dst, false);
    imagesavealpha($dst, false);

    $bg = imagecolorallocate($dst, $bgR, $bgG, $bgB);
    imagefilledrectangle($dst, 0, 0, $size, $size, $bg);

    imagealphablending($dst, true);

    $srcW = imagesx($src);
    $srcH = imagesy($src);
    $maxW = (int) round($size * $logoScale);
    $maxH = (int) round($size * $logoScale);
    $scale = min($maxW / $srcW, $maxH / $srcH);
    $dstW = max(1, (int) round($srcW * $scale));
    $dstH = max(1, (int) round($srcH * $scale));
    $dstX = (int) round(($size - $dstW) / 2);
    $dstY = (int) round(($size - $dstH) / 2);

    imagecopyresampled($dst, $src, $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);

    return $dst;
}

foreach ([192, 512] as $size) {
    $any = makeSquareIcon($src, $size, 0.72, $bgR, $bgG, $bgB);
    imagepng($any, $targetDir.'/icon-'.$size.'-v3.png', 9);
    imagepng($any, $targetDir.'/icon-'.$size.'.png', 9);
    imagedestroy($any);

    $maskable = makeSquareIcon($src, $size, 0.52, $bgR, $bgG, $bgB);
    imagepng($maskable, $targetDir.'/icon-'.$size.'-maskable-v3.png', 9);
    imagedestroy($maskable);
}

$apple = makeSquareIcon($src, 180, 0.72, $bgR, $bgG, $bgB);
imagepng($apple, $appleTouch, 9);
imagedestroy($apple);

imagedestroy($src);

echo "Generated light-background PWA icons in {$targetDir}\n";
echo "Updated {$appleTouch}\n";
