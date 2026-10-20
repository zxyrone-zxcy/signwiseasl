import './sign-quiz.css';
import { createTracker, startCamera, cameraErrorMessage } from './tracker.js';
import { buildTemplate, frameFeatures, MIN_FRAMES, WINDOW_MS } from './matcher.js';

const root = document.querySelector('[data-sign-recorder]');
const video = root.querySelector('[data-video]');
const status = root.querySelector('[data-status]');
const select = root.querySelector('[data-lesson]');
const startButton = root.querySelector('[data-start]');
const recordButton = root.querySelector('[data-record]');
const clearButton = root.querySelector('[data-clear]');
const downloadButton = root.querySelector('[data-download]');
const counts = root.querySelector('[data-counts]');
const bar = root.querySelector('[data-bar]');

const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

let tracker = null;
let stream = null;
let templates = {};

function renderCounts() {
  counts.innerHTML = '';
  for (const option of select.options) {
    const li = document.createElement('li');
    const n = templates[option.value]?.length ?? 0;
    li.textContent = `${option.value}: ${n} recording${n === 1 ? '' : 's'}`;
    counts.append(li);
  }
}

async function loadExisting() {
  try {
    const res = await fetch(root.dataset.templatesUrl, { cache: 'no-store' });
    if (res.ok) templates = await res.json();
  } catch {
    templates = {};
  }
  renderCounts();
}

startButton.addEventListener('click', async () => {
  startButton.disabled = true;
  status.textContent = 'Starting camera…';
  try {
    stream = await startCamera();
    video.srcObject = stream;
    await video.play();
  } catch (error) {
    status.textContent = cameraErrorMessage(error);
    startButton.disabled = false;
    return;
  }

  status.textContent = 'Loading hand tracking…';
  try {
    tracker = await createTracker();
  } catch (error) {
    console.error(error);
    status.textContent = 'Hand tracking could not load. Check that the MediaPipe files are in public/mediapipe.';
    startButton.disabled = false;
    return;
  }

  status.textContent = 'Ready. Pick a sign and press Record.';
  recordButton.disabled = false;
});

recordButton.addEventListener('click', async () => {
  recordButton.disabled = true;
  const title = select.value;

  for (let n = 3; n > 0; n--) {
    status.textContent = `"${title}" in ${n}…`;
    await sleep(1000);
  }
  status.textContent = `Sign "${title}" now, steadily, until the bar fills.`;

  const frames = [];
  const start = performance.now();
  let lastVideoTime = -1;

  await new Promise((resolve) => {
    const tick = (now) => {
      if (video.readyState >= 2 && video.currentTime !== lastVideoTime) {
        lastVideoTime = video.currentTime;
        const hands = tracker.detect(video, now);
        if (hands.length) frames.push(frameFeatures(hands));
      }
      bar.style.width = `${Math.min(100, ((now - start) / WINDOW_MS) * 100)}%`;
      if (now - start >= WINDOW_MS) return resolve();
      requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
  });

  if (frames.length < MIN_FRAMES) {
    status.textContent = `Only ${frames.length} frames had a visible hand. Keep your hand in view and try again.`;
  } else {
    (templates[title] ??= []).push(buildTemplate(frames));
    status.textContent = `Saved recording ${templates[title].length} for "${title}". Record a few more with small variations.`;
    renderCounts();
  }

  bar.style.width = '0%';
  recordButton.disabled = false;
});

clearButton.addEventListener('click', () => {
  delete templates[select.value];
  status.textContent = `Cleared recordings for "${select.value}".`;
  renderCounts();
});

downloadButton.addEventListener('click', () => {
  const blob = new Blob([JSON.stringify(templates)], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'sign-templates.json';
  a.click();
  URL.revokeObjectURL(url);
  status.textContent = 'Downloaded. Move sign-templates.json into the public folder of the project.';
});

window.addEventListener('pagehide', () => {
  stream?.getTracks().forEach((track) => track.stop());
  tracker?.close();
});

loadExisting();
