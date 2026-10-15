"""One note → one request item of `POST admin/evidence/import` (ADR-0010 §Importer contract)."""

import datetime as dt
import re
import unicodedata
from dataclasses import dataclass
from pathlib import Path
from typing import Any, NotRequired, TypedDict
from urllib.parse import quote, urlsplit

from ledger_importer.contract import (
    ELLIPSIS,
    EXTERNAL_KEY_MAX,
    NOTE_SUFFIX,
    SKILL_NAME_MAX,
    SUMMARY_MAX,
    TAG_NAME_MAX,
    TITLE_MAX,
    URL_MAX,
    URL_SCHEMES,
)
from ledger_importer.vault import FrontmatterError, split_frontmatter

# [[target|label]] → label, [[target]] → target, [text](url) and ![alt](url) → text
_WIKI_LINK = re.compile(r"!?\[\[([^\]|]*)(?:\|([^\]]*))?\]\]")
_MD_LINK = re.compile(r"!?\[([^\]]*)\]\([^)]*\)")
_DATE = re.compile(r"\d{4}-\d{2}-\d{2}")


class Note(TypedDict):
    external_key: str
    title: str
    url: str
    occurred_on: str
    summary: NotRequired[str]
    tags: list[str]
    skills: list[str]


class InvalidNoteError(ValueError):
    """A published note that cannot be sent; the message says why."""


@dataclass(frozen=True)
class NoteContext:
    root: Path
    note_base_url: str | None
    today: dt.date


def parse_note(path: Path, context: NoteContext) -> Note | None:
    """The request item of a published note, or None when the note is not published."""
    try:
        frontmatter, body = split_frontmatter(path.read_text(encoding="utf-8"))
    except FrontmatterError as error:
        # Cannot tell whether it is published, and skipping it could hide its row: treat it as invalid
        raise InvalidNoteError(str(error)) from None
    except UnicodeDecodeError:
        raise InvalidNoteError("file is not UTF-8") from None

    # Only the YAML boolean true publishes; "true", yes as a string, 1 do not
    if frontmatter.get("publish") is not True:
        return None

    key = external_key(path, context.root)
    note: Note = {
        "external_key": key,
        "title": _title(frontmatter, path),
        "url": _url(frontmatter, key, context.note_base_url),
        "occurred_on": _occurred_on(frontmatter, context.today),
        "tags": _names(frontmatter, "tags", TAG_NAME_MAX, strip_hash=True),
        "skills": _names(frontmatter, "skills", SKILL_NAME_MAX, strip_hash=False),
    }
    summary = _summary(frontmatter, body)
    if summary:
        note["summary"] = summary
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
    """The first block of text between blank lines that is not a heading."""
    for block in re.split(r"\n\s*\n", body):
        stripped = block.strip()
        if stripped and not stripped.startswith("#"):
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


def _occurred_on(frontmatter: dict[str, Any], today: dt.date) -> str:
    value = frontmatter.get("date")
    # YAML reads an unquoted 2026-10-01 as a date, a quoted one as text; a datetime has a time part
    if isinstance(value, dt.date) and not isinstance(value, dt.datetime):
        day = value
    elif isinstance(value, str) and _DATE.fullmatch(value.strip()):
        try:
            day = dt.date.fromisoformat(value.strip())
        except ValueError:
            raise InvalidNoteError(f"date {value!r} does not exist") from None
    elif value is None:
        raise InvalidNoteError("date is missing (YYYY-MM-DD)")
    else:
        raise InvalidNoteError(f"date {value!r} is not YYYY-MM-DD")
    if day > today:
        raise InvalidNoteError(f"date {day.isoformat()} is in the future")
    return day.isoformat()


def _is_http_url(value: str) -> bool:
    parts = urlsplit(value)
    return parts.scheme in URL_SCHEMES and bool(parts.netloc)


def _url(frontmatter: dict[str, Any], key: str, base_url: str | None) -> str:
    value = frontmatter.get("url")
    if value is not None:
        if not isinstance(value, str) or not _is_http_url(value.strip()):
            raise InvalidNoteError("url must be an http(s) URL")
        url = value.strip()
    elif base_url:
        path = "/".join(quote(segment, safe="") for segment in key.removesuffix(NOTE_SUFFIX).split("/"))
        url = f"{base_url.rstrip('/')}/{path}"
    else:
        raise InvalidNoteError("no url in frontmatter and LEDGER_NOTE_BASE_URL is not set")
    if len(url) > URL_MAX:
        raise InvalidNoteError(f"url is longer than {URL_MAX} characters")
    return url


def _names(frontmatter: dict[str, Any], field: str, max_length: int, *, strip_hash: bool) -> list[str]:
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
        if strip_hash:
            name = name.removeprefix("#").strip()
        if not name or name.casefold() in seen:
            continue
        if len(name) > max_length:
            raise InvalidNoteError(f"{field}: {name[:20]}… is longer than {max_length} characters")
        seen.add(name.casefold())
        names.append(unicodedata.normalize("NFC", name))
    return names
