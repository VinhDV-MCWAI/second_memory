import datetime as dt
from pathlib import Path

import pytest

from ledger_importer.note import NoteContext

TODAY = dt.date(2026, 10, 8)
BASE_URL = "https://notes.example.com"


class Vault:
    """A throwaway vault folder; `add` writes a note with optional frontmatter."""

    def __init__(self, root: Path) -> None:
        self.root = root

    def add(self, relative: str, frontmatter: str | None = None, body: str = "Body text.") -> Path:
        path = self.root / relative
        path.parent.mkdir(parents=True, exist_ok=True)
        text = body if frontmatter is None else f"---\n{frontmatter}\n---\n{body}"
        path.write_text(text, encoding="utf-8")
        return path

    def context(self, base_url: str | None = BASE_URL) -> NoteContext:
        return NoteContext(root=self.root, note_base_url=base_url, today=TODAY)


@pytest.fixture
def vault(tmp_path: Path) -> Vault:
    return Vault(tmp_path / "vault")
