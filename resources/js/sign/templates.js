// One shared, memoised download of the recorded sign templates (~570 KB).
// The server sends it with `Cache-Control: no-cache`, so the browser revalidates with an ETag
// (a tiny 304) instead of re-downloading it, and re-recorded gestures still show up straight away.
const requests = new Map();

export function fetchTemplates(url) {
  if (!requests.has(url)) {
    requests.set(
      url,
      fetch(url)
        .then((res) => (res.ok ? res.json() : null))
        .catch(() => null),
    );
  }

  return requests.get(url);
}
