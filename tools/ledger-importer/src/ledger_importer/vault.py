"""Finding notes in a vault and splitting their YAML frontmatter from the body."""

import re
from collections.abc import Iterator
from pathlib import Path
from typing import Any

import yaml

from ledger_importer.contract import FRONTMATTER_FENCE, NOTE_SUFFIX


class FrontmatterError(ValueError):
    """The frontmatter block exists but is not a YAML mapping."""


class _Yaml12BoolLoader(yaml.SafeLoader):
    """SafeLoader where only true / false are booleans (YAML 1.2, as Obsidian writes them).

    PyYAML follows YAML 1.1, where yes / no / on / off are booleans too; ADR-0010 says
    `publish: yes` must not publish, so those stay text.
    """


_Yaml12BoolLoader.yaml_implicit_resolvers = {
    first: [(tag, regexp) for tag, regexp in resolvers if tag != "tag:yaml.org,2002:bool"]
    for first, resolvers in yaml.SafeLoader.yaml_implicit_resolvers.items()
}
_Yaml12BoolLoader.add_implicit_resolver(
    "tag:yaml.org,2002:bool", re.compile(r"^(?:true|True|TRUE|false|False|FALSE)$"), list("tTfF")
)


def find_notes(root: Path) -> Iterator[Path]:
    """Every `.md` file under `root`, in a stable order, skipping folders that start with `.`."""
    for path in sorted(root.rglob(f"*{NOTE_SUFFIX}")):
        relative = path.relative_to(root)
        if path.is_file() and not any(part.startswith(".") for part in relative.parts[:-1]):
            yield path


def split_frontmatter(text: str) -> tuple[dict[str, Any], str]:
    """Frontmatter mapping and body. A note without a `---` block has empty frontmatter."""
    lines = text.lstrip("﻿").splitlines()
    if not lines or lines[0].strip() != FRONTMATTER_FENCE:
        return {}, text
    try:
        end = next(i for i, line in enumerate(lines[1:], start=1) if line.strip() == FRONTMATTER_FENCE)
    except StopIteration:
        raise FrontmatterError("frontmatter has no closing ---") from None

    try:
        data = yaml.load("\n".join(lines[1:end]), Loader=_Yaml12BoolLoader)  # noqa: S506 - a SafeLoader
    except yaml.YAMLError as error:
        raise FrontmatterError(f"frontmatter is not valid YAML ({error.__class__.__name__})") from None
    if data is None:
        data = {}
    if not isinstance(data, dict):
        raise FrontmatterError("frontmatter is not a key: value mapping")
    return data, "\n".join(lines[end + 1 :])
