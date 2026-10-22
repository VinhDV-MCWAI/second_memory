"""`ledger-import [VAULT] [--dry-run]`: scan the vault, stop on any invalid published note, send the rest."""

import argparse
import json
import os
import sys
from collections.abc import Mapping, Sequence
from pathlib import Path
from typing import TextIO
from urllib.parse import urlsplit

import httpx

from ledger_importer.client import ApiError, ImportResult, import_notes, make_client
from ledger_importer.contract import (
    DEFAULT_API_URL,
    DEFAULT_VAULT,
    ENV_API_TOKEN,
    ENV_API_URL,
    NOTES_MAX,
    REQUEST_BYTES_MAX,
    URL_SCHEMES,
    ExitCode,
)
from ledger_importer.note import InvalidNoteError, Note, parse_note
from ledger_importer.vault import find_notes


class UsageError(ValueError):
    pass


def main(
    argv: Sequence[str] | None = None,
    env: Mapping[str, str] | None = None,
    transport: httpx.BaseTransport | None = None,
    out: TextIO | None = None,
) -> int:
    """Entry point; the parameters after `argv` let tests replace the outside world."""
    env = os.environ if env is None else env
    out = sys.stdout if out is None else out
    args = _parser().parse_args(argv)
    try:
        vault, api_url, token = _config(args, env)
    except UsageError as error:
        print(f"error: {error}", file=sys.stderr)
        return ExitCode.USAGE

    notes, invalid, scanned = _collect(vault)
    print(f"Vault {vault}: {scanned} notes, {len(notes)} published, {len(invalid)} invalid", file=out)

    if invalid:
        print("Invalid published notes (fix them; nothing was sent):", file=out)
        for key, reason in invalid:
            print(f"  {key}: {reason}", file=out)
        return ExitCode.INVALID_NOTES
    if len(notes) > NOTES_MAX:
        print(
            f"{len(notes)} published notes is more than the {NOTES_MAX} one run accepts; nothing was sent.",
            file=out,
        )
        return ExitCode.INVALID_NOTES
    size = _request_bytes(notes)
    if size > REQUEST_BYTES_MAX:
        print(
            f"The notes add up to {size // 1024 // 1024} MB, more than the "
            f"{REQUEST_BYTES_MAX // 1024 // 1024} MB one run accepts; nothing was sent.",
            file=out,
        )
        return ExitCode.INVALID_NOTES

    try:
        with make_client(api_url, token, transport) as client:
            result = import_notes(client, notes, dry_run=args.dry_run)
    except ApiError as error:
        print(f"error: {error}", file=sys.stderr)
        return ExitCode.API_ERROR

    _report(result, dry_run=args.dry_run, out=out)
    return ExitCode.OK


def _parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        prog="ledger-import",
        description=(
            "Import the Obsidian notes marked `publish: true` as articles. "
            f"Needs {ENV_API_TOKEN}; reads {ENV_API_URL} (default {DEFAULT_API_URL})."
        ),
    )
    parser.add_argument(
        "vault", nargs="?", default=DEFAULT_VAULT, help=f"vault folder (default {DEFAULT_VAULT})"
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="validate and ask the API what would change, without writing anything",
    )
    return parser


def _config(args: argparse.Namespace, env: Mapping[str, str]) -> tuple[Path, str, str]:
    vault = Path(args.vault)
    if not vault.is_dir():
        raise UsageError(f"vault {vault} is not a folder")
    token = env.get(ENV_API_TOKEN, "").strip()
    if not token:
        raise UsageError(f"{ENV_API_TOKEN} is not set (php artisan ledger:import-token <login_id>)")
    api_url = env.get(ENV_API_URL, "").strip() or DEFAULT_API_URL
    if urlsplit(api_url).scheme not in URL_SCHEMES or not urlsplit(api_url).netloc:
        raise UsageError(f"{ENV_API_URL} must be an http(s) URL")
    return vault.resolve(), api_url, token


def _collect(root: Path) -> tuple[list[Note], list[tuple[str, str]], int]:
    notes: list[Note] = []
    invalid: list[tuple[str, str]] = []
    scanned = 0
    for path in find_notes(root):
        scanned += 1
        try:
            note = parse_note(path, root)
        except InvalidNoteError as error:
            invalid.append((path.relative_to(root).as_posix(), str(error)))
            continue
        if note is not None:
            notes.append(note)
    return notes, invalid, scanned


def _request_bytes(notes: list[Note]) -> int:
    """Size of the request body, encoded the way httpx encodes `json=`."""
    body = json.dumps({"notes": notes, "dry_run": False}, ensure_ascii=False, separators=(",", ":"))
    return len(body.encode("utf-8"))


def _report(result: ImportResult, *, dry_run: bool, out: TextIO) -> None:
    title = "Dry run, nothing written. Would be:" if dry_run else "Imported:"
    print(title, file=out)
    for field in ("created", "updated", "unchanged", "hidden"):
        print(f"  {field:<10} {result[field]}", file=out)
    categories = result.get("unknown_categories") or []
    if categories:
        print(
            "Unknown categories (not created; add them in the admin or fix the notes): "
            + ", ".join(categories),
            file=out,
        )
    links = result.get("unknown_links") or []
    if links:
        print(
            "Links shown as plain text (no published note or article by that name): " + ", ".join(links),
            file=out,
        )
