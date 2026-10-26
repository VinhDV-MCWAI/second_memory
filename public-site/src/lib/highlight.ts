// Marks the searched words in a title or heading the way the API compares them
// (SearchHighlighter: accent-free, lower case, words matched from their start), keeping the
// original characters. Returns pieces, so pages print them as text and never as HTML.

export interface Piece {
  text: string;
  mark: boolean;
}

/** Same as the API's SearchConst::WORD_SEPARATOR */
const WORD_SEPARATOR = /[^\p{L}\p{N}]+/u;
const WORD_CHAR = /[\p{L}\p{N}]/u;

const fold = (char: string): string =>
  char.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase().replace(/đ/g, 'd');

export function searchWords(query: string): string[] {
  return query
    .split(WORD_SEPARATOR)
    .filter(Boolean)
    .map((word) => Array.from(word, fold).join(''));
}

export function highlight(text: string, words: string[]): Piece[] {
  const chars = Array.from(text);
  // One folded string, and the original character each folded unit came from
  let folded = '';
  const origin: number[] = [];
  chars.forEach((char, index) => {
    const piece = fold(char) || ' ';
    folded += piece;
    for (let i = 0; i < piece.length; i++) origin.push(index);
  });

  /** First character → character after the match */
  const ends = new Map<number, number>();
  for (const word of words) {
    for (let at = folded.indexOf(word); at !== -1; at = folded.indexOf(word, at + 1)) {
      if (at > 0 && WORD_CHAR.test(folded[at - 1] ?? '')) continue;
      const start = origin[at] ?? 0;
      const end = (origin[at + word.length - 1] ?? start) + 1;
      ends.set(start, Math.max(ends.get(start) ?? 0, end));
    }
  }

  const pieces: Piece[] = [];
  let plain = '';
  for (let i = 0; i < chars.length; ) {
    const end = ends.get(i);
    if (end === undefined) {
      plain += chars[i];
      i++;
      continue;
    }
    if (plain) pieces.push({ text: plain, mark: false });
    plain = '';
    pieces.push({ text: chars.slice(i, end).join(''), mark: true });
    i = end;
  }
  if (plain) pieces.push({ text: plain, mark: false });
  return pieces;
}
