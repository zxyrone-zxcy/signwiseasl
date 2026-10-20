import './sign-quiz.css';
import { createTracker, startCamera, cameraErrorMessage } from './tracker.js';
import { SignMatcher, THRESHOLD, weightFrames } from './matcher.js';

const COUNTDOWN_SECONDS = 3;
const ATTEMPT_MS = 6000; // how long the learner has to sign after "Sign now!"
const MAX_ATTEMPTS = 4; // first try + up to three retries
const CONSECUTIVE_MATCHES = 2; // matcher must agree this many checks in a row

const root = document.querySelector('[data-sign-quiz]');
const form = root.querySelector('[data-quiz-form]');
const cards = [...root.querySelectorAll('[data-card]')];
const debug = root.dataset.debug === '1';

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
const waitForClick = (el) => new Promise((resolve) => el.addEventListener('click', resolve, { once: true }));

const env = { stream: null, tracker: null, matcher: null, failed: false };

async function loadTemplates(url) {
  try {
    const res = await fetch(url, { cache: 'no-store' });
    if (!res.ok) return null;
    const raw = await res.json();
    const out = {};
    for (const [title, list] of Object.entries(raw)) out[title] = list.map(weightFrames);
    return Object.keys(out).length ? out : null;
  } catch {
    return null;
  }
}

/** Starts camera + MediaPipe + templates once. Returns true when everything is ready. */
async function ensureReady(status) {
  if (env.failed) return false;
  if (env.matcher) return true;

  status.textContent = 'Starting camera…';
  try {
    env.stream = await startCamera();
  } catch (error) {
    env.failed = true;
    status.textContent = cameraErrorMessage(error);
    return false;
  }

  status.textContent = 'Loading hand tracking…';
  try {
    env.tracker = await createTracker();
  } catch (error) {
    console.error(error);
    env.failed = true;
    status.textContent = 'Hand tracking could not load. Check that the MediaPipe files are in public/mediapipe.';
    return false;
  }

  const templates = await loadTemplates(root.dataset.templatesUrl);
  if (!templates) {
    env.failed = true;
    status.textContent = 'No sign templates found yet. Record them first at /quiz/record.';
    return false;
  }

  env.matcher = new SignMatcher(templates);
  return true;
}

function stopEverything() {
  env.stream?.getTracks().forEach((track) => track.stop());
  env.stream = null;
  env.tracker?.close();
  env.tracker = null;
}

/** One attempt: countdown, then watch the camera until it matches or time runs out. Resolves true/false. */
async function attemptSign(video, status, title) {
  env.matcher.reset();

  status.className = 'sign-status';
  for (let n = COUNTDOWN_SECONDS; n > 0; n--) {
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
      if (video.readyState >= 2 && video.currentTime !== lastVideoTime) {
        lastVideoTime = video.currentTime;
        env.matcher.push(env.tracker.detect(video, now), now);
      }

      if (now - lastCheck > 150) {
        lastCheck = now;
        const result = env.matcher.evaluate(title);
        streak = result.matched ? streak + 1 : 0;

        if (debug && result.ready) {
          const others = Number.isFinite(result.others) ? result.others.toFixed(2) : '–';
          status.textContent = `target ${result.target?.toFixed(2) ?? '–'} | best other ${others} | threshold ${THRESHOLD}`;
        }
        if (streak >= CONSECUTIVE_MATCHES) return resolve(true);
      }

      if (now > deadline) return resolve(false);
      requestAnimationFrame(tick);
    };

    requestAnimationFrame(tick);
  });
}

async function run() {
  for (const [index, card] of cards.entries()) {
    const isLast = index === cards.length - 1;
    cards.forEach((c) => {
      c.hidden = c !== card;
    });

    const title = card.dataset.title;
    const video = card.querySelector('[data-video]');
    const status = card.querySelector('[data-status]');
    const flipButton = card.querySelector('[data-flip]');
    const retryButton = card.querySelector('[data-retry]');
    const nextButton = card.querySelector('[data-next]');
    const result = card.querySelector('[data-result]');

    await waitForClick(flipButton);
    card.classList.add('is-flipped');

    let passed = false;
    const ready = await ensureReady(status);

    if (ready) {
      video.srcObject = env.stream;
      await video.play().catch(() => {});

      for (let attempt = 1; attempt <= MAX_ATTEMPTS && !passed; attempt++) {
        passed = await attemptSign(video, status, title);
        if (!passed && attempt < MAX_ATTEMPTS) {
          const retriesLeft = MAX_ATTEMPTS - attempt;
          status.textContent = `Not quite. You have ${retriesLeft} retr${retriesLeft === 1 ? 'y' : 'ies'} left.`;
          status.classList.add('is-fail');
          retryButton.hidden = false;
          await waitForClick(retryButton);
          retryButton.hidden = true;
        }
      }

      status.textContent = passed ? 'Pass ✓' : 'Fail ✗';
      status.className = `sign-status ${passed ? 'is-pass' : 'is-fail'}`;
    }

    result.value = passed ? '1' : '0';
    nextButton.textContent = !ready ? (isLast ? 'Finish quiz' : 'Skip question') : isLast ? 'See my results' : 'Next question';
    nextButton.hidden = false;
    await waitForClick(nextButton);
  }

  stopEverything();
  form.requestSubmit();
}

window.addEventListener('pagehide', stopEverything);
run();
