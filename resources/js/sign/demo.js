// "Watch the sign": replays the recorded hand motion for a lesson as an animated
// hand diagram, so learners can see the sign instead of only reading about it.
// Read-only use of public/sign-templates.json; nothing here changes matching or recording.

import { SLOT, dtw, weightFrames } from './matcher.js';

const BONES = [
  [0, 1], [1, 2], [2, 3], [3, 4],
  [0, 5], [5, 6], [6, 7], [7, 8],
  [5, 9], [9, 10], [10, 11], [11, 12],
  [9, 13], [13, 14], [14, 15], [15, 16],
  [13, 17], [17, 18], [18, 19], [19, 20],
  [0, 17],
];
const PALM = [0, 1, 5, 9, 13, 17];
const TIPS = [4, 8, 12, 16, 20];
const HAND_COLORS = ['#c6aaf8', '#7be3cf'];
const LOOP_MS = 2600;
const HOLD_MS = 700;

const root = document.querySelector('[data-sign-demo]');
const canvas = root.querySelector('[data-demo-canvas]');
const emptyNote = root.querySelector('[data-demo-empty]');
const toggleButton = root.querySelector('[data-demo-toggle]');
const speedButton = root.querySelector('[data-demo-speed]');
const scrubber = root.querySelector('[data-demo-scrub]');
const title = root.dataset.title;
const ctx = canvas.getContext('2d');
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

let rafId = 0;
const state = { frames: null, view: null, playing: !reduceMotion, slow: false, phase: reduceMotion ? 0.55 : 0, last: 0, visible: true };

const PATH_LIMIT = 3; // keep hand travel inside a few hand-sizes so one bad frame can't zoom the view out
const MAX_HAND_EXTENT = 3; // a real hand spans ~2.5 wrist-to-knuckle lengths; larger means a foreshortened frame

function maxPathTravel(take) {
  let max = 0;
  for (const frame of take) for (let slot = 0; slot < 2; slot++) if (frame[slot * SLOT] === 1) max = Math.max(max, Math.abs(frame[slot * SLOT + 64]), Math.abs(frame[slot * SLOT + 65]));
  return max;
}

/**
 * Recorded frames are normalised by hand size, so a hand turned edge-on briefly looks huge.
 * Rescale those frames, soft-limit the travel path and smooth it. Display only: templates are untouched.
 */
function cleanTake(take) {
  const frames = take.map((frame) => {
    const out = frame.slice();
    for (let slot = 0; slot < 2; slot++) {
      const o = slot * SLOT;
      if (out[o] !== 1) continue;
      let extent = 0;
      for (let i = 0; i < 21; i++) extent = Math.max(extent, Math.hypot(out[o + 1 + i * 3], out[o + 2 + i * 3]));
      if (extent > MAX_HAND_EXTENT) {
        const k = MAX_HAND_EXTENT / extent;
        for (let i = 0; i < 63; i++) out[o + 1 + i] *= k;
      }
      out[o + 64] = PATH_LIMIT * Math.tanh(out[o + 64] / PATH_LIMIT);
      out[o + 65] = PATH_LIMIT * Math.tanh(out[o + 65] / PATH_LIMIT);
    }
    return out;
  });

  return frames.map((frame, i) => {
    const out = frame.slice();
    for (let slot = 0; slot < 2; slot++) {
      const o = slot * SLOT;
      if (frame[o] !== 1) continue;
      for (const k of [64, 65]) {
        const neighbours = [frames[i - 1], frame, frames[i + 1]].filter((f) => f && f[o] === 1);
        out[o + k] = neighbours.reduce((sum, f) => sum + f[o + k], 0) / neighbours.length;
      }
    }
    return out;
  });
}

/** How many hands are really in a take: a hand counts when it is visible in most frames (ignores flickers). */
function handCount(take) {
  let hands = 0;
  for (let slot = 0; slot < 2; slot++) {
    const visible = take.filter((frame) => frame[slot * SLOT] === 1).length;
    if (visible >= take.length * 0.6) hands++;
  }
  return hands;
}

/**
 * Pick the take that is closest to all the others, so the demo shows the most typical motion.
 * Takes that caught fewer hands than the best-recorded take are skipped, otherwise a two-handed
 * sign (like Love) can end up shown with a single hand when one recording missed the second arm.
 */
