import { getPublicFileUrl } from '@/lib/ledger-api';

const HTTP_FOUND = 302;
const HTTP_NOT_FOUND = 404;

// Each click asks the API for a fresh short-lived link, so the cached skill page can keep a
// stable address for its files (LEDGER-01).
export async function GET(_request: Request, { params }: { params: Promise<{ key: string }> }) {
  const url = await getPublicFileUrl((await params).key);
  if (!url) {
    return new Response('Not found', { status: HTTP_NOT_FOUND });
  }
  return new Response(null, {
    status: HTTP_FOUND,
    headers: { Location: url, 'Cache-Control': 'no-store' },
  });
}
