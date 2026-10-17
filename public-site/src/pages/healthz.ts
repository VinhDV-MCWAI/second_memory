import type { APIRoute } from 'astro';

// Container healthcheck: answers without calling the API, because nginx (the API's way in) starts
// only after this container is healthy.
export const GET: APIRoute = () => new Response('ok');
