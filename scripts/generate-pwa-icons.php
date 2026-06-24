<?php

$source = __DIR__.'/../public/images/favicon-awaan.png';
$targetDir = __DIR__.'/../public/images/pwa';

if (! file_exists($source)) {
    fwrite(STDERR, "Missing source icon: {$source}\n");
    exit(1);
}

if (! is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$src = imagecreatefrompng($source);

foreach ([192, 512] as $size) {
    $dst = imagecreatetruecolor($size, $size);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $size, $size, $transparent);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $size, $size, imagesx($src), imagesy($src));
    imagepng($dst, $targetDir.'/icon-'.$size.'.png');
    imagedestroy($dst);
}

imagedestroy($src);

echo "Generated PWA icons in {$targetDir}\n";
