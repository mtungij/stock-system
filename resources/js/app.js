import { initFlowbite } from 'flowbite';
import Toastify from 'toastify-js';

const initUi = () => {
	initFlowbite();
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initUi);
} else {
	initUi();
}

document.addEventListener('livewire:navigated', initUi);

window.Toastify = Toastify;

// Register Service Worker for PWA
if ('serviceWorker' in navigator) {
	window.addEventListener('load', () => {
		navigator.serviceWorker.register('/sw.js')
			.then((registration) => {
				console.log('ServiceWorker registration successful with scope: ', registration.scope);
				
				// Check for updates every hour
				setInterval(() => {
					registration.update();
				}, 3600000);
			})
			.catch((error) => {
				console.log('ServiceWorker registration failed: ', error);
			});
	});
}

// PWA Install Prompt
let deferredPrompt;

// Attach click handler on page load
function attachInstallHandler() {
	const installButton = document.getElementById('pwa-install-btn');
	if (installButton && !installButton.dataset.listenerAttached) {
		installButton.dataset.listenerAttached = 'true';
		installButton.addEventListener('click', async () => {
			if (deferredPrompt) {
				try {
					deferredPrompt.prompt();
					const { outcome } = await deferredPrompt.userChoice;
					console.log(`User response to the install prompt: ${outcome}`);
					if (outcome === 'accepted') {
						console.log('User accepted the install prompt');
					} else {
						console.log('User dismissed the install prompt');
					}
					deferredPrompt = null;
					installButton.style.display = 'none';
				} catch (error) {
					console.error('Error showing install prompt:', error);
				}
			} else {
				console.log('Install prompt not available. Try installing from browser menu.');
				alert('Please install the app from your browser menu:\nChrome: Menu → Install app\niOS Safari: Share → Add to Home Screen');
			}
		});
	}
}

// Run on initial load
attachInstallHandler();

// Re-attach after Livewire navigation
document.addEventListener('livewire:navigated', attachInstallHandler);

window.addEventListener('beforeinstallprompt', (e) => {
	console.log('beforeinstallprompt event fired');
	// Prevent the mini-infobar from appearing on mobile
	e.preventDefault();
	// Stash the event so it can be triggered later.
	deferredPrompt = e;
	// Show install button
	const installButton = document.getElementById('pwa-install-btn');
	if (installButton) {
		installButton.style.display = 'block';
		console.log('Install button is now visible and ready');
	}
});

// Track when app is installed
window.addEventListener('appinstalled', () => {
	console.log('PWA was installed');
	deferredPrompt = null;
	const installButton = document.getElementById('pwa-install-btn');
	if (installButton) {
		installButton.style.display = 'none';
	}
});
