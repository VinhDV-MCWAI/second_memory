"""One note → one request item of `POST admin/article/import` (ADR-0022 §Nhập từ Obsidian).

The body is sent as written: the API turns `[[links]]` to other published notes into article links.
"""

import re
import unicodedata
from pathlib import Path
from typing import Any, NotRequired, TypedDict

from ledger_importer.contract import (
    BODY_MAX,
    CATEGORY_NAME_MAX,
    ELLIPSIS,
    EXTERNAL_KEY_MAX,
    NOTE_SUFFIX,
    SUMMARY_MAX,
    TITLE_MAX,
)
from ledger_importer.vault import FrontmatterError, split_frontmatter

# [[target|label]] → label, [[target]] → target, [text](url) and ![alt](url) → text
_WIKI_LINK = re.compile(r"!?\[\[([^\]|]*)(?:\|([^\]]*))?\]\]")
_MD_LINK = re.compile(r"!?\[([^\]]*)\]\([^)]*\)")


class Note(TypedDict):
    external_key: str
    title: str
    body: str
    summary: NotRequired[str]
    parent: NotRequired[str]
    categories: list[str]


class InvalidNoteError(ValueError):
    """A published note that cannot be sent; the message says why."""


def parse_note(path: Path, root: Path) -> Note | None:
    """The request item of a published note, or None when the note is not published."""
    try:
        frontmatter, body = split_frontmatter(path.read_text(encoding="utf-8"))
    except FrontmatterError as error:
        # Cannot tell whether it is published, and skipping it could hide its article: treat it as invalid
        raise InvalidNoteError(str(error)) from None
    except UnicodeDecodeError:
        raise InvalidNoteError("file is not UTF-8") from None

    # Only the YAML boolean true publishes; "true", yes as a string, 1 do not
    if frontmatter.get("publish") is not True:
        return None

    body = body.strip("\n")
    if len(body) > BODY_MAX:
        raise InvalidNoteError(f"content is longer than {BODY_MAX} characters")

    note: Note = {
        "external_key": external_key(path, root),
        "title": _title(frontmatter, path),
        "body": body,
        "categories": _names(frontmatter, "categories", CATEGORY_NAME_MAX),
    }
    summary = _summary(frontmatter, body)
    if summary:
        note["summary"] = summary
    parent = _parent(frontmatter)
    if parent:
        note["parent"] = parent
    return note


def external_key(path: Path, root: Path) -> str:
    """Vault-relative path with `/` separators in Unicode NFC (macOS writes NFD)."""
    key = unicodedata.normalize("NFC", path.relative_to(root).as_posix())
    if len(key) > EXTERNAL_KEY_MAX:
        raise InvalidNoteError(f"path is longer than {EXTERNAL_KEY_MAX} characters")
    return key


def summarize(text: str) -> str:
    """Links reduced to their text, whitespace collapsed, cut at a word boundary with `…`."""
    text = _WIKI_LINK.sub(lambda m: m.group(2) if m.group(2) is not None else m.group(1), text)
    text = _MD_LINK.sub(r"\1", text)
    text = " ".join(text.split())
    if len(text) <= SUMMARY_MAX:
        return text
    limit = SUMMARY_MAX - len(ELLIPSIS)
    cut = text[:limit]
    # The cut falls inside a word: drop that partial word (unless it is the only word)
    if text[limit] != " " and " " in cut:
        cut = cut.rsplit(" ", 1)[0]
    return cut.rstrip() + ELLIPSIS


def first_paragraph(body: str) -> str:
    """The first block of text between blank lines that is not a heading or a code fence."""
    for block in re.split(r"\n\s*\n", body):
        stripped = block.strip()
        if stripped and not stripped.startswith(("#", "```", "~~~")):
            return stripped
    return ""


def _title(frontmatter: dict[str, Any], path: Path) -> str:
    value = frontmatter.get("title")
    if value is None:
        title = path.name.removesuffix(NOTE_SUFFIX)
    elif isinstance(value, str) and value.strip():
        title = value.strip()
    else:
        raise InvalidNoteError("title must be a non-empty text")
    if len(title) > TITLE_MAX:
        raise InvalidNoteError(f"title is longer than {TITLE_MAX} characters")
    return unicodedata.normalize("NFC", title)


def _summary(frontmatter: dict[str, Any], body: str) -> str:
    value = frontmatter.get("summary")
    if value is None:
        return summarize(first_paragraph(body))
    if not isinstance(value, str):
        raise InvalidNoteError("summary must be text")
    return summarize(value)


def _parent(frontmatter: dict[str, Any]) -> str:
    """The parent note as Obsidian names it: a path, a file name or "[[Note]]"; the API resolves it."""
    value = frontmatter.get("parent")
    if value is None:
        return ""
    if not isinstance(value, str):
        # An unquoted [[Note]] is a YAML list of lists
        raise InvalidNoteError('parent must be text: a note path or "[[Note]]" in quotes')
    parent = value.strip()
    if len(parent) > EXTERNAL_KEY_MAX:
        raise InvalidNoteError(f"parent is longer than {EXTERNAL_KEY_MAX} characters")
    return unicodedata.normalize("NFC", parent)


def _names(frontmatter: dict[str, Any], field: str, max_length: int) -> list[str]:
    """A list (or one text) of names, trimmed, without duplicates ignoring case, in note order."""
    value = frontmatter.get(field)
    if value is None:
        return []
    items = [value] if isinstance(value, str) else value
    if not isinstance(items, list) or not all(isinstance(item, str) for item in items):
        raise InvalidNoteError(f"{field} must be a list of texts")

    names: list[str] = []
    seen: set[str] = set()
    for item in items:
        name = item.strip()
        if not name or name.casefold() in seen:
            continue
        if len(name) > max_length:
            raise InvalidNoteError(f"{field}: {name[:20]}… is longer than {max_length} characters")
        seen.add(name.casefold())
        names.append(unicodedata.normalize("NFC", name))
    return names
