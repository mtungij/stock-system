<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<meta name="description" content="Complete inventory management system for tracking sales, purchases, and stock">
<meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
<!-- Icons -->
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">

<!-- PWA Manifest & Meta -->
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#000000">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">
<link rel="apple-touch-startup-image" href="/images/icons/splash-640x1136.png">
<script>
	if ('serviceWorker' in navigator) {
		window.addEventListener('load', function() {
			navigator.serviceWorker.register('/serviceworker.js');
		});
	}
</script>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
