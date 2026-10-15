// Cost of one request per endpoint at 1 user (PERF-01). Run by perf/profile.sh once per server
// variant. One user, no think time, so the numbers are service time, not queueing.
import http from 'k6/http';
import { check } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://ml-php:8099';
const ITERATIONS = Number(__ENV.ITERATIONS || 220);
const WARMUP = Number(__ENV.WARMUP || 20);
// Sanctum treats requests from this origin as the SPA: session + CSRF (ADR-0004)
const SPA = { Referer: `${BASE_URL}/` };
// [tag, path, headers]: /up has no middleware; public_list without the SPA Referer starts no session
const REQUESTS = [
  ['up', '/up', {}],
  ['public_list', '/api/public/skills', {}],
  ['public_list_session', '/api/public/skills', SPA],
  ['admin_search', '/api/admin/search?q=postgresql', SPA],
  ['admin_search_fuzzy', '/api/admin/search?q=postgersql', SPA],
];

export const options = {
  noCookiesReset: true,
  scenarios: { cost: { executor: 'per-vu-iterations', vus: 1, iterations: ITERATIONS, maxDuration: '10m' } },
  // Always-true thresholds, only so the summary export carries one sub-metric per endpoint
  thresholds: Object.fromEntries(
    REQUESTS.map(([tag]) => [`http_req_duration{endpoint:${tag},phase:measure}`, ['max>=0']]),
  ),
};

function login() {
  http.get(`${BASE_URL}/api/sanctum/csrf-cookie`, { headers: SPA });
  const xsrf = http.cookieJar().cookiesForURL(BASE_URL)['XSRF-TOKEN'];
  const res = http.post(
    `${BASE_URL}/api/admin/credential/login`,
    JSON.stringify({ user_name: __ENV.PERF_USER, password: __ENV.PERF_PASSWORD }),
    { headers: { ...SPA, 'Content-Type': 'application/json', 'X-XSRF-TOKEN': decodeURIComponent(xsrf[0]) } },
  );
  check(res, { 'login 200': (r) => r.status === 200 });
}

export default function () {
  if (__ITER === 0) login();
  const phase = __ITER < WARMUP ? 'warmup' : 'measure';
  for (const [tag, path, headers] of REQUESTS) {
    const res = http.get(`${BASE_URL}${path}`, {
      headers: { Accept: 'application/json', ...headers },
      tags: { endpoint: tag, phase },
    });
    check(res, { [`${tag} 200`]: (r) => r.status === 200 });
  }
}

export function handleSummary(data) {
  const rows = REQUESTS.map(([tag]) => {
    const v = data.metrics[`http_req_duration{endpoint:${tag},phase:measure}`].values;
    return `${tag.padEnd(22)} ${v.med.toFixed(1).padStart(7)} ${v['p(95)'].toFixed(1).padStart(7)}`;
  });
  const failed = data.metrics.checks.values.fails;
  return {
    stdout: `${'endpoint'.padEnd(22)} ${'p50 ms'.padStart(7)} ${'p95 ms'.padStart(7)}\n${rows.join('\n')}\nfailed checks: ${failed}\n`,
  };
}
