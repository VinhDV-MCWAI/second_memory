import re
import unicodedata

import pytest

from ledger_importer.contract import ELLIPSIS, SUMMARY_MAX
from ledger_importer.note import InvalidNoteError, first_paragraph, parse_note, summarize
from tests.conftest import BASE_URL, Vault

PUBLISHED = "publish: true\ndate: 2026-09-30"


def test_published_note_becomes_a_request_item(vault: Vault) -> None:
    path = vault.add(
        "lab/P3 search.md",
        "publish: true\ndate: 2026-09-30\ntitle: Search spike\n"
        "tags: ['#lab/p3', postgres]\nskills: [PostgreSQL]",
        "# Heading\n\nFirst [[ADR-0009|the ADR]] paragraph\nwith a [link](https://x.y).\n\nSecond.",
    )

    note = parse_note(path, vault.context())

    assert note == {
        "external_key": "lab/P3 search.md",
        "title": "Search spike",
        "url": f"{BASE_URL}/lab/P3%20search",
        "occurred_on": "2026-09-30",
        "summary": "First the ADR paragraph with a link.",
        "tags": ["lab/p3", "postgres"],
        "skills": ["PostgreSQL"],
    }


@pytest.mark.parametrize(
    "frontmatter",
    [
        None,
        "date: 2026-09-30",
        "publish: false",
        "publish: 'true'",
        "publish: yes",
        "publish: on",
        "publish: 1",
    ],
)
def test_only_the_yaml_boolean_true_publishes(vault: Vault, frontmatter: str | None) -> None:
    assert parse_note(vault.add("n.md", frontmatter), vault.context()) is None


def test_capitalized_true_publishes(vault: Vault) -> None:
    assert parse_note(vault.add("n.md", "publish: True\ndate: 2026-09-30"), vault.context()) is not None


def test_defaults_title_from_file_name_and_url_from_base(vault: Vault) -> None:
    note = parse_note(vault.add("Tối ưu/Truy vấn #1.md", PUBLISHED, ""), vault.context())

    assert note is not None
    assert note["title"] == "Truy vấn #1"
    assert note["url"] == f"{BASE_URL}/T%E1%BB%91i%20%C6%B0u/Truy%20v%E1%BA%A5n%20%231"
    assert "summary" not in note


def test_external_key_is_nfc(vault: Vault) -> None:
    decomposed = unicodedata.normalize("NFD", "Kỹ năng.md")
    note = parse_note(vault.add(decomposed, PUBLISHED), vault.context())

    assert note is not None
    assert note["external_key"] == unicodedata.normalize("NFC", "Kỹ năng.md")


def test_frontmatter_url_wins_over_base(vault: Vault) -> None:
    note = parse_note(
        vault.add("n.md", f"{PUBLISHED}\nurl: https://blog.example.com/p/1"), vault.context(None)
    )

    assert note is not None
    assert note["url"] == "https://blog.example.com/p/1"


def test_quoted_date_is_accepted(vault: Vault) -> None:
    note = parse_note(vault.add("n.md", "publish: true\ndate: '2026-10-08'"), vault.context())

    assert note is not None
    assert note["occurred_on"] == "2026-10-08"


def test_frontmatter_summary_is_cut_too(vault: Vault) -> None:
    note = parse_note(vault.add("n.md", f"{PUBLISHED}\nsummary: {'word ' * 100}"), vault.context())

    assert note is not None
    assert len(note["summary"]) <= SUMMARY_MAX
    assert note["summary"].endswith(ELLIPSIS)


def test_tags_accept_one_text_and_drop_duplicates_ignoring_case(vault: Vault) -> None:
    note = parse_note(
        vault.add("n.md", f"{PUBLISHED}\ntags: '#Lab'\nskills: [Go, go, ' Go ']"), vault.context()
    )

    assert note is not None
    assert note["tags"] == ["Lab"]
    assert note["skills"] == ["Go"]


@pytest.mark.parametrize(
    ("frontmatter", "reason"),
    [
        ("publish: true", "date is missing"),
        ("publish: true\ndate: 30/09/2026", "is not YYYY-MM-DD"),
        ("publish: true\ndate: '2026-02-30'", "does not exist"),
        ("publish: true\ndate: 2026-09-30 10:00:00", "is not YYYY-MM-DD"),
        ("publish: true\ndate: 2026-10-09", "in the future"),
        (f"{PUBLISHED}\nurl: obsidian://open?vault=x", "http(s) URL"),
        (f"{PUBLISHED}\ntitle: ''", "title must be"),
        (f"{PUBLISHED}\ntitle: {'x' * 201}", "longer than 200"),
        (f"{PUBLISHED}\ntags: {{a: b}}", "tags must be a list"),
        (f"{PUBLISHED}\nskills: [1, 2]", "skills must be a list"),
        (f"{PUBLISHED}\ntags: [{'t' * 51}]", "longer than 50"),
        (f"{PUBLISHED}\nsummary: [a]", "summary must be text"),
        ("publish: [true", "not valid YAML"),
    ],
)
def test_invalid_published_note_raises(vault: Vault, frontmatter: str, reason: str) -> None:
    with pytest.raises(InvalidNoteError, match=re.escape(reason)):
        parse_note(vault.add("n.md", frontmatter), vault.context())


def test_no_url_source_is_invalid(vault: Vault) -> None:
    with pytest.raises(InvalidNoteError, match="LEDGER_NOTE_BASE_URL"):
        parse_note(vault.add("n.md", PUBLISHED), vault.context(None))


def test_invalid_but_unpublished_note_is_ignored(vault: Vault) -> None:
    assert parse_note(vault.add("n.md", "publish: false\ndate: nonsense"), vault.context()) is None


def test_first_paragraph_skips_headings_and_blank_blocks() -> None:
    assert (
        first_paragraph("\n# Title\n\n## Sub\n\n  \n\nText line\nnext line\n\nOther")
        == "Text line\nnext line"
    )
    assert first_paragraph("# Only a heading") == ""


@pytest.mark.parametrize(
    ("text", "expected"),
    [
        ("See [[Note]] and [[Other|label]].", "See Note and label."),
        ("An ![image](a.png) and ![[embed.png]].", "An image and embed.png."),
        ("  many\n  spaces  ", "many spaces"),
    ],
)
def test_summarize_reduces_links_and_spaces(text: str, expected: str) -> None:
    assert summarize(text) == expected


def test_summarize_cuts_at_a_word_boundary() -> None:
    text = "abc " * 100  # 400 chars

    summary = summarize(text)

    assert len(summary) <= SUMMARY_MAX
    assert summary.endswith(f"abc{ELLIPSIS}")


def test_summarize_cuts_one_long_word() -> None:
    summary = summarize("x" * 400)

    assert summary == "x" * (SUMMARY_MAX - 1) + ELLIPSIS


def test_summary_of_exact_length_is_kept() -> None:
    assert summarize("y" * SUMMARY_MAX) == "y" * SUMMARY_MAX
