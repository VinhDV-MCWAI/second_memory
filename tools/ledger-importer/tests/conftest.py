from pathlib import Path

import pytest


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


@pytest.fixture
def vault(tmp_path: Path) -> Vault:
    return Vault(tmp_path / "vault")
