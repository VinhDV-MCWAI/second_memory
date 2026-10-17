import type { APIRoute } from 'astro';
import { getPublicFileUrl } from '@/lib/public-api';

const HTTP_FOUND = 302;
const HTTP_NOT_FOUND = 404;

// Each click asks the API for a fresh short-lived link, so the cached skill page keeps a stable
// address for its files.
export const GET: APIRoute = async ({ params }) => {
  const url = await getPublicFileUrl(params.key ?? '');
  if (!url) {
    return new Response('Not found', { status: HTTP_NOT_FOUND });
  }
  return new Response(null, {
    status: HTTP_FOUND,
    headers: { Location: url, 'Cache-Control': 'no-store' },
  });
};
