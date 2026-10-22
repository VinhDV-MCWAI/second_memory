// Splits HTML from the API around its fenced code blocks, so a page can colour them with Shiki
// on the server (ADR-0022). The API (league/commonmark) writes a block as
// <pre><code class="language-php">…escaped code…</code></pre>; everything else stays as it is.
const CODE_BLOCK = /<pre><code(?: class="language-([^"]+)")?>([\s\S]*?)<\/code><\/pre>/g;

// The only entities commonmark writes inside code
const ENTITIES: Record<string, string> = {
  '&lt;': '<',
  '&gt;': '>',
  '&quot;': '"',
  '&#039;': "'",
  '&amp;': '&',
};
const ENTITY = /&(?:lt|gt|quot|#039|amp);/g;

export type HtmlPart = { html: string } | { code: string; lang: string };

const PLAIN_TEXT = 'plaintext';

export function splitCodeBlocks(html: string): HtmlPart[] {
  const parts: HtmlPart[] = [];
  let from = 0;
  for (const match of html.matchAll(CODE_BLOCK)) {
    parts.push({ html: html.slice(from, match.index) });
    parts.push({
      // commonmark ends every block with a newline; Shiki would show it as an empty last line
      code: (match[2] ?? '')
        .replace(ENTITY, (entity) => ENTITIES[entity] ?? entity)
        .replace(/\n$/, ''),
      lang: match[1] ?? PLAIN_TEXT,
    });
    from = match.index + match[0].length;
  }
  parts.push({ html: html.slice(from) });
  return parts;
}
