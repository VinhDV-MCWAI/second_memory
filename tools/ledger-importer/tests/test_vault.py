import pytest

from ledger_importer.vault import FrontmatterError, find_notes, split_frontmatter
from tests.conftest import Vault


def test_find_notes_skips_dot_folders_and_other_files(vault: Vault) -> None:
    vault.add("b.md")
    vault.add("a/c.md")
    vault.add(".obsidian/workspace.md")
    vault.add(".trash/old.md")
    vault.add("a/.git/x.md")
    vault.add("image.png")

    found = [path.relative_to(vault.root).as_posix() for path in find_notes(vault.root)]

    assert found == ["a/c.md", "b.md"]


def test_split_frontmatter_returns_mapping_and_body() -> None:
    data, body = split_frontmatter("---\npublish: true\ntags: [a]\n---\n# Title\n\nText")

    assert data == {"publish": True, "tags": ["a"]}
    assert body == "# Title\n\nText"


def test_note_without_frontmatter_has_empty_mapping() -> None:
    assert split_frontmatter("Just text\n---\n") == ({}, "Just text\n---\n")


def test_empty_frontmatter_is_an_empty_mapping() -> None:
    assert split_frontmatter("---\n---\nText")[0] == {}


def test_byte_order_mark_is_ignored() -> None:
    assert split_frontmatter("﻿---\npublish: true\n---\n")[0] == {"publish": True}


@pytest.mark.parametrize(
    ("text", "reason"),
    [
        ("---\npublish: true\nno closing fence", "no closing"),
        ("---\npublish: [true\n---\n", "not valid YAML"),
        ("---\n- a\n- b\n---\n", "not a key: value mapping"),
    ],
)
def test_broken_frontmatter_raises(text: str, reason: str) -> None:
    with pytest.raises(FrontmatterError, match=reason):
        split_frontmatter(text)
