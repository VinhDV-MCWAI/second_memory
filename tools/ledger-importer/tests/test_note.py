import re
import unicodedata

import pytest

from ledger_importer.contract import BODY_MAX, ELLIPSIS, SUMMARY_MAX
from ledger_importer.note import InvalidNoteError, first_paragraph, parse_note, summarize
from tests.conftest import Vault

PUBLISHED = "publish: true"


def test_published_note_becomes_a_request_item_with_its_whole_content(vault: Vault) -> None:
    body = "# Heading\n\nFirst [[ADR-0009|the ADR]] paragraph\nwith a [link](https://x.y).\n\nSecond."
    path = vault.add(
        "lab/P3 search.md",
        "publish: true\ntitle: Search spike\ncategories: [Thiết kế, PostgreSQL]\nparent: '[[Lab]]'",
        f"\n{body}\n\n",
    )

    note = parse_note(path, vault.root)

    assert note == {
        "external_key": "lab/P3 search.md",
        "title": "Search spike",
        "body": body,
        "summary": "First the ADR paragraph with a link.",
        "parent": "[[Lab]]",
        "categories": ["Thiết kế", "PostgreSQL"],
    }


@pytest.mark.parametrize(
    "frontmatter",
    [
        None,
        "title: Draft",
        "publish: false",
        "publish: 'true'",
        "publish: yes",
        "publish: on",
        "publish: 1",
    ],
)
def test_only_the_yaml_boolean_true_publishes(vault: Vault, frontmatter: str | None) -> None:
    assert parse_note(vault.add("n.md", frontmatter), vault.root) is None


def test_capitalized_true_publishes(vault: Vault) -> None:
    assert parse_note(vault.add("n.md", "publish: True"), vault.root) is not None


def test_defaults_title_from_file_name_and_sends_no_empty_fields(vault: Vault) -> None:
    note = parse_note(vault.add("Tối ưu/Truy vấn #1.md", PUBLISHED, ""), vault.root)

    assert note == {
        "external_key": "Tối ưu/Truy vấn #1.md",
        "title": "Truy vấn #1",
        "body": "",
        "categories": [],
    }


def test_external_key_is_nfc(vault: Vault) -> None:
    decomposed = unicodedata.normalize("NFD", "Kỹ năng.md")
    note = parse_note(vault.add(decomposed, PUBLISHED), vault.root)

    assert note is not None
    assert note["external_key"] == unicodedata.normalize("NFC", "Kỹ năng.md")


def test_parent_may_be_a_path(vault: Vault) -> None:
    note = parse_note(vault.add("n.md", f"{PUBLISHED}\nparent: ' books/Laravel.md '"), vault.root)

    assert note is not None
    assert note["parent"] == "books/Laravel.md"


def test_frontmatter_summary_is_cut_too(vault: Vault) -> None:
    note = parse_note(vault.add("n.md", f"{PUBLISHED}\nsummary: {'word ' * 100}"), vault.root)

    assert note is not None
    assert len(note["summary"]) <= SUMMARY_MAX
    assert note["summary"].endswith(ELLIPSIS)


def test_categories_accept_one_text_and_drop_duplicates_ignoring_case(vault: Vault) -> None:
    note = parse_note(vault.add("n.md", f"{PUBLISHED}\ncategories: ' Go '"), vault.root)
    assert note is not None
    assert note["categories"] == ["Go"]

    note = parse_note(vault.add("m.md", f"{PUBLISHED}\ncategories: [Go, go, ' GO ', '']"), vault.root)
    assert note is not None
    assert note["categories"] == ["Go"]


@pytest.mark.parametrize(
    ("frontmatter", "reason"),
    [
        (f"{PUBLISHED}\ntitle: ''", "title must be"),
        (f"{PUBLISHED}\ntitle: {'x' * 201}", "longer than 200"),
        (f"{PUBLISHED}\ncategories: {{a: b}}", "categories must be a list"),
        (f"{PUBLISHED}\ncategories: [1, 2]", "categories must be a list"),
        (f"{PUBLISHED}\ncategories: [{'c' * 101}]", "longer than 100"),
        (f"{PUBLISHED}\nsummary: [a]", "summary must be text"),
        (f"{PUBLISHED}\nparent: [[Book]]", "parent must be text"),
        (f"{PUBLISHED}\nparent: {'p' * 501}", "parent is longer than 500"),
        ("publish: [true", "not valid YAML"),
    ],
)
def test_invalid_published_note_raises(vault: Vault, frontmatter: str, reason: str) -> None:
    with pytest.raises(InvalidNoteError, match=re.escape(reason)):
        parse_note(vault.add("n.md", frontmatter), vault.root)


def test_too_long_content_is_invalid(vault: Vault) -> None:
    with pytest.raises(InvalidNoteError, match=f"longer than {BODY_MAX}"):
        parse_note(vault.add("n.md", PUBLISHED, "x" * (BODY_MAX + 1)), vault.root)


def test_invalid_but_unpublished_note_is_ignored(vault: Vault) -> None:
    assert parse_note(vault.add("n.md", "publish: false\ncategories: {a: b}"), vault.root) is None


def test_first_paragraph_skips_headings_code_and_blank_blocks() -> None:
    assert (
        first_paragraph("\n# Title\n\n## Sub\n\n```php\ncode\n```\n\n  \n\nText line\nnext line\n\nOther")
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
