import { readFileSync, readdirSync } from 'node:fs';
import path from 'node:path';
import { describe, expect, it } from 'vitest';
import messages from '@/../messages/en.json';

const SRC_DIR = path.resolve(__dirname, '..');
const SOURCE_FILE = /\.tsx?$/;
const TEST_FILE = /\.test\.tsx?$/;
// `const t = useTranslations('ns')`, `const tCommon = await getTranslations('ns')`, `const t = useTranslations()`
const TRANSLATOR =
  /const\s+(\w+)\s*=\s*(?:await\s+)?(?:useTranslations|getTranslations)\(\s*(?:'([\w.]*)')?\s*\)/g;

const sourceFiles = (dir: string): string[] =>
  readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) return sourceFiles(full);
    return SOURCE_FILE.test(entry.name) && !TEST_FILE.test(entry.name) ? [full] : [];
  });

const hasMessage = (key: string): boolean => {
  let node: unknown = messages;
  for (const part of key.split('.')) {
    if (typeof node !== 'object' || node === null || !(part in node)) return false;
    node = (node as Record<string, unknown>)[part];
  }
  return true;
};

// Only literal keys can be checked; template keys like t(`status.${value}`) are skipped.
const usedKeys = (source: string): string[] =>
  [...source.matchAll(TRANSLATOR)].flatMap(([, name, namespace]) => {
    const call = new RegExp(`(?<![\\w.])${name}\\(\\s*(['"\`])([\\w.]+)\\1`, 'g');
    return [...source.matchAll(call)].map(([, , key]) => (namespace ? `${namespace}.${key}` : key));
  });

describe('messages/en.json', () => {
  it('has every literal translation key the admin app uses', () => {
    const missing = sourceFiles(SRC_DIR).flatMap((file) =>
      usedKeys(readFileSync(file, 'utf8'))
        .filter((key) => !hasMessage(key))
        .map((key) => `${path.relative(SRC_DIR, file)}: ${key}`),
    );

    expect(missing).toEqual([]);
  });
});
