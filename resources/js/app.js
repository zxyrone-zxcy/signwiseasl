

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

		if (cameraStream) return;
		if (startButton) startButton.disabled = true;
		if (message) message.textContent = 'Waiting for camera permission...';

		try {
			cameraStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'user' } }, audio: false });
			if (video) {
				video.srcObject = cameraStream;
				await video.play();
				video.classList.add('is-active');
			}
			if (placeholder) placeholder.hidden = true;
			if (stopButton) stopButton.hidden = false;
			if (message) message.textContent = 'Live preview is on this device only. SignWise does not record video.';
		} catch (error) {
			cameraStream?.getTracks().forEach((track) => track.stop());
			cameraStream = undefined;
			const errors = {
				NotAllowedError: 'Camera permission is blocked. Allow camera access for this site in your browser settings, then try again.',
				NotFoundError: 'No camera was found. Connect a camera and try again.',
				NotReadableError: 'The camera is busy or unavailable. Close other apps using it, then try again.',
				OverconstrainedError: 'The camera could not use the requested settings. Try another camera.',
				SecurityError: 'Camera access is blocked. Allow it for this site and try again.',
			};
			if (message) message.textContent = errors[error.name] ?? `Camera could not start (${error.name ?? 'unknown error'}). Check browser permissions and try again.`;
		} finally {
			if (startButton) startButton.disabled = false;
		}
	});

	stopButton?.addEventListener('click', stopCamera);
	window.addEventListener('pagehide', stopCamera, { once: true });
});

if (document.querySelector('[data-sign-quiz]')) import('./sign/quiz.js');
if (document.querySelector('[data-sign-lesson]')) import('./sign/lesson.js');
if (document.querySelector('[data-sign-demo]')) import('./sign/demo.js');
if (document.querySelector('[data-sign-recorder]')) import('./sign/recorder.js');

/* ---------- UI polish: purely visual helpers (no camera / gesture logic) ---------- */
(() => {
	const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	// Header gains depth once the page scrolls.
	const header = document.querySelector('.site-header');
	if (header) {
		const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 8);
		onScroll();
		window.addEventListener('scroll', onScroll, { passive: true });
	}

	// Soft cursor-follow glow on cards.
	document.querySelectorAll('.lesson-card, .rhythm-card').forEach((card) => {
		card.addEventListener('pointermove', (event) => {
			const rect = card.getBoundingClientRect();
			card.style.setProperty('--mx', `${event.clientX - rect.left}px`);
			card.style.setProperty('--my', `${event.clientY - rect.top}px`);
		});
	});

	// Reveal below-the-fold blocks as they scroll into view (skipped when motion is reduced).
	if (reduceMotion || !('IntersectionObserver' in window)) return;
	const targets = document.querySelectorAll('.rhythm-card, .lesson-card, .stat-card, .progress-row, .momentum-banner, .section-block > .eyebrow, .section-block > h2, .section-intro, .manage-lessons');
	const observer = new IntersectionObserver((entries) => {
		entries.forEach((entry) => {
			if (!entry.isIntersecting) return;
			entry.target.classList.add('is-visible');
			observer.unobserve(entry.target);
		});
	}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

	targets.forEach((element) => {
		if (element.getBoundingClientRect().top < window.innerHeight) return;
		const siblings = [...element.parentElement.children].filter((child) => child.matches('.rhythm-card, .lesson-card, .stat-card'));
		const index = siblings.indexOf(element);
		element.style.setProperty('--reveal-delay', `${Math.max(index, 0) * 0.08}s`);
		element.classList.add('reveal');
		observer.observe(element);
	});
})();
