import './sign-quiz.css';
import { createTracker, startCamera, cameraErrorMessage } from './tracker.js';
import { SignMatcher, weightFrames } from './matcher.js';

const COUNTDOWN_SECONDS = 3;
const ATTEMPT_MS = 6000; // how long the learner has to sign after "Sign now!"
const MAX_ATTEMPTS = 4; // first try + up to three retries
const CONSECUTIVE_MATCHES = 2; // matcher must agree this many checks in a row

const root = document.querySelector('[data-sign-lesson]');
const video = root.querySelector('[data-camera-video]');
const placeholder = root.querySelector('[data-camera-placeholder]');
const status = root.querySelector('[data-status]');
const message = root.querySelector('[data-camera-message]');
const startButton = root.querySelector('[data-camera-start]');
const stopButton = root.querySelector('[data-camera-stop]');
const retryButton = root.querySelector('[data-camera-retry]');
const title = root.dataset.title;

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

const env = { stream: null, tracker: null, matcher: null, failed: false, running: false, stop: false };
let retryResolve = null;

async function loadTemplates(url) {
  try {
    const res = await fetch(url, { cache: 'no-store' });
    if (!res.ok) return null;
    const raw = await res.json();
    const out = {};
    for (const [name, list] of Object.entries(raw)) out[name] = list.map(weightFrames);
    return Object.keys(out).length ? out : null;
  } catch {
    return null;
  }
}

/** Starts camera + MediaPipe + templates as needed. Returns true when everything is ready. */
async function ensureReady() {
  if (env.failed) return false;

  if (!env.stream) {
    status.textContent = 'Starting camera…';
    try {
      env.stream = await startCamera();
    } catch (error) {
      env.failed = true;
      status.textContent = cameraErrorMessage(error);
      return false;
    }
  }

  if (!env.tracker) {
    status.textContent = 'Loading hand tracking…';
    try {
      env.tracker = await createTracker();
    } catch (error) {
      console.error(error);
      env.failed = true;
      status.textContent = 'Hand tracking could not load. Check that the MediaPipe files are in public/mediapipe.';
      return false;
    }
  }

  if (!env.matcher) {
    const templates = await loadTemplates(root.dataset.templatesUrl);
    if (!templates) {
      env.failed = true;
      status.textContent = 'No sign templates found yet. Record them first at /quiz/record.';
      return false;
    }
    env.matcher = new SignMatcher(templates);
  }

  return true;
}

function stopEverything() {
  env.stream?.getTracks().forEach((track) => track.stop());
  env.stream = null;
  env.tracker?.close();
  env.tracker = null;
}

function setStatus(text, state) {
  status.textContent = text;
  status.className = `sign-status practice-status${state ? ` ${state}` : ''}`;
}

function waitForRetryOrStop() {
  return new Promise((resolve) => {
    const finish = () => {
      retryButton.removeEventListener('click', finish);
      retryResolve = null;
      resolve();
    };
    retryResolve = finish;
    retryButton.addEventListener('click', finish);
  });
}

function cancelWait() {
  retryResolve?.();
}

/** One attempt: countdown, then watch the camera until it matches or time runs out. Resolves true/false. */
async function attemptSign() {
  env.matcher.reset();

  status.className = 'sign-status practice-status';
  for (let n = COUNTDOWN_SECONDS; n > 0; n--) {
    if (env.stop) return false;
    status.textContent = `Get ready… ${n}`;
    await sleep(1000);
  }
  status.textContent = `Sign "${title}" now!`;

  return new Promise((resolve) => {
    const deadline = performance.now() + ATTEMPT_MS;
    let lastCheck = 0;
    let lastVideoTime = -1;
    let streak = 0;

    const tick = (now) => {
      if (env.stop) return resolve(false);

      if (video.readyState >= 2 && video.currentTime !== lastVideoTime) {
        lastVideoTime = video.currentTime;
        env.matcher.push(env.tracker.detect(video, now), now);
      }

      if (now - lastCheck > 150) {
        lastCheck = now;
        const result = env.matcher.evaluate(title);
        streak = result.matched ? streak + 1 : 0;
        if (streak >= CONSECUTIVE_MATCHES) return resolve(true);
      }

      if (now > deadline) return resolve(false);
      requestAnimationFrame(tick);
    };

    requestAnimationFrame(tick);
  });
}

function showCamera() {
  video.srcObject = env.stream;
  video.play().catch(() => {});
  video.classList.add('is-active');
  placeholder.hidden = true;
  stopButton.hidden = false;
  message.textContent = 'Watch the screen and sign when the countdown ends. Video stays on this device.';
}

function resetView() {
  video.classList.remove('is-active');
  placeholder.hidden = false;
  stopButton.hidden = true;
  retryButton.hidden = true;
  startButton.disabled = false;
  startButton.textContent = 'Start practice';
}

async function runPractice() {
  if (env.running) return;

  env.running = true;
  env.stop = false;
  env.failed = false;
  startButton.disabled = true;
  retryButton.hidden = true;
  setStatus('');

  const ready = await ensureReady();
  if (!ready || env.stop) {
    env.running = false;
    if (env.stop) {
      stopEverything();
      resetView();
      return;
    }
    startButton.disabled = false;
    startButton.textContent = 'Try again';
    return;
  }

  if (!env.matcher.templates[title]) {
    setStatus(`No recorded template for "${title}" yet.`, 'is-fail');
    message.textContent = 'Record this sign at /quiz/record, then try again.';
    env.running = false;
    startButton.disabled = false;
    startButton.textContent = 'Try again';
    return;
  }

  showCamera();

  let passed = false;
  for (let attempt = 1; attempt <= MAX_ATTEMPTS && !passed; attempt++) {
    if (env.stop) break;
    passed = await attemptSign();
    if (!passed && attempt < MAX_ATTEMPTS && !env.stop) {
      const retriesLeft = MAX_ATTEMPTS - attempt;
      setStatus(`Not quite. You have ${retriesLeft} retr${retriesLeft === 1 ? 'y' : 'ies'} left.`, 'is-fail');
      retryButton.hidden = false;
      await waitForRetryOrStop();
      retryButton.hidden = true;
    }
  }

  env.running = false;
  startButton.disabled = false;

  if (env.stop) {
    resetView();
    return;
  }

  setStatus(passed ? 'Pass ✓' : 'Fail ✗', passed ? 'is-pass' : 'is-fail');
  message.textContent = passed
    ? 'Nice work. Keep practicing to build muscle memory.'
    : 'Not quite this time. Try again whenever you are ready.';
  startButton.textContent = 'Practice again';
}

startButton.addEventListener('click', runPractice);

stopButton.addEventListener('click', () => {
  env.stop = true;
  cancelWait();
  stopEverything();
  resetView();
  setStatus('Practice stopped.');
  message.textContent = 'Camera is off. You can keep learning without it.';
});

window.addEventListener('pagehide', stopEverything);
