<?php

$file = 'public/images/st c logo.png';

if (!file_exists($file)) {
    die("File not found: $file\n");
}

$info = getimagesize($file);
$mime = $info['mime'];

switch ($mime) {
    case 'image/jpeg':
        $image = imagecreatefromjpeg($file);
        break;
    case 'image/png':
        $image = imagecreatefrompng($file);
        break;
    case 'image/gif':
        $image = imagecreatefromgif($file);
        break;
    default:
        die("Unknown image type: $mime\n");
}

if (!$image) {
    die("Failed to load image.\n");
}

$width = imagesx($image);
$height = imagesy($image);
$colors = [];

// Sample pixels (skip every 10th pixel for performance)
for ($x = 0; $x < $width; $x += 10) {
    for ($y = 0; $y < $height; $y += 10) {
        $rgb = imagecolorat($image, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $alpha = ($rgb >> 24) & 0x7F;

        // Skip transparent or near-white/black pixels
        if ($alpha > 120) continue;
        if ($r > 240 && $g > 240 && $b > 240) continue; // White-ish
        if ($r < 15 && $g < 15 && $b < 15) continue;   // Black-ish

        $hex = sprintf("#%02x%02x%02x", $r, $g, $b);
        if (!isset($colors[$hex])) {
            $colors[$hex] = 0;
        }
        $colors[$hex]++;
    }
}

arsort($colors);
$topColors = array_slice(array_keys($colors), 0, 5);

echo "Top Colors:\n";
foreach ($topColors as $color) {
    echo $color . "\n";
}
