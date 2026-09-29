// Fetches JSON. On success the response is cached in localStorage;
// if the network request fails, the last saved copy is used instead.
export async function fetchJson(url, cacheKey) {
  try {
    const response = await fetch(url);
    if (!response.ok) {
      throw new Error(`Server responded with status ${response.status}`);
    }
    const data = await response.json();
    saveCache(cacheKey, data);
    return { data, fromCache: false };
  } catch (error) {
    const cached = loadCache(cacheKey);
    if (cached) {
      return { data: cached, fromCache: true };
    }
    throw error;
  }
}

function saveCache(key, data) {
  try {
    localStorage.setItem(key, JSON.stringify({ savedAt: new Date().toISOString(), data }));
  } catch (error) { /* storage full or blocked: continue without cache */ }
}

function loadCache(key) {
  try {
    const raw = localStorage.getItem(key);
    return raw ? JSON.parse(raw).data : null;
  } catch (error) {
    return null;
  }
}