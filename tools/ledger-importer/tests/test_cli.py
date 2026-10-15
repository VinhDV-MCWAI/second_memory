import io
import json
from collections.abc import Callable
from typing import Any

import httpx
import pytest

from ledger_importer.cli import main
from ledger_importer.contract import IMPORT_PATH, ExitCode
from tests.conftest import BASE_URL, TODAY, Vault

TOKEN = "1|secret-token"  # noqa: S105 - a fake token
COUNTS = {"created": 2, "updated": 0, "unchanged": 1, "hidden": 1, "unknown_skills": ["Rust"]}


class FakeApi:
    """MockTransport that records requests and answers with a fixed status and body."""

    def __init__(self, status: int = 200, body: Any = None) -> None:
        self.requests: list[httpx.Request] = []
        self.status = status
        self.body = (
            {"data": COUNTS, "error": {"status": False, "code": 200, "messages": None}}
            if body is None
            else body
        )

    def handler(self, request: httpx.Request) -> httpx.Response:
        self.requests.append(request)
        return httpx.Response(self.status, json=self.body)

    @property
    def payload(self) -> Any:
        return json.loads(self.requests[0].content)


Run = Callable[..., tuple[int, str, str]]


@pytest.fixture
def run(vault: Vault, capsys: pytest.CaptureFixture[str]) -> Run:
    def _run(
        *args: str, api: FakeApi | None = None, env: dict[str, str] | None = None
    ) -> tuple[int, str, str]:
        environment = {"LEDGER_API_TOKEN": TOKEN, "LEDGER_NOTE_BASE_URL": BASE_URL} if env is None else env
        out = io.StringIO()
        transport = httpx.MockTransport((api or FakeApi()).handler)
        code = main([str(vault.root), *args], env=environment, transport=transport, today=TODAY, out=out)
        return code, out.getvalue(), capsys.readouterr().err

    return _run


def add_published(vault: Vault) -> None:
    vault.add("a.md", "publish: true\ndate: 2026-09-30\nskills: [Rust]")
    vault.add("b/c.md", "publish: true\ndate: 2026-09-01\ntags: [x]")
    vault.add("draft.md", "publish: false")
    vault.add("plain.md")


def test_sends_published_notes_with_the_token(vault: Vault, run: Run) -> None:
    add_published(vault)
    api = FakeApi()

    code, out, _ = run(api=api)

    assert code == ExitCode.OK
    request = api.requests[0]
    assert request.method == "POST"
    assert request.url == f"http://ml-nginx:8080{IMPORT_PATH}"
    assert request.headers["Authorization"] == f"Bearer {TOKEN}"
    assert request.headers["Accept"] == "application/json"
    assert api.payload["dry_run"] is False
    assert [note["external_key"] for note in api.payload["notes"]] == ["a.md", "b/c.md"]
    assert "4 notes, 2 published, 0 invalid" in out
    assert "created    2" in out
    assert "Unknown skills" in out
    assert "Rust" in out
    assert TOKEN not in out


def test_dry_run_asks_the_api_to_only_count(vault: Vault, run: Run) -> None:
    add_published(vault)
    api = FakeApi()

    code, out, _ = run("--dry-run", api=api)

    assert code == ExitCode.OK
    assert api.payload["dry_run"] is True
    assert "Dry run, nothing written" in out


def test_any_invalid_published_note_stops_the_run(vault: Vault, run: Run) -> None:
    add_published(vault)
    vault.add("broken.md", "publish: true")
    api = FakeApi()

    code, out, _ = run(api=api)

    assert code == ExitCode.INVALID_NOTES
    assert api.requests == []
    assert "broken.md: date is missing" in out
    assert "nothing was sent" in out


def test_dry_run_with_invalid_notes_sends_nothing_either(vault: Vault, run: Run) -> None:
    vault.add("broken.md", "publish: true")
    api = FakeApi()

    assert run("--dry-run", api=api)[0] == ExitCode.INVALID_NOTES
    assert api.requests == []


def test_empty_vault_sends_an_empty_list(vault: Vault, run: Run) -> None:
    vault.root.mkdir(parents=True)
    api = FakeApi()

    assert run(api=api)[0] == ExitCode.OK
    assert api.payload["notes"] == []


def test_too_many_notes_stops(vault: Vault, run: Run, monkeypatch: pytest.MonkeyPatch) -> None:
    monkeypatch.setattr("ledger_importer.cli.NOTES_MAX", 1)
    add_published(vault)
    api = FakeApi()

    code, out, _ = run(api=api)

    assert code == ExitCode.INVALID_NOTES
    assert "more than the 1 one run accepts" in out
    assert api.requests == []


@pytest.mark.parametrize(
    ("env", "message"),
    [
        ({}, "LEDGER_API_TOKEN is not set"),
        ({"LEDGER_API_TOKEN": TOKEN, "LEDGER_API_URL": "ml-nginx"}, "LEDGER_API_URL must be an http(s) URL"),
        ({"LEDGER_API_TOKEN": TOKEN, "LEDGER_NOTE_BASE_URL": "notes/"}, "LEDGER_NOTE_BASE_URL must be"),
    ],
)
def test_configuration_errors(vault: Vault, run: Run, env: dict[str, str], message: str) -> None:
    vault.root.mkdir(parents=True)

    code, _, err = run(env=env)

    assert code == ExitCode.USAGE
    assert message in err


def test_missing_vault_is_a_usage_error(run: Run) -> None:
    code, _, err = run()

    assert code == ExitCode.USAGE
    assert "is not a folder" in err


def test_api_url_from_the_environment(vault: Vault, run: Run) -> None:
    vault.root.mkdir(parents=True)
    api = FakeApi()

    run(api=api, env={"LEDGER_API_TOKEN": TOKEN, "LEDGER_API_URL": "http://localhost:81/"})

    assert api.requests[0].url == f"http://localhost:81{IMPORT_PATH}"


@pytest.mark.parametrize(
    ("status", "body", "message"),
    [
        (
            401,
            {"data": None, "error": {"status": True, "code": 401, "messages": "Unauthenticated."}},
            "401: Unauthenticated.",
        ),
        (
            403,
            {"data": None, "error": {"status": True, "code": 403, "messages": "Forbidden"}},
            "403: Forbidden",
        ),
        (
            422,
            {
                "data": None,
                "error": {"status": True, "code": 422, "messages": {"notes.0.url": ["The url is invalid."]}},
            },
            "notes.0.url: The url is invalid.",
        ),
        (
            429,
            {"data": None, "error": {"status": True, "code": 429, "messages": "Too Many Attempts."}},
            "429",
        ),
        (500, "not json", "500: no error message"),
        (200, {"data": {"created": 1}}, "without the expected import counts"),
    ],
)
def test_api_errors_exit_3(vault: Vault, run: Run, status: int, body: Any, message: str) -> None:
    add_published(vault)

    code, _, err = run(api=FakeApi(status, body))

    assert code == ExitCode.API_ERROR
    assert message in err
    assert TOKEN not in err


def test_network_error_exits_3(vault: Vault, capsys: pytest.CaptureFixture[str]) -> None:
    vault.root.mkdir(parents=True)

    def refuse(request: httpx.Request) -> httpx.Response:
        raise httpx.ConnectError("refused", request=request)

    code = main(
        [str(vault.root)],
        env={"LEDGER_API_TOKEN": TOKEN},
        transport=httpx.MockTransport(refuse),
        today=TODAY,
        out=io.StringIO(),
    )

    assert code == ExitCode.API_ERROR
    assert "cannot reach the API" in capsys.readouterr().err
