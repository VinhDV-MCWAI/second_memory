/** Browser and system of a session, read from its User-Agent header (sessions screen, AUTH-04). */
export interface DeviceName {
  browser: string | null;
  os: string | null;
}

// Order matters: Edge and Opera also say "Chrome", Chrome also says "Safari"
const BROWSERS: [RegExp, string][] = [
  [/Edg(e|A|iOS)?\//, 'Edge'],
  [/OPR\/|Opera/, 'Opera'],
  [/Firefox\/|FxiOS\//, 'Firefox'],
  [/Chrome\/|CriOS\//, 'Chrome'],
  [/Safari\//, 'Safari'],
];

// Android says "Linux" and iOS says "Mac OS X": test them first
const SYSTEMS: [RegExp, string][] = [
  [/Android/, 'Android'],
  [/iPhone|iPad|iPod/, 'iOS'],
  [/Windows/, 'Windows'],
  [/Mac OS X|Macintosh/, 'macOS'],
  [/CrOS/, 'ChromeOS'],
  [/Linux/, 'Linux'],
];

const first = (list: [RegExp, string][], userAgent: string) =>
  list.find(([pattern]) => pattern.test(userAgent))?.[1] ?? null;

export function describeUserAgent(userAgent: string | null | undefined): DeviceName {
  if (!userAgent) return { browser: null, os: null };
  return { browser: first(BROWSERS, userAgent), os: first(SYSTEMS, userAgent) };
}