function pickRepresentative(allTakes) {
  const steady = allTakes.filter((take) => maxPathTravel(take) <= 15);
  const stable = steady.length ? steady : allTakes;
  const mostHands = Math.max(...stable.map(handCount));
  const takes = stable.filter((take) => handCount(take) === mostHands);
  if (takes.length < 3) return takes[0];
  const weighted = new Map(takes.map((take) => [take, weightFrames(take)]));
  let best = takes[0];
  let bestCost = Infinity;
  for (const candidate of takes) {
    let cost = 0;
    for (const other of takes) if (other !== candidate) cost += dtw(weighted.get(candidate), weighted.get(other));
    if (cost < bestCost) {
      bestCost = cost;
      best = candidate;
    }
  }
  return best;
}

function landmarkAt(frame, slot, index) {
  const o = slot * SLOT;
  return {
    x: frame[o + 64] + frame[o + 1 + index * 3],
    y: frame[o + 65] + frame[o + 2 + index * 3],
  };
}

function computeView(frames) {
  let minX = Infinity;
  let minY = Infinity;
  let maxX = -Infinity;
  let maxY = -Infinity;
  for (const frame of frames) {
    for (let slot = 0; slot < 2; slot++) {
      if (frame[slot * SLOT] !== 1) continue;
      for (let i = 0; i < 21; i++) {
        const p = landmarkAt(frame, slot, i);
        minX = Math.min(minX, p.x);
        maxX = Math.max(maxX, p.x);
        minY = Math.min(minY, p.y);
        maxY = Math.max(maxY, p.y);
      }
    }
  }
  const pad = 0.7;
  return { minX: minX - pad, maxX: maxX + pad, minY: minY - pad, maxY: maxY + pad };
}

function resizeCanvas() {
  const rect = canvas.getBoundingClientRect();
  const dpr = Math.min(window.devicePixelRatio || 1, 2);
  canvas.width = Math.max(1, Math.round(rect.width * dpr));
  canvas.height = Math.max(1, Math.round(rect.height * dpr));
  ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  draw();
}

/** Smoothly blend between the two recorded frames around position t (0..frames-1). */
function frameAt(t) {
  const frames = state.frames;
  const a = Math.floor(t);
  const b = Math.min(a + 1, frames.length - 1);
  const f = t - a;
  const out = frames[a].map((v, k) => v + (frames[b][k] - v) * f);
  for (let slot = 0; slot < 2; slot++) {
    const o = slot * SLOT;
    out[o] = (f < 0.5 ? frames[a][o] : frames[b][o]) === 1 ? 1 : 0;
  }
  return out;
}

function easeInOut(x) {
  return x < 0.5 ? 2 * x * x : 1 - Math.pow(-2 * x + 2, 2) / 2;
}

