// k6 baseline of the Skill Ledger read paths (P3-16, REQ-002 NFR: p95 < 300 ms).
// Run through scripts/perf-baseline.sh, which seeds the throwaway `perf` database and
// starts the API on BASE_URL. SCENARIO=smoke (1 user) or load (ramp to 10 users).
import http from 'k6/http';
import { check } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://ml-php:8099';
const SCENARIO = __ENV.SCENARIO || 'smoke';
// Sanctum treats requests from this origin as the SPA (session + CSRF, ADR-0004)
const HEADERS = { Accept: 'application/json', Referer: `${BASE_URL}/` };
// Seeded by PerfLedgerSeeder: slugs skill-1 … skill-100, every third one private (404 by design)
const PUBLIC_SLUGS = Array.from({ length: 100 }, (_, i) => i + 1)
  .filter((n) => n % 3 !== 0)
  .map((n) => `skill-${n}`);
const QUERIES = ['ky nang', 'postgresql', 'toi uu truy van', 'postgersql', 'redis cache', 'giam sat'];

const SCENARIOS = {
  smoke: { executor: 'constant-vus', vus: 1, duration: '20s' },
  load: {
    executor: 'ramping-vus',
    startVUs: 0,
    stages: [
      { duration: '15s', target: 10 },
      { duration: '60s', target: 10 },
      { duration: '10s', target: 0 },
    ],
  },
};

export const options = {
  // Keep the session cookie of the first iteration's login; k6 clears the jar per iteration by default
  noCookiesReset: true,
  scenarios: { [SCENARIO]: SCENARIOS[SCENARIO] },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    'http_req_duration{endpoint:public_list}': ['p(95)<300'],
    'http_req_duration{endpoint:public_detail}': ['p(95)<300'],
    'http_req_duration{endpoint:admin_search}': ['p(95)<300'],
    'http_req_duration{endpoint:admin_skill_list}': ['p(95)<300'],
  },
};

const pick = (items) => items[Math.floor(Math.random() * items.length)];

/** Session login once per virtual user: CSRF cookie, then credentials with the XSRF header. */
function login() {
  http.get(`${BASE_URL}/api/sanctum/csrf-cookie`, { headers: HEADERS, tags: { endpoint: 'login' } });
  const xsrf = http.cookieJar().cookiesForURL(BASE_URL)['XSRF-TOKEN'];
  const res = http.post(
    `${BASE_URL}/api/admin/credential/login`,
    JSON.stringify({ user_name: __ENV.PERF_USER, password: __ENV.PERF_PASSWORD }),
    {
      headers: { ...HEADERS, 'Content-Type': 'application/json', 'X-XSRF-TOKEN': decodeURIComponent(xsrf[0]) },
      tags: { endpoint: 'login' },
    },
  );
  check(res, { 'login 200': (r) => r.status === 200 });
}

export default function () {
  if (__ITER === 0) login();

  const get = (path, endpoint) => {
    const res = http.get(`${BASE_URL}${path}`, { headers: HEADERS, tags: { endpoint } });
    check(res, { [`${endpoint} 200`]: (r) => r.status === 200 });
  };

  get('/api/public/skills', 'public_list');
  get(`/api/public/skills/${pick(PUBLIC_SLUGS)}`, 'public_detail');
  get(`/api/admin/search?q=${encodeURIComponent(pick(QUERIES))}`, 'admin_search');
  get('/api/admin/skill/list?per_page=15', 'admin_skill_list');
}
