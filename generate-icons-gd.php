#!/usr/bin/env php
<?php

/**
 * Generate PWA icons using GD library (no ImageMagick required)
 * Usage: php generate-icons-gd.php [source-image.png]
 */

// Check if GD is available
if (!extension_loaded('gd')) {
    echo "❌ GD extension is not installed. Please install it:\n";
    echo "   Ubuntu/Debian: sudo apt-get install php-gd\n";
    echo "   Then restart your web server\n";
    exit(1);
}

// Get source image
$sourceImage = $argv[1] ?? 'public/favicon.svg';

if (!file_exists($sourceImage)) {
    echo "❌ Source image not found: $sourceImage\n";
    echo "Please provide a PNG or JPG image file.\n";
    echo "Usage: php generate-icons-gd.php path/to/image.png\n";
    exit(1);
}

// Create directories
@mkdir('public/images/icons', 0755, true);
@mkdir('public/images/screenshots', 0755, true);

// Icon sizes
$sizes = [72, 96, 128, 144, 152, 192, 384, 512];

echo "🎨 Generating PWA icons from $sourceImage...\n\n";

// Determine image type
$imageInfo = getimagesize($sourceImage);
$mimeType = $imageInfo['mime'];

// Load source image
switch ($mimeType) {
    case 'image/jpeg':
        $source = imagecreatefromjpeg($sourceImage);
        break;
    case 'image/png':
        $source = imagecreatefrompng($sourceImage);
        break;
    case 'image/gif':
        $source = imagecreatefromgif($sourceImage);
        break;
    default:
        echo "❌ Unsupported image format. Please use PNG or JPG.\n";
        exit(1);
}

if (!$source) {
    echo "❌ Failed to load source image.\n";
    exit(1);
}

// Enable alpha blending
imagealphablending($source, true);
imagesavealpha($source, true);

// Generate icons
foreach ($sizes as $size) {
    echo "📦 Creating {$size}x{$size} icon...";
    
    // Create new image
    $icon = imagecreatetruecolor($size, $size);
    
    // Preserve transparency
    imagealphablending($icon, false);
    imagesavealpha($icon, true);
    $transparent = imagecolorallocatealpha($icon, 0, 0, 0, 127);
    imagefilledrectangle($icon, 0, 0, $size, $size, $transparent);
    imagealphablending($icon, true);
    
    // Resize and copy
    imagecopyresampled(
        $icon, $source,
        0, 0, 0, 0,
        $size, $size,
        imagesx($source), imagesy($source)
    );
    
    // Save
    $filename = "public/images/icons/icon-{$size}x{$size}.png";
    imagepng($icon, $filename, 9);
    imagedestroy($icon);
    
    echo " ✅\n";
}

// Generate apple-touch-icon
echo "📦 Creating apple-touch-icon (180x180)...";
$appleIcon = imagecreatetruecolor(180, 180);
imagealphablending($appleIcon, false);
imagesavealpha($appleIcon, true);
$transparent = imagecolorallocatealpha($appleIcon, 0, 0, 0, 127);
imagefilledrectangle($appleIcon, 0, 0, 180, 180, $transparent);
imagealphablending($appleIcon, true);
imagecopyresampled(
    $appleIcon, $source,
    0, 0, 0, 0,
    180, 180,
    imagesx($source), imagesy($source)
);
imagepng($appleIcon, 'public/apple-touch-icon.png', 9);
imagedestroy($appleIcon);
echo " ✅\n";

// Cleanup
imagedestroy($source);

echo "\n✅ All icons generated successfully!\n";
echo "📁 Icons location: public/images/icons/\n";
echo "🍎 Apple icon: public/apple-touch-icon.png\n\n";
echo "💡 Next steps:\n";
echo "   1. Run: npm run build\n";
echo "   2. Test your PWA on mobile devices\n";
echo "   3. Ensure HTTPS is enabled in production\n";
