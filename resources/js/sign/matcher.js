// Pure logic (no DOM, no MediaPipe) so it is easy to test and tune.
//
// Idea: every video frame becomes a feature vector (hand shape + hand position),
// a short window of frames is resampled to a fixed length, and that sequence is
// compared with recorded templates using DTW (dynamic time warping).

export const SLOT = 66; // per hand: 1 presence flag + 63 landmark values + 2 path values
export const N_FRAMES = 20; // every sequence is resampled to this many frames
export const WINDOW_MS = 1800; // how much recent motion is compared (also the recording length)
export const MIN_FRAMES = 12; // need at least this many frames with a hand visible
export const THRESHOLD = 2.5; // max average distance that counts as a match  <-- tune with ?debug=1
export const MARGIN = 1.1; // the target sign must beat every other sign by 10%

const PATH_WEIGHT = 2; // how much hand movement matters compared with hand shape
const FLAG_WEIGHT = 3; // how strongly "number of hands visible" must agree

/**
 * hands: array of 0..2 landmark arrays as returned by MediaPipe (21 points each).
 * Returns one flat feature vector with two hand slots, ordered left-to-right in the image.
 */
export function frameFeatures(hands) {
  const sorted = [...hands].sort((a, b) => a[0].x - b[0].x).slice(0, 2);
  const out = [];
  for (let i = 0; i < 2; i++) {
    const lm = sorted[i];
    if (!lm) {
      for (let k = 0; k < SLOT; k++) out.push(0);
      continue;
    }
    const w = lm[0]; // wrist
    const scale = Math.hypot(lm[9].x - w.x, lm[9].y - w.y) || 1; // wrist -> middle finger base
    out.push(1);
    for (const p of lm) out.push((p.x - w.x) / scale, (p.y - w.y) / scale, (p.z - w.z) / scale);
    out.push(w.x / scale, w.y / scale);
  }
  return out;
}

/** Make hand position relative to where the hand was in the first frame it appeared. */
export function relativize(frames) {
  const base = [null, null];
  return frames.map((f) => {
    const g = f.slice();
    for (let s = 0; s < 2; s++) {
      const o = s * SLOT;
      if (f[o] !== 1) continue;
      if (base[s] === null) base[s] = [f[o + 64], f[o + 65]];
      g[o + 64] = f[o + 64] - base[s][0];
      g[o + 65] = f[o + 65] - base[s][1];
    }
    return g;
  });
}

/** Apply the importance weights. Kept separate so templates do not need re-recording when weights change. */
export function weightFrames(frames) {
  return frames.map((f) => {
    const g = f.slice();
    for (let s = 0; s < 2; s++) {
      const o = s * SLOT;
      g[o] *= FLAG_WEIGHT;
      g[o + 64] *= PATH_WEIGHT;
      g[o + 65] *= PATH_WEIGHT;
    }
    return g;
  });
}

/** Linear resample of a frame sequence to n frames. */
export function resample(seq, n = N_FRAMES) {
  if (seq.length === 0) return [];
  if (seq.length === 1) return Array.from({ length: n }, () => seq[0].slice());
  const out = [];
  for (let i = 0; i < n; i++) {
    const t = (i * (seq.length - 1)) / (n - 1);
    const a = Math.floor(t);
    const b = Math.min(a + 1, seq.length - 1);
    const f = t - a;
    out.push(seq[a].map((v, k) => v + (seq[b][k] - v) * f));
  }
  return out;
}

function frameDistance(a, b) {
  let sum = 0;
  for (let i = 0; i < a.length; i++) {
    const d = a[i] - b[i];
    sum += d * d;
  }
  return Math.sqrt(sum);
}

/** Dynamic time warping with a Sakoe-Chiba band. Returns the average per-step distance. */
export function dtw(a, b, band = 5) {
  const n = a.length;
  const m = b.length;
  let prev = new Array(m + 1).fill(Infinity);
  prev[0] = 0;
  for (let i = 1; i <= n; i++) {
    const cur = new Array(m + 1).fill(Infinity);
    const from = Math.max(1, i - band);
    const to = Math.min(m, i + band);
    for (let j = from; j <= to; j++) {
      const cost = frameDistance(a[i - 1], b[j - 1]);
      cur[j] = cost + Math.min(prev[j], cur[j - 1], prev[j - 1]);
    }
    prev = cur;
  }
  return prev[m] / (n + m);
}

/** Turn recorded raw frames into a compact template (this is what gets saved to JSON). */
export function buildTemplate(frames) {
  return resample(relativize(frames)).map((f) => f.map((v) => Math.round(v * 1000) / 1000));
}

export class SignMatcher {
  /** templates: { "Hello": [template, template, ...], ... } where each template is already passed through weightFrames. */
  constructor(templates) {
    this.templates = templates;
    this.buffer = [];
  }

  reset() {
    this.buffer = [];
  }

  /** Call once per new video frame. `hands` may be empty (nothing is stored then). */
  push(hands, now) {
    if (!hands || hands.length === 0) return;
    this.buffer.push({ t: now, f: frameFeatures(hands) });
    while (this.buffer.length && now - this.buffer[0].t > WINDOW_MS) this.buffer.shift();
  }

  /** Compare the recent window with every template and decide whether `title` was signed. */
  evaluate(title) {
    if (this.buffer.length < MIN_FRAMES) return { ready: false, matched: false, scores: {} };

    const query = weightFrames(resample(relativize(this.buffer.map((b) => b.f))));
    const scores = {};
    for (const [name, list] of Object.entries(this.templates)) {
      let best = Infinity;
      for (const tpl of list) best = Math.min(best, dtw(query, tpl));
      scores[name] = best;
    }

    const target = scores[title];
    if (target === undefined) return { ready: true, matched: false, scores };

    let others = Infinity;
    for (const [name, value] of Object.entries(scores)) {
      if (name !== title && value < others) others = value;
    }

    const matched = target <= THRESHOLD && target * MARGIN <= others;
    return { ready: true, matched, target, others, scores };
  }
}
