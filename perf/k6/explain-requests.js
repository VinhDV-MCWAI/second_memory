// One request per hot read endpoint (DB-01). Run by perf/explain.sh while auto_explain logs the
// plan of every statement on the perf database; the requests themselves are not measured here.
import http from 'k6/http';
import { check } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://ml-php:8099';
// Sanctum treats requests from this origin as the SPA: session + CSRF (ADR-0004)
const SPA = { Referer: `${BASE_URL}/` };
const REQUESTS = [
  ['public_list', '/api/public/skills', {}],
  ['public_detail', '/api/public/skills/skill-1', {}],
  ['admin_search', '/api/admin/search?q=postgresql', SPA],
  ['admin_search_fuzzy', '/api/admin/search?q=postgersql', SPA],
  ['admin_skill_list', '/api/admin/skill/list?per_page=15', SPA],
  ['admin_evidence_list', '/api/admin/evidence/list?per_page=15', SPA],
  ['admin_dashboard', '/api/admin/dashboard/summary', SPA],
];

export const options = { iterations: 1, vus: 1 };

export default function () {
  http.get(`${BASE_URL}/api/sanctum/csrf-cookie`, { headers: SPA });
  const xsrf = http.cookieJar().cookiesForURL(BASE_URL)['XSRF-TOKEN'];
  const login = http.post(
    `${BASE_URL}/api/admin/credential/login`,
    JSON.stringify({ user_name: __ENV.PERF_USER, password: __ENV.PERF_PASSWORD }),
    { headers: { ...SPA, 'Content-Type': 'application/json', 'X-XSRF-TOKEN': decodeURIComponent(xsrf[0]) } },
  );
  check(login, { 'login 200': (r) => r.status === 200 });

  for (const [tag, path, headers] of REQUESTS) {
    const res = http.get(`${BASE_URL}${path}`, { headers: { Accept: 'application/json', ...headers }, tags: { endpoint: tag } });
    check(res, { [`${tag} 200`]: (r) => r.status === 200 });
  }
}
