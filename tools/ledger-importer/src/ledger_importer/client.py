"""The one API call: `POST /api/admin/evidence/import` with a Sanctum token (ADR-0010)."""

from typing import Any, TypedDict, cast

import httpx

from ledger_importer.contract import HTTP_TIMEOUT_SECONDS, IMPORT_PATH
from ledger_importer.note import Note

# Validation errors can list one message per note; the report shows the first few
_MESSAGES_SHOWN = 5


class ImportResult(TypedDict):
    created: int
    updated: int
    unchanged: int
    hidden: int
    unknown_skills: list[str]


class ApiError(RuntimeError):
    """Authentication, validation, rate limit, server or network failure."""


def make_client(api_url: str, token: str, transport: httpx.BaseTransport | None = None) -> httpx.Client:
    return httpx.Client(
        base_url=api_url.rstrip("/"),
        headers={"Authorization": f"Bearer {token}", "Accept": "application/json"},
        timeout=HTTP_TIMEOUT_SECONDS,
        transport=transport,
    )


def import_notes(client: httpx.Client, notes: list[Note], *, dry_run: bool) -> ImportResult:
    """Send the complete list of published notes; with `dry_run` the API only counts."""
    try:
        response = client.post(IMPORT_PATH, json={"notes": notes, "dry_run": dry_run})
    except httpx.HTTPError as error:
        raise ApiError(f"cannot reach the API at {client.base_url}: {error.__class__.__name__}") from None

    body = _json(response)
    if response.status_code != httpx.codes.OK:
        raise ApiError(f"API answered {response.status_code}: {_error_message(body)}")
    data = body.get("data") if isinstance(body, dict) else None
    if not isinstance(data, dict) or not {"created", "updated", "unchanged", "hidden"} <= data.keys():
        raise ApiError("API answered 200 without the expected import counts")
    return cast(ImportResult, data)


def _json(response: httpx.Response) -> Any:
    try:
        return response.json()
    except ValueError:
        return None


def _error_message(body: Any) -> str:
    """The `error.messages` of the API envelope: a text, or field → messages for a 422."""
    error = body.get("error") if isinstance(body, dict) else None
    messages = error.get("messages") if isinstance(error, dict) else None
    if isinstance(messages, str) and messages:
        return messages
    if isinstance(messages, dict) and messages:
        lines = [f"{field}: {' '.join(map(str, texts))}" for field, texts in messages.items()]
        more = len(lines) - _MESSAGES_SHOWN
        return "; ".join(lines[:_MESSAGES_SHOWN]) + (f"; … {more} more" if more > 0 else "")
    return "no error message in the response"
