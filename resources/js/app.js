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
window.addEventListener('beforeinstallprompt', (e) => {
	// Prevent the mini-infobar from appearing on mobile
	e.preventDefault();
	// Stash the event so it can be triggered later.
	deferredPrompt = e;
	// Show install button/banner
	showInstallPromotion();
});

function showInstallPromotion() {
	// You can show a custom install button here
	const installButton = document.getElementById('pwa-install-btn');
	if (installButton) {
		installButton.style.display = 'block';
		installButton.addEventListener('click', async () => {
			if (deferredPrompt) {
				deferredPrompt.prompt();
				const { outcome } = await deferredPrompt.userChoice;
				console.log(`User response to the install prompt: ${outcome}`);
				deferredPrompt = null;
				installButton.style.display = 'none';
			}
		});
	}
}

// Track when app is installed
window.addEventListener('appinstalled', () => {
	console.log('PWA was installed');
	deferredPrompt = null;
});
