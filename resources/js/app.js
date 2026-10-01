

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.querySelectorAll('[data-camera-panel]').forEach((panel) => {
	const video = panel.querySelector('[data-camera-video]');
	const message = panel.querySelector('[data-camera-message]');
	const startButton = panel.querySelector('[data-camera-start]');
	const stopButton = panel.querySelector('[data-camera-stop]');
	const placeholder = panel.querySelector('[data-camera-placeholder]');
	let cameraStream;

	const stopCamera = () => {
		cameraStream?.getTracks().forEach((track) => track.stop());
		cameraStream = undefined;
		video?.classList.remove('is-active');
		if (placeholder) placeholder.hidden = false;
		if (stopButton) stopButton.hidden = true;
		if (message) message.textContent = 'Camera is off. You can keep learning without it.';
	};

	startButton?.addEventListener('click', async () => {
		if (!navigator.mediaDevices?.getUserMedia) {
			if (message) message.textContent = 'Camera preview needs a secure connection and a supported browser.';
			return;
		}

		try {
			cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
			video.srcObject = cameraStream;
			video.classList.add('is-active');
			if (placeholder) placeholder.hidden = true;
			if (stopButton) stopButton.hidden = false;
			if (message) message.textContent = 'Live preview is on this device only. SignWise does not record video.';
		} catch {
			if (message) message.textContent = 'Camera access was not granted. You can continue with the written sign reference.';
		}
	});

	stopButton?.addEventListener('click', stopCamera);
	window.addEventListener('pagehide', stopCamera, { once: true });
});
