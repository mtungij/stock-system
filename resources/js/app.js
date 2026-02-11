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
