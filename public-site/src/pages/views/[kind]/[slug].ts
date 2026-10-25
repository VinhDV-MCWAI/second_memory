// The reading page's script reports here that someone really read an article or stayed on a
// category (ADR-0024: tab shown long enough, article scrolled). The visitor is a hash of IP (nginx's
// X-Real-IP) and browser; the API keeps it 30 minutes as a cache key to count a reader once, and
// never stores who read.
import type { APIRoute } from 'astro';
import { createHash } from 'node:crypto';
import { reportView } from '@/lib/public-api';
import { VIEW_KINDS } from '@/lib/routes';

const HTTP_NO_CONTENT = 204;
const HTTP_NOT_FOUND = 404;

export const POST: APIRoute = async ({ params, request, clientAddress }) => {
  const kind = VIEW_KINDS.find((known) => known === params.kind);
  if (!kind || !params.slug) {
    return new Response(null, { status: HTTP_NOT_FOUND });
  }
  const ip = request.headers.get('x-real-ip') ?? clientAddress;
  const browser = request.headers.get('user-agent') ?? '';
  const visitor = createHash('sha256').update(`${ip}|${browser}`).digest('hex');
  await reportView(kind, params.slug, visitor);
  return new Response(null, { status: HTTP_NO_CONTENT });
};
