#!/bin/bash

# Script to generate PWA icons from a source image
# Usage: ./generate-icons.sh source-image.png

# Create icons directory
mkdir -p public/images/icons

# Check if ImageMagick is installed
if ! command -v convert &> /dev/null; then
    echo "ImageMagick is not installed. Please install it first:"
    echo "  Ubuntu/Debian: sudo apt-get install imagemagick"
    echo "  macOS: brew install imagemagick"
    echo "  Or use an online tool to generate icons manually"
    exit 1
fi

# Source image (you should replace this with your logo)
SOURCE_IMAGE="${1:-public/favicon.svg}"

if [ ! -f "$SOURCE_IMAGE" ]; then
    echo "Source image not found: $SOURCE_IMAGE"
    echo "Please provide a source image as argument or place it at public/favicon.svg"
    exit 1
fi

# Generate icons in various sizes
sizes=(72 96 128 144 152 192 384 512)

echo "Generating PWA icons from $SOURCE_IMAGE..."

for size in "${sizes[@]}"; do
    echo "Creating ${size}x${size} icon..."
    convert "$SOURCE_IMAGE" -resize "${size}x${size}" "public/images/icons/icon-${size}x${size}.png"
done

# Generate apple-touch-icon
convert "$SOURCE_IMAGE" -resize "180x180" "public/apple-touch-icon.png"

echo "✅ All icons generated successfully!"
echo "Icons location: public/images/icons/"
echo ""
echo "Note: You may want to optimize these images further using tools like:"
echo "  - pngquant (lossless compression)"
echo "  - imageoptim (macOS)"
echo "  - or online tools like tinypng.com"
