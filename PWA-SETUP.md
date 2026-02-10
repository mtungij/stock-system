# PWA (Progressive Web App) Setup

## Overview
Your application is now configured as a Progressive Web App (PWA), allowing users to install it on their Android and iOS devices like a native app.

## Features Implemented

### 1. Manifest File (`public/manifest.json`)
- Defines app name, icons, colors, and display settings
- Configured for standalone mode (looks like a native app)

### 2. Service Worker (`public/sw.js`)
- Enables offline functionality
- Caches important assets for faster loading
- Handles push notifications (ready for future use)

### 3. PWA Meta Tags
- Added to `resources/views/partials/head.blade.php`
- Includes iOS-specific meta tags for optimal installation experience

### 4. Service Worker Registration
- Added to `resources/js/app.js`
- Automatically registers on page load
- Includes install prompt handler

## Generating Icons

### Option 1: Using the Script (Requires ImageMagick)
```bash
# Install ImageMagick first if not installed
# Ubuntu/Debian: sudo apt-get install imagemagick
# macOS: brew install imagemagick

# Run the script with your logo
./generate-icons.sh path/to/your-logo.png
```

### Option 2: Manual Generation
Create PNG icons in these sizes and place them in `public/images/icons/`:
- icon-72x72.png
- icon-96x96.png
- icon-128x128.png
- icon-144x144.png
- icon-152x152.png
- icon-192x192.png
- icon-384x384.png
- icon-512x512.png

Also create:
- `public/apple-touch-icon.png` (180x180)

### Option 3: Online Tools
Use one of these free online tools:
- https://realfavicongenerator.net/
- https://www.pwabuilder.com/imageGenerator
- https://favicon.io/favicon-converter/

## Testing Your PWA

### On Android (Chrome/Edge)
1. Open your app URL in Chrome/Edge
2. You'll see an "Install" button in the address bar
3. Or use the menu → "Install app" or "Add to Home screen"

### On iOS (Safari)
1. Open your app URL in Safari
2. Tap the Share button (square with arrow)
3. Scroll down and tap "Add to Home Screen"
4. Tap "Add" in the top right

### On Desktop (Chrome/Edge)
1. Look for the install icon (+) in the address bar
2. Or use browser menu → "Install [App Name]"

## Install Button Component

Add an install button anywhere in your app:

```html
<button id="pwa-install-btn" style="display: none;" class="your-classes">
    📱 Install App
</button>
```

The JavaScript in `app.js` will show this button when installation is available.

## Customization

### Update App Name and Colors
Edit `public/manifest.json`:
```json
{
  "name": "Your App Name",
  "short_name": "App",
  "theme_color": "#your-color",
  "background_color": "#your-color"
}
```

### Update Cache Strategy
Edit `public/sw.js` to modify what gets cached and caching behavior.

### Add Screenshots
Add app screenshots to `public/images/screenshots/` and update manifest.json:
```json
"screenshots": [
  {
    "src": "/images/screenshots/screenshot1.png",
    "sizes": "540x720",
    "type": "image/png"
  }
]
```

## Deployment Checklist

Before deploying to production:

1. ✅ Generate proper icons from your logo
2. ✅ Update manifest.json with your app details
3. ✅ Test offline functionality
4. ✅ Run `npm run build` to compile assets
5. ✅ Ensure HTTPS is enabled (required for PWA)
6. ✅ Test installation on Android, iOS, and Desktop
7. ✅ Verify service worker registration in browser DevTools

## Troubleshooting

### Service Worker Not Registering
- Check browser console for errors
- Ensure you're using HTTPS (or localhost for testing)
- Clear browser cache and try again

### Install Prompt Not Showing
- PWA criteria must be met (manifest, service worker, HTTPS)
- User must visit site multiple times (Chrome requirement)
- Some browsers may not show prompt immediately

### Icons Not Displaying
- Check file paths in manifest.json
- Ensure icons are in correct directory
- Verify image sizes match manifest specifications

## Next Steps

1. **Generate Icons**: Run the icon generation script or create them manually
2. **Build Assets**: Run `npm run build` to compile the updated JavaScript
3. **Test**: Open your app and test the installation process
4. **Deploy**: Push changes to production (ensure HTTPS is enabled)

## Resources

- [MDN PWA Guide](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps)
- [web.dev PWA](https://web.dev/progressive-web-apps/)
- [PWA Builder](https://www.pwabuilder.com/)
