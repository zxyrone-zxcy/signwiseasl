// Small DOM helper for the live accuracy meter. No camera or matching logic here.

export function tierFor(accuracy) {
  if (accuracy >= 80) return 'is-great';
  if (accuracy >= 60) return 'is-good';
  return 'is-low';
}

/** Show or hide a meter element ([data-accuracy]) and set its value (0-100, or null to reset). */
export function setAccuracy(meter, accuracy) {
  if (!meter) return;
  const value = meter.querySelector('[data-accuracy-value]');
  const bar = meter.querySelector('[data-accuracy-bar]');

  if (accuracy === null || accuracy === undefined) {
    meter.hidden = true;
    meter.classList.remove('is-great', 'is-good', 'is-low');
    if (bar) bar.style.setProperty('--accuracy', '0%');
    if (value) value.textContent = '0%';
    return;
  }

  meter.hidden = false;
  meter.classList.remove('is-great', 'is-good', 'is-low');
  meter.classList.add(tierFor(accuracy));
  if (bar) bar.style.setProperty('--accuracy', `${accuracy}%`);
  if (value) value.textContent = `${accuracy}%`;
}
