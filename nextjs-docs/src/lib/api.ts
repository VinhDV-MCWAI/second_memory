// For client-side (browser): use NEXT_PUBLIC_API_URL or localhost  
// For server-side (container): use internal nginx
const isServer = typeof window === 'undefined';
const API_BASE_URL = isServer 
  ? 'http://ml-nginx/api'
  : (process.env.NEXT_PUBLIC_API_URL || 'http://localhost/api');

// Request deduplicator cache
const requestCache = new Map<string, Promise<any>>();

export async function fetchApi<T>(endpoint: string): Promise<T> {
  const url = `${API_BASE_URL}${endpoint}`;

  if (requestCache.has(url)) {
    return requestCache.get(url) as Promise<T>;
  }

  const promise = (async () => {
    const response = await fetch(url, {
      headers: {
        'Content-Type': 'application/json',
      },
      cache: 'no-store',
    });

    if (!response.ok) {
      throw new Error(`API call failed: ${response.statusText}`);
    }

    const json = await response.json();
    return json.data?.data || json.data;
  })();

  requestCache.set(url, promise);

  try {
    const result = await promise;
    // Clear cache immediately after the tick to only dedup true parallel requests
    setTimeout(() => requestCache.delete(url), 500);
    return result;
  } catch (error) {
    requestCache.delete(url);
    throw error;
  }
}

export const api = {
  async getCategories() {
    return fetchApi('/docs/categories');
  },

  async getEntriesByCategory(categorySlug: string) {
    return fetchApi(`/docs/categories/${categorySlug}/entries`);
  },

  async getEntryDetail(entrySlug: string) {
    return fetchApi(`/docs/entries/${entrySlug}`);
  },

  async search(query: string) {
    return fetchApi(`/docs/search?q=${encodeURIComponent(query)}`);
  },
};