function draw() {
  if (!state.frames) return;
  const w = canvas.clientWidth;
  const h = canvas.clientHeight;
  ctx.clearRect(0, 0, w, h);

  const { minX, maxX, minY, maxY } = state.view;
  const scale = Math.min(w / (maxX - minX), h / (maxY - minY));
  const midX = (minX + maxX) / 2;
  const midY = (minY + maxY) / 2;
  // Mirrored on purpose: it matches what learners see in their own camera preview.
  const toScreen = (p) => ({ x: w / 2 - (p.x - midX) * scale, y: h / 2 + (p.y - midY) * scale });

  const progress = easeInOut(Math.min(state.phase, 1));
  const t = progress * (state.frames.length - 1);
  const frame = frameAt(t);
  const line = Math.max(2.5, scale * 0.045);

  for (let slot = 0; slot < 2; slot++) {
    const o = slot * SLOT;
    if (frame[o] !== 1) continue;
    const color = HAND_COLORS[slot];

    // Where the hand travels: faint full path, brighter up to the current moment.
    const wristPath = state.frames.map((f) => ({ present: f[o] === 1, p: toScreen({ x: f[o + 64], y: f[o + 65] }) }));
    ctx.save();
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.setLineDash([2, line * 1.8]);
    ctx.strokeStyle = 'rgba(255,255,255,.28)';
    ctx.lineWidth = line * 0.7;
    ctx.beginPath();
    wristPath.forEach((s, i) => (i === 0 ? ctx.moveTo(s.p.x, s.p.y) : ctx.lineTo(s.p.x, s.p.y)));
    ctx.stroke();
    ctx.setLineDash([]);
    ctx.strokeStyle = color;
    ctx.globalAlpha = 0.65;
    ctx.lineWidth = line * 0.9;
    ctx.beginPath();
    const upTo = Math.floor(t);
    wristPath.slice(0, upTo + 1).forEach((s, i) => (i === 0 ? ctx.moveTo(s.p.x, s.p.y) : ctx.lineTo(s.p.x, s.p.y)));
    const now = toScreen({ x: frame[o + 64], y: frame[o + 65] });
    ctx.lineTo(now.x, now.y);
    ctx.stroke();
    ctx.restore();

    const pts = Array.from({ length: 21 }, (_, i) => toScreen(landmarkAt(frame, slot, i)));

    // Palm
    ctx.save();
    ctx.beginPath();
    PALM.forEach((i, k) => (k === 0 ? ctx.moveTo(pts[i].x, pts[i].y) : ctx.lineTo(pts[i].x, pts[i].y)));
    ctx.closePath();
    ctx.fillStyle = `${color}33`;
    ctx.fill();
    ctx.restore();

    // Bones
    ctx.save();
    ctx.lineCap = 'round';
    ctx.strokeStyle = color;
    ctx.lineWidth = line;
    ctx.shadowColor = color;
    ctx.shadowBlur = 10;
    ctx.beginPath();
    for (const [a, b] of BONES) {
      ctx.moveTo(pts[a].x, pts[a].y);
      ctx.lineTo(pts[b].x, pts[b].y);
    }
    ctx.stroke();
    ctx.restore();

    // Joints and fingertips
    pts.forEach((p, i) => {
      const tip = TIPS.includes(i);
      ctx.beginPath();
      ctx.arc(p.x, p.y, tip ? line * 1.25 : line * 0.7, 0, Math.PI * 2);
      ctx.fillStyle = tip ? '#f6d481' : '#ffffff';
      ctx.fill();
    });
  }

  if (scrubber) scrubber.value = String(Math.round(Math.min(state.phase, 1) * 1000));
}

function setPlaying(playing) {
  state.playing = playing;
  toggleButton.textContent = playing ? 'Pause' : 'Play';
  toggleButton.setAttribute('aria-pressed', String(!playing));
  startLoop();
}

/** Always cancels the previous frame request so only one animation loop ever runs. */
function startLoop() {
  cancelAnimationFrame(rafId);
  state.last = 0;
  if (state.playing && state.visible) rafId = requestAnimationFrame(loop);
}

function loop(now) {
  if (!state.playing || !state.visible) return;
  if (!state.last) state.last = now;
  const dt = now - state.last;
  state.last = now;

  const duration = state.slow ? LOOP_MS * 2 : LOOP_MS;
  const hold = HOLD_MS / duration;
  state.phase += dt / duration;
  if (state.phase > 1 + hold) state.phase = 0;
  draw();
  rafId = requestAnimationFrame(loop);
}

async function init() {
  let takes = null;
  try {
    const res = await fetch(root.dataset.templatesUrl, { cache: 'force-cache' });
    if (res.ok) takes = (await res.json())[title];
  } catch {
    // fall through to the empty state
  }

  if (!Array.isArray(takes) || takes.length === 0) {
    root.classList.add('is-empty');
    emptyNote.hidden = false;
    return;
  }

  state.frames = cleanTake(pickRepresentative(takes));
  state.view = computeView(state.frames);
  root.classList.add('is-ready');
  resizeCanvas();
  new ResizeObserver(resizeCanvas).observe(canvas);

  toggleButton.addEventListener('click', () => setPlaying(!state.playing));
  speedButton.addEventListener('click', () => {
    state.slow = !state.slow;
    speedButton.setAttribute('aria-pressed', String(state.slow));
    speedButton.textContent = state.slow ? 'Normal speed' : 'Slow motion';
  });
  scrubber.addEventListener('input', () => {
    setPlaying(false);
    state.phase = Number(scrubber.value) / 1000;
    draw();
  });

  // Don't animate while scrolled out of view.
  new IntersectionObserver(([entry]) => {
    state.visible = entry.isIntersecting;
    startLoop();
  }).observe(root);

  setPlaying(state.playing);
  draw();
}

init();
