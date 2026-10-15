# Obsidian → Skill Ledger importer (ADR-0010). One-shot CLI, not a Compose service.
#   docker build -t ledger-importer tools/ledger-importer                  → runtime image
#   docker build --target dev -t ledger-importer-dev tools/ledger-importer → ruff, mypy, pytest
FROM ghcr.io/astral-sh/uv:0.12.23 AS uv

FROM python:3.14.8-slim AS base
COPY --from=uv /uv /usr/local/bin/uv
ENV UV_COMPILE_BYTECODE=1 \
    UV_LINK_MODE=copy \
    UV_PYTHON_DOWNLOADS=never \
    UV_PROJECT_ENVIRONMENT=/opt/venv \
    PATH=/opt/venv/bin:$PATH
WORKDIR /app
COPY pyproject.toml uv.lock README.md ./

# Quality gates; caches go to /tmp because the image runs as a non-root user
FROM base AS dev
RUN uv sync --frozen --no-install-project
COPY src ./src
COPY tests ./tests
RUN uv sync --frozen
ENV RUFF_CACHE_DIR=/tmp/ruff MYPY_CACHE_DIR=/tmp/mypy
USER 10001
CMD ["sh", "-c", "ruff check && ruff format --check && mypy && pytest -p no:cacheprovider"]

FROM base AS build
RUN uv sync --frozen --no-dev --no-install-project
COPY src ./src
RUN uv sync --frozen --no-dev --no-editable

FROM python:3.14.8-slim AS runtime
COPY --from=build /opt/venv /opt/venv
ENV PATH=/opt/venv/bin:$PATH
RUN useradd --system --uid 10001 --no-create-home importer
USER importer
# A one-shot CLI: nothing long-running to check
HEALTHCHECK NONE
ENTRYPOINT ["ledger-import"]
