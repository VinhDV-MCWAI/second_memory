"""Limits and codes of the import contract (ADR-0010, ADR-0022 `article/import`).

The limits mirror `App\\Constants\\ContentConst` in laravel-api; the API validates them again.
"""

from enum import IntEnum

NOTES_MAX = 2000
EXTERNAL_KEY_MAX = 500
TITLE_MAX = 200
BODY_MAX = 100_000
CATEGORY_NAME_MAX = 100
# Shorter than the API's 500: a summary is a teaser for lists and search results
SUMMARY_MAX = 300
ELLIPSIS = "…"
# PHP accepts request bodies up to 50 MB (docker/laravel/php.ini post_max_size); keep clear of it
REQUEST_BYTES_MAX = 45 * 1024 * 1024

NOTE_SUFFIX = ".md"
FRONTMATTER_FENCE = "---"
URL_SCHEMES = ("http", "https")

IMPORT_PATH = "/api/admin/article/import"
HTTP_TIMEOUT_SECONDS = 60.0

ENV_API_URL = "LEDGER_API_URL"
ENV_API_TOKEN = "LEDGER_API_TOKEN"  # noqa: S105 - the name of the variable, not a secret
DEFAULT_API_URL = "http://ml-nginx:8080"  # nginx listens on 8080 inside the Compose network (P4-02)
DEFAULT_VAULT = "/vault"


class ExitCode(IntEnum):
    OK = 0
    INVALID_NOTES = 1
    USAGE = 2
    API_ERROR = 3
