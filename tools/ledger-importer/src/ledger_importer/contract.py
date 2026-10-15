"""Limits and codes of the import contract (ADR-0010, ADR-0008 row `evidence/import`).

The limits mirror `App\\Constants\\LedgerConst` in laravel-api; the API validates them again.
"""

from enum import IntEnum

NOTES_MAX = 2000
EXTERNAL_KEY_MAX = 500
TITLE_MAX = 200
URL_MAX = 2048
TAG_NAME_MAX = 50
SKILL_NAME_MAX = 100
# Shorter than the API's 1,000: a summary is a teaser, the note itself is behind the URL
SUMMARY_MAX = 300
ELLIPSIS = "…"

NOTE_SUFFIX = ".md"
FRONTMATTER_FENCE = "---"
URL_SCHEMES = ("http", "https")

IMPORT_PATH = "/api/admin/evidence/import"
HTTP_TIMEOUT_SECONDS = 30.0

ENV_API_URL = "LEDGER_API_URL"
ENV_API_TOKEN = "LEDGER_API_TOKEN"  # noqa: S105 - the name of the variable, not a secret
ENV_NOTE_BASE_URL = "LEDGER_NOTE_BASE_URL"
DEFAULT_API_URL = "http://ml-nginx:8080"  # nginx listens on 8080 inside the Compose network (P4-02)
DEFAULT_VAULT = "/vault"


class ExitCode(IntEnum):
    OK = 0
    INVALID_NOTES = 1
    USAGE = 2
    API_ERROR = 3
